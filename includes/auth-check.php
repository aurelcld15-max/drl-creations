<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

if (is_logged_in()) {
    require_once dirname(__DIR__) . '/classes/SupabaseAuth.php';
    try {
        SupabaseAuth::refreshIfNeeded();
    } catch (Throwable $e) {
        error_log('[DRL Supabase session refresh] ' . $e->getMessage());
        SupabaseAuth::signOut();
        set_flash('warning', 'Votre session a expiré. Veuillez vous reconnecter.');
    }
}

if (!is_logged_in()) {
    set_flash('warning', current_lang() === 'en'
        ? 'Please sign in to access your private client space.'
        : 'Veuillez vous connecter pour accéder à votre espace client privé.');
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}
