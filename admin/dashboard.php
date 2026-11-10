<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin-check.php';

$adminTitle = 'Tableau de Bord Général';

$stats = [
    'products_available' => (int)(Database::fetchOne("SELECT COUNT(*) AS c FROM products WHERE availability_status = 'available'")['c'] ?? 0),
    'products_sold'      => (int)(Database::fetchOne("SELECT COUNT(*) AS c FROM products WHERE availability_status = 'sold'")['c'] ?? 0),
    'creations_pub'      => (int)(Database::fetchOne("SELECT COUNT(*) AS c FROM creations WHERE publication_status = 'published'")['c'] ?? 0),
    'clients_count'      => (int)(Database::fetchOne("SELECT COUNT(*) AS c FROM users WHERE role = 'client'")['c'] ?? 0),
    'pending_requests'   => (int)(Database::fetchOne("SELECT COUNT(*) AS c FROM custom_requests WHERE status = 'pending'")['c'] ?? 0),
    'ai_chats'           => (int)(Database::fetchOne("SELECT COUNT(*) AS c FROM ai_conversations")['c'] ?? 0),
];

$recentRequests = Database::fetchAll(
    "SELECT r.*, c.reference AS creation_ref, p.reference AS product_ref
     FROM custom_requests r
     LEFT JOIN creations c ON c.id = r.creation_id
     LEFT JOIN products p ON p.id = r.product_id
     ORDER BY r.created_at DESC LIMIT 8"
);
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

<div class="drl-admin-stats-grid">
    <div class="drl-stat-card">
        <div class="drl-stat-icon"><i class="fa-solid fa-bag-shopping"></i></div>
        <div>
            <h3><?= $stats['products_available'] ?></h3>
            <p>Articles disponibles en Boutique</p>
        </div>
    </div>
    <div class="drl-stat-card">
        <div class="drl-stat-icon"><i class="fa-solid fa-check-double"></i></div>
        <div>
            <h3><?= $stats['products_sold'] ?></h3>
            <p>Articles marqués vendus</p>
        </div>
    </div>
    <div class="drl-stat-card">
        <div class="drl-stat-icon"><i class="fa-solid fa-wand-magic-sparkles"></i></div>
        <div>
            <h3><?= $stats['creations_pub'] ?></h3>
            <p>Modèles publiés (Créations)</p>
        </div>
    </div>
    <div class="drl-stat-card">
        <div class="drl-stat-icon"><i class="fa-solid fa-users"></i></div>
        <div>
            <h3><?= $stats['clients_count'] ?></h3>
            <p>Clients inscrits</p>
        </div>
    </div>
    <div class="drl-stat-card">
        <div class="drl-stat-icon"><i class="fa-solid fa-ruler-combined"></i></div>
        <div>
            <h3><?= $stats['pending_requests'] ?></h3>
            <p>Demandes en attente</p>
        </div>
    </div>
    <div class="drl-stat-card">
        <div class="drl-stat-icon"><i class="fa-solid fa-robot"></i></div>
        <div>
            <h3><?= $stats['ai_chats'] ?></h3>
            <p>Interactions DRL Assistant</p>
        </div>
    </div>
</div>

<div class="drl-admin-quick-bar">
    <a href="<?= BASE_URL ?>/admin/ajouter-produit.php" class="drl-btn drl-btn-primary">
        <i class="fa-solid fa-plus"></i> Ajouter un article en Boutique
    </a>
    <a href="<?= BASE_URL ?>/admin/ajouter-creation.php" class="drl-btn drl-btn-outline">
        <i class="fa-solid fa-wand-magic-sparkles"></i> Ajouter un modèle réalisable
    </a>
    <a href="<?= BASE_URL ?>/admin/parametres.php" class="drl-btn drl-btn-outline">
        <i class="fa-brands fa-whatsapp"></i> Configurer le numéro WhatsApp (<?= e(WhatsApp::getNumber()) ?>)
    </a>
</div>

<div class="drl-admin-panel">
    <h2>Dernières demandes de modèles personnalisés &amp; contacts</h2>
    <div class="drl-table-responsive">
        <table class="drl-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Client</th>
                    <th>Contact</th>
                    <th>Objet / Référence</th>
                    <th>Message &amp; Mesures</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentRequests)): ?>
                    <tr><td colspan="6">Aucune demande enregistrée pour le moment.</td></tr>
                <?php else: ?>
                    <?php foreach ($recentRequests as $req): ?>
                    <tr>
                        <td><?= e(date('d/m/Y H:i', strtotime((string)$req['created_at']))) ?></td>
                        <td><strong><?= e($req['full_name']) ?></strong></td>
                        <td>
                            <div><?= e($req['email']) ?></div>
                            <?php if (!empty($req['phone'])): ?>
                                <small><?= e($req['phone']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= e($req['subject'] ?? $req['request_type']) ?>
                            <?php if (!empty($req['creation_ref'])): ?>
                                <div><span class="drl-badge-ref"><?= e($req['creation_ref']) ?></span></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= e(mb_strimwidth((string)$req['message'], 0, 110, '…')) ?>
                            <?php if (!empty($req['measurements_notes'])): ?>
                                <div><small><em>Mesures : <?= e($req['measurements_notes']) ?></em></small></div>
                            <?php endif; ?>
                        </td>
                        <td><span class="drl-status-pill status-<?= e($req['status']) ?>"><?= e($req['status']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
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
