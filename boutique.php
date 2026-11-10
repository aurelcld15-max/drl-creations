<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/classes/Product.php';

$pageTitle = __('boutique_page_title');
$lang = current_lang();
$user = current_user();

$selectedCat  = isset($_GET['category']) ? (int)$_GET['category'] : null;
$selectedMode = isset($_GET['mode']) ? trim((string)$_GET['mode']) : null;
$searchQuery  = isset($_GET['q']) ? trim((string)$_GET['q']) : null;

$categories = get_categories('product');
$products   = Product::getAvailableProducts($selectedCat, $selectedMode, $searchQuery);

require_once __DIR__ . '/includes/header.php';
?>

<section class="drl-page-hero">
    <div class="drl-container">
        <span class="drl-kicker">COLLECTION PRÊT-À-PORTER &amp; PIÈCES UNIQUES</span>
        <h1 class="drl-page-title"><?= e(__('boutique_page_title')) ?></h1>
        <p class="drl-page-sub"><?= e(__('boutique_page_sub')) ?></p>
    </div>
</section>

<section class="drl-section">
    <div class="drl-container">
        <!-- Filtres Catégories & Modes de tarification (Prix fixe, Prix négociable, Prix sur demande) -->
        <div class="drl-filter-bar">
            <div class="drl-filter-pills">
                <a href="<?= BASE_URL ?>/boutique.php" class="drl-filter-pill <?= (!$selectedCat && !$selectedMode) ? 'active' : '' ?>">
                    <?= e(__('creations_all_cats')) ?>
                </a>
                <?php foreach ($categories as $cat): ?>
                    <a href="<?= BASE_URL ?>/boutique.php?category=<?= (int)$cat['id'] ?><?= $selectedMode ? '&mode=' . urlencode($selectedMode) : '' ?>"
                       class="drl-filter-pill <?= $selectedCat === (int)$cat['id'] ? 'active' : '' ?>">
                        <?= e(localized_field($cat, 'name')) ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="drl-pricing-modes-filter">
                <a href="<?= BASE_URL ?>/boutique.php?mode=fixed<?= $selectedCat ? '&category=' . (int)$selectedCat : '' ?>"
                   class="drl-mode-chip <?= $selectedMode === 'fixed' ? 'active' : '' ?>">
                    <i class="fa-solid fa-tag"></i> <?= e(__('pricing_fixed')) ?>
                </a>
                <a href="<?= BASE_URL ?>/boutique.php?mode=negotiable<?= $selectedCat ? '&category=' . (int)$selectedCat : '' ?>"
                   class="drl-mode-chip <?= $selectedMode === 'negotiable' ? 'active' : '' ?>">
                    <i class="fa-solid fa-handshake"></i> <?= e(__('pricing_negotiable')) ?>
                </a>
                <a href="<?= BASE_URL ?>/boutique.php?mode=on_request<?= $selectedCat ? '&category=' . (int)$selectedCat : '' ?>"
                   class="drl-mode-chip <?= $selectedMode === 'on_request' ? 'active' : '' ?>">
                    <i class="fa-solid fa-gem"></i> <?= e(__('pricing_on_request')) ?>
                </a>
            </div>
        </div>

        <?php if (empty($products)): ?>
            <div class="drl-empty-state">
                <i class="fa-solid fa-bag-shopping"></i>
                <h3><?= $lang === 'en' ? 'No available article matches your criteria' : 'Aucun article disponible ne correspond à ces critères' ?></h3>
                <p><?= $lang === 'en' ? 'Note: Sold or archived pieces are automatically removed from the active catalogue.' : 'Les articles vendus ou archivés ne figurent plus parmi les pièces disponibles.' ?></p>
                <a href="<?= BASE_URL ?>/boutique.php" class="drl-btn drl-btn-outline"><?= e(__('creations_all_cats')) ?></a>
            </div>
        <?php else: ?>
            <div class="drl-cards-grid">
                <?php foreach ($products as $product):
                    $pName = localized_field($product, 'name');
                    $pCat  = localized_field($product, 'category_name');
                    $pUrl  = BASE_URL . '/article.php?id=' . (int)$product['id'];
                    $priceLabel = Product::formatPriceDisplay($product, $lang);
                    $waLink = WhatsApp::generateItemLink($pName, (string)$product['reference'], $pUrl, $lang, $priceLabel);
                    $isFav = $user ? Favorite::isFavorited((int)$user['id'], 'product', (int)$product['id']) : false;
                    $modeKey = 'pricing_' . ($product['pricing_mode'] ?? 'fixed');
                ?>
                <article class="drl-card reveal-up">
                    <div class="drl-card-media">
                        <span class="drl-card-badge drl-badge-mode-<?= e($product['pricing_mode']) ?>">
                            <?= e(__($modeKey)) ?>
                        </span>
                        <button type="button"
                                class="drl-card-fav <?= $isFav ? 'active' : '' ?>"
                                data-fav-toggle
                                data-type="product"
                                data-id="<?= (int)$product['id'] ?>"
                                aria-label="<?= e(__('btn_favorite_add')) ?>">
                            <i class="<?= $isFav ? 'fa-solid' : 'fa-regular' ?> fa-heart"></i>
                        </button>
                        <a href="<?= e($pUrl) ?>">
                            <img src="<?= asset_url((string)$product['primary_image']) ?>" alt="<?= e($pName) ?>" loading="lazy">
                        </a>
                    </div>
                    <div class="drl-card-body">
                        <div class="drl-card-meta">
                            <span class="drl-ref"><?= e($product['reference']) ?></span>
                            <span class="drl-cat-tag"><?= e($pCat) ?></span>
                        </div>
                        <h2 class="drl-card-title"><a href="<?= e($pUrl) ?>"><?= e($pName) ?></a></h2>
                        <p class="drl-card-excerpt"><?= e(mb_strimwidth(localized_field($product, 'description'), 0, 115, '…')) ?></p>
                        <div class="drl-card-price"><?= e($priceLabel) ?></div>
                        <div class="drl-card-footer">
                            <a href="<?= e($pUrl) ?>" class="drl-btn drl-btn-sm drl-btn-outline">
                                <?= $lang === 'en' ? 'View Piece' : 'Détails' ?>
                            </a>
                            <a href="<?= e($waLink) ?>" target="_blank" rel="noopener" class="drl-btn drl-btn-sm drl-btn-wa">
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
