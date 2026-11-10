<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/security.php';
require_once dirname(__DIR__) . '/classes/Database.php';
require_once dirname(__DIR__) . '/classes/WhatsApp.php';
require_once dirname(__DIR__) . '/classes/Favorite.php';

/**
 * Gestion de la langue courante (fr / en)
 */
if (isset($_GET['lang']) && in_array($_GET['lang'], ['fr', 'en'], true)) {
    $_SESSION['lang'] = $_GET['lang'];
}

function current_lang(): string
{
    return $_SESSION['lang'] ?? 'fr';
}

/**
 * Fonction de traduction réutilisable (Exigence Section 14)
 *
 * @param string $key Clé de traduction
 * @param array<string,string> $replace Variables de remplacement (ex: [':name' => 'Amina'])
 */
function __(string $key, array $replace = []): string
{
    static $translations = [];
    $lang = current_lang();

    if (!isset($translations[$lang])) {
        $file = DRL_ROOT . '/languages/' . $lang . '.php';
        $translations[$lang] = is_readable($file) ? require $file : [];
    }

    $text = $translations[$lang][$key] ?? $key;
    foreach ($replace as $placeholder => $val) {
        $text = str_replace($placeholder, (string)$val, $text);
    }
    return $text;
}

/**
 * Retourne le champ localisé d'un enregistrement MySQL (ex: name_fr ou name_en).
 */
function localized_field(array $row, string $baseField): string
{
    $lang = current_lang();
    $field = $baseField . '_' . $lang;
    if (!empty($row[$field])) {
        return (string)$row[$field];
    }
    $fallback = $baseField . '_fr';
    return (string)($row[$fallback] ?? ($row[$baseField] ?? ''));
}

/**
 * Récupère un paramètre global depuis la table site_settings avec cache mémoire.
 */
function site_setting(string $key, string $default = ''): string
{
    static $settings = null;
    if ($settings === null) {
        $settings = [];
        try {
            $rows = Database::fetchAll("SELECT setting_key, setting_value FROM site_settings");
            foreach ($rows as $r) {
                $settings[$r['setting_key']] = (string)($r['setting_value'] ?? '');
            }
        } catch (Throwable) {
            // Utiliser les valeurs par défaut si la base n'est pas encore importée
        }
    }
    return $settings[$key] ?? $default;
}

/**
 * Récupère toutes les catégories actives.
 */
function get_categories(?string $type = null): array
{
    try {
        if ($type !== null && in_array($type, ['creation', 'product'], true)) {
            return Database::fetchAll(
                "SELECT * FROM categories WHERE is_active = 1 AND (type = :t OR type = 'both') ORDER BY sort_order ASC, id ASC",
                [':t' => $type]
            );
        }
        return Database::fetchAll("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, id ASC");
    } catch (Throwable) {
        return [];
    }
}

/**
 * Utilisateur actuellement connecté ou null.
 */
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return !empty($_SESSION['user']['id']);
}

function is_admin(): bool
{
    return is_logged_in() && (($_SESSION['user']['role'] ?? '') === 'admin');
}

/**
 * Messages flash en session.
 */
function set_flash(string $type, string $message): void
{
    $_SESSION['_flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array
{
    if (!empty($_SESSION['_flash'])) {
        $flash = $_SESSION['_flash'];
        unset($_SESSION['_flash']);
        return $flash;
    }
    return null;
}

/**
 * Génère une URL absolue vers une ressource du site.
 */
function asset_url(string $path): string
{
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        return $path;
    }
    return BASE_URL . '/' . ltrim($path, '/');
}
