<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/classes/SupabaseAuth.php';

$tokenHash = (string)($_GET['token_hash'] ?? '');
$type = (string)($_GET['type'] ?? 'email');
$result = ['success' => false, 'error' => 'Le lien Supabase est incomplet ou invalide.'];
if ($tokenHash !== '') {
    try {
        $result = SupabaseAuth::verifyOtp($tokenHash, $type);
        if (!empty($result['success']) && !empty($result['is_recovery'])) {
            header('Location: ' . BASE_URL . '/auth/reset-password.php');
            exit;
        }
    } catch (Throwable $e) {
        error_log('[DRL Supabase email verification] ' . $e->getMessage());
        $result = ['success' => false, 'error' => $e->getMessage()];
    }
}

$pageTitle = 'Vérification de votre adresse e-mail';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<section class="drl-auth-section">
    <div class="drl-container">
        <div class="drl-auth-card drl-text-center">
            <span class="drl-kicker">CONFIRMATION DU COMPTE</span>
            <h1>Vérification de l'adresse e-mail</h1>
            <?php if ($result['success']): ?>
                <div class="drl-alert drl-alert-success" role="status"><?= e($result['message']) ?></div>
                <a href="<?= BASE_URL ?>/auth/login.php" class="drl-btn drl-btn-primary">Accéder à la connexion</a>
            <?php else: ?>
                <div class="drl-alert drl-alert-danger"><?= e($result['error']) ?></div>
                <a href="<?= BASE_URL ?>/auth/register.php" class="drl-btn drl-btn-outline">Retour à l'inscription</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
