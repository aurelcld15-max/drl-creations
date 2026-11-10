<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/classes/Product.php';
require_once __DIR__ . '/classes/Creation.php';

$pageTitle = __('nav_home');
$user = current_user();
$lang = current_lang();

try {
    $featuredCreations = Creation::getPublishedCreations(null, null, 3);
    $featuredProducts  = Product::getAvailableProducts(null, null, null, 3);
    $categories        = get_categories();
    $faqs              = Database::fetchAll("SELECT * FROM faq WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 4");
} catch (Throwable) {
    $featuredCreations = [];
    $featuredProducts  = [];
    $categories        = [];
    $faqs              = [];
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- BANNIÈRE DE SALUTATION PERSONNALISÉE (Exigence Section 5) -->
<section class="drl-greeting-banner">
    <div class="drl-container">
        <p class="drl-greeting-text">
            <?php if ($user && !empty($user['first_name'])): ?>
                « <?= e(__('greeting_user', [':name' => $user['first_name']])) ?> »
            <?php else: ?>
                « <?= e(__('greeting_guest')) ?> »
            <?php endif; ?>
        </p>
    </div>
</section>

<!-- HERO SECTION AVEC EFFETS DE PARALLAXE SUBTILS (Exigence Section 5) -->
<section class="drl-hero" data-parallax-container>
    <div class="drl-hero-bg-layer" data-parallax-speed="0.22"></div>
    <div class="drl-container drl-hero-grid">
        <div class="drl-hero-content reveal-up">
            <span class="drl-kicker"><?= e(__('hero_badge')) ?></span>
            <h1 class="drl-hero-title">« <?= e(__('hero_title')) ?> »</h1>
            <p class="drl-hero-description">
                <?= e(__('hero_subtitle')) ?>
            </p>
            <div class="drl-hero-actions">
                <a href="<?= BASE_URL ?>/creations.php" class="drl-btn drl-btn-primary">
                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                    <span><?= e(__('hero_btn_creations')) ?></span>
                </a>
                <a href="<?= BASE_URL ?>/boutique.php" class="drl-btn drl-btn-outline">
                    <i class="fa-solid fa-bag-shopping"></i>
                    <span><?= e(__('hero_btn_boutique')) ?></span>
                </a>
            </div>
            <div class="drl-hero-metrics">
                <div class="drl-metric-item">
                    <strong>100%</strong>
                    <span><?= $lang === 'en' ? 'Handcrafted Finishes' : 'Finitions Façon Main' ?></span>
                </div>
                <div class="drl-metric-item">
                    <strong>Sur-Mesure</strong>
                    <span><?= $lang === 'en' ? 'Bespoke Silhouette' : 'Patronage Exclusif' ?></span>
                </div>
                <div class="drl-metric-item">
                    <strong>Haute Couture</strong>
                    <span><?= $lang === 'en' ? 'Noble Fabrics' : "Étoffes d'Exception" ?></span>
                </div>
            </div>
        </div>

        <div class="drl-hero-visual reveal-scale" data-parallax-speed="-0.12">
            <div class="drl-hero-frame">
                <img src="<?= asset_url('assets/images/hero-couture.svg') ?>" alt="Silhouette Haute Couture DRL CRÉATIONS" class="drl-hero-img">
                <div class="drl-hero-floating-badge">
                    <i class="fa-solid fa-crown"></i>
                    <div>
                        <strong>Maison DRL</strong>
                        <span><?= $lang === 'en' ? 'Signature Atelier' : "Signature d'Atelier" ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- RUBAN DES COLLECTIONS / CATÉGORIES -->
<section class="drl-section drl-categories-strip">
    <div class="drl-container">
        <div class="drl-pills-scroll">
            <?php foreach ($categories as $cat): ?>
                <a href="<?= BASE_URL ?>/creations.php?category=<?= (int)$cat['id'] ?>" class="drl-category-pill">
                    <span><?= e(localized_field($cat, 'name')) ?></span>
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- SECTION : SIGNATURES DE L'ATELIER (ESPACE NOS CRÉATIONS) -->
<section class="drl-section">
    <div class="drl-container">
        <div class="drl-section-header reveal-up">
            <div>
                <span class="drl-kicker"><?= $lang === 'en' ? 'BESPOKE ATELIER' : 'CONFFECTION SUR-MESURE' ?></span>
                <h2 class="drl-section-title"><?= e(__('home_featured_creations_title')) ?></h2>
                <p class="drl-section-sub"><?= e(__('home_featured_creations_sub')) ?></p>
            </div>
            <a href="<?= BASE_URL ?>/creations.php" class="drl-link-gold">
                <?= $lang === 'en' ? 'View all creations' : 'Voir tous les modèles' ?> <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        <div class="drl-cards-grid">
            <?php foreach ($featuredCreations as $creation):
                $cName = localized_field($creation, 'name');
                $cCat  = localized_field($creation, 'category_name');
                $cUrl  = BASE_URL . '/modele.php?id=' . (int)$creation['id'];
                $waLink = WhatsApp::generateItemLink($cName, (string)$creation['reference'], $cUrl, $lang, 'Demande de confection sur-mesure');
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
                    <h3 class="drl-card-title"><a href="<?= e($cUrl) ?>"><?= e($cName) ?></a></h3>
                    <p class="drl-card-excerpt"><?= e(mb_strimwidth(localized_field($creation, 'description'), 0, 120, '…')) ?></p>
                    <div class="drl-card-footer">
                        <a href="<?= e($cUrl) ?>" class="drl-btn drl-btn-sm drl-btn-primary">
                            <?= e(__('creations_request_btn')) ?>
                        </a>
                        <a href="<?= e($waLink) ?>" target="_blank" rel="noopener" class="drl-btn drl-btn-sm drl-btn-wa" title="WhatsApp">
                            <i class="fa-brands fa-whatsapp"></i>
                        </a>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- SECTION : L'ART DU SUR-MESURE DRL (TRIPTYQUE HAUTE COUTURE) -->
<section class="drl-section drl-savoir-faire">
    <div class="drl-container">
        <div class="drl-section-header drl-text-center reveal-up">
            <span class="drl-kicker">EXCELLENCE ARTISANALE</span>
            <h2 class="drl-section-title"><?= e(__('home_savoir_faire_title')) ?></h2>
        </div>
        <div class="drl-steps-grid">
            <div class="drl-step-card reveal-up">
                <div class="drl-step-icon"><i class="fa-solid fa-compass-drafting"></i></div>
                <h3><?= e(__('home_step_1_title')) ?></h3>
                <p><?= e(__('home_step_1_desc')) ?></p>
            </div>
            <div class="drl-step-card reveal-up">
                <div class="drl-step-icon"><i class="fa-solid fa-scissors"></i></div>
                <h3><?= e(__('home_step_2_title')) ?></h3>
                <p><?= e(__('home_step_2_desc')) ?></p>
            </div>
            <div class="drl-step-card reveal-up">
                <div class="drl-step-icon"><i class="fa-solid fa-gem"></i></div>
                <h3><?= e(__('home_step_3_title')) ?></h3>
                <p><?= e(__('home_step_3_desc')) ?></p>
            </div>
        </div>
    </div>
</section>

<!-- SECTION : PIÈCES PRÊTES À PORTER (ESPACE BOUTIQUE) -->
<section class="drl-section">
    <div class="drl-container">
        <div class="drl-section-header reveal-up">
            <div>
                <span class="drl-kicker"><?= $lang === 'en' ? 'EXCLUSIVE BOUTIQUE' : 'ÉDITIONS LIMITÉES' ?></span>
                <h2 class="drl-section-title"><?= e(__('home_featured_boutique_title')) ?></h2>
                <p class="drl-section-sub"><?= e(__('home_featured_boutique_sub')) ?></p>
            </div>
            <a href="<?= BASE_URL ?>/boutique.php" class="drl-link-gold">
                <?= $lang === 'en' ? 'Explore all items' : 'Explorer toute la boutique' ?> <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        <div class="drl-cards-grid">
            <?php foreach ($featuredProducts as $product):
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
                    <h3 class="drl-card-title"><a href="<?= e($pUrl) ?>"><?= e($pName) ?></a></h3>
                    <div class="drl-card-price"><?= e($priceLabel) ?></div>
                    <div class="drl-card-footer">
                        <a href="<?= e($pUrl) ?>" class="drl-btn drl-btn-sm drl-btn-outline">
                            <?= $lang === 'en' ? 'Details' : "Voir l'article" ?>
                        </a>
                        <a href="<?= e($waLink) ?>" target="_blank" rel="noopener" class="drl-btn drl-btn-sm drl-btn-wa">
                            <i class="fa-brands fa-whatsapp"></i> WhatsApp
                        </a>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- SECTION FAQ & CONSEIL HAUTE COUTURE -->
<?php if (!empty($faqs)): ?>
<section class="drl-section drl-faq-section">
    <div class="drl-container">
        <div class="drl-section-header drl-text-center reveal-up">
            <span class="drl-kicker">QUESTIONS FRÉQUENTES</span>
            <h2 class="drl-section-title"><?= $lang === 'en' ? 'Everything About Your Bespoke Experience' : 'Tout savoir sur votre expérience DRL CRÉATIONS' ?></h2>
        </div>
        <div class="drl-faq-list">
            <?php foreach ($faqs as $faqItem): ?>
                <details class="drl-faq-item reveal-up">
                    <summary>
                        <span><?= e(localized_field($faqItem, 'question')) ?></span>
                        <i class="fa-solid fa-plus"></i>
                    </summary>
                    <div class="drl-faq-body">
                        <p><?= nl2br(e(localized_field($faqItem, 'answer'))) ?></p>
                    </div>
                </details>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
