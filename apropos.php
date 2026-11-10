<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

$pageTitle = __('nav_about');
$lang = current_lang();
$story = $lang === 'en' ? site_setting('about_story_en') : site_setting('about_story_fr');

require_once __DIR__ . '/includes/header.php';
?>

<section class="drl-page-hero">
    <div class="drl-container">
        <span class="drl-kicker">HISTOIRE &amp; PHILOSOPHIE</span>
        <h1 class="drl-page-title">Maison DRL CRÉATIONS</h1>
        <p class="drl-page-sub">« <?= e($lang === 'en' ? site_setting('site_slogan_en') : site_setting('site_slogan_fr')) ?> »</p>
    </div>
</section>

<section class="drl-section">
    <div class="drl-container">
        <div class="drl-about-grid">
            <div class="drl-about-text reveal-up">
                <span class="drl-kicker">L'ESPRIT DE L'ATELIER</span>
                <h2><?= $lang === 'en' ? 'Where Heritage Meets Haute Couture' : "Quand l'héritage textile rencontre la Haute Couture" ?></h2>
                <p><?= nl2br(e($story)) ?></p>
                <div class="drl-about-values">
                    <div class="drl-value-box">
                        <h4><i class="fa-solid fa-gem drl-gold-icon"></i> <?= $lang === 'en' ? 'Noble Materials' : 'Matières Nobles' ?></h4>
                        <p><?= $lang === 'en' ? 'Silk duchesse, organza, handwoven Kanvo, rich Bazin and fine guipure lace.' : "Satin duchesse, organza de soie, Kanvo tissé main, Bazin riche et guipure d'exception." ?></p>
                    </div>
                    <div class="drl-value-box">
                        <h4><i class="fa-solid fa-ruler-combined drl-gold-icon"></i> <?= $lang === 'en' ? 'Architectural Fit' : 'Tombé Architectural' ?></h4>
                        <p><?= $lang === 'en' ? 'Every garment is sculpted to embrace and elevate your natural posture.' : 'Chaque pièce est sculptée pour épouser et sublimer votre allure naturelle.' ?></p>
                    </div>
                </div>
            </div>
            <div class="drl-about-visual reveal-scale">
                <img src="<?= asset_url('assets/images/hero-couture.svg') ?>" alt="Atelier DRL CRÉATIONS">
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
