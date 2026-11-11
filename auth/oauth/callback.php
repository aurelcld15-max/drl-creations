<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/functions.php';
set_flash('warning', 'Le retour OAuth doit être configuré dans Supabase et utiliser le nouveau parcours de connexion.');
header('Location: ' . BASE_URL . '/auth/login.php');
exit;
