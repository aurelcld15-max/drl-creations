<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin-check.php';
require_once dirname(__DIR__) . '/classes/Product.php';

$categories = get_categories('product');
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Jeton CSRF invalide.';
    } else {
        $uploadedPaths = [];
        if (!empty($_FILES['photos']['name'][0])) {
            $count = count($_FILES['photos']['name']);
            for ($i = 0; $i < $count; $i++) {
                $singleFile = [
                    'name'     => $_FILES['photos']['name'][$i],
                    'type'     => $_FILES['photos']['type'][$i],
                    'tmp_name' => $_FILES['photos']['tmp_name'][$i],
                    'error'    => $_FILES['photos']['error'][$i],
                    'size'     => $_FILES['photos']['size'][$i],
                ];
                if ($singleFile['error'] === UPLOAD_ERR_OK) {
                    $up = secure_image_upload($singleFile, 'products');
                    if ($up['success']) {
                        $uploadedPaths[] = $up['path'];
                    } else {
                        $error = $up['error'];
                        break;
                    }
                }
            }
        }

        if ($error === null) {
            try {
                Product::create($_POST, $uploadedPaths);
                set_flash('success', 'Le nouvel article a été publié dans la Boutique avec succès.');
                header('Location: ' . BASE_URL . '/admin/produits.php');
                exit;
            } catch (Throwable $e) {
                $error = 'Erreur lors de la création : ' . $e->getMessage();
            }
        }
    }
}

$adminTitle = 'Ajouter un Article en Boutique';
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
    <?php if ($error): ?>
        <div class="drl-alert drl-alert-danger"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="drl-form-grid">
        <?= csrf_field() ?>
        <div class="drl-form-group">
            <label for="name_fr">Nom de l'article (Français) *</label>
            <input type="text" id="name_fr" name="name_fr" required>
        </div>
        <div class="drl-form-group">
            <label for="name_en">Nom de l'article (Anglais)</label>
            <input type="text" id="name_en" name="name_en">
        </div>
        <div class="drl-form-group">
            <label for="reference">Référence unique *</label>
            <input type="text" id="reference" name="reference" value="DRL-BTQ-<?= random_int(110, 999) ?>" required>
        </div>
        <div class="drl-form-group">
            <label for="category_id">Catégorie *</label>
            <select id="category_id" name="category_id" required>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= (int)$cat['id'] ?>"><?= e($cat['name_fr']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="drl-form-group">
            <label for="pricing_mode">Mode de tarification (Section 7) *</label>
            <select id="pricing_mode" name="pricing_mode" required>
                <option value="fixed">1. Prix fixe</option>
                <option value="negotiable">2. Prix négociable</option>
                <option value="on_request">3. Prix sur demande</option>
            </select>
        </div>
        <div class="drl-form-group">
            <label for="price">Prix (en FCFA — laisser vide si Prix sur demande)</label>
            <input type="number" id="price" name="price" step="0.01" min="0" placeholder="Ex: 150000">
        </div>

        <div class="drl-form-group">
            <label for="fabric_details">Étoffes &amp; Finitions</label>
            <input type="text" id="fabric_details" name="fabric_details" placeholder="Ex: Satin duchesse & Broderie main">
        </div>
        <div class="drl-form-group">
            <label for="sizes_available">Tailles disponibles</label>
            <input type="text" id="sizes_available" name="sizes_available" value="S, M, L, Sur-mesure">
        </div>

        <div class="drl-form-group drl-full-width">
            <label for="description_fr">Description détaillée (Français) *</label>
            <textarea id="description_fr" name="description_fr" rows="4" required></textarea>
        </div>
        <div class="drl-form-group drl-full-width">
            <label for="description_en">Description détaillée (Anglais)</label>
            <textarea id="description_en" name="description_en" rows="3"></textarea>
        </div>

        <div class="drl-form-group">
            <label for="availability_status">Statut de disponibilité</label>
            <select id="availability_status" name="availability_status">
                <option value="available">Disponible en boutique</option>
                <option value="sold">Vendu</option>
                <option value="archived">Archivé</option>
                <option value="hidden">Masqué</option>
            </select>
        </div>

        <div class="drl-form-group">
            <label for="photos">Importer plusieurs photographies (JPG, PNG, WEBP — max 5 Mo/photo)</label>
            <input type="file" id="photos" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple>
        </div>

        <div class="drl-form-group drl-full-width">
            <label>
                <input type="checkbox" name="is_featured" value="1" checked> Mettre en avant sur la page d'accueil
            </label>
        </div>

        <div class="drl-form-actions drl-full-width">
            <button type="submit" class="drl-btn drl-btn-primary"><i class="fa-solid fa-check"></i> Publier l'article</button>
            <a href="<?= BASE_URL ?>/admin/produits.php" class="drl-btn drl-btn-outline">Annuler</a>
        </div>
    </form>
</div>

            </div>
        </div>
    </div>
    <script src="<?= asset_url('assets/js/admin.js') ?>"></script>
</body>
</html>
