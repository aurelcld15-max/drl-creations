<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth-check.php';
require_once dirname(__DIR__) . '/classes/User.php';

$user = current_user();
$requests = User::getClientRequests((int)$user['id'], (string)$user['email']);

$pageTitle = __('client_menu_requests');
require_once dirname(__DIR__) . '/includes/header.php';
?>

<section class="drl-section">
    <div class="drl-container">
        <div class="drl-client-layout">
            <aside class="drl-client-sidebar">
                <nav class="drl-client-nav">
                    <a href="<?= BASE_URL ?>/client/profil.php"><i class="fa-regular fa-id-badge"></i> <?= e(__('client_menu_profile')) ?></a>
                    <a href="<?= BASE_URL ?>/client/favoris.php"><i class="fa-regular fa-heart"></i> <?= e(__('client_menu_favorites')) ?></a>
                    <a href="<?= BASE_URL ?>/client/demandes.php" class="active"><i class="fa-solid fa-ruler-combined"></i> <?= e(__('client_menu_requests')) ?></a>
                    <a href="<?= BASE_URL ?>/client/parametres.php"><i class="fa-solid fa-sliders"></i> <?= e(__('client_menu_settings')) ?></a>
                    <a href="<?= BASE_URL ?>/auth/logout.php" class="drl-text-danger"><i class="fa-solid fa-arrow-right-from-bracket"></i> <?= e(__('client_menu_logout')) ?></a>
                </nav>
            </aside>

            <div class="drl-client-content">
                <div class="drl-panel">
                    <h2>Historique de mes demandes sur-mesure &amp; contacts</h2>
                    <?php if (empty($requests)): ?>
                        <p class="drl-text-muted">Vous n'avez encore soumis aucune demande de modèle personnalisé.</p>
                        <a href="<?= BASE_URL ?>/creations.php" class="drl-btn drl-btn-sm drl-btn-primary" style="margin-top:12px;">Découvrir nos créations</a>
                    <?php else: ?>
                        <div class="drl-table-responsive">
                            <table class="drl-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Objet / Modèle</th>
                                        <th>Détails &amp; Mensurations</th>
                                        <th>Statut</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($requests as $req): ?>
                                    <tr>
                                        <td><?= e(date('d/m/Y H:i', strtotime((string)$req['created_at']))) ?></td>
                                        <td>
                                            <strong><?= e($req['subject'] ?? 'Demande Atelier') ?></strong>
                                            <?php if (!empty($req['creation_ref'])): ?>
                                                <div><small class="drl-gold-text">Réf : <?= e($req['creation_ref']) ?></small></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= e($req['message']) ?>
                                            <?php if (!empty($req['measurements_notes'])): ?>
                                                <div><small><em>Mesures : <?= e($req['measurements_notes']) ?></em></small></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="drl-status-pill status-<?= e($req['status']) ?>">
                                                <?= e($req['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
