<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/classes/Auth.php';

if (is_logged_in()) {
    header('Location: ' . BASE_URL . '/client/profil.php');
    exit;
}

$error = null;
$verifyHintUrl = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Jeton de sécurité invalide. Veuillez rafraîchir la page.';
    } else {
        $email = trim((string)($_POST['email'] ?? ''));
        $pass  = (string)($_POST['password'] ?? '');
        $res   = Auth::login($email, $pass);

        if ($res['success']) {
            set_flash('success', 'Heureux de vous revoir, ' . $_SESSION['user']['first_name'] . ' !');
            if (is_admin()) {
                header('Location: ' . BASE_URL . '/admin/dashboard.php');
            } else {
                header('Location: ' . BASE_URL . '/client/profil.php');
            }
            exit;
        }
        $error = $res['error'] ?? 'Erreur de connexion.';
        $verifyHintUrl = $res['verify_url'] ?? null;
    }
}

$pageTitle = __('auth_login_title');
$isAuthPage = true;
$authImages = [
    'assets/images/pg1.jpg',
    'assets/images/pg2.jpg',
    'assets/images/pg3.jpg',
    'assets/images/pg4.jpg',
];
require_once dirname(__DIR__) . '/includes/header.php';
?>

<section class="drl-auth-scene drl-auth-scene-login">
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
            <h2>Une allure qui<br><em>vous ressemble.</em></h2>
            <p>Des créations pensées pour vous, façonnées avec soin dans notre atelier.</p>
        </div>
        <span class="drl-auth-editorial-foot">LE SAVOIR-FAIRE · LA SIGNATURE · VOUS</span>
    </div>

    <div class="drl-auth-panel">
        <a href="<?= BASE_URL ?>/index.php" class="drl-auth-close" aria-label="Retour à l'accueil"><i class="fa-solid fa-xmark"></i></a>
        <div class="drl-auth-panel-inner">
            <div class="drl-auth-header">
                <span class="drl-auth-eyebrow">ESPACE PRIVILÈGE</span>
                <h1><?= e(__('auth_login_title')) ?></h1>
                <p>Retrouvez votre espace et vos créations favorites.</p>
            </div>

            <?php if ($error): ?>
                <div class="drl-alert drl-alert-danger" role="alert">
                    <div>
                        <?= e($error) ?>
                        <?php if ($verifyHintUrl): ?>
                            <div style="margin-top:8px;">
                                <a href="<?= e($verifyHintUrl) ?>" class="drl-link-gold">Confirmer mon adresse e-mail maintenant</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= BASE_URL ?>/auth/login.php" class="drl-auth-form drl-auth-form-modern">
                <?= csrf_field() ?>
                <div class="drl-form-group">
                    <label for="email"><?= e(__('auth_email')) ?></label>
                    <input type="email" id="email" name="email" required autocomplete="email" value="<?= e($_POST['email'] ?? '') ?>">
                </div>

                <div class="drl-form-group">
                    <div class="drl-label-row">
                        <label for="password"><?= e(__('auth_password')) ?></label>
                        <a href="<?= BASE_URL ?>/auth/forgot-password.php" class="drl-link-muted"><?= e(__('auth_forgot_password')) ?></a>
                    </div>
                    <input type="password" id="password" name="password" required autocomplete="current-password">
                </div>

                <button type="submit" class="drl-btn drl-btn-primary drl-full-width drl-auth-submit">
                    <?= e(__('auth_login_btn')) ?>
                    <i class="fa-solid fa-arrow-right"></i>
                </button>
            </form>

            <div class="drl-auth-divider drl-auth-divider-modern"><span><?= e(__('auth_or_continue')) ?></span></div>

            <div class="drl-oauth-buttons drl-oauth-buttons-modern">
                <a href="<?= BASE_URL ?>/auth/oauth/google.php" class="drl-btn drl-btn-oauth drl-auth-social">
                    <i class="fa-brands fa-google"></i>
                    <span><?= e(__('auth_google_btn')) ?></span>
                </a>
                <a href="<?= BASE_URL ?>/auth/oauth/apple.php" class="drl-btn drl-btn-oauth drl-auth-social">
                    <i class="fa-brands fa-apple"></i>
                    <span><?= e(__('auth_apple_btn')) ?></span>
                </a>
            </div>

            <p class="drl-auth-switch drl-auth-switch-modern">
                Pas encore de compte ? <a href="<?= BASE_URL ?>/auth/register.php"><?= e(__('nav_register')) ?></a>
            </p>
            <p class="drl-auth-legal">En continuant, vous accédez à l'univers privé DRL CRÉATIONS.</p>
        </div>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
