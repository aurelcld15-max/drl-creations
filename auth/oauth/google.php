<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/functions.php';

/**
 * Redirection OAuth 2.0 / OpenID Connect vers Google
 * Utilise league/oauth2-google si installé via Composer ou le flux OAuth 2.0 standard.
 */
$clientId     = (string)env('GOOGLE_CLIENT_ID', '');
$clientSecret = (string)env('GOOGLE_CLIENT_SECRET', '');
$redirectUri  = (string)env('GOOGLE_REDIRECT_URI', BASE_URL . '/auth/oauth/callback.php?provider=google');

if ($clientId === '' || str_starts_with($clientId, 'your-google-client-id')) {
    set_flash('warning', 'Pour activer la connexion Google OAuth 2.0, renseignez GOOGLE_CLIENT_ID et GOOGLE_CLIENT_SECRET dans le fichier .env (voir README.md).');
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

if (class_exists('\League\OAuth2\Client\Provider\Google')) {
    $provider = new \League\OAuth2\Client\Provider\Google([
        'clientId'     => $clientId,
        'clientSecret' => $clientSecret,
        'redirectUri'  => $redirectUri,
    ]);
    $authUrl = $provider->getAuthorizationUrl(['scope' => ['openid', 'email', 'profile']]);
    $_SESSION['oauth2state'] = $provider->getState();
    header('Location: ' . $authUrl);
    exit;
}

$state = bin2hex(random_bytes(16));
$_SESSION['oauth2state'] = $state;
$params = http_build_query([
    'client_id'     => $clientId,
    'redirect_uri'  => $redirectUri,
    'response_type' => 'code',
    'scope'         => 'openid email profile',
    'state'         => $state,
    'access_type'   => 'online',
    'prompt'        => 'select_account',
]);
header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . $params);
exit;
