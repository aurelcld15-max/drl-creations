<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/classes/SupabaseAuth.php';

if (is_logged_in()) {
    header('Location: ' . BASE_URL . '/client/profil.php');
    exit;
}

$error = null;
$successData = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Jeton de sécurité invalide. Veuillez réessayer.';
    } else {
        $firstName = trim((string)($_POST['first_name'] ?? ''));
        $lastName  = trim((string)($_POST['last_name'] ?? ''));
        $email     = trim((string)($_POST['email'] ?? ''));
        $phone     = trim((string)($_POST['phone'] ?? ''));
        $pass      = (string)($_POST['password'] ?? '');
        $confirm   = (string)($_POST['password_confirm'] ?? '');

        try {
            $res = SupabaseAuth::register($firstName, $lastName, $email, $pass, $confirm, $phone);
        } catch (Throwable $e) {
            error_log('[DRL Supabase signup] ' . $e->getMessage());
            $res = ['success' => false, 'error' => $e->getMessage()];
        }
        if ($res['success']) {
            $successData = $res;
        } else {
            $error = $res['error'] ?? "Erreur lors de l'inscription.";
        }
    }
}

$pageTitle = __('auth_register_title');
$isAuthPage = true;
$authImages = [
    'assets/images/pg1.jpg',
    'assets/images/pg2.jpg',
    'assets/images/pg3.jpg',
    'assets/images/pg4.jpg',
];
require_once dirname(__DIR__) . '/includes/header.php';
?>

<section class="drl-auth-scene drl-auth-scene-register">
    <div class="drl-auth-editorial">
        <div class="drl-auth-slides" data-auth-slideshow aria-hidden="true">
            <?php foreach ($authImages as $index => $image): ?>
                <img src="<?= asset_url($image) ?>" alt="" class="<?= $index === 0 ? 'is-active' : '' ?>"<?= $index > 0 ? ' loading="lazy"' : '' ?>>
            <?php endforeach; ?>
        </div>
        <a href="<?= BASE_URL ?>/index.php" class="drl-auth-brand">
            <img src="<?= asset_url('logo.jpeg') ?>" alt="Logo DRL CRÉATIONS">
            <span>DRL <strong>CRÉATIONS</strong><small>ATELIER DE COUTURE</small></span>
        </a>
        <div class="drl-auth-editorial-copy">
            <span class="drl-auth-eyebrow">MAISON DRL · COUTURE D'EXCEPTION</span>
            <h2>Votre histoire,<br><em>votre silhouette.</em></h2>
            <p>Entrez dans un univers où chaque détail est imaginé pour vous.</p>
        </div>
        <span class="drl-auth-editorial-foot">LE SAVOIR-FAIRE · LA SIGNATURE · VOUS</span>
    </div>

    <div class="drl-auth-panel">
        <a href="<?= BASE_URL ?>/index.php" class="drl-auth-close" aria-label="Retour à l'accueil"><i class="fa-solid fa-xmark"></i></a>
        <div class="drl-auth-panel-inner">
            <div class="drl-auth-header">
                <span class="drl-auth-eyebrow">BIENVENUE CHEZ DRL</span>
                <h1><?= e(__('auth_register_title')) ?></h1>
                <p>Créez votre compte pour profiter de votre espace privilégié.</p>
            </div>

            <?php if ($successData): ?>
                <div class="drl-alert drl-alert-success" role="status">
                    <div>
                        <strong><?= e($successData['message']) ?></strong>
                        <p style="margin-top:10px;font-size:0.9rem;">
                            Consultez votre boîte e-mail et suivez le lien Supabase pour confirmer votre adresse.
                        </p>
                    </div>
                </div>
            <?php else: ?>
                <?php if ($error): ?>
                    <div class="drl-alert drl-alert-danger"><?= e($error) ?></div>
                <?php endif; ?>

                <form method="post" action="<?= BASE_URL ?>/auth/register.php" class="drl-auth-form drl-auth-form-modern">
                    <?= csrf_field() ?>
                    <div class="drl-form-grid-2">
                        <div class="drl-form-group">
                            <label for="first_name"><?= e(__('auth_first_name')) ?> *</label>
                            <input type="text" id="first_name" name="first_name" required value="<?= e($_POST['first_name'] ?? '') ?>">
                        </div>
                        <div class="drl-form-group">
                            <label for="last_name"><?= e(__('auth_last_name')) ?> *</label>
                            <input type="text" id="last_name" name="last_name" required value="<?= e($_POST['last_name'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="drl-form-group">
                        <label for="email"><?= e(__('auth_email')) ?> *</label>
                        <input type="email" id="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>">
                    </div>

                    <div class="drl-form-group">
                        <label for="phone"><?= e(__('auth_phone')) ?></label>
                        <input type="text" id="phone" name="phone" value="<?= e($_POST['phone'] ?? '') ?>" placeholder="+229 ...">
                    </div>

                    <div class="drl-form-grid-2">
                        <div class="drl-form-group">
                            <label for="password"><?= e(__('auth_password')) ?> * (min. 8 car.)</label>
                            <input type="password" id="password" name="password" minlength="8" required>
                        </div>
                        <div class="drl-form-group">
                            <label for="password_confirm"><?= e(__('auth_password_confirm')) ?> *</label>
                            <input type="password" id="password_confirm" name="password_confirm" minlength="8" required>
                        </div>
                    </div>

                    <button type="submit" class="drl-btn drl-btn-primary drl-full-width drl-auth-submit">
                        <?= e(__('auth_register_btn')) ?>
                        <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </form>

                <div class="drl-auth-divider drl-auth-divider-modern"><span><?= e(__('auth_or_continue')) ?></span></div>

                <div class="drl-oauth-buttons drl-oauth-buttons-modern">
                    <a href="<?= BASE_URL ?>/auth/oauth/supabase.php?provider=google" class="drl-btn drl-btn-oauth drl-auth-social">
                        <i class="fa-brands fa-google"></i>
                        <span><?= e(__('auth_google_btn')) ?></span>
                    </a>
                    <a href="<?= BASE_URL ?>/auth/oauth/supabase.php?provider=apple" class="drl-btn drl-btn-oauth drl-auth-social">
                        <i class="fa-brands fa-apple"></i>
                        <span><?= e(__('auth_apple_btn')) ?></span>
                    </a>
                </div>
            <?php endif; ?>

            <p class="drl-auth-switch drl-auth-switch-modern">
                Déjà membre ? <a href="<?= BASE_URL ?>/auth/login.php"><?= e(__('nav_login')) ?></a>
            </p>
            <p class="drl-auth-legal">En créant un compte, vous rejoignez l'univers privé DRL CRÉATIONS.</p>
        </div>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
