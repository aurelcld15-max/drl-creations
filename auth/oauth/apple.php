<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/functions.php';

/**
 * Redirection OAuth 2.0 / OpenID Connect vers Sign in with Apple
 */
$clientId    = (string)env('APPLE_CLIENT_ID', '');
$redirectUri = (string)env('APPLE_REDIRECT_URI', BASE_URL . '/auth/oauth/callback.php?provider=apple');

if ($clientId === '' || $clientId === 'com.drlcreations.web') {
    set_flash('warning', 'Pour activer Sign in with Apple, renseignez APPLE_CLIENT_ID, APPLE_TEAM_ID et APPLE_KEY_ID dans votre fichier .env (voir README.md).');
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$state = bin2hex(random_bytes(16));
$_SESSION['oauth2state'] = $state;
$params = http_build_query([
    'client_id'     => $clientId,
    'redirect_uri'  => $redirectUri,
    'response_type' => 'code id_token',
    'response_mode' => 'form_post',
    'scope'         => 'name email',
    'state'         => $state,
]);
header('Location: https://appleid.apple.com/auth/authorize?' . $params);
exit;
