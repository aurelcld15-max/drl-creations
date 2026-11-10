<?php
declare(strict_types=1);

$currentScript = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
$user = current_user();
$favCount = $user ? Favorite::countByUser((int)$user['id']) : 0;
$lang = current_lang();
?>
<!-- HEADER PRINCIPAL HAUTE COUTURE -->
<header class="drl-header" id="drlHeader">
    <div class="drl-container drl-header-inner">
        <!-- Logo DRL CRÉATIONS -->
        <a href="<?= BASE_URL ?>/index.php" class="drl-brand" aria-label="DRL CRÉATIONS">
            <img src="<?= asset_url(site_setting('site_logo', 'assets/images/logo.svg')) ?>" alt="Logo DRL CRÉATIONS" class="drl-logo-img">
            <div class="drl-brand-text">
                <span class="drl-brand-name">DRL CRÉATIONS</span>
                <span class="drl-brand-sub">HAUTE COUTURE &amp; SUR-MESURE</span>
            </div>
        </a>

        <!-- Navigation Desktop Élégante -->
        <nav class="drl-desktop-nav" aria-label="Navigation principale">
            <a href="<?= BASE_URL ?>/index.php" class="drl-nav-link <?= $currentScript === 'index.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-house-chimney"></i>
                <span><?= e(__('nav_home')) ?></span>
            </a>
            <a href="<?= BASE_URL ?>/creations.php" class="drl-nav-link <?= in_array($currentScript, ['creations.php', 'modele.php'], true) ? 'active' : '' ?>">
                <i class="fa-solid fa-wand-magic-sparkles"></i>
                <span><?= e(__('nav_creations')) ?></span>
            </a>
            <a href="<?= BASE_URL ?>/boutique.php" class="drl-nav-link <?= in_array($currentScript, ['boutique.php', 'article.php'], true) ? 'active' : '' ?>">
                <i class="fa-solid fa-bag-shopping"></i>
                <span><?= e(__('nav_boutique')) ?></span>
            </a>
            <a href="<?= BASE_URL ?>/apropos.php" class="drl-nav-link <?= $currentScript === 'apropos.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-feather-pointed"></i>
                <span><?= e(__('nav_about')) ?></span>
            </a>
            <a href="<?= BASE_URL ?>/contact.php" class="drl-nav-link <?= $currentScript === 'contact.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-envelope-open-text"></i>
                <span><?= e(__('nav_contact')) ?></span>
            </a>
        </nav>

        <!-- Actions : Recherche, Thème Soleil/Lune, Favoris, Profil Circulaire -->
        <div class="drl-header-actions">
            <!-- Bouton Recherche Dynamique -->
            <button type="button" class="drl-icon-btn" id="openSearchModalBtn" title="<?= e(__('nav_search')) ?>" aria-label="<?= e(__('nav_search')) ?>">
                <i class="fa-solid fa-magnifying-glass"></i>
            </button>

            <!-- Bascule Mode Clair / Mode Sombre (Soleil / Lune) -->
            <button type="button" class="drl-icon-btn" id="themeToggleBtn" title="Mode Clair / Sombre" aria-label="Basculer le thème">
                <i class="fa-solid fa-moon" id="themeIcon"></i>
            </button>

            <!-- Favoris -->
            <a href="<?= $user ? (BASE_URL . '/client/favoris.php') : (BASE_URL . '/auth/login.php') ?>" class="drl-icon-btn drl-fav-btn" title="<?= e(__('nav_favorites')) ?>">
                <i class="fa-regular fa-heart"></i>
                <span class="drl-badge-count" id="headerFavCount"><?= (int)$favCount ?></span>
            </a>

            <!-- Accès au Profil depuis une icône circulaire (Section 9) -->
            <div class="drl-profile-dropdown-wrap">
                <button type="button" class="drl-profile-circle" id="profileDropdownBtn" aria-expanded="false" aria-label="Menu profil">
                    <?php if ($user && !empty($user['avatar'])): ?>
                        <img src="<?= asset_url((string)$user['avatar']) ?>" alt="<?= e($user['first_name']) ?>">
                    <?php elseif ($user): ?>
                        <span class="drl-avatar-initials"><?= e(mb_strtoupper(mb_substr((string)$user['first_name'], 0, 1) . mb_substr((string)$user['last_name'], 0, 1))) ?></span>
                    <?php else: ?>
                        <i class="fa-regular fa-user"></i>
                    <?php endif; ?>
                </button>

                <div class="drl-profile-dropdown" id="profileDropdownMenu">
                    <?php if ($user): ?>
                        <div class="drl-profile-dropdown-header">
                            <strong><?= e($user['first_name'] . ' ' . $user['last_name']) ?></strong>
                            <small><?= e($user['email']) ?></small>
                        </div>
                        <?php if (is_admin()): ?>
                            <a href="<?= BASE_URL ?>/admin/dashboard.php" class="drl-dropdown-item drl-dropdown-gold">
                                <i class="fa-solid fa-crown"></i> <?= e(__('nav_admin')) ?>
                            </a>
                        <?php endif; ?>
                        <a href="<?= BASE_URL ?>/client/profil.php" class="drl-dropdown-item">
                            <i class="fa-regular fa-id-badge"></i> <?= e(__('client_menu_profile')) ?>
                        </a>
                        <a href="<?= BASE_URL ?>/client/profil.php#edit-info" class="drl-dropdown-item">
                            <i class="fa-solid fa-user-pen"></i> <?= e(__('client_menu_edit')) ?>
                        </a>
                        <a href="<?= BASE_URL ?>/client/profil.php#avatar-upload" class="drl-dropdown-item">
                            <i class="fa-solid fa-camera-retro"></i> <?= e(__('client_menu_avatar')) ?>
                        </a>
                        <a href="<?= BASE_URL ?>/client/favoris.php" class="drl-dropdown-item">
                            <i class="fa-regular fa-heart"></i> <?= e(__('client_menu_favorites')) ?>
                        </a>
                        <a href="<?= BASE_URL ?>/client/demandes.php" class="drl-dropdown-item">
                            <i class="fa-solid fa-ruler-combined"></i> <?= e(__('client_menu_requests')) ?>
                        </a>
                        <a href="<?= BASE_URL ?>/client/parametres.php" class="drl-dropdown-item">
                            <i class="fa-solid fa-sliders"></i> <?= e(__('client_menu_settings')) ?>
                        </a>
                        <div class="drl-dropdown-divider"></div>
                        <a href="<?= BASE_URL ?>/auth/logout.php" class="drl-dropdown-item drl-dropdown-danger">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i> <?= e(__('client_menu_logout')) ?>
                        </a>
                    <?php else: ?>
                        <div class="drl-profile-dropdown-header">
                            <strong>Maison DRL CRÉATIONS</strong>
                            <small><?= $lang === 'en' ? 'Private Client Access' : 'Accès Client Privilège' ?></small>
                        </div>
                        <a href="<?= BASE_URL ?>/auth/login.php" class="drl-dropdown-item">
                            <i class="fa-solid fa-right-to-bracket"></i> <?= e(__('nav_login')) ?>
                        </a>
                        <a href="<?= BASE_URL ?>/auth/register.php" class="drl-dropdown-item">
                            <i class="fa-solid fa-user-plus"></i> <?= e(__('nav_register')) ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- BARRE DE NAVIGATION FLOTTANTE COMPOSÉE D'ICÔNES MODERNES (Sans menu hamburger classique - Section 5) -->
<nav class="drl-floating-dock" aria-label="Navigation flottante rapide">
    <a href="<?= BASE_URL ?>/index.php" class="drl-dock-item <?= $currentScript === 'index.php' ? 'active' : '' ?>" title="<?= e(__('nav_home')) ?>">
        <i class="fa-solid fa-house-chimney"></i>
        <span><?= e(__('nav_home')) ?></span>
    </a>
    <a href="<?= BASE_URL ?>/creations.php" class="drl-dock-item <?= in_array($currentScript, ['creations.php', 'modele.php'], true) ? 'active' : '' ?>" title="<?= e(__('nav_creations')) ?>">
        <i class="fa-solid fa-wand-magic-sparkles"></i>
        <span><?= e(__('nav_creations')) ?></span>
    </a>
    <a href="<?= BASE_URL ?>/boutique.php" class="drl-dock-item <?= in_array($currentScript, ['boutique.php', 'article.php'], true) ? 'active' : '' ?>" title="<?= e(__('nav_boutique')) ?>">
        <i class="fa-solid fa-bag-shopping"></i>
        <span><?= e(__('nav_boutique')) ?></span>
    </a>
    <a href="<?= BASE_URL ?>/apropos.php" class="drl-dock-item <?= $currentScript === 'apropos.php' ? 'active' : '' ?>" title="<?= e(__('nav_about')) ?>">
        <i class="fa-solid fa-feather-pointed"></i>
        <span><?= e(__('nav_about')) ?></span>
    </a>
    <a href="<?= BASE_URL ?>/contact.php" class="drl-dock-item <?= $currentScript === 'contact.php' ? 'active' : '' ?>" title="<?= e(__('nav_contact')) ?>">
        <i class="fa-solid fa-envelope-open-text"></i>
        <span><?= e(__('nav_contact')) ?></span>
    </a>
</nav>

<!-- MODALE DE RECHERCHE INSTANTANÉE AJAX -->
<div class="drl-search-overlay" id="searchModalOverlay" aria-hidden="true">
    <div class="drl-search-modal">
        <div class="drl-search-header">
            <i class="fa-solid fa-magnifying-glass drl-gold-icon"></i>
            <input type="search" id="liveSearchInput" placeholder="<?= e(__('nav_search')) ?>" autocomplete="off">
            <button type="button" class="drl-close-search" id="closeSearchModalBtn" aria-label="Fermer">&times;</button>
        </div>
        <div class="drl-search-results" id="liveSearchResults">
            <p class="drl-search-hint">
                <?= $lang === 'en'
                    ? 'Type a model name, reference (e.g. DRL-MOD-001) or category...'
                    : "Saisissez le nom d'un modèle, une référence (ex: DRL-MOD-001) ou une catégorie..." ?>
            </p>
        </div>
    </div>
</div>
