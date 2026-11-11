<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/classes/Database.php';

if (Database::driver() !== 'pgsql') {
    fwrite(STDERR, "Configurez DB_DRIVER=pgsql et les paramètres PostgreSQL Supabase avant la migration.\n");
    exit(1);
}

$sourceHost = (string)env('MYSQL_SOURCE_HOST', '');
$sourcePort = (int)env('MYSQL_SOURCE_PORT', 3306);
$sourceName = (string)env('MYSQL_SOURCE_DB', '');
$sourceUser = (string)env('MYSQL_SOURCE_USER', '');
$sourcePassword = (string)env('MYSQL_SOURCE_PASSWORD', '');

if ($sourceHost === '' || $sourceName === '' || $sourceUser === '') {
    fwrite(STDERR, "Renseignez MYSQL_SOURCE_HOST, MYSQL_SOURCE_DB et MYSQL_SOURCE_USER dans .env.\n");
    exit(1);
}

$tables = [
    'users',
    'categories',
    'products',
    'product_images',
    'creations',
    'creation_images',
    'favorites',
    'site_settings',
    'faq',
    'ai_conversations',
    'custom_requests',
];
$sensitiveUserColumns = [
    'password_hash',
    'verification_token',
    'reset_token',
    'reset_expires_at',
    'failed_login_attempts',
    'locked_until',
];

try {
    $source = new PDO(
        sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $sourceHost, $sourcePort, $sourceName),
        $sourceUser,
        $sourcePassword,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    $target = Database::getConnection();
    $source->beginTransaction();
    $target->beginTransaction();

    foreach (array_diff($tables, ['site_settings']) as $table) {
        $count = (int)$target->query('SELECT COUNT(*) FROM public."' . $table . '"')->fetchColumn();
        if ($count > 0) {
            throw new RuntimeException("La table Supabase {$table} n'est pas vide. La migration doit être faite avant de créer des données sur Supabase.");
        }
    }

    foreach ($tables as $table) {
        $sourceColumns = $source->query('SHOW COLUMNS FROM `' . $table . '`')->fetchAll(PDO::FETCH_COLUMN);
        $columnQuery = $target->prepare(
            "SELECT column_name FROM information_schema.columns
             WHERE table_schema = 'public' AND table_name = :table
             ORDER BY ordinal_position"
        );
        $columnQuery->execute([':table' => $table]);
        $targetColumns = $columnQuery->fetchAll(PDO::FETCH_COLUMN);
        $columns = array_values(array_intersect($sourceColumns, $targetColumns));

        if ($table === 'users') {
            $columns = array_values(array_diff($columns, $sensitiveUserColumns));
        }
        if (!in_array('id', $columns, true)) {
            throw new RuntimeException("La colonne id manque dans la table {$table}.");
        }

        $quotedColumns = array_map(static fn(string $column): string => '"' . $column . '"', $columns);
        $placeholders = array_map(static fn(int $index): string => ':v' . $index, array_keys($columns));
        $insert = $target->prepare(
            'INSERT INTO public."' . $table . '" (' . implode(', ', $quotedColumns) . ')
             VALUES (' . implode(', ', $placeholders) . ') ON CONFLICT DO NOTHING'
        );
        $select = $source->query('SELECT `' . implode('`, `', $columns) . '` FROM `' . $table . '`');
        $copied = 0;
        $skipped = 0;
        while (($row = $select->fetch()) !== false) {
            foreach ($columns as $index => $column) {
                $insert->bindValue(':v' . $index, $row[$column]);
            }
            $insert->execute();
            if ($insert->rowCount() > 0) {
                $copied++;
            } else {
                $skipped++;
            }
        }
        printf("%s: %d copié(s), %d ignoré(s) en cas de conflit.\n", $table, $copied, $skipped);
    }

    foreach ($tables as $table) {
        $target->query(
            "SELECT setval(
                pg_get_serial_sequence('public.{$table}', 'id'),
                COALESCE((SELECT MAX(id) FROM public.\"{$table}\"), 1),
                EXISTS (SELECT 1 FROM public.\"{$table}\")
            )"
        );
    }

    $bootstrapStatement = $source->query(
        "SELECT setting_value FROM site_settings WHERE setting_key = 'admin_bootstrap_claimed' LIMIT 1"
    );
    $bootstrapClaimed = (string)($bootstrapStatement->fetchColumn() ?: '0') === '1';
    $adminExists = (bool)$target->query("SELECT EXISTS (SELECT 1 FROM public.users WHERE role = 'admin')")->fetchColumn();
    if ($bootstrapClaimed || $adminExists) {
        $target->exec(
            "INSERT INTO public.site_settings (setting_key, setting_value, setting_group)
             VALUES ('admin_bootstrap_claimed', '1', 'system')
             ON CONFLICT (setting_key) DO UPDATE SET setting_value = '1'"
        );
    }

    $target->commit();
    $source->commit();
    fwrite(STDOUT, "Migration terminée. Les mots de passe et jetons PHP historiques n'ont pas été transférés.\n");
    fwrite(STDOUT, "Les utilisateurs devront s'inscrire ou récupérer leur accès via Supabase Auth.\n");
    fwrite(STDOUT, "Les anciens fichiers du dossier uploads/ restent sur le serveur et ne sont pas copiés par ce script.\n");
} catch (Throwable $e) {
    if (isset($target) && $target instanceof PDO && $target->inTransaction()) {
        $target->rollBack();
    }
    if (isset($source) && $source instanceof PDO && $source->inTransaction()) {
        $source->rollBack();
    }
    fwrite(STDERR, "Migration interrompue : " . $e->getMessage() . "\n");
    exit(1);
}
