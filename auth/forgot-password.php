<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/classes/Auth.php';

$sent = false;
$resetUrlPreview = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Jeton CSRF invalide.';
    } else {
        $email = trim((string)($_POST['email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Veuillez saisir une adresse e-mail valide.';
        } else {
            $res = Auth::createPasswordReset($email);
            $sent = true;
            $resetUrlPreview = $res['reset_url'] ?? null;
        }
    }
}

$pageTitle = 'Mot de passe oublié';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<section class="drl-auth-section">
    <div class="drl-container">
        <div class="drl-auth-card">
            <div class="drl-auth-header">
                <span class="drl-kicker">SÉCURITÉ DU COMPTE</span>
                <h1>Réinitialiser mon mot de passe</h1>
            </div>

            <?php if ($sent): ?>
                <div class="drl-alert drl-alert-success">
                    <div>
                        Si un compte correspond à cette adresse e-mail, un lien de réinitialisation vous a été envoyé.
                        <?php if ($resetUrlPreview): ?>
                            <div style="margin-top:10px;">
                                <a href="<?= e($resetUrlPreview) ?>" class="drl-btn drl-btn-sm drl-btn-primary">Réinitialiser maintenant (Mode local)</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <?php if ($error): ?>
                    <div class="drl-alert drl-alert-danger"><?= e($error) ?></div>
                <?php endif; ?>
                <form method="post" class="drl-auth-form">
                    <?= csrf_field() ?>
                    <div class="drl-form-group">
                        <label for="email"><?= e(__('auth_email')) ?></label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <button type="submit" class="drl-btn drl-btn-primary drl-full-width">Envoyer le lien de récupération</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
