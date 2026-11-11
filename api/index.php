<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

$root = dirname(__DIR__);
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$uri = preg_replace('#/+#', '/', $uri);

function drl_resolve_php_file(string $rootDir, string $uri): ?string
{
    $normalized = trim($uri, '/');

    if ($normalized === '') {
        $candidate = $rootDir . '/index.php';
        return file_exists($candidate) ? $candidate : null;
    }

    $candidate = $rootDir . '/' . $normalized;

    if (is_dir($candidate)) {
        $fallback = rtrim($candidate, '/') . '/index.php';
        return file_exists($fallback) ? $fallback : null;
    }

    if (str_ends_with($candidate, '.php')) {
        return file_exists($candidate) ? $candidate : null;
    }

    if (pathinfo($candidate, PATHINFO_EXTENSION) === '') {
        $phpCandidate = $candidate . '.php';
        if (file_exists($phpCandidate)) {
            return $phpCandidate;
        }
    }

    if (file_exists($candidate . '/index.php')) {
        return $candidate . '/index.php';
    }

    return null;
}

$file = drl_resolve_php_file($root, $uri);
if ($file !== null) {
    require_once $file;
    exit;
}

http_response_code(404);
header('Content-Type: text/html; charset=utf-8');
echo '<!doctype html><html lang="fr"><head><meta charset="UTF-8"><title>404</title></head><body><h1>Page introuvable</h1></body></html>';
exit;
