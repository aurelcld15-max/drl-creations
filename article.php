<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/classes/Product.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = $id > 0 ? Product::findById($id, is_admin()) : null;

if (!$product) {
    set_flash('warning', current_lang() === 'en'
        ? 'This item is no longer available or has been sold.'
        : "Cet article a été vendu ou n'est plus disponible en boutique.");
    header('Location: ' . BASE_URL . '/boutique.php');
    exit;
}

$lang = current_lang();
$user = current_user();

Database::query("UPDATE products SET views_count = views_count + 1 WHERE id = :id", [':id' => $product['id']]);

$pName      = localized_field($product, 'name');
$pDesc      = localized_field($product, 'description');
$pCat       = localized_field($product, 'category_name');
$priceLabel = Product::formatPriceDisplay($product, $lang);
$itemUrl    = BASE_URL . '/article.php?id=' . (int)$product['id'];
$waLink     = WhatsApp::generateItemLink($pName, (string)$product['reference'], $itemUrl, $lang, $priceLabel);
$isFav      = $user ? Favorite::isFavorited((int)$user['id'], 'product', (int)$product['id']) : false;
$images     = $product['images'] ?? [];
$modeKey    = 'pricing_' . ($product['pricing_mode'] ?? 'fixed');

$waBtnText = match ($product['pricing_mode']) {
    'negotiable' => __('btn_whatsapp_negotiate'),
    'on_request' => __('btn_whatsapp_quote'),
    default      => __('btn_whatsapp_order'),
};

$pageTitle = $pName . ' (' . $product['reference'] . ')';
require_once __DIR__ . '/includes/header.php';
?>

<section class="drl-section">
    <div class="drl-container">
        <nav class="drl-breadcrumb" aria-label="Fil d'Ariane">
            <a href="<?= BASE_URL ?>/index.php"><?= e(__('nav_home')) ?></a>
            <span>/</span>
            <a href="<?= BASE_URL ?>/boutique.php"><?= e(__('nav_boutique')) ?></a>
            <span>/</span>
            <span><?= e($pName) ?></span>
        </nav>

        <div class="drl-detail-grid">
            <!-- Galerie Photographique -->
            <div class="drl-gallery-box">
                <div class="drl-gallery-main">
                    <img src="<?= asset_url((string)$images[0]['image_path']) ?>" alt="<?= e($pName) ?>" id="mainArticleImage">
                </div>
                <?php if (count($images) > 1): ?>
                <div class="drl-gallery-thumbs">
                    <?php foreach ($images as $idx => $img): ?>
                        <button type="button" class="drl-thumb-btn <?= $idx === 0 ? 'active' : '' ?>"
                                onclick="document.getElementById('mainArticleImage').src='<?= e(asset_url((string)$img['image_path'])) ?>'">
                            <img src="<?= asset_url((string)$img['image_path']) ?>" alt="<?= e($pName) ?>">
                        </button>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Détails de l'article -->
            <div class="drl-detail-info">
                <div class="drl-detail-badges">
                    <span class="drl-kicker"><?= e($pCat) ?></span>
                    <span class="drl-mode-badge drl-badge-mode-<?= e($product['pricing_mode']) ?>"><?= e(__($modeKey)) ?></span>
                </div>

                <h1 class="drl-detail-title"><?= e($pName) ?></h1>
                <p class="drl-detail-ref"><?= e(__('creations_ref_label')) ?> : <strong><?= e($product['reference']) ?></strong></p>

                <div class="drl-detail-price-box">
                    <span class="drl-detail-price"><?= e($priceLabel) ?></span>
                </div>

                <div class="drl-detail-description">
                    <?= nl2br(e($pDesc)) ?>
                </div>

                <div class="drl-specs-list">
                    <?php if (!empty($product['fabric_details'])): ?>
                    <div class="drl-spec-row">
                        <span><i class="fa-solid fa-layer-group drl-gold-icon"></i> <?= e(__('product_fabric')) ?></span>
                        <strong><?= e($product['fabric_details']) ?></strong>
                    </div>
                    <?php endif; ?>
                    <div class="drl-spec-row">
                        <span><i class="fa-solid fa-ruler-horizontal drl-gold-icon"></i> <?= e(__('product_sizes')) ?></span>
                        <strong><?= e($product['sizes_available']) ?></strong>
                    </div>
                </div>

                <!-- Boutons d'action : WhatsApp, Favoris, Partager (Exigence Section 7) -->
                <div class="drl-detail-actions">
                    <a href="<?= e($waLink) ?>" target="_blank" rel="noopener" class="drl-btn drl-btn-wa">
                        <i class="fa-brands fa-whatsapp"></i>
                        <span><?= e($waBtnText) ?></span>
                    </a>

                    <button type="button"
                            class="drl-btn drl-btn-outline <?= $isFav ? 'active' : '' ?>"
                            data-fav-toggle
                            data-type="product"
                            data-id="<?= (int)$product['id'] ?>">
                        <i class="<?= $isFav ? 'fa-solid' : 'fa-regular' ?> fa-heart"></i>
                        <span><?= e($isFav ? __('btn_favorite_remove') : __('btn_favorite_add')) ?></span>
                    </button>

                    <button type="button"
                            class="drl-btn drl-btn-outline"
                            data-share-url="<?= e($itemUrl) ?>"
                            data-share-title="<?= e($pName . ' — DRL CRÉATIONS') ?>">
                        <i class="fa-solid fa-share-nodes"></i>
                        <span><?= e(__('btn_share')) ?></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
