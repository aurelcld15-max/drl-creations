<?php
declare(strict_types=1);

require_once __DIR__ . '/Database.php';

/**
 * Classe WhatsApp — Centralise la récupération du numéro WhatsApp depuis MySQL
 * et la génération des liens de messages personnalisés pour les articles et modèles.
 */
class WhatsApp
{
    private static ?string $cachedPhone = null;

    /**
     * Récupère le numéro WhatsApp officiel enregistré dans la table site_settings.
     */
    public static function getNumber(): string
    {
        if (self::$cachedPhone !== null) {
            return self::$cachedPhone;
        }

        try {
            $row = Database::fetchOne(
                "SELECT setting_value FROM site_settings WHERE setting_key = 'whatsapp_number' LIMIT 1"
            );
            $raw = $row['setting_value'] ?? '2290197000000';
        } catch (Throwable) {
            $raw = '2290197000000';
        }

        $cleaned = preg_replace('/[^0-9]/', '', (string)$raw);
        self::$cachedPhone = !empty($cleaned) ? $cleaned : '2290197000000';
        return self::$cachedPhone;
    }

    /**
     * Génère le lien WhatsApp officiel selon le format exact exigé (Section 11) :
     *
     * Bonjour DRL CRÉATIONS,
     * Je suis intéressé(e) par : [Nom].
     * Référence : [Référence].
     * Lien : [URL].
     * Je souhaiterais obtenir davantage d'informations.
     * Merci.
     */
    public static function generateItemLink(
        string $itemName,
        string $reference,
        string $itemUrl,
        string $lang = 'fr',
        ?string $pricingNote = null
    ): string {
        $phone = self::getNumber();

        if ($lang === 'en') {
            $lines = [
                "Hello DRL CRÉATIONS,",
                "I am interested in: {$itemName}.",
                "Reference: {$reference}.",
            ];
            if ($pricingNote !== null && $pricingNote !== '') {
                $lines[] = "Option: {$pricingNote}.";
            }
            $lines[] = "Link: {$itemUrl}.";
            $lines[] = "I would like to receive more information.";
            $lines[] = "Thank you.";
        } else {
            $lines = [
                "Bonjour DRL CRÉATIONS,",
                "Je suis intéressé(e) par : {$itemName}.",
                "Référence : {$reference}.",
            ];
            if ($pricingNote !== null && $pricingNote !== '') {
                $lines[] = "Modalité : {$pricingNote}.";
            }
            $lines[] = "Lien : {$itemUrl}.";
            $lines[] = "Je souhaiterais obtenir davantage d'informations.";
            $lines[] = "Merci.";
        }

        $message = implode("
", $lines);
        return 'https://wa.me/' . rawurlencode($phone) . '?text=' . rawurlencode($message);
    }

    /**
     * Génère un lien WhatsApp général pour contacter l'atelier.
     */
    public static function generateGeneralLink(?string $customText = null, string $lang = 'fr'): string
    {
        $phone = self::getNumber();
        if ($customText === null || trim($customText) === '') {
            $customText = $lang === 'en'
                ? "Hello DRL CRÉATIONS,
I would like to book a bespoke consultation or get information about your creations.
Thank you."
                : "Bonjour DRL CRÉATIONS,
Je souhaiterais prendre rendez-vous ou obtenir des renseignements sur vos confections sur-mesure.
Merci.";
        }
        return 'https://wa.me/' . rawurlencode($phone) . '?text=' . rawurlencode($customText);
    }
}
