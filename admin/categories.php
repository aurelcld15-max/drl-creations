<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin-check.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    if (isset($_POST['add_category'])) {
        $nfr  = trim((string)($_POST['name_fr'] ?? ''));
        $nen  = trim((string)($_POST['name_en'] ?? $nfr));
        $type = in_array($_POST['type'] ?? '', ['creation', 'product', 'both'], true) ? $_POST['type'] : 'both';
        $dfr  = trim((string)($_POST['description_fr'] ?? ''));
        $slug = strtolower(trim((string)preg_replace('/[^a-z0-9]+/i', '-', $nfr), '-'));

        if ($nfr !== '') {
            Database::query(
                "INSERT INTO categories (name_fr, name_en, slug, type, description_fr, description_en, sort_order, is_active)
                 VALUES (:nfr, :nen, :slug, :type, :dfr, :dfr, 10, 1)",
                [':nfr' => $nfr, ':nen' => $nen, ':slug' => $slug . '-' . random_int(10, 99), ':type' => $type, ':dfr' => $dfr]
            );
            set_flash('success', 'La nouvelle catégorie a été créée.');
        }
    } elseif (isset($_POST['toggle_cat_id'])) {
        Database::query(
            "UPDATE categories SET is_active = IF(is_active = 1, 0, 1) WHERE id = :id",
            [':id' => (int)$_POST['toggle_cat_id']]
        );
        set_flash('success', 'Statut de la catégorie mis à jour.');
    }
    header('Location: ' . BASE_URL . '/admin/categories.php');
    exit;
}

$categories = Database::fetchAll("SELECT * FROM categories ORDER BY sort_order ASC, id ASC");
$adminTitle = 'Gestion des Catégories';
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
    <h2>Ajouter une catégorie</h2>
    <form method="post" class="drl-form-grid">
        <?= csrf_field() ?>
        <div class="drl-form-group">
            <label for="name_fr">Nom (Français) *</label>
            <input type="text" id="name_fr" name="name_fr" required>
        </div>
        <div class="drl-form-group">
            <label for="name_en">Nom (Anglais)</label>
            <input type="text" id="name_en" name="name_en">
        </div>
        <div class="drl-form-group">
            <label for="type">Affectation</label>
            <select id="type" name="type">
                <option value="both">Créations &amp; Boutique</option>
                <option value="creation">Nos Créations uniquement</option>
                <option value="product">Boutique uniquement</option>
            </select>
        </div>
        <div class="drl-form-group">
            <label for="description_fr">Description courte</label>
            <input type="text" id="description_fr" name="description_fr">
        </div>
        <div class="drl-form-actions drl-full-width">
            <button type="submit" name="add_category" value="1" class="drl-btn drl-btn-primary">Ajouter la catégorie</button>
        </div>
    </form>
</div>

<div class="drl-admin-panel" style="margin-top:28px;">
    <h2>Catégories existantes</h2>
    <div class="drl-table-responsive">
        <table class="drl-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nom FR / EN</th>
                    <th>Type</th>
                    <th>Statut</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $cat): ?>
                <tr>
                    <td>#<?= (int)$cat['id'] ?></td>
                    <td>
                        <strong><?= e($cat['name_fr']) ?></strong> / <?= e($cat['name_en']) ?>
                        <div><small><?= e($cat['description_fr'] ?? '') ?></small></div>
                    </td>
                    <td><?= e($cat['type']) ?></td>
                    <td><?= (int)$cat['is_active'] === 1 ? 'Active' : 'Masquée' ?></td>
                    <td>
                        <form method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="toggle_cat_id" value="<?= (int)$cat['id'] ?>">
                            <button type="submit" class="drl-btn drl-btn-sm drl-btn-outline">
                                <?= (int)$cat['is_active'] === 1 ? 'Désactiver' : 'Activer' ?>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

            </div>
        </div>
    </div>
    <script src="<?= asset_url('assets/js/admin.js') ?>"></script>
</body>
</html>
