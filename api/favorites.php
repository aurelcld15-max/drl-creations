<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/classes/Favorite.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
    exit;
}

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode([
        'success'      => false,
        'auth_required'=> true,
        'login_url'    => BASE_URL . '/auth/login.php',
        'error'        => current_lang() === 'en'
            ? 'Please sign in to save your favorite pieces.'
            : 'Veuillez vous connecter pour enregistrer vos pièces favorites.',
    ]);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode((string)$raw, true) ?: $_POST;

$token = (string)($data['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
if (!verify_csrf($token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Jeton CSRF invalide.']);
    exit;
}

$itemType = (string)($data['item_type'] ?? 'product');
$itemId   = (int)($data['item_id'] ?? 0);

if ($itemId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Identifiant invalide.']);
    exit;
}

$user = current_user();
$res = Favorite::toggle((int)$user['id'], $itemType, $itemId);

echo json_encode([
    'success' => true,
    'action'  => $res['action'],
    'count'   => $res['count'],
]);
