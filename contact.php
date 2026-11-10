<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

$pageTitle = __('nav_contact');
$lang = current_lang();
$user = current_user();
$sent = false;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_contact'])) {
    if (!verify_csrf()) {
        $error = 'Session expirée ou jeton CSRF invalide.';
    } else {
        $name    = trim((string)($_POST['full_name'] ?? ''));
        $email   = trim((string)($_POST['email'] ?? ''));
        $phone   = trim((string)($_POST['phone'] ?? ''));
        $subject = trim((string)($_POST['subject'] ?? 'Rendez-vous Atelier'));
        $message = trim((string)($_POST['message'] ?? ''));

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $message === '') {
            $error = 'Veuillez renseigner votre nom, une adresse e-mail valide et votre message.';
        } else {
            Database::query(
                "INSERT INTO custom_requests (user_id, request_type, full_name, email, phone, subject, message, status, created_at)
                 VALUES (:uid, 'contact', :name, :email, :phone, :subj, :msg, 'pending', NOW())",
                [
                    ':uid'   => $user ? (int)$user['id'] : null,
                    ':name'  => $name,
                    ':email' => $email,
                    ':phone' => $phone,
                    ':subj'  => $subject,
                    ':msg'   => $message,
                ]
            );
            $sent = true;
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="drl-page-hero">
    <div class="drl-container">
        <span class="drl-kicker">SALON PRIVÉ &amp; CONCIERGERIE</span>
        <h1 class="drl-page-title"><?= e(__('contact_title')) ?></h1>
        <p class="drl-page-sub"><?= e(__('contact_sub')) ?></p>
    </div>
</section>

<section class="drl-section">
    <div class="drl-container">
        <div class="drl-contact-grid">
            <div class="drl-contact-card reveal-up">
                <h3><?= $lang === 'en' ? 'Atelier Coordinates' : "Coordonnées de l'Atelier" ?></h3>
                <ul class="drl-contact-list">
                    <li>
                        <i class="fa-solid fa-location-dot"></i>
                        <div>
                            <strong>Adresse</strong>
                            <span><?= e(site_setting('atelier_address', 'Quartier Haie Vive, Cotonou')) ?></span>
                        </div>
                    </li>
                    <li>
                        <i class="fa-solid fa-phone"></i>
                        <div>
                            <strong>Téléphone &amp; WhatsApp</strong>
                            <span><?= e(site_setting('contact_phone', '+229 01 97 00 00 00')) ?></span>
                        </div>
                    </li>
                    <li>
                        <i class="fa-solid fa-envelope"></i>
                        <div>
                            <strong>E-mail officiel</strong>
                            <span><?= e(site_setting('contact_email', 'contact@drl-creations.com')) ?></span>
                        </div>
                    </li>
                    <li>
                        <i class="fa-regular fa-clock"></i>
                        <div>
                            <strong>Horaires d'ouverture</strong>
                            <span><?= e($lang === 'en' ? site_setting('atelier_hours_en') : site_setting('atelier_hours_fr')) ?></span>
                        </div>
                    </li>
                </ul>

                <div style="margin-top:24px;">
                    <a href="<?= e(WhatsApp::generateGeneralLink(null, $lang)) ?>" target="_blank" rel="noopener" class="drl-btn drl-btn-wa drl-full-width">
                        <i class="fa-brands fa-whatsapp"></i> Écrire directement sur WhatsApp
                    </a>
                </div>
            </div>

            <div class="drl-contact-form-wrap reveal-up">
                <?php if ($sent): ?>
                    <div class="drl-alert drl-alert-success">
                        <strong>Merci ! Votre message a bien été enregistré par l'atelier DRL CRÉATIONS.</strong>
                        <p>Notre équipe vous contactera sous 24h.</p>
                    </div>
                <?php else: ?>
                    <?php if ($error): ?>
                        <div class="drl-alert drl-alert-danger"><?= e($error) ?></div>
                    <?php endif; ?>

                    <form method="post" action="<?= BASE_URL ?>/contact.php" class="drl-form-grid">
                        <?= csrf_field() ?>
                        <div class="drl-form-group">
                            <label for="full_name">Nom &amp; Prénom *</label>
                            <input type="text" id="full_name" name="full_name" required value="<?= e($user ? ($user['first_name'] . ' ' . $user['last_name']) : '') ?>">
                        </div>
                        <div class="drl-form-group">
                            <label for="email">Adresse e-mail *</label>
                            <input type="email" id="email" name="email" required value="<?= e($user['email'] ?? '') ?>">
                        </div>
                        <div class="drl-form-group">
                            <label for="phone">Téléphone / WhatsApp</label>
                            <input type="text" id="phone" name="phone" value="<?= e($user['phone'] ?? '') ?>">
                        </div>
                        <div class="drl-form-group">
                            <label for="subject">Objet de votre demande</label>
                            <select id="subject" name="subject">
                                <option value="Prise de rendez-vous sur-mesure">Prise de rendez-vous sur-mesure</option>
                                <option value="Information sur un article en boutique">Information sur un article en boutique</option>
                                <option value="Commande internationale">Commande internationale</option>
                                <option value="Autre demande">Autre demande</option>
                            </select>
                        </div>
                        <div class="drl-form-group drl-full-width">
                            <label for="message">Votre message *</label>
                            <textarea id="message" name="message" rows="5" required></textarea>
                        </div>
                        <div class="drl-form-actions drl-full-width">
                            <button type="submit" name="send_contact" value="1" class="drl-btn drl-btn-primary">
                                <i class="fa-solid fa-paper-plane"></i> <?= e(__('contact_send_btn')) ?>
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
