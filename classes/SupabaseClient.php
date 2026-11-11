<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

/**
 * Minimal server-side client for Supabase Auth and Storage HTTP APIs.
 */
final class SupabaseClient
{
    private static function config(): array
    {
        $url = rtrim((string)env('SUPABASE_URL', ''), '/');
        $apiKey = (string)env('SUPABASE_ANON_KEY', '');
        $bucket = trim((string)env('SUPABASE_STORAGE_BUCKET', 'drl-media'), '/');

        if ($url === '' || $apiKey === '') {
            throw new RuntimeException('Configurez SUPABASE_URL et SUPABASE_ANON_KEY dans le fichier .env.');
        }

        if (!filter_var($url, FILTER_VALIDATE_URL) || !str_starts_with($url, 'https://')) {
            throw new RuntimeException('SUPABASE_URL doit être une URL HTTPS valide.');
        }

        if ($bucket === '') {
            throw new RuntimeException('SUPABASE_STORAGE_BUCKET ne peut pas être vide.');
        }

        return ['url' => $url, 'api_key' => $apiKey, 'bucket' => $bucket];
    }

    public static function request(
        string $method,
        string $path,
        ?array $payload = null,
        ?string $accessToken = null
    ): array {
        $config = self::config();
        $headers = [
            'apikey: ' . $config['api_key'],
            'Accept: application/json',
            'Content-Type: application/json',
        ];
        if ($accessToken !== null && $accessToken !== '') {
            $headers[] = 'Authorization: Bearer ' . $accessToken;
        } else {
            $headers[] = 'Authorization: Bearer ' . $config['api_key'];
        }

        $ch = curl_init($config['url'] . $path);
        if ($ch === false) {
            throw new RuntimeException('Impossible d’initialiser la requête Supabase.');
        }

        $options = [
            CURLOPT_CUSTOMREQUEST  => strtoupper($method),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 30,
        ];
        if ($payload !== null) {
            $json = json_encode($payload, JSON_THROW_ON_ERROR);
            $options[CURLOPT_POSTFIELDS] = $json;
        }
        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            error_log('[DRL Supabase HTTP] ' . $curlError);
            throw new RuntimeException('La connexion au service Supabase a échoué.');
        }

        $decoded = $response !== '' ? json_decode($response, true) : [];
        if (!is_array($decoded)) {
            throw new RuntimeException('Supabase a renvoyé une réponse invalide.');
        }

        if ($status < 200 || $status >= 300) {
            $message = (string)($decoded['msg'] ?? $decoded['message'] ?? $decoded['error_description'] ?? $decoded['error'] ?? '');
            if ($message === '') {
                $message = 'La requête Supabase a échoué (HTTP ' . $status . ').';
            }
            throw new RuntimeException($message);
        }

        return $decoded;
    }

    public static function signup(string $email, string $password, array $metadata, string $redirectTo): array
    {
        $path = '/auth/v1/signup?redirect_to=' . rawurlencode($redirectTo);
        return self::request('POST', $path, [
            'email'    => $email,
            'password' => $password,
            'data'     => $metadata,
        ]);
    }

    public static function signIn(string $email, string $password): array
    {
        return self::request('POST', '/auth/v1/token?grant_type=password', [
            'email'    => $email,
            'password' => $password,
        ]);
    }

    public static function verifyOtp(string $tokenHash, string $type): array
    {
        return self::request('POST', '/auth/v1/verify', [
            'token_hash' => $tokenHash,
            'type'       => $type,
        ]);
    }

    public static function exchangeCode(string $code, string $verifier): array
    {
        return self::request('POST', '/auth/v1/token?grant_type=pkce', [
            'auth_code'     => $code,
            'code_verifier' => $verifier,
        ]);
    }

    public static function refreshSession(string $refreshToken): array
    {
        return self::request('POST', '/auth/v1/token?grant_type=refresh_token', [
            'refresh_token' => $refreshToken,
        ]);
    }

    public static function sendPasswordRecovery(string $email, string $redirectTo): array
    {
        return self::request(
            'POST',
            '/auth/v1/recover?redirect_to=' . rawurlencode($redirectTo),
            ['email' => $email]
        );
    }

    public static function updatePassword(string $password, string $accessToken): array
    {
        return self::request('PUT', '/auth/v1/user', ['password' => $password], $accessToken);
    }

    public static function signOut(string $accessToken): array
    {
        return self::request('POST', '/auth/v1/logout', null, $accessToken);
    }

    public static function authorizationUrl(
        string $provider,
        string $redirectTo,
        string $state,
        string $challenge
    ): string {
        $config = self::config();
        $query = http_build_query([
            'provider'             => $provider,
            'redirect_to'          => $redirectTo,
            'state'                => $state,
            'code_challenge'       => $challenge,
            'code_challenge_method'=> 's256',
        ], '', '&', PHP_QUERY_RFC3986);

        return $config['url'] . '/auth/v1/authorize?' . $query;
    }

    public static function upload(string $localPath, string $objectPath, string $mime, string $accessToken): string
    {
        $config = self::config();
        $bytes = file_get_contents($localPath);
        if ($bytes === false) {
            throw new RuntimeException('Impossible de lire le fichier à téléverser.');
        }

        $encodedPath = implode('/', array_map('rawurlencode', explode('/', trim($objectPath, '/'))));
        $url = $config['url'] . '/storage/v1/object/' . rawurlencode($config['bucket']) . '/' . $encodedPath;
        $ch = curl_init($url);
        if ($ch === false) {
            throw new RuntimeException('Impossible d’initialiser le téléversement Supabase.');
        }

        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => $bytes,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'apikey: ' . $config['api_key'],
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: ' . $mime,
                'x-upsert: false',
            ],
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 60,
        ]);

        $response = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            error_log('[DRL Supabase Storage] ' . $curlError);
            throw new RuntimeException('Le téléversement vers Supabase Storage a échoué.');
        }
        if ($status < 200 || $status >= 300) {
            $result = json_decode($response, true);
            $message = is_array($result)
                ? (string)($result['message'] ?? $result['error'] ?? '')
                : '';
            throw new RuntimeException($message !== '' ? $message : 'Supabase Storage a refusé le fichier (HTTP ' . $status . ').');
        }

        return $config['url'] . '/storage/v1/object/public/' . rawurlencode($config['bucket']) . '/' . $encodedPath;
    }
}
