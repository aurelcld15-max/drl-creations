<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/classes/SupabaseAuth.php';

$done = false;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Jeton CSRF invalide.';
    } else {
        try {
            $pass = (string)($_POST['password'] ?? '');
            $confirm = (string)($_POST['password_confirm'] ?? '');
            $res = SupabaseAuth::resetPassword($pass, $confirm);
            if ($res['success']) {
                SupabaseAuth::signOut();
                $done = true;
            } else {
                $error = $res['error'] ?? 'Erreur de réinitialisation.';
            }
        } catch (Throwable $e) {
            error_log('[DRL Supabase password update] ' . $e->getMessage());
            $error = $e->getMessage();
        }
    }
} elseif (empty($_SESSION['supabase_access_token'])) {
    $error = 'Ouvrez le lien de récupération reçu par e-mail pour choisir un nouveau mot de passe.';
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
                    <div class="drl-alert drl-alert-danger" role="alert"><?= e($error) ?></div>
                <?php endif; ?>
                <?php if (!empty($_SESSION['supabase_access_token'])): ?>
                <form method="post" class="drl-auth-form">
                    <?= csrf_field() ?>
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
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
