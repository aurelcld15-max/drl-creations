<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin-check.php';
require_once dirname(__DIR__) . '/classes/User.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    if (isset($_POST['toggle_user_id'])) {
        User::toggleActiveStatus((int)$_POST['toggle_user_id']);
        set_flash('success', 'Le statut du compte client a été mis à jour.');
    } elseif (isset($_POST['delete_user_id'])) {
        User::deleteClient((int)$_POST['delete_user_id']);
        set_flash('info', 'Le compte client a été supprimé.');
    } elseif (isset($_POST['request_id'], $_POST['request_status'])) {
        $st = (string)$_POST['request_status'];
        if (in_array($st, ['pending', 'in_progress', 'completed', 'cancelled'], true)) {
            Database::query("UPDATE custom_requests SET status = :st WHERE id = :id", [
                ':st' => $st,
                ':id' => (int)$_POST['request_id'],
            ]);
            set_flash('success', 'Le statut de la demande client a été mis à jour.');
        }
    }
    header('Location: ' . BASE_URL . '/admin/clients.php');
    exit;
}

$clients = User::getAllClients();
$requests = Database::fetchAll(
    "SELECT r.*, c.reference AS creation_ref
     FROM custom_requests r
     LEFT JOIN creations c ON c.id = r.creation_id
     ORDER BY r.created_at DESC"
);
$adminTitle = 'Gestion des Clients & Demandes Sur-Mesure';
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
    <h2>Comptes Clients Inscrits</h2>
    <div class="drl-table-responsive">
        <table class="drl-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nom &amp; Prénom</th>
                    <th>E-mail &amp; Téléphone</th>
                    <th>Authentification</th>
                    <th>Inscription</th>
                    <th>Favoris / Demandes</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($clients as $c): ?>
                <tr>
                    <td>#<?= (int)$c['id'] ?></td>
                    <td><strong><?= e($c['first_name'] . ' ' . $c['last_name']) ?></strong></td>
                    <td>
                        <div><?= e($c['email']) ?></div>
                        <small><?= e($c['phone'] ?? '—') ?></small>
                    </td>
                    <td><?= e(strtoupper((string)$c['oauth_provider'])) ?> (<?= e($c['role']) ?>)</td>
                    <td><?= e(date('d/m/Y', strtotime((string)$c['created_at']))) ?></td>
                    <td><?= (int)$c['favorites_count'] ?> fav. / <?= (int)$c['requests_count'] ?> dem.</td>
                    <td>
                        <span class="drl-status-pill <?= (int)$c['is_active'] === 1 ? 'status-available' : 'status-sold' ?>">
                            <?= (int)$c['is_active'] === 1 ? 'Actif' : 'Suspendu' ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($c['role'] !== 'admin'): ?>
                        <div class="drl-admin-actions">
                            <form method="post" style="display:inline;">
                                <?= csrf_field() ?>
                                <input type="hidden" name="toggle_user_id" value="<?= (int)$c['id'] ?>">
                                <button type="submit" class="drl-btn drl-btn-sm drl-btn-outline">
                                    <?= (int)$c['is_active'] === 1 ? 'Suspendre' : 'Activer' ?>
                                </button>
                            </form>
                            <form method="post" style="display:inline;" onsubmit="return confirm('Supprimer ce compte client ?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="delete_user_id" value="<?= (int)$c['id'] ?>">
                                <button type="submit" class="drl-btn drl-btn-sm drl-btn-danger"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </div>
                        <?php else: ?>
                            <small class="drl-gold-text">Administratrice</small>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="drl-admin-panel" style="margin-top:28px;">
    <h2>Demandes de Modèles &amp; Rendez-vous</h2>
    <div class="drl-table-responsive">
        <table class="drl-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Client</th>
                    <th>Objet / Modèle</th>
                    <th>Détails</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $r): ?>
                <tr>
                    <td><?= e(date('d/m/Y H:i', strtotime((string)$r['created_at']))) ?></td>
                    <td>
                        <strong><?= e($r['full_name']) ?></strong>
                        <div><small><?= e($r['email']) ?> — <?= e($r['phone'] ?? '') ?></small></div>
                    </td>
                    <td>
                        <?= e($r['subject'] ?? $r['request_type']) ?>
                        <?php if (!empty($r['creation_ref'])): ?>
                            <div><span class="drl-badge-ref"><?= e($r['creation_ref']) ?></span></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= e($r['message']) ?>
                        <?php if (!empty($r['measurements_notes'])): ?>
                            <div><small><em>Mesures : <?= e($r['measurements_notes']) ?></em></small></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="request_id" value="<?= (int)$r['id'] ?>">
                            <select name="request_status" onchange="this.form.submit()" class="drl-select-inline">
                                <option value="pending" <?= $r['status'] === 'pending' ? 'selected' : '' ?>>En attente</option>
                                <option value="in_progress" <?= $r['status'] === 'in_progress' ? 'selected' : '' ?>>En confection</option>
                                <option value="completed" <?= $r['status'] === 'completed' ? 'selected' : '' ?>>Terminée</option>
                                <option value="cancelled" <?= $r['status'] === 'cancelled' ? 'selected' : '' ?>>Annulée</option>
                            </select>
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
