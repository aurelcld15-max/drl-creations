<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/classes/Product.php';
require_once dirname(__DIR__) . '/classes/Creation.php';

header('Content-Type: application/json; charset=utf-8');

$q = trim((string)($_GET['q'] ?? ''));
$lang = current_lang();

if (mb_strlen($q) < 2) {
    echo json_encode(['success' => true, 'results' => []]);
    exit;
}

$results = [];

try {
    $creations = Creation::getPublishedCreations(null, $q, 5);
    foreach ($creations as $cr) {
        $results[] = [
            'type'  => $lang === 'en' ? 'Creation' : 'Création',
            'title' => localized_field($cr, 'name'),
            'ref'   => $cr['reference'],
            'meta'  => localized_field($cr, 'category_name'),
            'image' => asset_url((string)$cr['primary_image']),
            'url'   => BASE_URL . '/modele.php?id=' . (int)$cr['id'],
        ];
    }

    $products = Product::getAvailableProducts(null, null, $q, 5);
    foreach ($products as $p) {
        $results[] = [
            'type'  => 'Boutique',
            'title' => localized_field($p, 'name'),
            'ref'   => $p['reference'],
            'meta'  => Product::formatPriceDisplay($p, $lang),
            'image' => asset_url((string)$p['primary_image']),
            'url'   => BASE_URL . '/article.php?id=' . (int)$p['id'],
        ];
    }
} catch (Throwable) {
}

echo json_encode(['success' => true, 'results' => $results]);
