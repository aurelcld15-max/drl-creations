<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth-check.php';
require_once dirname(__DIR__) . '/classes/Product.php';

$user = current_user();
$userId = (int)$user['id'];
$lang = current_lang();
$favorites = Favorite::getUserFavorites($userId);

$pageTitle = __('client_menu_favorites');
require_once dirname(__DIR__) . '/includes/header.php';
?>

<section class="drl-section">
    <div class="drl-container">
        <div class="drl-client-layout">
            <aside class="drl-client-sidebar">
                <nav class="drl-client-nav">
                    <a href="<?= BASE_URL ?>/client/profil.php"><i class="fa-regular fa-id-badge"></i> <?= e(__('client_menu_profile')) ?></a>
                    <a href="<?= BASE_URL ?>/client/favoris.php" class="active"><i class="fa-regular fa-heart"></i> <?= e(__('client_menu_favorites')) ?></a>
                    <a href="<?= BASE_URL ?>/client/demandes.php"><i class="fa-solid fa-ruler-combined"></i> <?= e(__('client_menu_requests')) ?></a>
                    <a href="<?= BASE_URL ?>/client/parametres.php"><i class="fa-solid fa-sliders"></i> <?= e(__('client_menu_settings')) ?></a>
                    <a href="<?= BASE_URL ?>/auth/logout.php" class="drl-text-danger"><i class="fa-solid fa-arrow-right-from-bracket"></i> <?= e(__('client_menu_logout')) ?></a>
                </nav>
            </aside>

            <div class="drl-client-content">
                <div class="drl-panel">
                    <h2><?= e(__('client_menu_favorites')) ?></h2>

                    <?php if (empty($favorites['products']) && empty($favorites['creations'])): ?>
                        <p class="drl-text-muted">Vous n'avez encore enregistré aucun coup de cœur dans vos favoris.</p>
                        <div style="margin-top:16px;display:flex;gap:12px;">
                            <a href="<?= BASE_URL ?>/creations.php" class="drl-btn drl-btn-sm drl-btn-primary"><?= e(__('hero_btn_creations')) ?></a>
                            <a href="<?= BASE_URL ?>/boutique.php" class="drl-btn drl-btn-sm drl-btn-outline"><?= e(__('hero_btn_boutique')) ?></a>
                        </div>
                    <?php else: ?>
                        <?php if (!empty($favorites['creations'])): ?>
                            <h3 style="margin:20px 0 12px;">Modèles de l'Atelier</h3>
                            <div class="drl-cards-grid">
                                <?php foreach ($favorites['creations'] as $cr):
                                    $cName = localized_field($cr, 'name');
                                    $cUrl  = BASE_URL . '/modele.php?id=' . (int)$cr['id'];
                                ?>
                                <article class="drl-card">
                                    <div class="drl-card-media">
                                        <a href="<?= e($cUrl) ?>"><img src="<?= asset_url((string)$cr['primary_image']) ?>" alt="<?= e($cName) ?>"></a>
                                    </div>
                                    <div class="drl-card-body">
                                        <span class="drl-ref"><?= e($cr['reference']) ?></span>
                                        <h4 class="drl-card-title"><a href="<?= e($cUrl) ?>"><?= e($cName) ?></a></h4>
                                        <div class="drl-card-footer">
                                            <a href="<?= e($cUrl) ?>" class="drl-btn drl-btn-sm drl-btn-primary">Voir le modèle</a>
                                        </div>
                                    </div>
                                </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($favorites['products'])): ?>
                            <h3 style="margin:28px 0 12px;">Articles de la Boutique</h3>
                            <div class="drl-cards-grid">
                                <?php foreach ($favorites['products'] as $pr):
                                    $pName = localized_field($pr, 'name');
                                    $pUrl  = BASE_URL . '/article.php?id=' . (int)$pr['id'];
                                ?>
                                <article class="drl-card">
                                    <div class="drl-card-media">
                                        <a href="<?= e($pUrl) ?>"><img src="<?= asset_url((string)$pr['primary_image']) ?>" alt="<?= e($pName) ?>"></a>
                                    </div>
                                    <div class="drl-card-body">
                                        <span class="drl-ref"><?= e($pr['reference']) ?></span>
                                        <h4 class="drl-card-title"><a href="<?= e($pUrl) ?>"><?= e($pName) ?></a></h4>
                                        <div class="drl-card-price"><?= e(Product::formatPriceDisplay($pr, $lang)) ?></div>
                                        <div class="drl-card-footer">
                                            <a href="<?= e($pUrl) ?>" class="drl-btn drl-btn-sm drl-btn-outline">Voir l'article</a>
                                        </div>
                                    </div>
                                </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
