<?php
declare(strict_types=1);

require_once __DIR__ . '/Database.php';

/**
 * Classe Creation — Gestion des modèles réalisables par l'atelier (Espace Nos Créations)
 */
class Creation
{
    public static function getPublishedCreations(?int $categoryId = null, ?string $search = null, ?int $limit = null): array
    {
        $sql = "SELECT cr.*, c.name_fr AS category_name_fr, c.name_en AS category_name_en, c.slug AS category_slug,
                       COALESCE(
                           (SELECT ci.image_path FROM creation_images ci WHERE ci.creation_id = cr.id ORDER BY ci.is_primary DESC, ci.sort_order ASC LIMIT 1),
                           'assets/images/placeholder-creation.svg'
                       ) AS primary_image
                FROM creations cr
                INNER JOIN categories c ON c.id = cr.category_id
                WHERE cr.publication_status = 'published'";
        $params = [];

        if ($categoryId !== null && $categoryId > 0) {
            $sql .= " AND cr.category_id = :cat_id";
            $params[':cat_id'] = $categoryId;
        }

        if ($search !== null && trim($search) !== '') {
            $q = '%' . trim($search) . '%';
            $sql .= " AND (cr.name_fr LIKE :q1 OR cr.name_en LIKE :q2 OR cr.reference LIKE :q3 OR cr.description_fr LIKE :q4)";
            $params[':q1'] = $q;
            $params[':q2'] = $q;
            $params[':q3'] = $q;
            $params[':q4'] = $q;
        }

        $sql .= " ORDER BY cr.is_featured DESC, cr.created_at DESC";
        if ($limit !== null && $limit > 0) {
            $sql .= " LIMIT " . (int)$limit;
        }

        return Database::fetchAll($sql, $params);
    }

    public static function getAllForAdmin(?string $statusFilter = null): array
    {
        $sql = "SELECT cr.*, c.name_fr AS category_name_fr,
                       COALESCE(
                           (SELECT ci.image_path FROM creation_images ci WHERE ci.creation_id = cr.id ORDER BY ci.is_primary DESC, ci.sort_order ASC LIMIT 1),
                           'assets/images/placeholder-creation.svg'
                       ) AS primary_image
                FROM creations cr
                INNER JOIN categories c ON c.id = cr.category_id";
        $params = [];

        if ($statusFilter !== null && in_array($statusFilter, ['published', 'unpublished', 'draft'], true)) {
            $sql .= " WHERE cr.publication_status = :st";
            $params[':st'] = $statusFilter;
        }

        $sql .= " ORDER BY cr.created_at DESC";
        return Database::fetchAll($sql, $params);
    }

    public static function findById(int $id, bool $includeUnpublished = false): ?array
    {
        $sql = "SELECT cr.*, c.name_fr AS category_name_fr, c.name_en AS category_name_en, c.slug AS category_slug
                FROM creations cr
                INNER JOIN categories c ON c.id = cr.category_id
                WHERE cr.id = :id";
        if (!$includeUnpublished) {
            $sql .= " AND cr.publication_status = 'published'";
        }
        $sql .= " LIMIT 1";

        $creation = Database::fetchOne($sql, [':id' => $id]);
        if (!$creation) {
            return null;
        }

        $creation['images'] = self::getImages((int)$creation['id']);
        return $creation;
    }

    public static function getImages(int $creationId): array
    {
        $imgs = Database::fetchAll(
            "SELECT * FROM creation_images WHERE creation_id = :cid ORDER BY is_primary DESC, sort_order ASC, id ASC",
            [':cid' => $creationId]
        );
        if (empty($imgs)) {
            return [[
                'id'          => 0,
                'creation_id' => $creationId,
                'image_path'  => 'assets/images/placeholder-creation.svg',
                'alt_text'    => 'DRL CRÉATIONS',
                'is_primary'  => 1,
            ]];
        }
        return $imgs;
    }

    public static function create(array $data, array $uploadedImagePaths = []): int
    {
        Database::beginTransaction();
        try {
            $ref = !empty($data['reference']) ? strtoupper(trim((string)$data['reference'])) : ('DRL-MOD-' . random_int(100, 999));
            $slug = self::slugify((string)$data['name_fr'] . '-' . $ref);
            $indicativePrice = ($data['indicative_price'] ?? '') !== '' && $data['indicative_price'] !== null
                ? (float)$data['indicative_price']
                : null;

            Database::query(
                "INSERT INTO creations
                 (category_id, reference, slug, name_fr, name_en, description_fr, description_en, estimated_delay, customizable_options, indicative_price, publication_status, is_featured, created_at)
                 VALUES
                 (:cat, :ref, :slug, :nfr, :nen, :dfr, :den, :delay, :opts, :price, :status, :feat, NOW())",
                [
                    ':cat'    => (int)$data['category_id'],
                    ':ref'    => $ref,
                    ':slug'   => $slug,
                    ':nfr'    => trim((string)$data['name_fr']),
                    ':nen'    => trim((string)($data['name_en'] ?: $data['name_fr'])),
                    ':dfr'    => trim((string)$data['description_fr']),
                    ':den'    => trim((string)($data['description_en'] ?: $data['description_fr'])),
                    ':delay'  => trim((string)($data['estimated_delay'] ?? '7 à 14 jours ouvrés')),
                    ':opts'   => trim((string)($data['customizable_options'] ?? 'Tissu, Couleur, Mensurations sur-mesure')),
                    ':price'  => $indicativePrice,
                    ':status' => in_array($data['publication_status'] ?? '', ['published', 'unpublished', 'draft'], true)
                        ? $data['publication_status']
                        : 'published',
                    ':feat'   => !empty($data['is_featured']) ? 1 : 0,
                ]
            );

            $creationId = (int)Database::lastInsertId();

            foreach ($uploadedImagePaths as $idx => $path) {
                Database::query(
                    "INSERT INTO creation_images (creation_id, image_path, alt_text, is_primary, sort_order)
                     VALUES (:cid, :path, :alt, :prim, :sort)",
                    [
                        ':cid'  => $creationId,
                        ':path' => $path,
                        ':alt'  => trim((string)$data['name_fr']),
                        ':prim' => $idx === 0 ? 1 : 0,
                        ':sort' => $idx + 1,
                    ]
                );
            }

            Database::commit();
            return $creationId;
        } catch (Throwable $e) {
            Database::rollBack();
            throw $e;
        }
    }

    public static function update(int $id, array $data, array $newImagePaths = []): bool
    {
        Database::beginTransaction();
        try {
            $indicativePrice = ($data['indicative_price'] ?? '') !== '' && $data['indicative_price'] !== null
                ? (float)$data['indicative_price']
                : null;

            Database::query(
                "UPDATE creations
                 SET category_id = :cat,
                     reference = :ref,
                     name_fr = :nfr,
                     name_en = :nen,
                     description_fr = :dfr,
                     description_en = :den,
                     estimated_delay = :delay,
                     customizable_options = :opts,
                     indicative_price = :price,
                     publication_status = :status,
                     is_featured = :feat,
                     updated_at = NOW()
                 WHERE id = :id",
                [
                    ':cat'    => (int)$data['category_id'],
                    ':ref'    => strtoupper(trim((string)$data['reference'])),
                    ':nfr'    => trim((string)$data['name_fr']),
                    ':nen'    => trim((string)($data['name_en'] ?: $data['name_fr'])),
                    ':dfr'    => trim((string)$data['description_fr']),
                    ':den'    => trim((string)($data['description_en'] ?: $data['description_fr'])),
                    ':delay'  => trim((string)($data['estimated_delay'] ?? '7 à 14 jours ouvrés')),
                    ':opts'   => trim((string)($data['customizable_options'] ?? 'Tissu, Couleur, Mensurations sur-mesure')),
                    ':price'  => $indicativePrice,
                    ':status' => in_array($data['publication_status'] ?? '', ['published', 'unpublished', 'draft'], true)
                        ? $data['publication_status']
                        : 'published',
                    ':feat'   => !empty($data['is_featured']) ? 1 : 0,
                    ':id'     => $id,
                ]
            );

            foreach ($newImagePaths as $idx => $path) {
                Database::query(
                    "INSERT INTO creation_images (creation_id, image_path, alt_text, is_primary, sort_order)
                     VALUES (:cid, :path, :alt, 0, :sort)",
                    [
                        ':cid'  => $id,
                        ':path' => $path,
                        ':alt'  => trim((string)$data['name_fr']),
                        ':sort' => $idx + 10,
                    ]
                );
            }

            Database::commit();
            return true;
        } catch (Throwable $e) {
            Database::rollBack();
            throw $e;
        }
    }

    public static function updateStatus(int $id, string $status): bool
    {
        if (!in_array($status, ['published', 'unpublished', 'draft'], true)) {
            return false;
        }
        Database::query(
            "UPDATE creations SET publication_status = :status, updated_at = NOW() WHERE id = :id",
            [':status' => $status, ':id' => $id]
        );
        return true;
    }

    public static function delete(int $id): bool
    {
        Database::query("DELETE FROM creations WHERE id = :id", [':id' => $id]);
        return true;
    }

    private static function slugify(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/i', '-', $text);
        return trim((string)$text, '-') ?: ('modele-' . bin2hex(random_bytes(4)));
    }
}
