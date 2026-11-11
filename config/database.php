<?php
declare(strict_types=1);

require_once __DIR__ . '/app.php';

$driver = strtolower((string)env('DB_DRIVER', 'mysql'));
if (!in_array($driver, ['mysql', 'pgsql'], true)) {
    throw new RuntimeException('DB_DRIVER doit être mysql ou pgsql.');
}

return [
    'driver'   => $driver,
    'host'     => getenv('DB_HOST') ?: '127.0.0.1',
    'port'     => (int) (getenv('DB_PORT') ?: ($driver === 'pgsql' ? '5432' : '3306')),
    'database' => getenv('DB_NAME') ?: 'drl_creations',
    'username' => getenv('DB_USER') ?: 'root',
    'password' => (string)env('DB_PASSWORD', env('DB_PASS', '')),
    'sslmode'  => (string)env('DB_SSLMODE', $driver === 'pgsql' ? 'require' : 'prefer'),
    'charset'  => 'utf8mb4',
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ],
];
