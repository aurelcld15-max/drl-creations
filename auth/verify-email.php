<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/classes/Auth.php';

$email = (string)($_GET['email'] ?? '');
$token = (string)($_GET['token'] ?? '');
$result = Auth::verifyEmailToken($email, $token);

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
