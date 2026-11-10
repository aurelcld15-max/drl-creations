<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth-check.php';
require_once dirname(__DIR__) . '/classes/User.php';

$sessionUser = current_user();
$userId = (int)$sessionUser['id'];
$user = User::findById($userId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        set_flash('danger', 'Jeton CSRF invalide.');
    } elseif (isset($_POST['update_preferences'])) {
        $prefLang  = (string)($_POST['preferred_lang'] ?? 'fr');
        $prefTheme = (string)($_POST['preferred_theme'] ?? 'light');
        User::updatePreferences($userId, $prefLang, $prefTheme);
        $_SESSION['lang'] = in_array($prefLang, ['fr', 'en'], true) ? $prefLang : 'fr';
        set_flash('success', "Vos préférences d'affichage et de langue ont été enregistrées.");
        header('Location: ' . BASE_URL . '/client/parametres.php');
        exit;
    } elseif (isset($_POST['update_password'])) {
        $cur = (string)($_POST['current_password'] ?? '');
        $new = (string)($_POST['new_password'] ?? '');
        $res = User::updatePassword($userId, $cur, $new);
        if ($res['success']) {
            set_flash('success', 'Votre mot de passe a été modifié avec succès.');
        } else {
            set_flash('danger', (string)$res['error']);
        }
        header('Location: ' . BASE_URL . '/client/parametres.php');
        exit;
    }
}

$pageTitle = __('client_menu_settings');
require_once dirname(__DIR__) . '/includes/header.php';
?>

<section class="drl-section">
    <div class="drl-container">
        <div class="drl-client-layout">
            <aside class="drl-client-sidebar">
                <nav class="drl-client-nav">
                    <a href="<?= BASE_URL ?>/client/profil.php"><i class="fa-regular fa-id-badge"></i> <?= e(__('client_menu_profile')) ?></a>
                    <a href="<?= BASE_URL ?>/client/favoris.php"><i class="fa-regular fa-heart"></i> <?= e(__('client_menu_favorites')) ?></a>
                    <a href="<?= BASE_URL ?>/client/demandes.php"><i class="fa-solid fa-ruler-combined"></i> <?= e(__('client_menu_requests')) ?></a>
                    <a href="<?= BASE_URL ?>/client/parametres.php" class="active"><i class="fa-solid fa-sliders"></i> <?= e(__('client_menu_settings')) ?></a>
                    <a href="<?= BASE_URL ?>/auth/logout.php" class="drl-text-danger"><i class="fa-solid fa-arrow-right-from-bracket"></i> <?= e(__('client_menu_logout')) ?></a>
                </nav>
            </aside>

            <div class="drl-client-content">
                <div class="drl-panel">
                    <h2>Préférences de langue &amp; de thème</h2>
                    <form method="post" class="drl-form-grid">
                        <?= csrf_field() ?>
                        <div class="drl-form-group">
                            <label for="preferred_lang">Langue de l'interface</label>
                            <select id="preferred_lang" name="preferred_lang">
                                <option value="fr" <?= ($user['preferred_lang'] ?? 'fr') === 'fr' ? 'selected' : '' ?>>Français</option>
                                <option value="en" <?= ($user['preferred_lang'] ?? '') === 'en' ? 'selected' : '' ?>>English</option>
                            </select>
                        </div>
                        <div class="drl-form-group">
                            <label for="preferred_theme">Thème par défaut</label>
                            <select id="preferred_theme" name="preferred_theme">
                                <option value="light" <?= ($user['preferred_theme'] ?? 'light') === 'light' ? 'selected' : '' ?>>Mode Clair (Ivoire &amp; Or Champagne)</option>
                                <option value="dark" <?= ($user['preferred_theme'] ?? '') === 'dark' ? 'selected' : '' ?>>Mode Sombre (Bleu Nuit &amp; Or)</option>
                            </select>
                        </div>
                        <div class="drl-form-actions drl-full-width">
                            <button type="submit" name="update_preferences" value="1" class="drl-btn drl-btn-primary">Enregistrer mes préférences</button>
                        </div>
                    </form>
                </div>

                <div class="drl-panel" style="margin-top:28px;">
                    <h2>Sécurité &amp; Mot de passe</h2>
                    <form method="post" class="drl-form-grid">
                        <?= csrf_field() ?>
                        <div class="drl-form-group">
                            <label for="current_password">Mot de passe actuel</label>
                            <input type="password" id="current_password" name="current_password" required>
                        </div>
                        <div class="drl-form-group">
                            <label for="new_password">Nouveau mot de passe (min. 8 caractères)</label>
                            <input type="password" id="new_password" name="new_password" minlength="8" required>
                        </div>
                        <div class="drl-form-actions drl-full-width">
                            <button type="submit" name="update_password" value="1" class="drl-btn drl-btn-outline">Modifier mon mot de passe</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
