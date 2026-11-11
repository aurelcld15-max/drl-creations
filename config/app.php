<?php
declare(strict_types=1);

/**
 * DRL CRÉATIONS — Configuration générale de l'application
 * Charge les variables d'environnement (.env) sans dépendance obligatoire.
 */

if (!defined('DRL_ROOT')) {
    define('DRL_ROOT', dirname(__DIR__));
}

// Chargement de l'autoloader Composer s'il est installé (pour les librairies OAuth)
if (file_exists(DRL_ROOT . '/vendor/autoload.php')) {
    require_once DRL_ROOT . '/vendor/autoload.php';
}

// Parseur léger et natif de fichier .env
if (!function_exists('drl_load_env')) {
    function drl_load_env(string $filePath): void
    {
        if (!is_readable($filePath)) {
            return;
        }
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }
            [$name, $value] = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            if (
                (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))
            ) {
                $value = substr($value, 1, -1);
            }
            if (!array_key_exists($name, $_ENV)) {
                $_ENV[$name] = $value;
                putenv("{$name}={$value}");
            }
        }
    }
}

drl_load_env(DRL_ROOT . '/.env');

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        $val = $_ENV[$key] ?? getenv($key);
        if ($val === false || $val === null || $val === '') {
            return $default;
        }
        $lower = strtolower((string)$val);
        if ($lower === 'true') return true;
        if ($lower === 'false') return false;
        if ($lower === 'null') return null;
        return $val;
    }
}

define('APP_ENV', (string)env('APP_ENV', 'production'));
define('APP_DEBUG', (bool)env('APP_DEBUG', false));
define('APP_NAME', (string)env('APP_NAME', 'DRL CRÉATIONS'));
define('APP_TIMEZONE', (string)env('APP_TIMEZONE', 'Africa/Porto-Novo'));

date_default_timezone_set(APP_TIMEZONE);

// Désactivation stricte de l'affichage des erreurs en production (Exigence Section 17)
if (APP_DEBUG && APP_ENV !== 'production') {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
}

// Détection dynamique de l'URL de base pour fonctionner sous XAMPP, WAMP, Laragon ou hébergement mutualisé
if (!defined('BASE_URL')) {
    $configuredUrl = env('APP_URL', '');
    if (!empty($configuredUrl) && isset($_SERVER['HTTP_HOST']) && str_contains((string)$configuredUrl, (string)$_SERVER['HTTP_HOST'])) {
        define('BASE_URL', rtrim((string)$configuredUrl, '/'));
    } elseif (isset($_SERVER['HTTP_HOST'])) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        $basePath = preg_replace('#/(admin|auth(?:/oauth)?|client|api|includes|config)$#', '', $scriptDir);
        $basePath = rtrim((string)$basePath, '/');
        define('BASE_URL', $scheme . '://' . $_SERVER['HTTP_HOST'] . $basePath);
    } else {
        define('BASE_URL', 'http://localhost/DRL-CREATIONS');
    }
}
