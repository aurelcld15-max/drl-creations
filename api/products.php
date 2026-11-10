<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/classes/Product.php';

header('Content-Type: application/json; charset=utf-8');

$catId = isset($_GET['category']) ? (int)$_GET['category'] : null;
$mode  = isset($_GET['mode']) ? trim((string)$_GET['mode']) : null;
$q     = isset($_GET['q']) ? trim((string)$_GET['q']) : null;
$lang  = current_lang();

$products = Product::getAvailableProducts($catId, $mode, $q);
$formatted = [];

foreach ($products as $p) {
    $name = localized_field($p, 'name');
    $priceLabel = Product::formatPriceDisplay($p, $lang);
    $url = BASE_URL . '/article.php?id=' . (int)$p['id'];
    $formatted[] = [
        'id'           => (int)$p['id'],
        'reference'    => $p['reference'],
        'name'         => $name,
        'category'     => localized_field($p, 'category_name'),
        'pricing_mode' => $p['pricing_mode'],
        'price_label'  => $priceLabel,
        'image'        => asset_url((string)$p['primary_image']),
        'url'          => $url,
        'whatsapp_url' => WhatsApp::generateItemLink($name, (string)$p['reference'], $url, $lang, $priceLabel),
    ];
}

echo json_encode(['success' => true, 'products' => $formatted]);
