<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/functions.php';
require_once dirname(__DIR__, 2) . '/classes/SupabaseAuth.php';

$provider = (string)($_GET['provider'] ?? '');
try {
    $callback = BASE_URL . '/auth/oauth/supabase-callback.php';
    header('Location: ' . SupabaseAuth::beginOAuth($provider, $callback));
    exit;
} catch (Throwable $e) {
    error_log('[DRL Supabase OAuth start] ' . $e->getMessage());
    set_flash('danger', 'La connexion avec ce fournisseur est momentanément indisponible.');
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}
