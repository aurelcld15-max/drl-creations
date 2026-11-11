<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

if (is_logged_in()) {
    require_once dirname(__DIR__) . '/classes/SupabaseAuth.php';
    try {
        SupabaseAuth::refreshIfNeeded();
    } catch (Throwable $e) {
        error_log('[DRL Supabase admin session refresh] ' . $e->getMessage());
        SupabaseAuth::signOut();
        set_flash('warning', 'Votre session a expiré. Veuillez vous reconnecter.');
    }
}

if (!is_admin()) {
    set_flash('danger', current_lang() === 'en'
        ? 'Restricted access: Administrator privileges required.'
        : "Accès restreint : privilèges d'administratrice requis.");
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}
