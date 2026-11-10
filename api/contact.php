<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode((string)$raw, true) ?: $_POST;

if (!verify_csrf((string)($data['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')))) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Jeton CSRF invalide.']);
    exit;
}

$name    = trim((string)($data['full_name'] ?? ''));
$email   = trim((string)($data['email'] ?? ''));
$phone   = trim((string)($data['phone'] ?? ''));
$subject = trim((string)($data['subject'] ?? 'Contact Atelier'));
$message = trim((string)($data['message'] ?? ''));

if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $message === '') {
    echo json_encode(['success' => false, 'error' => 'Veuillez renseigner tous les champs obligatoires.']);
    exit;
}

$user = current_user();
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

echo json_encode([
    'success' => true,
    'message' => "Votre demande a bien été transmise à l'atelier DRL CRÉATIONS.",
]);
