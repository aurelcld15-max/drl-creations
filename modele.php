<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/classes/Creation.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$creation = $id > 0 ? Creation::findById($id, is_admin()) : null;

if (!$creation) {
    set_flash('warning', current_lang() === 'en' ? 'Creation not found.' : "Ce modèle n'est plus disponible ou n'existe pas.");
    header('Location: ' . BASE_URL . '/creations.php');
    exit;
}

$lang = current_lang();
$user = current_user();

// Traitement du formulaire « Demander ce modèle »
$requestSuccess = false;
$requestError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_creation_request'])) {
    if (!verify_csrf()) {
        $requestError = 'Jeton de sécurité invalide. Veuillez réessayer.';
    } else {
        $fullName = trim((string)($_POST['full_name'] ?? ''));
        $email    = trim((string)($_POST['email'] ?? ''));
        $phone    = trim((string)($_POST['phone'] ?? ''));
        $measures = trim((string)($_POST['measurements_notes'] ?? ''));
        $message  = trim((string)($_POST['message'] ?? ''));

        if ($fullName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $message === '') {
            $requestError = 'Veuillez remplir votre nom, un e-mail valide et les détails de votre demande.';
        } else {
            Database::query(
                "INSERT INTO custom_requests (user_id, request_type, creation_id, full_name, email, phone, subject, measurements_notes, message, status, created_at)
                 VALUES (:uid, 'creation_order', :cid, :name, :email, :phone, :subj, :meas, :msg, 'pending', NOW())",
                [
                    ':uid'   => $user ? (int)$user['id'] : null,
                    ':cid'   => (int)$creation['id'],
                    ':name'  => $fullName,
                    ':email' => $email,
                    ':phone' => $phone,
                    ':subj'  => 'Demande du modèle ' . $creation['reference'] . ' — ' . $creation['name_fr'],
                    ':meas'  => $measures,
                    ':msg'   => $message,
                ]
            );
            $requestSuccess = true;
        }
    }
}

// Incrémenter le compteur de vues
Database::query("UPDATE creations SET views_count = views_count + 1 WHERE id = :id", [':id' => $creation['id']]);

$cName   = localized_field($creation, 'name');
$cDesc   = localized_field($creation, 'description');
$cCat    = localized_field($creation, 'category_name');
$itemUrl = BASE_URL . '/modele.php?id=' . (int)$creation['id'];
$waLink  = WhatsApp::generateItemLink($cName, (string)$creation['reference'], $itemUrl, $lang, 'Demande de confection sur-mesure');
$isFav   = $user ? Favorite::isFavorited((int)$user['id'], 'creation', (int)$creation['id']) : false;
$images  = $creation['images'] ?? [];

$pageTitle = $cName . ' (' . $creation['reference'] . ')';
require_once __DIR__ . '/includes/header.php';
?>

<section class="drl-section">
    <div class="drl-container">
        <nav class="drl-breadcrumb" aria-label="Fil d'Ariane">
            <a href="<?= BASE_URL ?>/index.php"><?= e(__('nav_home')) ?></a>
            <span>/</span>
            <a href="<?= BASE_URL ?>/creations.php"><?= e(__('nav_creations')) ?></a>
            <span>/</span>
            <span><?= e($cName) ?></span>
        </nav>

        <div class="drl-detail-grid">
            <!-- Galerie Photographique Interactive -->
            <div class="drl-gallery-box">
                <div class="drl-gallery-main">
                    <img src="<?= asset_url((string)$images[0]['image_path']) ?>" alt="<?= e($cName) ?>" id="mainGalleryImage">
                </div>
                <?php if (count($images) > 1): ?>
                <div class="drl-gallery-thumbs">
                    <?php foreach ($images as $idx => $img): ?>
                        <button type="button" class="drl-thumb-btn <?= $idx === 0 ? 'active' : '' ?>"
                                onclick="document.getElementById('mainGalleryImage').src='<?= e(asset_url((string)$img['image_path'])) ?>'">
                            <img src="<?= asset_url((string)$img['image_path']) ?>" alt="<?= e($cName) ?>">
                        </button>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Informations & Actions du Modèle -->
            <div class="drl-detail-info">
                <span class="drl-kicker"><?= e($cCat) ?></span>
                <h1 class="drl-detail-title"><?= e($cName) ?></h1>
                <p class="drl-detail-ref"><?= e(__('creations_ref_label')) ?> : <strong><?= e($creation['reference']) ?></strong></p>

                <div class="drl-detail-price-box">
                    <span class="drl-bespoke-tag"><i class="fa-solid fa-wand-magic-sparkles"></i> <?= e(__('creations_no_price')) ?></span>
                </div>

                <div class="drl-detail-description">
                    <?= nl2br(e($cDesc)) ?>
                </div>

                <div class="drl-specs-list">
                    <div class="drl-spec-row">
                        <span><i class="fa-regular fa-clock drl-gold-icon"></i> <?= e(__('creations_delay_label')) ?></span>
                        <strong><?= e($creation['estimated_delay']) ?></strong>
                    </div>
                    <div class="drl-spec-row">
                        <span><i class="fa-solid fa-sliders drl-gold-icon"></i> <?= e(__('creations_custom_label')) ?></span>
                        <strong><?= e($creation['customizable_options']) ?></strong>
                    </div>
                </div>

                <div class="drl-detail-actions">
                    <a href="#demander-modele" class="drl-btn drl-btn-primary">
                        <i class="fa-solid fa-ruler-combined"></i>
                        <span><?= e(__('creations_request_btn')) ?></span>
                    </a>
                    <a href="<?= e($waLink) ?>" target="_blank" rel="noopener" class="drl-btn drl-btn-wa">
                        <i class="fa-brands fa-whatsapp"></i>
                        <span>WhatsApp Direct</span>
                    </a>
                    <button type="button"
                            class="drl-btn drl-btn-outline <?= $isFav ? 'active' : '' ?>"
                            data-fav-toggle
                            data-type="creation"
                            data-id="<?= (int)$creation['id'] ?>">
                        <i class="<?= $isFav ? 'fa-solid' : 'fa-regular' ?> fa-heart"></i>
                    </button>
                    <button type="button" class="drl-btn drl-btn-outline" data-share-url="<?= e($itemUrl) ?>" data-share-title="<?= e($cName) ?>">
                        <i class="fa-solid fa-share-nodes"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Formulaire dédié « Demander ce modèle » enregistré dans MySQL -->
        <div class="drl-request-panel" id="demander-modele">
            <div class="drl-request-header">
                <span class="drl-kicker">ATELIER DE CONFECTION</span>
                <h2><?= e(__('creations_modal_title')) ?> : <?= e($cName) ?></h2>
                <p>Référence : <strong><?= e($creation['reference']) ?></strong> — Précisez vos souhaits de personnalisation (couleur, étoffe, mensurations ou date d'événement).</p>
            </div>

            <?php if ($requestSuccess): ?>
                <div class="drl-alert drl-alert-success">
                    <div>
                        <strong>Votre demande pour le modèle <?= e($creation['reference']) ?> a bien été enregistrée !</strong>
                        <p>Notre cheffe d'atelier vous répondra dans les plus brefs délais. Vous pouvez également poursuivre l'échange instantanément sur WhatsApp :</p>
                        <a href="<?= e($waLink) ?>" target="_blank" rel="noopener" class="drl-btn drl-btn-sm drl-btn-wa">
                            <i class="fa-brands fa-whatsapp"></i> Ouvrir la discussion WhatsApp pré-remplie
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <?php if ($requestError): ?>
                    <div class="drl-alert drl-alert-danger"><?= e($requestError) ?></div>
                <?php endif; ?>

                <form method="post" action="#demander-modele" class="drl-form-grid">
                    <?= csrf_field() ?>
                    <div class="drl-form-group">
                        <label for="full_name">Nom complet *</label>
                        <input type="text" id="full_name" name="full_name" required
                               value="<?= e($user ? ($user['first_name'] . ' ' . $user['last_name']) : '') ?>">
                    </div>
                    <div class="drl-form-group">
                        <label for="email">Adresse e-mail *</label>
                        <input type="email" id="email" name="email" required
                               value="<?= e($user['email'] ?? '') ?>">
                    </div>
                    <div class="drl-form-group">
                        <label for="phone">Téléphone / WhatsApp *</label>
                        <input type="text" id="phone" name="phone" required
                               value="<?= e($user['phone'] ?? '') ?>" placeholder="+229 ...">
                    </div>
                    <div class="drl-form-group">
                        <label for="measurements_notes">Taille habituelle ou mensurations (optionnel)</label>
                        <input type="text" id="measurements_notes" name="measurements_notes" placeholder="Ex: Taille M, Tour de poitrine 92cm, événement le 15 décembre...">
                    </div>
                    <div class="drl-form-group drl-full-width">
                        <label for="message">Vos souhaits de personnalisation *</label>
                        <textarea id="message" name="message" rows="4" required placeholder="Précisez la couleur souhaitée, le tissu ou vos questions sur ce modèle..."></textarea>
                    </div>
                    <div class="drl-form-actions drl-full-width">
                        <button type="submit" name="submit_creation_request" value="1" class="drl-btn drl-btn-primary">
                            <i class="fa-solid fa-paper-plane"></i> Envoyer ma demande à l'atelier
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
