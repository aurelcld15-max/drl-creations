<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/classes/Creation.php';

$pageTitle = __('creations_page_title');
$lang = current_lang();
$user = current_user();

$selectedCat = isset($_GET['category']) ? (int)$_GET['category'] : null;
$searchQuery = isset($_GET['q']) ? trim((string)$_GET['q']) : null;

$categories = get_categories('creation');
$creations  = Creation::getPublishedCreations($selectedCat, $searchQuery);

require_once __DIR__ . '/includes/header.php';
?>

<section class="drl-page-hero">
    <div class="drl-container">
        <span class="drl-kicker">ATELIER SUR-MESURE</span>
        <h1 class="drl-page-title"><?= e(__('creations_page_title')) ?></h1>
        <p class="drl-page-sub"><?= e(__('creations_page_sub')) ?></p>
    </div>
</section>

<section class="drl-section">
    <div class="drl-container">
        <!-- Filtres par catégorie et recherche -->
        <div class="drl-filter-bar">
            <div class="drl-filter-pills">
                <a href="<?= BASE_URL ?>/creations.php" class="drl-filter-pill <?= !$selectedCat ? 'active' : '' ?>">
                    <?= e(__('creations_all_cats')) ?>
                </a>
                <?php foreach ($categories as $cat): ?>
                    <a href="<?= BASE_URL ?>/creations.php?category=<?= (int)$cat['id'] ?>"
                       class="drl-filter-pill <?= $selectedCat === (int)$cat['id'] ? 'active' : '' ?>">
                        <?= e(localized_field($cat, 'name')) ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <form method="get" action="<?= BASE_URL ?>/creations.php" class="drl-inline-search">
                <?php if ($selectedCat): ?>
                    <input type="hidden" name="category" value="<?= (int)$selectedCat ?>">
                <?php endif; ?>
                <input type="search" name="q" value="<?= e($searchQuery) ?>" placeholder="<?= e(__('nav_search')) ?>">
                <button type="submit" aria-label="Rechercher"><i class="fa-solid fa-magnifying-glass"></i></button>
            </form>
        </div>

        <?php if (empty($creations)): ?>
            <div class="drl-empty-state">
                <i class="fa-solid fa-wand-magic-sparkles"></i>
                <h3><?= $lang === 'en' ? 'No creation matches your selection' : 'Aucun modèle ne correspond à votre sélection' ?></h3>
                <p><?= $lang === 'en' ? 'Contact our atelier directly to sketch a custom creation.' : 'Contactez directement notre atelier pour dessiner une pièce personnalisée.' ?></p>
                <a href="<?= BASE_URL ?>/creations.php" class="drl-btn drl-btn-outline"><?= e(__('creations_all_cats')) ?></a>
            </div>
        <?php else: ?>
            <div class="drl-cards-grid">
                <?php foreach ($creations as $creation):
                    $cName = localized_field($creation, 'name');
                    $cCat  = localized_field($creation, 'category_name');
                    $cUrl  = BASE_URL . '/modele.php?id=' . (int)$creation['id'];
                    $waLink = WhatsApp::generateItemLink($cName, (string)$creation['reference'], $cUrl, $lang, 'Demande de réalisation du modèle');
                    $isFav = $user ? Favorite::isFavorited((int)$user['id'], 'creation', (int)$creation['id']) : false;
                ?>
                <article class="drl-card reveal-up">
                    <div class="drl-card-media">
                        <span class="drl-card-badge"><?= e($cCat) ?></span>
                        <button type="button"
                                class="drl-card-fav <?= $isFav ? 'active' : '' ?>"
                                data-fav-toggle
                                data-type="creation"
                                data-id="<?= (int)$creation['id'] ?>"
                                aria-label="<?= e(__('btn_favorite_add')) ?>">
                            <i class="<?= $isFav ? 'fa-solid' : 'fa-regular' ?> fa-heart"></i>
                        </button>
                        <a href="<?= e($cUrl) ?>">
                            <img src="<?= asset_url((string)$creation['primary_image']) ?>" alt="<?= e($cName) ?>" loading="lazy">
                        </a>
                    </div>
                    <div class="drl-card-body">
                        <div class="drl-card-meta">
                            <span class="drl-ref"><?= e(__('creations_ref_label')) ?> : <?= e($creation['reference']) ?></span>
                            <span class="drl-delay"><i class="fa-regular fa-clock"></i> <?= e($creation['estimated_delay']) ?></span>
                        </div>
                        <h2 class="drl-card-title"><a href="<?= e($cUrl) ?>"><?= e($cName) ?></a></h2>
                        <p class="drl-card-excerpt"><?= e(mb_strimwidth(localized_field($creation, 'description'), 0, 140, '…')) ?></p>
                        <p class="drl-card-custom-note"><i class="fa-solid fa-scissors"></i> <?= e(__('creations_no_price')) ?></p>
                        <div class="drl-card-footer">
                            <a href="<?= e($cUrl) ?>#demander-modele" class="drl-btn drl-btn-sm drl-btn-primary">
                                <?= e(__('creations_request_btn')) ?>
                            </a>
                            <a href="<?= e($waLink) ?>" target="_blank" rel="noopener" class="drl-btn drl-btn-sm drl-btn-wa" title="WhatsApp">
                                <i class="fa-brands fa-whatsapp"></i> WhatsApp
                            </a>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
