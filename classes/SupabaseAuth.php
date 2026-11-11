<?php
declare(strict_types=1);

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/SupabaseClient.php';

/**
 * Bridges Supabase Auth sessions to the site's existing integer user profiles.
 */
final class SupabaseAuth
{
    private static bool $refreshedThisRequest = false;

    public static function register(
        string $firstName,
        string $lastName,
        string $email,
        string $password,
        string $passwordConfirm,
        string $phone = ''
    ): array {
        $firstName = trim($firstName);
        $lastName = trim($lastName);
        $email = strtolower(trim($email));

        if ($firstName === '' || $lastName === '') {
            return ['success' => false, 'error' => 'Veuillez renseigner votre prénom et votre nom.'];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => "Le format de l'adresse e-mail est invalide."];
        }
        if (strlen($password) < 8) {
            return ['success' => false, 'error' => 'Le mot de passe doit contenir au moins 8 caractères.'];
        }
        if ($password !== $passwordConfirm) {
            return ['success' => false, 'error' => 'La confirmation du mot de passe ne correspond pas.'];
        }

        $redirectTo = BASE_URL . '/auth/verify-email.php';
        $result = SupabaseClient::signup($email, $password, [
            'first_name' => $firstName,
            'last_name'  => $lastName,
            'phone'      => trim($phone),
        ], $redirectTo);

        if (!empty($result['access_token']) && !empty($result['user'])) {
            self::establishSession($result);
            return [
                'success' => true,
                'message' => 'Votre compte Supabase est créé et votre session est ouverte.',
            ];
        }

        return [
            'success' => true,
            'message' => 'Votre compte a été créé. Consultez votre boîte e-mail pour confirmer votre adresse.',
        ];
    }

    public static function login(string $email, string $password): array
    {
        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
            return ['success' => false, 'error' => 'Veuillez saisir une adresse e-mail et un mot de passe valides.'];
        }

        $result = SupabaseClient::signIn($email, $password);
        if (empty($result['access_token']) || empty($result['user'])) {
            return ['success' => false, 'error' => 'Supabase n’a pas retourné de session valide.'];
        }

        try {
            $profile = self::establishSession($result);
        } catch (RuntimeException $e) {
            self::signOut();
            return ['success' => false, 'error' => $e->getMessage()];
        }
        Database::query(
            'UPDATE public.users SET last_login_at = CURRENT_TIMESTAMP WHERE id = :id',
            [':id' => $profile['id']]
        );

        return ['success' => true, 'user' => $_SESSION['user']];
    }

    public static function verifyOtp(string $tokenHash, string $type): array
    {
        $tokenHash = trim($tokenHash);
        if ($tokenHash === '' || !in_array($type, ['email', 'signup', 'recovery', 'magiclink', 'invite', 'email_change'], true)) {
            return ['success' => false, 'error' => 'Lien Supabase incomplet ou invalide.'];
        }

        $result = SupabaseClient::verifyOtp($tokenHash, $type);
        if (empty($result['access_token']) || empty($result['user'])) {
            return ['success' => false, 'error' => 'Supabase n’a pas validé ce lien. Il peut avoir expiré ou avoir déjà été utilisé.'];
        }

        $profile = self::establishSession($result);
        $isRecovery = $type === 'recovery';
        return [
            'success'      => true,
            'is_recovery'  => $isRecovery,
            'became_admin' => $profile['role'] === 'admin',
            'message'      => $isRecovery
                ? 'Votre identité a été confirmée. Choisissez maintenant un nouveau mot de passe.'
                : 'Votre adresse e-mail est confirmée. Votre compte DRL CRÉATIONS est activé.',
        ];
    }

    public static function establishOAuthSession(string $code, string $verifier): array
    {
        $result = SupabaseClient::exchangeCode($code, $verifier);
        if (empty($result['access_token']) || empty($result['user'])) {
            throw new RuntimeException('Supabase n’a pas retourné une session OAuth valide.');
        }

        self::establishSession($result);
        return ['success' => true, 'user' => $_SESSION['user']];
    }

    public static function beginOAuth(string $provider, string $redirectTo): string
    {
        if (!in_array($provider, ['google', 'apple'], true)) {
            throw new InvalidArgumentException('Fournisseur OAuth non autorisé.');
        }

        $verifier = self::base64UrlEncode(random_bytes(48));
        $challenge = self::base64UrlEncode(hash('sha256', $verifier, true));
        $state = bin2hex(random_bytes(24));
        $_SESSION['supabase_oauth_verifier'] = $verifier;
        $_SESSION['supabase_oauth_state'] = $state;

        return SupabaseClient::authorizationUrl($provider, $redirectTo, $state, $challenge);
    }

    public static function refreshIfNeeded(): void
    {
        if (self::$refreshedThisRequest) {
            return;
        }
        self::$refreshedThisRequest = true;

        $expiresAt = (int)($_SESSION['supabase_expires_at'] ?? 0);
        $refreshToken = (string)($_SESSION['supabase_refresh_token'] ?? '');
        if (empty($_SESSION['user'])) {
            return;
        }
        if ($refreshToken === '') {
            self::clearSession();
            throw new RuntimeException('La session Supabase est invalide. Veuillez vous reconnecter.');
        }
        if ($expiresAt > time() + 60) {
            return;
        }

        $result = SupabaseClient::refreshSession($refreshToken);
        if (empty($result['access_token']) || empty($result['refresh_token']) || empty($result['user'])) {
            self::clearSession();
            throw new RuntimeException('La session Supabase a expiré. Veuillez vous reconnecter.');
        }
        self::establishSession($result);
    }

    public static function requestPasswordReset(string $email): array
    {
        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Veuillez saisir une adresse e-mail valide.'];
        }

        SupabaseClient::sendPasswordRecovery($email, BASE_URL . '/auth/verify-email.php');
        return ['success' => true];
    }

    public static function resetPassword(string $password, string $confirmation): array
    {
        self::refreshIfNeeded();
        if ($password !== $confirmation || strlen($password) < 8) {
            return ['success' => false, 'error' => 'Le mot de passe doit contenir au moins 8 caractères et correspondre à la confirmation.'];
        }

        $accessToken = (string)($_SESSION['supabase_access_token'] ?? '');
        if ($accessToken === '') {
            return ['success' => false, 'error' => 'Le lien de réinitialisation a expiré. Demandez-en un nouveau.'];
        }

        SupabaseClient::updatePassword($password, $accessToken);
        return ['success' => true];
    }

    public static function updatePassword(string $currentPassword, string $newPassword): array
    {
        self::refreshIfNeeded();
        if (strlen($newPassword) < 8) {
            return ['success' => false, 'error' => 'Le nouveau mot de passe doit contenir au moins 8 caractères.'];
        }

        $email = (string)($_SESSION['user']['email'] ?? '');
        $currentSession = SupabaseClient::signIn($email, $currentPassword);
        if (empty($currentSession['access_token'])) {
            return ['success' => false, 'error' => 'Le mot de passe actuel est incorrect.'];
        }

        SupabaseClient::updatePassword($newPassword, (string)$currentSession['access_token']);
        self::storeTokens($currentSession);
        return ['success' => true];
    }

    public static function signOut(): void
    {
        $accessToken = (string)($_SESSION['supabase_access_token'] ?? '');
        if ($accessToken !== '') {
            try {
                SupabaseClient::signOut($accessToken);
            } catch (Throwable $e) {
                error_log('[DRL Supabase signout] ' . $e->getMessage());
            }
        }
        self::clearSession();
    }

    private static function establishSession(array $authResult): array
    {
        $authUser = $authResult['user'] ?? null;
        if (!is_array($authUser) || empty($authUser['id']) || empty($authUser['email'])) {
            throw new RuntimeException('Le profil retourné par Supabase est incomplet.');
        }

        $profile = self::syncProfile($authUser);
        if ((int)($profile['is_active'] ?? 0) !== 1) {
            throw new RuntimeException('Ce compte a été désactivé. Contactez l’atelier pour obtenir de l’aide.');
        }
        self::storeTokens($authResult);
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        $_SESSION['_last_regen'] = time();
        $_SESSION['user'] = [
            'id'         => (int)$profile['id'],
            'first_name' => (string)$profile['first_name'],
            'last_name'  => (string)$profile['last_name'],
            'email'      => (string)$profile['email'],
            'phone'      => (string)($profile['phone'] ?? ''),
            'avatar'     => (string)($profile['avatar'] ?? ''),
            'role'       => (string)$profile['role'],
        ];
        $_SESSION['supabase_uid'] = (string)$authUser['id'];
        return $profile;
    }

    private static function syncProfile(array $authUser): array
    {
        $metadata = is_array($authUser['user_metadata'] ?? null) ? $authUser['user_metadata'] : [];
        $provider = (string)($authUser['app_metadata']['provider'] ?? 'email');
        if (!in_array($provider, ['google', 'apple'], true)) {
            $provider = 'local';
        }
        $emailVerifiedAt = $authUser['email_confirmed_at'] ?? $authUser['confirmed_at'] ?? null;
        $email = strtolower((string)$authUser['email']);

        Database::query(
            "INSERT INTO public.users
                (supabase_uid, first_name, last_name, email, phone, oauth_provider, email_verified_at, is_active, created_at, updated_at)
             VALUES
                (:uid, :first_name, :last_name, :email, :phone, :provider, :verified, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
             ON CONFLICT (email) DO UPDATE SET
                supabase_uid = EXCLUDED.supabase_uid,
                first_name = CASE WHEN EXCLUDED.first_name = 'Client' THEN public.users.first_name ELSE EXCLUDED.first_name END,
                last_name = CASE WHEN EXCLUDED.last_name = 'DRL' THEN public.users.last_name ELSE EXCLUDED.last_name END,
                phone = COALESCE(EXCLUDED.phone, public.users.phone),
                oauth_provider = CASE WHEN EXCLUDED.oauth_provider = 'local' THEN public.users.oauth_provider ELSE EXCLUDED.oauth_provider END,
                email_verified_at = COALESCE(public.users.email_verified_at, EXCLUDED.email_verified_at),
                updated_at = CURRENT_TIMESTAMP",
            [
                ':uid'       => $authUser['id'],
                ':first_name'=> trim((string)($metadata['first_name'] ?? $metadata['full_name'] ?? 'Client')),
                ':last_name' => trim((string)($metadata['last_name'] ?? 'DRL')),
                ':email'     => $email,
                ':phone'     => trim((string)($metadata['phone'] ?? '')) ?: null,
                ':provider'  => $provider,
                ':verified'  => $emailVerifiedAt,
            ]
        );

        $profile = Database::fetchOne(
            'SELECT * FROM public.users WHERE supabase_uid = :uid LIMIT 1',
            [':uid' => $authUser['id']]
        );
        if ($profile === null) {
            throw new RuntimeException('Le profil associé au compte Supabase n’a pas pu être chargé.');
        }

        if (!empty($profile['email_verified_at']) && (string)$profile['role'] === 'client') {
            Database::query('SELECT public.drl_claim_first_admin(:id)', [':id' => $profile['id']]);
            $profile = Database::fetchOne(
                'SELECT * FROM public.users WHERE supabase_uid = :uid LIMIT 1',
                [':uid' => $authUser['id']]
            ) ?? $profile;
        }

        return $profile;
    }

    private static function storeTokens(array $result): void
    {
        $_SESSION['supabase_access_token'] = (string)($result['access_token'] ?? '');
        $_SESSION['supabase_refresh_token'] = (string)($result['refresh_token'] ?? '');
        $_SESSION['supabase_expires_at'] = time() + max(0, (int)($result['expires_in'] ?? 3600));
    }

    private static function clearSession(): void
    {
        unset(
            $_SESSION['user'],
            $_SESSION['supabase_access_token'],
            $_SESSION['supabase_refresh_token'],
            $_SESSION['supabase_expires_at'],
            $_SESSION['supabase_uid'],
            $_SESSION['supabase_oauth_verifier'],
            $_SESSION['supabase_oauth_state']
        );
    }

    private static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
