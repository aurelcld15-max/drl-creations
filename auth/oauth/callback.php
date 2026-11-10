<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/functions.php';
require_once dirname(__DIR__, 2) . '/classes/Auth.php';

$provider = (string)($_GET['provider'] ?? 'google');
$state    = (string)($_REQUEST['state'] ?? '');
$code     = (string)($_REQUEST['code'] ?? '');

if (empty($_SESSION['oauth2state']) || !hash_equals((string)$_SESSION['oauth2state'], $state)) {
    unset($_SESSION['oauth2state']);
    set_flash('danger', "Échec de validation de l'état OAuth 2.0 (protection CSRF).");
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}
unset($_SESSION['oauth2state']);

if ($provider === 'google' && $code !== '') {
    $clientId     = (string)env('GOOGLE_CLIENT_ID', '');
    $clientSecret = (string)env('GOOGLE_CLIENT_SECRET', '');
    $redirectUri  = (string)env('GOOGLE_REDIRECT_URI', BASE_URL . '/auth/oauth/callback.php?provider=google');

    if (class_exists('\League\OAuth2\Client\Provider\Google')) {
        try {
            $googleProvider = new \League\OAuth2\Client\Provider\Google([
                'clientId'     => $clientId,
                'clientSecret' => $clientSecret,
                'redirectUri'  => $redirectUri,
            ]);
            $token = $googleProvider->getAccessToken('authorization_code', ['code' => $code]);
            /** @var \League\OAuth2\Client\Provider\GoogleUser $owner */
            $owner = $googleProvider->getResourceOwner($token);
            $res = Auth::loginOrRegisterOAuth(
                'google',
                (string)$owner->getId(),
                (string)$owner->getEmail(),
                (string)($owner->getFirstName() ?? 'Client'),
                (string)($owner->getLastName() ?? 'DRL'),
                $owner->getAvatar()
            );
            if ($res['success']) {
                header('Location: ' . BASE_URL . '/client/profil.php');
                exit;
            }
        } catch (Throwable $e) {
            error_log('[DRL Google OAuth] ' . $e->getMessage());
        }
    }
}

set_flash('warning', 'Authentification OAuth interrompue ou configuration incomplète.');
header('Location: ' . BASE_URL . '/auth/login.php');
exit;
