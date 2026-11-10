<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/classes/Product.php';
require_once dirname(__DIR__) . '/classes/Creation.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
    exit;
}

if (site_setting('ai_enabled', '1') !== '1') {
    echo json_encode([
        'success' => true,
        'reply'   => "L'assistant virtuel est momentanément en maintenance. Vous pouvez nous écrire directement sur WhatsApp.",
        'whatsapp_url' => WhatsApp::generateGeneralLink(),
    ]);
    exit;
}

$rawInput = file_get_contents('php://input');
$payload = json_decode((string)$rawInput, true) ?: $_POST;
$userMessage = trim((string)($payload['message'] ?? ''));
$lang = current_lang();

if ($userMessage === '') {
    echo json_encode(['success' => false, 'error' => 'Message vide.']);
    exit;
}

$normalized = mb_strtolower($userMessage, 'UTF-8');
$intent = 'general';
$items = [];
$replyLines = [];

try {
    // 1. Recherche directe dans les FAQ officielles enregistrées en base
    $faqs = Database::fetchAll("SELECT * FROM faq WHERE is_active = 1 ORDER BY sort_order ASC");
    $matchedFaq = null;
    foreach ($faqs as $f) {
        $keywords = array_filter(array_map('trim', explode(',', mb_strtolower((string)($f['keywords'] ?? ''), 'UTF-8'))));
        foreach ($keywords as $kw) {
            if ($kw !== '' && mb_strpos($normalized, $kw) !== false) {
                $matchedFaq = $f;
                break 2;
            }
        }
    }

    // 2. Détection d'intention sur les Prix / Boutique / Articles disponibles
    if (preg_match('/(prix|tarif|combien|coût|cout|boutique|acheter|vendre|article|price|cost|shop|buy)/u', $normalized)) {
        $intent = 'boutique_prices';
        $products = Product::getAvailableProducts(null, null, null, 6);
        if (!empty($products)) {
            $replyLines[] = $lang === 'en'
                ? "Here are the available pieces currently in our Boutique with their official recorded prices:"
                : "Voici les pièces actuellement disponibles dans notre Boutique avec leurs tarifs officiels enregistrés :";
            foreach ($products as $p) {
                $pName = localized_field($p, 'name');
                $priceDisplay = Product::formatPriceDisplay($p, $lang);
                $pUrl = BASE_URL . '/article.php?id=' . (int)$p['id'];
                $replyLines[] = "• {$pName} (Réf: {$p['reference']}) — {$priceDisplay}";
                $items[] = [
                    'title' => $pName,
                    'ref'   => $p['reference'],
                    'meta'  => $priceDisplay,
                    'url'   => $pUrl,
                    'wa'    => WhatsApp::generateItemLink($pName, (string)$p['reference'], $pUrl, $lang, $priceDisplay),
                ];
            }
        } else {
            $replyLines[] = $lang === 'en'
                ? "There are currently no ready-to-wear articles in stock, but our atelier crafts all models to order."
                : "Aucun article prêt-à-porter n'est en stock immédiat pour le moment, mais notre atelier confectionne tous nos modèles sur commande.";
        }
    }
    // 3. Détection d'intention sur les Créations / Modèles réalisables
    elseif (preg_match('/(création|creation|modèle|modele|robe|costume|tenue|soirée|soiree|cérémonie|ceremonie|bazin|kanvo|smoking|gown|dress|suit)/u', $normalized)) {
        $intent = 'creations_showcase';
        // Chercher d'abord un mot-clé précis dans les créations
        $creations = Creation::getPublishedCreations(null, null, 6);
        $replyLines[] = $lang === 'en'
            ? "Here are our signature bespoke models realizable by the DRL CRÉATIONS atelier:"
            : "Voici nos modèles signatures réalisables sur-mesure par l'atelier DRL CRÉATIONS :";
        foreach ($creations as $cr) {
            $cName = localized_field($cr, 'name');
            $cCat  = localized_field($cr, 'category_name');
            $cUrl  = BASE_URL . '/modele.php?id=' . (int)$cr['id'];
            $replyLines[] = "• {$cName} [{$cCat}] — Réf: {$cr['reference']} (Délai : {$cr['estimated_delay']})";
            $items[] = [
                'title' => $cName,
                'ref'   => $cr['reference'],
                'meta'  => $cCat . ' • ' . $cr['estimated_delay'],
                'url'   => $cUrl,
                'wa'    => WhatsApp::generateItemLink($cName, (string)$cr['reference'], $cUrl, $lang, 'Demande de réalisation sur-mesure'),
            ];
        }
    }
    // 4. Détection d'intention sur les Catégories
    elseif (preg_match('/(catégorie|categorie|collection|catalogue|type|category|categories)/u', $normalized)) {
        $intent = 'categories_list';
        $cats = get_categories();
        $replyLines[] = $lang === 'en'
            ? "Our couture house offers the following official categories:"
            : "Notre maison de couture vous propose les collections suivantes :";
        foreach ($cats as $cat) {
            $replyLines[] = "• " . localized_field($cat, 'name') . " : " . localized_field($cat, 'description');
        }
    }
    // 5. Si une FAQ correspond aux mots-clés (sur-mesure, délais, livraison, rendez-vous, etc.)
    elseif ($matchedFaq !== null) {
        $intent = 'faq_match';
        $replyLines[] = localized_field($matchedFaq, 'answer');
    }
    // 6. Recherche plein texte dans les articles et créations
    else {
        $intent = 'catalog_search';
        $foundProducts  = Product::getAvailableProducts(null, null, $userMessage, 3);
        $foundCreations = Creation::getPublishedCreations(null, $userMessage, 3);

        if (!empty($foundProducts) || !empty($foundCreations)) {
            $replyLines[] = $lang === 'en'
                ? "Here is what I found in the official DRL CRÉATIONS database for your query:"
                : "Voici les pièces trouvées dans le catalogue officiel DRL CRÉATIONS pour votre recherche :";

            foreach ($foundCreations as $cr) {
                $cName = localized_field($cr, 'name');
                $cUrl  = BASE_URL . '/modele.php?id=' . (int)$cr['id'];
                $replyLines[] = "• [Modèle Sur-Mesure] {$cName} (Réf: {$cr['reference']}) — Délai : {$cr['estimated_delay']}";
                $items[] = [
                    'title' => $cName,
                    'ref'   => $cr['reference'],
                    'meta'  => 'Modèle Sur-Mesure',
                    'url'   => $cUrl,
                    'wa'    => WhatsApp::generateItemLink($cName, (string)$cr['reference'], $cUrl, $lang),
                ];
            }
            foreach ($foundProducts as $p) {
                $pName = localized_field($p, 'name');
                $priceDisplay = Product::formatPriceDisplay($p, $lang);
                $pUrl = BASE_URL . '/article.php?id=' . (int)$p['id'];
                $replyLines[] = "• [Boutique] {$pName} (Réf: {$p['reference']}) — {$priceDisplay}";
                $items[] = [
                    'title' => $pName,
                    'ref'   => $p['reference'],
                    'meta'  => $priceDisplay,
                    'url'   => $pUrl,
                    'wa'    => WhatsApp::generateItemLink($pName, (string)$p['reference'], $pUrl, $lang, $priceDisplay),
                ];
            }
        } else {
            $replyLines[] = $lang === 'en'
                ? "I only provide verified information from our MySQL catalog. Would you like to explore our 7 bespoke categories (Evening gowns, Ceremonial dresses, Traditional attire, African couture, Modern ensembles, Suits, Custom creations), check available boutique prices, or speak directly with our head of atelier on WhatsApp?"
                : "Je me base exclusivement sur les données réelles de l'atelier DRL CRÉATIONS. Souhaitez-vous découvrir nos 7 collections (Robes de soirée, Robes de cérémonie, Tenues traditionnelles, Tenues africaines, Ensembles modernes, Costumes, Créations personnalisées), consulter les tarifs de la boutique ou échanger directement avec notre atelier sur WhatsApp ?";
        }
    }

    $finalReply = implode("
", $replyLines);

    // Enregistrement de la conversation dans la table ai_conversations
    $user = current_user();
    $ipHash = hash('sha256', (string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'));
    Database::query(
        "INSERT INTO ai_conversations (session_id, user_id, user_message, assistant_reply, detected_intent, ip_hash, created_at)
         VALUES (:sess, :uid, :umsg, :areply, :intent, :ip, NOW())",
        [
            ':sess'   => session_id() ?: 'anon',
            ':uid'    => $user ? (int)$user['id'] : null,
            ':umsg'   => $userMessage,
            ':areply' => $finalReply,
            ':intent' => $intent,
            ':ip'     => $ipHash,
        ]
    );

    echo json_encode([
        'success'      => true,
        'intent'       => $intent,
        'reply'        => $finalReply,
        'items'        => $items,
        'whatsapp_url' => WhatsApp::generateGeneralLink("Bonjour DRL CRÉATIONS, suite à mon échange avec DRL Assistant : " . $userMessage, $lang),
    ]);
} catch (Throwable $e) {
    error_log('[DRL Assistant Error] ' . $e->getMessage());
    echo json_encode([
        'success'      => true,
        'reply'        => 'Je suis à votre disposition pour vous orienter vers notre atelier sur WhatsApp.',
        'whatsapp_url' => WhatsApp::generateGeneralLink(),
    ]);
}
