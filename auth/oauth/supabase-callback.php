<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/functions.php';
require_once dirname(__DIR__, 2) . '/classes/SupabaseAuth.php';

$state = (string)($_GET['state'] ?? '');
$expectedState = (string)($_SESSION['supabase_oauth_state'] ?? '');
$verifier = (string)($_SESSION['supabase_oauth_verifier'] ?? '');
$code = (string)($_GET['code'] ?? '');
unset($_SESSION['supabase_oauth_state'], $_SESSION['supabase_oauth_verifier']);

if ($state === '' || $expectedState === '' || !hash_equals($expectedState, $state) || $verifier === '' || $code === '') {
    set_flash('danger', 'La validation de la connexion Supabase a échoué. Veuillez réessayer.');
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

try {
    SupabaseAuth::establishOAuthSession($code, $verifier);
    header('Location: ' . BASE_URL . '/client/profil.php');
    exit;
} catch (Throwable $e) {
    error_log('[DRL Supabase OAuth callback] ' . $e->getMessage());
    set_flash('danger', 'La connexion avec le fournisseur n’a pas abouti. Vérifiez la configuration OAuth de Supabase.');
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}
