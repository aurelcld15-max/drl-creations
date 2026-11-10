<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth-check.php';
require_once dirname(__DIR__) . '/classes/User.php';

$sessionUser = current_user();
$userId = (int)$sessionUser['id'];
$user = User::findById($userId);

// Mise à jour des informations personnelles ou de la photo de profil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        set_flash('danger', 'Jeton CSRF invalide.');
    } elseif (isset($_POST['update_profile'])) {
        $fn = trim((string)($_POST['first_name'] ?? ''));
        $ln = trim((string)($_POST['last_name'] ?? ''));
        $ph = trim((string)($_POST['phone'] ?? ''));
        if ($fn !== '' && $ln !== '') {
            User::updateProfile($userId, $fn, $ln, $ph);
            set_flash('success', 'Vos informations personnelles ont été mises à jour.');
            header('Location: ' . BASE_URL . '/client/profil.php');
            exit;
        }
    } elseif (isset($_POST['upload_avatar']) && isset($_FILES['avatar_file'])) {
        $upload = secure_image_upload($_FILES['avatar_file'], 'profiles');
        if ($upload['success']) {
            User::updateAvatar($userId, (string)$upload['path']);
            set_flash('success', 'Votre photo de profil a été enregistrée.');
            header('Location: ' . BASE_URL . '/client/profil.php');
            exit;
        } else {
            set_flash('danger', (string)$upload['error']);
        }
    }
    $user = User::findById($userId);
}

$pageTitle = __('client_menu_profile');
require_once dirname(__DIR__) . '/includes/header.php';
?>

<section class="drl-section">
    <div class="drl-container">
        <div class="drl-client-layout">
            <!-- Navigation latérale Espace Client -->
            <aside class="drl-client-sidebar">
                <div class="drl-client-user-box">
                    <div class="drl-client-avatar-large">
                        <?php if (!empty($user['avatar'])): ?>
                            <img src="<?= asset_url((string)$user['avatar']) ?>" alt="<?= e($user['first_name']) ?>">
                        <?php else: ?>
                            <span><?= e(mb_strtoupper(mb_substr((string)$user['first_name'], 0, 1) . mb_substr((string)$user['last_name'], 0, 1))) ?></span>
                        <?php endif; ?>
                    </div>
                    <h3><?= e($user['first_name'] . ' ' . $user['last_name']) ?></h3>
                    <small><?= e($user['email']) ?></small>
                </div>

                <nav class="drl-client-nav">
                    <a href="<?= BASE_URL ?>/client/profil.php" class="active"><i class="fa-regular fa-id-badge"></i> <?= e(__('client_menu_profile')) ?></a>
                    <a href="<?= BASE_URL ?>/client/favoris.php"><i class="fa-regular fa-heart"></i> <?= e(__('client_menu_favorites')) ?></a>
                    <a href="<?= BASE_URL ?>/client/demandes.php"><i class="fa-solid fa-ruler-combined"></i> <?= e(__('client_menu_requests')) ?></a>
                    <a href="<?= BASE_URL ?>/client/parametres.php"><i class="fa-solid fa-sliders"></i> <?= e(__('client_menu_settings')) ?></a>
                    <a href="<?= BASE_URL ?>/auth/logout.php" class="drl-text-danger"><i class="fa-solid fa-arrow-right-from-bracket"></i> <?= e(__('client_menu_logout')) ?></a>
                </nav>
            </aside>

            <!-- Contenu principal du profil -->
            <div class="drl-client-content">
                <div class="drl-panel" id="edit-info">
                    <h2><?= e(__('client_menu_edit')) ?></h2>
                    <form method="post" class="drl-form-grid">
                        <?= csrf_field() ?>
                        <div class="drl-form-group">
                            <label for="first_name"><?= e(__('auth_first_name')) ?></label>
                            <input type="text" id="first_name" name="first_name" required value="<?= e($user['first_name'] ?? '') ?>">
                        </div>
                        <div class="drl-form-group">
                            <label for="last_name"><?= e(__('auth_last_name')) ?></label>
                            <input type="text" id="last_name" name="last_name" required value="<?= e($user['last_name'] ?? '') ?>">
                        </div>
                        <div class="drl-form-group">
                            <label>Adresse e-mail (vérifiée)</label>
                            <input type="email" value="<?= e($user['email'] ?? '') ?>" disabled>
                        </div>
                        <div class="drl-form-group">
                            <label for="phone"><?= e(__('auth_phone')) ?></label>
                            <input type="text" id="phone" name="phone" value="<?= e($user['phone'] ?? '') ?>">
                        </div>
                        <div class="drl-form-actions drl-full-width">
                            <button type="submit" name="update_profile" value="1" class="drl-btn drl-btn-primary">
                                Enregistrer mes informations
                            </button>
                        </div>
                    </form>
                </div>

                <div class="drl-panel" id="avatar-upload" style="margin-top:28px;">
                    <h2><?= e(__('client_menu_avatar')) ?></h2>
                    <form method="post" enctype="multipart/form-data" class="drl-form-grid">
                        <?= csrf_field() ?>
                        <div class="drl-form-group drl-full-width">
                            <label for="avatar_file">Choisir une photographie (JPG, PNG ou WEBP — max 5 Mo)</label>
                            <input type="file" id="avatar_file" name="avatar_file" accept="image/jpeg,image/png,image/webp" required>
                        </div>
                        <div class="drl-form-actions drl-full-width">
                            <button type="submit" name="upload_avatar" value="1" class="drl-btn drl-btn-outline">
                                <i class="fa-solid fa-camera-retro"></i> Mettre à jour ma photo
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
