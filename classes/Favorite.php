<?php
declare(strict_types=1);

require_once __DIR__ . '/Database.php';

/**
 * Classe Favorite — Gestion des favoris des clients (articles de boutique & modèles de créations)
 */
class Favorite
{
    public static function toggle(int $userId, string $itemType, int $itemId): array
    {
        $itemType = $itemType === 'creation' ? 'creation' : 'product';

        if ($itemType === 'product') {
            $existing = Database::fetchOne(
                "SELECT id FROM favorites WHERE user_id = :uid AND item_type = 'product' AND product_id = :pid LIMIT 1",
                [':uid' => $userId, ':pid' => $itemId]
            );
            if ($existing) {
                Database::query("DELETE FROM favorites WHERE id = :id", [':id' => $existing['id']]);
                return ['action' => 'removed', 'count' => self::countByUser($userId)];
            }
            Database::query(
                "INSERT INTO favorites (user_id, item_type, product_id, created_at) VALUES (:uid, 'product', :pid, NOW())",
                [':uid' => $userId, ':pid' => $itemId]
            );
            return ['action' => 'added', 'count' => self::countByUser($userId)];
        }

        $existing = Database::fetchOne(
            "SELECT id FROM favorites WHERE user_id = :uid AND item_type = 'creation' AND creation_id = :cid LIMIT 1",
            [':uid' => $userId, ':cid' => $itemId]
        );
        if ($existing) {
            Database::query("DELETE FROM favorites WHERE id = :id", [':id' => $existing['id']]);
            return ['action' => 'removed', 'count' => self::countByUser($userId)];
        }
        Database::query(
            "INSERT INTO favorites (user_id, item_type, creation_id, created_at) VALUES (:uid, 'creation', :cid, NOW())",
            [':uid' => $userId, ':cid' => $itemId]
        );
        return ['action' => 'added', 'count' => self::countByUser($userId)];
    }

    public static function isFavorited(int $userId, string $itemType, int $itemId): bool
    {
        if ($userId <= 0) {
            return false;
        }
        if ($itemType === 'creation') {
            $row = Database::fetchOne(
                "SELECT id FROM favorites WHERE user_id = :uid AND item_type = 'creation' AND creation_id = :cid LIMIT 1",
                [':uid' => $userId, ':cid' => $itemId]
            );
            return $row !== null;
        }
        $row = Database::fetchOne(
            "SELECT id FROM favorites WHERE user_id = :uid AND item_type = 'product' AND product_id = :pid LIMIT 1",
            [':uid' => $userId, ':pid' => $itemId]
        );
        return $row !== null;
    }

    public static function countByUser(int $userId): int
    {
        if ($userId <= 0) {
            return 0;
        }
        $row = Database::fetchOne("SELECT COUNT(*) AS cnt FROM favorites WHERE user_id = :uid", [':uid' => $userId]);
        return (int)($row['cnt'] ?? 0);
    }

    public static function getUserFavorites(int $userId): array
    {
        $products = Database::fetchAll(
            "SELECT f.id AS favorite_id, f.created_at AS favorited_at, 'product' AS item_type,
                    p.*, c.name_fr AS category_name_fr, c.name_en AS category_name_en,
                    COALESCE(
                        (SELECT pi.image_path FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.is_primary DESC LIMIT 1),
                        'assets/images/placeholder-product.svg'
                    ) AS primary_image
             FROM favorites f
             INNER JOIN products p ON p.id = f.product_id
             INNER JOIN categories c ON c.id = p.category_id
             WHERE f.user_id = :uid AND f.item_type = 'product'
             ORDER BY f.created_at DESC",
            [':uid' => $userId]
        );

        $creations = Database::fetchAll(
            "SELECT f.id AS favorite_id, f.created_at AS favorited_at, 'creation' AS item_type,
                    cr.*, c.name_fr AS category_name_fr, c.name_en AS category_name_en,
                    COALESCE(
                        (SELECT ci.image_path FROM creation_images ci WHERE ci.creation_id = cr.id ORDER BY ci.is_primary DESC LIMIT 1),
                        'assets/images/placeholder-creation.svg'
                    ) AS primary_image
             FROM favorites f
             INNER JOIN creations cr ON cr.id = f.creation_id
             INNER JOIN categories c ON c.id = cr.category_id
             WHERE f.user_id = :uid AND f.item_type = 'creation'
             ORDER BY f.created_at DESC",
            [':uid' => $userId]
        );

        return [
            'products'  => $products,
            'creations' => $creations,
        ];
    }
}
