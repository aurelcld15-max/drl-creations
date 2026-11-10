<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

if (!is_admin()) {
    set_flash('danger', current_lang() === 'en'
        ? 'Restricted access: Administrator privileges required.'
        : "Accès restreint : privilèges d'administratrice requis.");
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}
