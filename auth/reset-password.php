<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/classes/Auth.php';

$email = (string)($_GET['email'] ?? ($_POST['email'] ?? ''));
$token = (string)($_GET['token'] ?? ($_POST['token'] ?? ''));
$done = false;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Jeton CSRF invalide.';
    } else {
        $pass    = (string)($_POST['password'] ?? '');
        $confirm = (string)($_POST['password_confirm'] ?? '');
        $res     = Auth::resetPasswordWithToken($email, $token, $pass, $confirm);
        if ($res['success']) {
            $done = true;
        } else {
            $error = $res['error'] ?? 'Erreur de réinitialisation.';
        }
    }
}

$pageTitle = 'Nouveau mot de passe';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<section class="drl-auth-section">
    <div class="drl-container">
        <div class="drl-auth-card">
            <div class="drl-auth-header">
                <span class="drl-kicker">SÉCURITÉ</span>
                <h1>Choisir un nouveau mot de passe</h1>
            </div>

            <?php if ($done): ?>
                <div class="drl-alert drl-alert-success">Votre mot de passe a été mis à jour avec succès.</div>
                <a href="<?= BASE_URL ?>/auth/login.php" class="drl-btn drl-btn-primary drl-full-width">Se connecter</a>
            <?php else: ?>
                <?php if ($error): ?>
                    <div class="drl-alert drl-alert-danger"><?= e($error) ?></div>
                <?php endif; ?>
                <form method="post" class="drl-auth-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="email" value="<?= e($email) ?>">
                    <input type="hidden" name="token" value="<?= e($token) ?>">
                    <div class="drl-form-group">
                        <label for="password">Nouveau mot de passe (min. 8 caractères)</label>
                        <input type="password" id="password" name="password" minlength="8" required>
                    </div>
                    <div class="drl-form-group">
                        <label for="password_confirm">Confirmer le nouveau mot de passe</label>
                        <input type="password" id="password_confirm" name="password_confirm" minlength="8" required>
                    </div>
                    <button type="submit" class="drl-btn drl-btn-primary drl-full-width">Enregistrer le mot de passe</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
