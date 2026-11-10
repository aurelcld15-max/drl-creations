<?php
declare(strict_types=1);

$lang = current_lang();
$aiEnabled = site_setting('ai_enabled', '1') === '1';
$aiWelcome = $lang === 'en'
    ? site_setting('ai_welcome_en', 'Hello and welcome to DRL CRÉATIONS ✨ I am DRL Assistant, your personal haute couture advisor.')
    : site_setting('ai_welcome_fr', 'Bonjour et bienvenue chez DRL CRÉATIONS ✨ Je suis DRL Assistant, votre conseiller haute couture personnel.');
$whatsappUrl = WhatsApp::generateGeneralLink(null, $lang);
?>
    </main>

    <?php if (empty($isAuthPage)): ?>
    <!-- SÉLECTEUR DE LANGUE FLOTTANT (Section 14) -->
    <div class="drl-lang-switcher" aria-label="Sélecteur de langue">
        <a href="?lang=fr" class="drl-lang-pill <?= $lang === 'fr' ? 'active' : '' ?>" title="Français">FR</a>
        <a href="?lang=en" class="drl-lang-pill <?= $lang === 'en' ? 'active' : '' ?>" title="English">EN</a>
    </div>

    <?php if ($aiEnabled): ?>
    <!-- ASSISTANT IA FLOTTANT : DRL ASSISTANT (Section 10) -->
    <div class="drl-ai-widget" id="drlAiWidget">
        <button type="button" class="drl-ai-Launcher" id="aiToggleBtn" aria-label="<?= e(__('ai_title')) ?>">
            <span class="drl-ai-pulse"></span>
            <i class="fa-solid fa-sparkles"></i>
            <span class="drl-ai-launcher-label"><?= e(__('ai_title')) ?></span>
        </button>

        <div class="drl-ai-window" id="aiChatWindow" aria-hidden="true">
            <div class="drl-ai-header">
                <div class="drl-ai-header-info">
                    <div class="drl-ai-avatar">
                        <i class="fa-solid fa-wand-magic-sparkles"></i>
                    </div>
                    <div>
                        <h4><?= e(__('ai_title')) ?></h4>
                        <small><?= e(__('ai_subtitle')) ?></small>
                    </div>
                </div>
                <button type="button" class="drl-ai-close" id="aiCloseBtn" aria-label="Fermer">&times;</button>
            </div>

            <div class="drl-ai-messages" id="aiMessagesContainer">
                <div class="drl-ai-msg drl-ai-msg-bot">
                    <div class="drl-ai-bubble"><?= nl2br(e($aiWelcome)) ?></div>
                </div>
            </div>

            <div class="drl-ai-quick-actions">
                <button type="button" class="drl-ai-chip" data-prompt="<?= e(__('ai_quick_creations')) ?>"><?= e(__('ai_quick_creations')) ?></button>
                <button type="button" class="drl-ai-chip" data-prompt="<?= e(__('ai_quick_prices')) ?>"><?= e(__('ai_quick_prices')) ?></button>
                <button type="button" class="drl-ai-chip" data-prompt="<?= e(__('ai_quick_custom')) ?>"><?= e(__('ai_quick_custom')) ?></button>
                <a href="<?= e($whatsappUrl) ?>" target="_blank" rel="noopener" class="drl-ai-chip drl-ai-chip-wa">
                    <i class="fa-brands fa-whatsapp"></i> WhatsApp
                </a>
            </div>

            <form class="drl-ai-input-area" id="aiChatForm">
                <input type="text" id="aiUserInput" placeholder="<?= e(__('ai_placeholder')) ?>" autocomplete="off" required>
                <button type="submit" id="aiSendBtn" aria-label="<?= e(__('ai_send')) ?>">
                    <i class="fa-solid fa-paper-plane"></i>
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- FOOTER HAUTE COUTURE -->
    <footer class="drl-footer">
        <div class="drl-container">
            <div class="drl-footer-grid">
                <div class="drl-footer-col">
                    <div class="drl-footer-brand">
                        <img src="<?= asset_url('assets/images/logo-gold.svg') ?>" alt="DRL CRÉATIONS" class="drl-footer-logo">
                        <h3>DRL CRÉATIONS</h3>
                    </div>
                    <p class="drl-footer-slogan">« <?= e($lang === 'en' ? site_setting('site_slogan_en') : site_setting('site_slogan_fr')) ?> »</p>
                    <div class="drl-social-links">
                        <a href="<?= e(site_setting('instagram_url', '#')) ?>" target="_blank" rel="noopener" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                        <a href="<?= e(site_setting('facebook_url', '#')) ?>" target="_blank" rel="noopener" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                        <a href="<?= e(site_setting('tiktok_url', '#')) ?>" target="_blank" rel="noopener" aria-label="TikTok"><i class="fa-brands fa-tiktok"></i></a>
                        <a href="<?= e($whatsappUrl) ?>" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>
                    </div>
                </div>

                <div class="drl-footer-col">
                    <h4><?= $lang === 'en' ? 'Maison & Collections' : 'Maison & Collections' ?></h4>
                    <ul class="drl-footer-links">
                        <li><a href="<?= BASE_URL ?>/creations.php"><?= e(__('nav_creations')) ?></a></li>
                        <li><a href="<?= BASE_URL ?>/boutique.php"><?= e(__('nav_boutique')) ?></a></li>
                        <li><a href="<?= BASE_URL ?>/apropos.php"><?= e(__('nav_about')) ?></a></li>
                        <li><a href="<?= BASE_URL ?>/contact.php"><?= e(__('nav_contact')) ?></a></li>
                    </ul>
                </div>

                <div class="drl-footer-col">
                    <h4><?= $lang === 'en' ? 'Bespoke Specialties' : "Spécialités de l'Atelier" ?></h4>
                    <ul class="drl-footer-links">
                        <li><a href="<?= BASE_URL ?>/creations.php?category=1"><?= $lang === 'en' ? 'Evening Gowns' : 'Robes de soirée' ?></a></li>
                        <li><a href="<?= BASE_URL ?>/creations.php?category=2"><?= $lang === 'en' ? 'Ceremonial Dresses' : 'Robes de cérémonie' ?></a></li>
                        <li><a href="<?= BASE_URL ?>/creations.php?category=4"><?= $lang === 'en' ? 'African Couture' : 'Tenues africaines' ?></a></li>
                        <li><a href="<?= BASE_URL ?>/creations.php?category=6"><?= $lang === 'en' ? 'Bespoke Suits' : 'Costumes sur-mesure' ?></a></li>
                    </ul>
                </div>

                <div class="drl-footer-col">
                    <h4><?= $lang === 'en' ? 'Private Atelier' : "Coordonnées de l'Atelier" ?></h4>
                    <p><i class="fa-solid fa-location-dot drl-gold-icon"></i> <?= e(site_setting('atelier_address', 'Quartier Haie Vive, Cotonou')) ?></p>
                    <p><i class="fa-solid fa-phone drl-gold-icon"></i> <?= e(site_setting('contact_phone', '+229 01 97 00 00 00')) ?></p>
                    <p><i class="fa-solid fa-envelope drl-gold-icon"></i> <?= e(site_setting('contact_email', 'contact@drl-creations.com')) ?></p>
                    <p class="drl-footer-hours"><i class="fa-regular fa-clock drl-gold-icon"></i> <?= e($lang === 'en' ? site_setting('atelier_hours_en') : site_setting('atelier_hours_fr')) ?></p>
                </div>
            </div>

            <div class="drl-footer-bottom">
                <p>&copy; <?= date('Y') ?> <strong>DRL CRÉATIONS</strong>. <?= e(__('footer_rights')) ?></p>
            </div>
        </div>
    </footer>
    <?php endif; ?>

    <!-- GSAP 3 Core pour les animations fluides Haute Couture (Section 2 & 15) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="<?= asset_url('assets/js/app.js') ?>?v=<?= (int)filemtime(DRL_ROOT . '/assets/js/app.js') ?>"></script>
    <script src="<?= asset_url('assets/js/assistant.js') ?>"></script>
</body>
</html>
