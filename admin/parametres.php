<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin-check.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $keys = [
        'site_name', 'site_slogan_fr', 'site_slogan_en',
        'whatsapp_number', 'contact_email', 'contact_phone',
        'atelier_address', 'atelier_hours_fr', 'atelier_hours_en',
        'instagram_url', 'facebook_url', 'tiktok_url', 'pinterest_url',
        'about_story_fr', 'about_story_en',
    ];

    foreach ($keys as $k) {
        if (isset($_POST[$k])) {
            $val = trim((string)$_POST[$k]);
            Database::query(
                "INSERT INTO site_settings (setting_key, setting_value, setting_group)
                 VALUES (:k, :v, 'general')
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()",
                [':k' => $k, ':v' => $val]
            );
        }
    }

    if (!empty($_FILES['logo_file']['name'])) {
        $up = secure_image_upload($_FILES['logo_file'], 'profiles');
        if ($up['success']) {
            Database::query(
                "INSERT INTO site_settings (setting_key, setting_value, setting_group)
                 VALUES ('site_logo', :v, 'general')
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)",
                [':v' => $up['path']]
            );
        }
    }

    set_flash('success', "Les paramètres de l'atelier et le numéro WhatsApp ont été enregistrés dans MySQL.");
    header('Location: ' . BASE_URL . '/admin/parametres.php');
    exit;
}

$adminTitle = "Paramètres de l'Atelier & WhatsApp";
?>
<?php
$currentAdminPage = basename($_SERVER['SCRIPT_NAME'] ?? 'dashboard.php');
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="fr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($adminTitle ?? 'Administration') ?> — DRL CRÉATIONS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,600;1,400&family=Playfair+Display:wght@600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= asset_url('assets/css/style.css') ?>">
    <link rel="stylesheet" href="<?= asset_url('assets/css/admin.css') ?>">
</head>
<body class="drl-admin-body">
    <div class="drl-admin-wrapper">
        <aside class="drl-admin-sidebar">
            <div class="drl-admin-brand">
                <img src="<?= asset_url('assets/images/logo-gold.svg') ?>" alt="DRL CRÉATIONS">
                <div>
                    <strong>DRL CRÉATIONS</strong>
                    <span>Direction d'Atelier</span>
                </div>
            </div>

            <nav class="drl-admin-nav">
                <a href="<?= BASE_URL ?>/admin/dashboard.php" class="<?= $currentAdminPage === 'dashboard.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-chart-pie"></i> Tableau de bord
                </a>
                <a href="<?= BASE_URL ?>/admin/produits.php" class="<?= in_array($currentAdminPage, ['produits.php', 'ajouter-produit.php', 'modifier-produit.php'], true) ? 'active' : '' ?>">
                    <i class="fa-solid fa-bag-shopping"></i> Boutique (Articles)
                </a>
                <a href="<?= BASE_URL ?>/admin/creations.php" class="<?= in_array($currentAdminPage, ['creations.php', 'ajouter-creation.php', 'modifier-creation.php'], true) ? 'active' : '' ?>">
                    <i class="fa-solid fa-wand-magic-sparkles"></i> Nos Créations (Modèles)
                </a>
                <a href="<?= BASE_URL ?>/admin/categories.php" class="<?= $currentAdminPage === 'categories.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-layer-group"></i> Catégories
                </a>
                <a href="<?= BASE_URL ?>/admin/clients.php" class="<?= $currentAdminPage === 'clients.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-users"></i> Clients &amp; Demandes
                </a>
                <a href="<?= BASE_URL ?>/admin/assistant-ia.php" class="<?= $currentAdminPage === 'assistant-ia.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-robot"></i> Gestion Assistant IA
                </a>
                <a href="<?= BASE_URL ?>/admin/parametres.php" class="<?= $currentAdminPage === 'parametres.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-sliders"></i> Paramètres &amp; WhatsApp
                </a>
            </nav>

            <div class="drl-admin-sidebar-footer">
                <a href="<?= BASE_URL ?>/index.php" class="drl-btn drl-btn-sm drl-btn-outline drl-full-width">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i> Voir le site public
                </a>
                <a href="<?= BASE_URL ?>/auth/logout.php" class="drl-admin-logout">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i> Déconnexion
                </a>
            </div>
        </aside>

        <div class="drl-admin-main">
            <header class="drl-admin-topbar">
                <h1><?= e($adminTitle ?? 'Tableau de bord') ?></h1>
                <div class="drl-admin-user">
                    <span><i class="fa-solid fa-crown drl-gold-icon"></i> <?= e($_SESSION['user']['first_name'] ?? 'Admin') ?></span>
                </div>
            </header>

            <div class="drl-admin-content">
                <?php if ($flash): ?>
                    <div class="drl-alert drl-alert-<?= e($flash['type']) ?>">
                        <span><?= e($flash['message']) ?></span>
                        <button type="button" class="drl-alert-close" onclick="this.parentElement.remove()">&times;</button>
                    </div>
                <?php endif; ?>

<div class="drl-admin-panel">
    <form method="post" enctype="multipart/form-data" class="drl-form-grid">
        <?= csrf_field() ?>
        <div class="drl-form-group">
            <label for="whatsapp_number"><i class="fa-brands fa-whatsapp drl-gold-icon"></i> Numéro WhatsApp officiel (format international sans +)</label>
            <input type="text" id="whatsapp_number" name="whatsapp_number" required value="<?= e(site_setting('whatsapp_number', '2290197000000')) ?>">
        </div>
        <div class="drl-form-group">
            <label for="contact_phone">Téléphone affiché</label>
            <input type="text" id="contact_phone" name="contact_phone" value="<?= e(site_setting('contact_phone')) ?>">
        </div>
        <div class="drl-form-group">
            <label for="contact_email">E-mail de l'atelier</label>
            <input type="email" id="contact_email" name="contact_email" value="<?= e(site_setting('contact_email')) ?>">
        </div>
        <div class="drl-form-group">
            <label for="atelier_address">Adresse physique de l'atelier</label>
            <input type="text" id="atelier_address" name="atelier_address" value="<?= e(site_setting('atelier_address')) ?>">
        </div>
        <div class="drl-form-group">
            <label for="atelier_hours_fr">Horaires d'ouverture (FR)</label>
            <input type="text" id="atelier_hours_fr" name="atelier_hours_fr" value="<?= e(site_setting('atelier_hours_fr')) ?>">
        </div>
        <div class="drl-form-group">
            <label for="atelier_hours_en">Horaires d'ouverture (EN)</label>
            <input type="text" id="atelier_hours_en" name="atelier_hours_en" value="<?= e(site_setting('atelier_hours_en')) ?>">
        </div>
        <div class="drl-form-group">
            <label for="instagram_url">Lien Instagram</label>
            <input type="url" id="instagram_url" name="instagram_url" value="<?= e(site_setting('instagram_url')) ?>">
        </div>
        <div class="drl-form-group">
            <label for="facebook_url">Lien Facebook</label>
            <input type="url" id="facebook_url" name="facebook_url" value="<?= e(site_setting('facebook_url')) ?>">
        </div>
        <div class="drl-form-group">
            <label for="tiktok_url">Lien TikTok</label>
            <input type="url" id="tiktok_url" name="tiktok_url" value="<?= e(site_setting('tiktok_url')) ?>">
        </div>
        <div class="drl-form-group">
            <label for="logo_file">Importer un nouveau logo (JPG, PNG, WEBP)</label>
            <input type="file" id="logo_file" name="logo_file" accept="image/jpeg,image/png,image/webp">
        </div>
        <div class="drl-form-group drl-full-width">
            <label for="about_story_fr">Texte de présentation de la Maison (Français)</label>
            <textarea id="about_story_fr" name="about_story_fr" rows="4"><?= e(site_setting('about_story_fr')) ?></textarea>
        </div>
        <div class="drl-form-group drl-full-width">
            <label for="about_story_en">Texte de présentation de la Maison (Anglais)</label>
            <textarea id="about_story_en" name="about_story_en" rows="4"><?= e(site_setting('about_story_en')) ?></textarea>
        </div>
        <div class="drl-form-actions drl-full-width">
            <button type="submit" class="drl-btn drl-btn-primary">Enregistrer tous les paramètres</button>
        </div>
    </form>
</div>

            </div>
        </div>
    </div>
    <script src="<?= asset_url('assets/js/admin.js') ?>"></script>
</body>
</html>
