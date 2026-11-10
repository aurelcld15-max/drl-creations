<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/classes/Auth.php';

Auth::logout();
session_start();
set_flash('info', 'Vous avez été déconnecté(e) en toute sécurité.');
header('Location: ' . BASE_URL . '/index.php');
exit;
