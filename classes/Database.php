<?php
declare(strict_types=1);

/**
 * Classe Database — Gestionnaire PDO Singleton avec support des transactions SQL
 */
class Database
{
    private static ?PDO $instance = null;

    private function __construct() {}
    private function __clone() {}

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $config = require DRL_ROOT . '/config/database.php';
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $config['host'],
                $config['port'],
                $config['database'],
                $config['charset']
            );

            try {
                self::$instance = new PDO($dsn, $config['username'], $config['password'], $config['options']);
            } catch (PDOException $e) {
                error_log('[DRL Database Error] ' . $e->getMessage());
                if (defined('APP_DEBUG') && APP_DEBUG) {
                    throw new RuntimeException('Erreur de connexion MySQL : ' . $e->getMessage());
                }
                throw new RuntimeException(
                    'La connexion à la base de données MySQL est momentanément indisponible. Vérifiez que vous avez importé database/drl_creations.sql et configuré le fichier .env.'
                );
            }
        }

        return self::$instance;
    }

    /**
     * Exécute une requête préparée et retourne le PDOStatement.
     *
     * @param string $sql
     * @param array<int|string,mixed> $params
     */
    public static function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    public static function fetchOne(string $sql, array $params = []): ?array
    {
        $row = self::query($sql, $params)->fetch();
        return $row !== false ? $row : null;
    }

    public static function lastInsertId(): string
    {
        return self::getConnection()->lastInsertId();
    }

    public static function beginTransaction(): bool
    {
        return self::getConnection()->beginTransaction();
    }

    public static function commit(): bool
    {
        return self::getConnection()->commit();
    }

    public static function rollBack(): bool
    {
        if (self::getConnection()->inTransaction()) {
            return self::getConnection()->rollBack();
        }
        return false;
    }
}
