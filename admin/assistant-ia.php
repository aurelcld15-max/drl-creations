<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin-check.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    if (isset($_POST['save_ai_settings'])) {
        $enabled = isset($_POST['ai_enabled']) ? '1' : '0';
        $wFr = trim((string)($_POST['ai_welcome_fr'] ?? ''));
        $wEn = trim((string)($_POST['ai_welcome_en'] ?? ''));

        foreach (['ai_enabled' => $enabled, 'ai_welcome_fr' => $wFr, 'ai_welcome_en' => $wEn] as $k => $v) {
            Database::query(
                "INSERT INTO site_settings (setting_key, setting_value, setting_group)
                 VALUES (:k, :v, 'ai')
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)",
                [':k' => $k, ':v' => $v]
            );
        }
        set_flash('success', 'La configuration de DRL Assistant a été mise à jour.');
    } elseif (isset($_POST['add_faq'])) {
        $qFr = trim((string)($_POST['question_fr'] ?? ''));
        $qEn = trim((string)($_POST['question_en'] ?? $qFr));
        $aFr = trim((string)($_POST['answer_fr'] ?? ''));
        $aEn = trim((string)($_POST['answer_en'] ?? $aFr));
        $kw  = trim((string)($_POST['keywords'] ?? ''));

        if ($qFr !== '' && $aFr !== '') {
            Database::query(
                "INSERT INTO faq (question_fr, question_en, answer_fr, answer_en, keywords, sort_order, is_active)
                 VALUES (:qfr, :qen, :afr, :aen, :kw, 10, 1)",
                [':qfr' => $qFr, ':qen' => $qEn, ':afr' => $aFr, ':aen' => $aEn, ':kw' => $kw]
            );
            set_flash('success', 'La question fréquente et sa réponse officielle ont été ajoutées.');
        }
    } elseif (isset($_POST['delete_faq_id'])) {
        Database::query("DELETE FROM faq WHERE id = :id", [':id' => (int)$_POST['delete_faq_id']]);
        set_flash('info', 'Question fréquente supprimée.');
    }
    header('Location: ' . BASE_URL . '/admin/assistant-ia.php');
    exit;
}

$faqs = Database::fetchAll("SELECT * FROM faq ORDER BY sort_order ASC, id DESC");
$conversations = Database::fetchAll("SELECT * FROM ai_conversations ORDER BY created_at DESC LIMIT 12");
$adminTitle = 'Gestion de DRL Assistant (Intelligence Artificielle)';
?>
<?php
$currentAdminPage = basename($_SERVER['SCRIPT_NAME'] ?? 'dashboard.php');
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="fr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($adminTitle ?? 'Administration') ?> — DRL CRÉATIONS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,600;1,400&family=Playfair+Display:wght@600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= asset_url('assets/css/style.css') ?>">
    <link rel="stylesheet" href="<?= asset_url('assets/css/admin.css') ?>">
</head>
<body class="drl-admin-body">
    <div class="drl-admin-wrapper">
        <aside class="drl-admin-sidebar">
            <div class="drl-admin-brand">
                <img src="<?= asset_url('assets/images/logo-gold.svg') ?>" alt="DRL CRÉATIONS">
                <div>
                    <strong>DRL CRÉATIONS</strong>
                    <span>Direction d'Atelier</span>
                </div>
            </div>

            <nav class="drl-admin-nav">
                <a href="<?= BASE_URL ?>/admin/dashboard.php" class="<?= $currentAdminPage === 'dashboard.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-chart-pie"></i> Tableau de bord
                </a>
                <a href="<?= BASE_URL ?>/admin/produits.php" class="<?= in_array($currentAdminPage, ['produits.php', 'ajouter-produit.php', 'modifier-produit.php'], true) ? 'active' : '' ?>">
                    <i class="fa-solid fa-bag-shopping"></i> Boutique (Articles)
                </a>
                <a href="<?= BASE_URL ?>/admin/creations.php" class="<?= in_array($currentAdminPage, ['creations.php', 'ajouter-creation.php', 'modifier-creation.php'], true) ? 'active' : '' ?>">
                    <i class="fa-solid fa-wand-magic-sparkles"></i> Nos Créations (Modèles)
                </a>
                <a href="<?= BASE_URL ?>/admin/categories.php" class="<?= $currentAdminPage === 'categories.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-layer-group"></i> Catégories
                </a>
                <a href="<?= BASE_URL ?>/admin/clients.php" class="<?= $currentAdminPage === 'clients.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-users"></i> Clients &amp; Demandes
                </a>
                <a href="<?= BASE_URL ?>/admin/assistant-ia.php" class="<?= $currentAdminPage === 'assistant-ia.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-robot"></i> Gestion Assistant IA
                </a>
                <a href="<?= BASE_URL ?>/admin/parametres.php" class="<?= $currentAdminPage === 'parametres.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-sliders"></i> Paramètres &amp; WhatsApp
                </a>
            </nav>

            <div class="drl-admin-sidebar-footer">
                <a href="<?= BASE_URL ?>/index.php" class="drl-btn drl-btn-sm drl-btn-outline drl-full-width">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i> Voir le site public
                </a>
                <a href="<?= BASE_URL ?>/auth/logout.php" class="drl-admin-logout">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i> Déconnexion
                </a>
            </div>
        </aside>

        <div class="drl-admin-main">
            <header class="drl-admin-topbar">
                <h1><?= e($adminTitle ?? 'Tableau de bord') ?></h1>
                <div class="drl-admin-user">
                    <span><i class="fa-solid fa-crown drl-gold-icon"></i> <?= e($_SESSION['user']['first_name'] ?? 'Admin') ?></span>
                </div>
            </header>

            <div class="drl-admin-content">
                <?php if ($flash): ?>
                    <div class="drl-alert drl-alert-<?= e($flash['type']) ?>">
                        <span><?= e($flash['message']) ?></span>
                        <button type="button" class="drl-alert-close" onclick="this.parentElement.remove()">&times;</button>
                    </div>
                <?php endif; ?>

<div class="drl-admin-panel">
    <h2>Activation &amp; Message d'accueil de DRL Assistant</h2>
    <form method="post" class="drl-form-grid">
        <?= csrf_field() ?>
        <div class="drl-form-group drl-full-width">
            <label>
                <input type="checkbox" name="ai_enabled" value="1" <?= site_setting('ai_enabled', '1') === '1' ? 'checked' : '' ?>>
                <strong>Activer l'assistant flottant DRL Assistant sur toutes les pages publiques</strong>
            </label>
        </div>
        <div class="drl-form-group drl-full-width">
            <label for="ai_welcome_fr">Message d'accueil officiel (Français)</label>
            <textarea id="ai_welcome_fr" name="ai_welcome_fr" rows="3"><?= e(site_setting('ai_welcome_fr')) ?></textarea>
        </div>
        <div class="drl-form-group drl-full-width">
            <label for="ai_welcome_en">Message d'accueil officiel (Anglais)</label>
            <textarea id="ai_welcome_en" name="ai_welcome_en" rows="3"><?= e(site_setting('ai_welcome_en')) ?></textarea>
        </div>
        <div class="drl-form-actions drl-full-width">
            <button type="submit" name="save_ai_settings" value="1" class="drl-btn drl-btn-primary">Enregistrer les paramètres IA</button>
        </div>
    </form>
</div>

<div class="drl-admin-panel" style="margin-top:28px;">
    <h2>Ajouter une Question Fréquente &amp; Réponse Officielle</h2>
    <form method="post" class="drl-form-grid">
        <?= csrf_field() ?>
        <div class="drl-form-group">
            <label for="question_fr">Question (Français) *</label>
            <input type="text" id="question_fr" name="question_fr" required>
        </div>
        <div class="drl-form-group">
            <label for="question_en">Question (Anglais)</label>
            <input type="text" id="question_en" name="question_en">
        </div>
        <div class="drl-form-group drl-full-width">
            <label for="keywords">Mots-clés déclencheurs (séparés par des virgules)</label>
            <input type="text" id="keywords" name="keywords" placeholder="Ex: délai, livraison, essayage, prix, bazin">
        </div>
        <div class="drl-form-group drl-full-width">
            <label for="answer_fr">Réponse officielle de l'atelier (Français) *</label>
            <textarea id="answer_fr" name="answer_fr" rows="3" required></textarea>
        </div>
        <div class="drl-form-group drl-full-width">
            <label for="answer_en">Réponse officielle de l'atelier (Anglais)</label>
            <textarea id="answer_en" name="answer_en" rows="3"></textarea>
        </div>
        <div class="drl-form-actions drl-full-width">
            <button type="submit" name="add_faq" value="1" class="drl-btn drl-btn-outline">Ajouter à la base de connaissances IA</button>
        </div>
    </form>

    <h3 style="margin-top:28px;">Réponses Officielles Actives</h3>
    <div class="drl-table-responsive">
        <table class="drl-table">
            <thead>
                <tr>
                    <th>Question</th>
                    <th>Mots-clés</th>
                    <th>Réponse officielle</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($faqs as $f): ?>
                <tr>
                    <td><strong><?= e($f['question_fr']) ?></strong></td>
                    <td><small><?= e($f['keywords']) ?></small></td>
                    <td><?= e(mb_strimwidth((string)$f['answer_fr'], 0, 120, '…')) ?></td>
                    <td>
                        <form method="post" onsubmit="return confirm('Supprimer cette FAQ ?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="delete_faq_id" value="<?= (int)$f['id'] ?>">
                            <button type="submit" class="drl-btn drl-btn-sm drl-btn-danger"><i class="fa-solid fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

            </div>
        </div>
    </div>
    <script src="<?= asset_url('assets/js/admin.js') ?>"></script>
</body>
</html>
