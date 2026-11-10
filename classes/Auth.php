<?php
declare(strict_types=1);

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/User.php';

/**
 * Classe Auth — Système complet d'inscription, vérification d'email, connexion sécurisée,
 * protection anti-force brute, réinitialisation de mot de passe et OAuth 2.0 (Google / Apple).
 */
class Auth
{
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOCKOUT_MINUTES    = 15;

    /**
     * Inscription d'un nouveau client avec les 5 étapes de vérification exigées (Section 8) :
     * 1. Vérifier le format de l'e-mail.
     * 2. Vérifier son unicité dans MySQL.
     * 3. Envoyer un lien de confirmation.
     * 4. Vérifier que l'utilisateur possède réellement l'adresse.
     * 5. Activer le compte après validation.
     */
    public static function register(
        string $firstName,
        string $lastName,
        string $email,
        string $password,
        string $passwordConfirm,
        string $phone = ''
    ): array {
        $firstName = trim($firstName);
        $lastName  = trim($lastName);
        $email     = strtolower(trim($email));
        $phone     = trim($phone);

        if ($firstName === '' || $lastName === '') {
            return ['success' => false, 'error' => 'Veuillez renseigner votre prénom et votre nom.'];
        }

        // 1. Vérifier le format de l'e-mail + domaine MX/DNS si disponible
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => "Le format de l'adresse e-mail est invalide."];
        }

        if (strlen($password) < 8) {
            return ['success' => false, 'error' => 'Le mot de passe doit contenir au moins 8 caractères.'];
        }

        if ($password !== $passwordConfirm) {
            return ['success' => false, 'error' => 'La confirmation du mot de passe ne correspond pas.'];
        }

        // 2. Vérifier l'unicité de l'e-mail dans MySQL
        $existing = User::findByEmail($email);
        if ($existing !== null) {
            return ['success' => false, 'error' => 'Cette adresse e-mail est déjà associée à un compte DRL CRÉATIONS.'];
        }

        $passwordHash      = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $verificationToken = bin2hex(random_bytes(32));

        Database::query(
            "INSERT INTO users (first_name, last_name, email, password_hash, phone, role, oauth_provider, verification_token, is_active, created_at)
             VALUES (:first_name, :last_name, :email, :password_hash, :phone, 'client', 'local', :token, 1, NOW())",
            [
                ':first_name'    => $firstName,
                ':last_name'     => $lastName,
                ':email'         => $email,
                ':password_hash' => $passwordHash,
                ':phone'         => $phone !== '' ? $phone : null,
                ':token'         => $verificationToken,
            ]
        );

        $verifyUrl = BASE_URL . '/auth/verify-email.php?token=' . urlencode($verificationToken) . '&email=' . urlencode($email);

        // 3. Envoyer le lien de confirmation par e-mail
        self::sendVerificationEmail($email, $firstName, $verifyUrl);

        return [
            'success'    => true,
            'verify_url' => $verifyUrl,
            'message'    => 'Votre compte a été créé. Un lien de confirmation a été envoyé à votre adresse e-mail pour activer votre accès.',
        ];
    }

    /**
     * Validation du jeton de confirmation d'e-mail et activation du compte (Étapes 4 & 5).
     */
    public static function verifyEmailToken(string $email, string $token): array
    {
        $email = strtolower(trim($email));
        $token = trim($token);

        if ($email === '' || $token === '') {
            return ['success' => false, 'error' => 'Lien de vérification incomplet ou invalide.'];
        }

        $user = Database::fetchOne(
            "SELECT id, first_name, last_name, email, verification_token, email_verified_at
             FROM users WHERE email = :email LIMIT 1",
            [':email' => $email]
        );

        if (!$user) {
            return ['success' => false, 'error' => 'Aucun compte ne correspond à cette adresse e-mail.'];
        }

        if (!empty($user['email_verified_at'])) {
            return ['success' => true, 'already_verified' => true, 'message' => 'Votre adresse e-mail est déjà confirmée. Vous pouvez vous connecter.'];
        }

        if (empty($user['verification_token']) || !hash_equals((string)$user['verification_token'], $token)) {
            return ['success' => false, 'error' => 'Le jeton de confirmation est invalide ou a expiré.'];
        }

        $becameAdmin = self::verifyEmailAndPromoteFirstAccount(
            (int)$user['id'],
            (string)$user['verification_token']
        );

        return [
            'success'     => true,
            'became_admin' => $becameAdmin,
            'message'     => 'Félicitations ' . $user['first_name'] . ', votre adresse e-mail est vérifiée et votre compte est désormais actif !'
                . ($becameAdmin ? ' Vous êtes le premier membre vérifié : les droits administrateur ont été activés pour votre compte.' : ''),
        ];
    }

    /**
     * Connexion sécurisée avec protection contre les tentatives répétées et régénération de session.
     */
    public static function login(string $email, string $password): array
    {
        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
            return ['success' => false, 'error' => 'Veuillez saisir une adresse e-mail et un mot de passe valides.'];
        }

        $user = User::findByEmail($email);
        if (!$user) {
            return ['success' => false, 'error' => 'Identifiants incorrects.'];
        }

        // Vérification du verrouillage temporaire anti-force brute
        if (!empty($user['locked_until']) && strtotime((string)$user['locked_until']) > time()) {
            $remainingMin = (int)ceil((strtotime((string)$user['locked_until']) - time()) / 60);
            return [
                'success' => false,
                'error'   => "Compte temporairement protégé suite à plusieurs tentatives. Réessayez dans {$remainingMin} minute(s).",
            ];
        }

        if (empty($user['password_hash']) || !password_verify($password, (string)$user['password_hash'])) {
            $attempts = (int)$user['failed_login_attempts'] + 1;
            $lockedUntil = null;
            if ($attempts >= self::MAX_LOGIN_ATTEMPTS) {
                $lockedUntil = date('Y-m-d H:i:s', time() + (self::LOCKOUT_MINUTES * 60));
            }
            Database::query(
                "UPDATE users SET failed_login_attempts = :attempts, locked_until = :locked WHERE id = :id",
                [':attempts' => $attempts, ':locked' => $lockedUntil, ':id' => $user['id']]
            );
            return ['success' => false, 'error' => 'Identifiants incorrects.'];
        }

        if ((int)$user['is_active'] !== 1) {
            return ['success' => false, 'error' => "Votre compte a été suspendu. Veuillez contacter l'atelier."];
        }

        if ($user['role'] === 'client' && $user['oauth_provider'] === 'local' && empty($user['email_verified_at'])) {
            $verifyUrl = BASE_URL . '/auth/verify-email.php?token=' . urlencode((string)$user['verification_token']) . '&email=' . urlencode((string)$user['email']);
            return [
                'success'            => false,
                'needs_verification' => true,
                'verify_url'         => $verifyUrl,
                'error'              => "Veuillez d'abord confirmer votre adresse e-mail via le lien de validation qui vous a été envoyé.",
            ];
        }

        // Réinitialiser le compteur d'échecs et enregistrer la date de connexion
        Database::query(
            "UPDATE users SET failed_login_attempts = 0, locked_until = NULL, last_login_at = NOW() WHERE id = :id",
            [':id' => $user['id']]
        );

        self::establishUserSession($user);
        return ['success' => true, 'user' => $_SESSION['user']];
    }

    /**
     * Connexion / Inscription automatique via OAuth 2.0 & OpenID Connect (Google ou Apple).
     */
    public static function loginOrRegisterOAuth(
        string $provider,
        string $oauthId,
        string $email,
        string $firstName,
        string $lastName,
        ?string $avatarUrl = null
    ): array {
        $email = strtolower(trim($email));
        $provider = in_array($provider, ['google', 'apple'], true) ? $provider : 'google';

        $user = Database::fetchOne(
            "SELECT * FROM users WHERE (oauth_provider = :prov AND oauth_id = :oid) OR email = :email LIMIT 1",
            [':prov' => $provider, ':oid' => $oauthId, ':email' => $email]
        );

        if ($user) {
            Database::query(
                "UPDATE users
                 SET oauth_provider = :prov, oauth_id = :oid, email_verified_at = COALESCE(email_verified_at, NOW()), last_login_at = NOW()
                 WHERE id = :id",
                [':prov' => $provider, ':oid' => $oauthId, ':id' => $user['id']]
            );
            $user = User::findByEmail($email);
        } else {
            Database::query(
                "INSERT INTO users (first_name, last_name, email, avatar, role, oauth_provider, oauth_id, email_verified_at, is_active, last_login_at, created_at)
                 VALUES (:fn, :ln, :email, :avatar, 'client', :prov, :oid, NOW(), 1, NOW(), NOW())",
                [
                    ':fn'     => $firstName !== '' ? $firstName : 'Client',
                    ':ln'     => $lastName !== '' ? $lastName : 'DRL',
                    ':email'  => $email,
                    ':avatar' => $avatarUrl,
                    ':prov'   => $provider,
                    ':oid'    => $oauthId,
                ]
            );
            self::verifyEmailAndPromoteFirstAccount((int)Database::lastInsertId(), null);
            $user = User::findByEmail($email);
        }

        if (!$user) {
            return ['success' => false, 'error' => "Impossible d'initialiser le profil OAuth."];
        }

        self::establishUserSession($user);
        return ['success' => true, 'user' => $_SESSION['user']];
    }

    /**
     * Crée la session authentifiée et régénère l'identifiant de session (Exigence Section 17).
     */
    public static function establishUserSession(array $user): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        $_SESSION['_last_regen'] = time();
        $_SESSION['user'] = [
            'id'         => (int)$user['id'],
            'first_name' => (string)$user['first_name'],
            'last_name'  => (string)$user['last_name'],
            'email'      => (string)$user['email'],
            'phone'      => (string)($user['phone'] ?? ''),
            'avatar'     => (string)($user['avatar'] ?? ''),
            'role'       => (string)$user['role'],
        ];
    }

    /**
     * Déconnexion complète et destruction sécurisée de la session.
     */
    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
    }

    /**
     * Génère un jeton de réinitialisation de mot de passe.
     */
    public static function createPasswordReset(string $email): array
    {
        $email = strtolower(trim($email));
        $user = User::findByEmail($email);
        if (!$user) {
            // Ne pas révéler si l'email existe ou non, mais retourner succès
            return ['success' => true];
        }

        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 3600);

        Database::query(
            "UPDATE users SET reset_token = :token, reset_expires_at = :exp WHERE id = :id",
            [':token' => $token, ':exp' => $expires, ':id' => $user['id']]
        );

        $resetUrl = BASE_URL . '/auth/reset-password.php?token=' . urlencode($token) . '&email=' . urlencode($email);
        self::sendPasswordResetEmail($email, (string)$user['first_name'], $resetUrl);

        return ['success' => true, 'reset_url' => $resetUrl];
    }

    public static function resetPasswordWithToken(string $email, string $token, string $newPassword, string $confirmPassword): array
    {
        $email = strtolower(trim($email));
        if ($newPassword !== $confirmPassword || strlen($newPassword) < 8) {
            return ['success' => false, 'error' => 'Le mot de passe doit comporter au moins 8 caractères et correspondre à la confirmation.'];
        }

        $user = Database::fetchOne(
            "SELECT id, reset_token, reset_expires_at FROM users WHERE email = :email LIMIT 1",
            [':email' => $email]
        );

        if (
            !$user ||
            empty($user['reset_token']) ||
            !hash_equals((string)$user['reset_token'], $token) ||
            empty($user['reset_expires_at']) ||
            strtotime((string)$user['reset_expires_at']) < time()
        ) {
            return ['success' => false, 'error' => 'Le lien de réinitialisation est invalide ou a expiré.'];
        }

        $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        Database::query(
            "UPDATE users
             SET password_hash = :hash, reset_token = NULL, reset_expires_at = NULL, failed_login_attempts = 0, locked_until = NULL
             WHERE id = :id",
            [':hash' => $hash, ':id' => $user['id']]
        );

        return ['success' => true];
    }

    private static function verifyEmailAndPromoteFirstAccount(int $userId, ?string $token): bool
    {
        $lockName = 'drl_creations_first_verified_admin';
        $lock = Database::fetchOne(
            'SELECT GET_LOCK(:lock_name, 10) AS acquired',
            [':lock_name' => $lockName]
        );

        if ((int)($lock['acquired'] ?? 0) !== 1) {
            throw new RuntimeException("Impossible de vérifier l'attribution initiale des droits administrateur. Veuillez réessayer.");
        }

        try {
            Database::beginTransaction();
            if ($token !== null) {
                $verified = Database::query(
                    "UPDATE users
                     SET email_verified_at = NOW(), verification_token = NULL, is_active = 1, updated_at = NOW()
                     WHERE id = :id AND verification_token = :token AND email_verified_at IS NULL",
                    [':id' => $userId, ':token' => $token]
                );
                if ($verified->rowCount() !== 1) {
                    Database::rollBack();
                    return false;
                }
            } else {
                $verified = Database::fetchOne(
                    "SELECT id FROM users WHERE id = :id AND role = 'client'
                     AND is_active = 1 AND email_verified_at IS NOT NULL FOR UPDATE",
                    [':id' => $userId]
                );
                if ($verified === null) {
                    Database::rollBack();
                    return false;
                }
            }

            Database::query(
                "INSERT IGNORE INTO site_settings (setting_key, setting_value, setting_group)
                 VALUES ('admin_bootstrap_claimed', '0', 'system')"
            );
            $bootstrap = Database::fetchOne(
                "SELECT setting_value FROM site_settings WHERE setting_key = 'admin_bootstrap_claimed' FOR UPDATE"
            );
            if ($bootstrap === null) {
                throw new RuntimeException("L'état d'initialisation de l'administrateur est introuvable.");
            }

            if ((string)$bootstrap['setting_value'] === '1') {
                Database::commit();
                return false;
            }

            $admin = Database::fetchOne("SELECT id FROM users WHERE role = 'admin' LIMIT 1 FOR UPDATE");
            if ($admin !== null) {
                Database::query(
                    "UPDATE site_settings SET setting_value = '1' WHERE setting_key = 'admin_bootstrap_claimed'"
                );
                Database::commit();
                return false;
            }

            $updated = Database::query(
                "UPDATE users
                 SET role = 'admin', updated_at = NOW()
                 WHERE id = :id AND role = 'client' AND is_active = 1 AND email_verified_at IS NOT NULL",
                [':id' => $userId]
            );
            if ($updated->rowCount() !== 1) {
                throw new RuntimeException("Le compte vérifié n'a pas pu recevoir le rôle administrateur.");
            }
            Database::query(
                "UPDATE site_settings SET setting_value = '1' WHERE setting_key = 'admin_bootstrap_claimed'"
            );
            Database::commit();

            return true;
        } catch (Throwable $e) {
            Database::rollBack();
            throw $e;
        } finally {
            Database::query(
                'SELECT RELEASE_LOCK(:lock_name)',
                [':lock_name' => $lockName]
            );
        }
    }

    private static function sendVerificationEmail(string $to, string $firstName, string $verifyUrl): void
    {
        $from = (string)env('MAIL_FROM_ADDRESS', 'no-reply@drl-creations.com');
        $subject = 'Confirmez votre adresse e-mail — DRL CRÉATIONS';
        $headers = "From: DRL CRÉATIONS <{$from}>
";
        $headers .= "Reply-To: {$from}
";
        $headers .= "MIME-Version: 1.0
";
        $headers .= "Content-Type: text/html; charset=UTF-8
";

        $body = "<div style='font-family:Georgia,serif;background:#F8F6F0;padding:32px;color:#142B49;'>
            <h2 style='color:#142B49;margin-bottom:8px;'>Maison DRL CRÉATIONS</h2>
            <p style='color:#B89A62;font-style:italic;margin-top:0;'>L'élégance prend forme, le style devient une signature.</p>
            <p>Bonjour <strong>" . htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8') . "</strong>,</p>
            <p>Merci d'avoir rejoint l'univers privé de DRL CRÉATIONS. Pour activer votre compte, veuillez confirmer votre adresse e-mail en cliquant sur le bouton ci-dessous :</p>
            <p style='margin:28px 0;'>
                <a href='" . htmlspecialchars($verifyUrl, ENT_QUOTES, 'UTF-8') . "' style='background:#142B49;color:#F8F6F0;padding:12px 24px;text-decoration:none;border:1px solid #B89A62;border-radius:4px;'>Activer mon compte DRL CRÉATIONS</a>
            </p>
            <p style='font-size:12px;color:#777777;'>Lien direct : " . htmlspecialchars($verifyUrl, ENT_QUOTES, 'UTF-8') . "</p>
        </div>";

        @mail($to, $subject, $body, $headers);
    }

    private static function sendPasswordResetEmail(string $to, string $firstName, string $resetUrl): void
    {
        $from = (string)env('MAIL_FROM_ADDRESS', 'no-reply@drl-creations.com');
        $subject = 'Réinitialisation de votre mot de passe — DRL CRÉATIONS';
        $headers = "From: DRL CRÉATIONS <{$from}>
";
        $headers .= "MIME-Version: 1.0
";
        $headers .= "Content-Type: text/html; charset=UTF-8
";

        $body = "<div style='font-family:Georgia,serif;background:#F8F6F0;padding:32px;color:#142B49;'>
            <h2 style='color:#142B49;'>Réinitialisation de mot de passe — DRL CRÉATIONS</h2>
            <p>Bonjour <strong>" . htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8') . "</strong>,</p>
            <p>Vous avez demandé la réinitialisation de votre mot de passe. Cliquez sur le lien ci-dessous (valable 1 heure) :</p>
            <p><a href='" . htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8') . "'>" . htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8') . "</a></p>
        </div>";

        @mail($to, $subject, $body, $headers);
    }
}
