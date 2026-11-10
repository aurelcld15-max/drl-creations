<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

$pageTitle = isset($pageTitle) ? ($pageTitle . ' — ' . site_setting('site_name', 'DRL CRÉATIONS')) : (site_setting('site_name', 'DRL CRÉATIONS') . ' — ' . __('hero_title'));
$pageDescription = $pageDescription ?? __('hero_subtitle');
$lang = current_lang();
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($pageDescription) ?>">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <meta name="base-url" content="<?= e(BASE_URL) ?>">
    <link rel="icon" type="image/jpeg" href="<?= asset_url('logo.jpeg') ?>">
    <link rel="apple-touch-icon" href="<?= asset_url('logo.jpeg') ?>">

    <!-- Typographies Haute Couture : Playfair Display, Cormorant Garamond, Poppins (Section 3) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&family=Playfair+Display:ital,wght@0,500;0,600;0,700;1,400;1,600&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

    <!-- Font Awesome 6 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Feuille de style principale DRL CRÉATIONS -->
    <link rel="stylesheet" href="<?= asset_url('assets/css/style.css') ?>">

    <!-- Script d'initialisation immédiate du thème Clair/Sombre (évite le flash visuel) -->
    <script>
        (function() {
            try {
                var savedTheme = localStorage.getItem('drl_theme') || 'light';
                document.documentElement.setAttribute('data-theme', savedTheme);
            } catch (e) {}
        })();
    </script>
</head>
<body class="drl-body<?= !empty($isAuthPage) ? ' drl-auth-body' : '' ?>">
    <div class="drl-page-transition" id="pageTransition"></div>

    <?php if (empty($isAuthPage)) require_once __DIR__ . '/navbar.php'; ?>

    <?php if ($flash): ?>
        <div class="drl-container drl-flash-wrap">
            <div class="drl-alert drl-alert-<?= e($flash['type']) ?>">
                <span><?= e($flash['message']) ?></span>
                <button type="button" class="drl-alert-close" onclick="this.parentElement.remove()">&times;</button>
            </div>
        </div>
    <?php endif; ?>

    <main class="drl-main">
