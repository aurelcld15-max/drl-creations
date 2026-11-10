<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin-check.php';
require_once dirname(__DIR__) . '/classes/Product.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $id = (int)($_POST['product_id'] ?? 0);
    $action = (string)($_POST['action'] ?? '');

    if ($id > 0) {
        if (in_array($action, ['available', 'sold', 'archived', 'hidden'], true)) {
            Product::updateStatus($id, $action);
            set_flash('success', "Le statut de disponibilité de l'article a été mis à jour (" . $action . ').');
        } elseif ($action === 'delete') {
            Product::delete($id);
            set_flash('info', "L'article a été supprimé définitivement.");
        }
    }
    header('Location: ' . BASE_URL . '/admin/produits.php');
    exit;
}

$statusFilter = isset($_GET['status']) ? trim((string)$_GET['status']) : null;
$products = Product::getAllForAdmin($statusFilter);
$adminTitle = 'Gestion des Articles de la Boutique';
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

<div class="drl-admin-toolbar">
    <div class="drl-filter-pills">
        <a href="<?= BASE_URL ?>/admin/produits.php" class="drl-filter-pill <?= !$statusFilter ? 'active' : '' ?>">Tous</a>
        <a href="<?= BASE_URL ?>/admin/produits.php?status=available" class="drl-filter-pill <?= $statusFilter === 'available' ? 'active' : '' ?>">Disponibles</a>
        <a href="<?= BASE_URL ?>/admin/produits.php?status=sold" class="drl-filter-pill <?= $statusFilter === 'sold' ? 'active' : '' ?>">Vendus</a>
        <a href="<?= BASE_URL ?>/admin/produits.php?status=archived" class="drl-filter-pill <?= $statusFilter === 'archived' ? 'active' : '' ?>">Archivés</a>
        <a href="<?= BASE_URL ?>/admin/produits.php?status=hidden" class="drl-filter-pill <?= $statusFilter === 'hidden' ? 'active' : '' ?>">Masqués</a>
    </div>
    <a href="<?= BASE_URL ?>/admin/ajouter-produit.php" class="drl-btn drl-btn-primary">
        <i class="fa-solid fa-plus"></i> Nouvel Article
    </a>
</div>

<div class="drl-admin-panel">
    <div class="drl-table-responsive">
        <table class="drl-table">
            <thead>
                <tr>
                    <th>Photo</th>
                    <th>Référence &amp; Nom</th>
                    <th>Catégorie</th>
                    <th>Tarification</th>
                    <th>Statut</th>
                    <th>Actions rapides</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                <tr>
                    <td>
                        <img src="<?= asset_url((string)$p['primary_image']) ?>" alt="" class="drl-admin-thumb">
                    </td>
                    <td>
                        <strong><?= e($p['name_fr']) ?></strong>
                        <div><small class="drl-gold-text"><?= e($p['reference']) ?></small></div>
                    </td>
                    <td><?= e($p['category_name_fr']) ?></td>
                    <td>
                        <strong><?= e(Product::formatPriceDisplay($p, 'fr')) ?></strong>
                        <div><small>Mode : <?= e($p['pricing_mode']) ?></small></div>
                    </td>
                    <td>
                        <span class="drl-status-pill status-<?= e($p['availability_status']) ?>">
                            <?= e($p['availability_status']) ?>
                        </span>
                    </td>
                    <td>
                        <div class="drl-admin-actions">
                            <a href="<?= BASE_URL ?>/admin/modifier-produit.php?id=<?= (int)$p['id'] ?>" class="drl-btn drl-btn-sm drl-btn-outline" title="Modifier">
                                <i class="fa-solid fa-pen"></i>
                            </a>

                            <form method="post" style="display:inline;">
                                <?= csrf_field() ?>
                                <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                                <select name="action" onchange="this.form.submit()" class="drl-select-inline" aria-label="Changer statut">
                                    <option value="">Changer statut...</option>
                                    <option value="available">Marquer Disponible</option>
                                    <option value="sold">Marquer comme Vendu</option>
                                    <option value="archived">Archiver</option>
                                    <option value="hidden">Masquer de la boutique</option>
                                </select>
                            </form>

                            <form method="post" style="display:inline;" onsubmit="return confirm('Supprimer définitivement cet article ?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                                <input type="hidden" name="action" value="delete">
                                <button type="submit" class="drl-btn drl-btn-sm drl-btn-danger" title="Supprimer définitivement">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </div>
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
