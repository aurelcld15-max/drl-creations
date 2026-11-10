<?php
declare(strict_types=1);

require_once __DIR__ . '/Database.php';

/**
 * Classe Product — Gestion complète des articles de la Boutique (3 modes de tarification,
 * galerie multi-photos, gestion des articles vendus/archivés/masqués et transactions SQL).
 */
class Product
{
    /**
     * Liste les articles disponibles en boutique (un article vendu, archivé ou masqué n'apparaît pas).
     */
    public static function getAvailableProducts(?int $categoryId = null, ?string $pricingMode = null, ?string $search = null, ?int $limit = null): array
    {
        $sql = "SELECT p.*, c.name_fr AS category_name_fr, c.name_en AS category_name_en, c.slug AS category_slug,
                       COALESCE(
                           (SELECT pi.image_path FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.is_primary DESC, pi.sort_order ASC LIMIT 1),
                           'assets/images/placeholder-product.svg'
                       ) AS primary_image
                FROM products p
                INNER JOIN categories c ON c.id = p.category_id
                WHERE p.availability_status = 'available'";
        $params = [];

        if ($categoryId !== null && $categoryId > 0) {
            $sql .= " AND p.category_id = :cat_id";
            $params[':cat_id'] = $categoryId;
        }

        if ($pricingMode !== null && in_array($pricingMode, ['fixed', 'negotiable', 'on_request'], true)) {
            $sql .= " AND p.pricing_mode = :pmode";
            $params[':pmode'] = $pricingMode;
        }

        if ($search !== null && trim($search) !== '') {
            $q = '%' . trim($search) . '%';
            $sql .= " AND (p.name_fr LIKE :q1 OR p.name_en LIKE :q2 OR p.reference LIKE :q3 OR p.description_fr LIKE :q4)";
            $params[':q1'] = $q;
            $params[':q2'] = $q;
            $params[':q3'] = $q;
            $params[':q4'] = $q;
        }

        $sql .= " ORDER BY p.is_featured DESC, p.created_at DESC";
        if ($limit !== null && $limit > 0) {
            $sql .= " LIMIT " . (int)$limit;
        }

        return Database::fetchAll($sql, $params);
    }

    /**
     * Récupère tous les articles pour l'administration (incluant vendus, archivés et masqués).
     */
    public static function getAllForAdmin(?string $statusFilter = null): array
    {
        $sql = "SELECT p.*, c.name_fr AS category_name_fr,
                       COALESCE(
                           (SELECT pi.image_path FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.is_primary DESC, pi.sort_order ASC LIMIT 1),
                           'assets/images/placeholder-product.svg'
                       ) AS primary_image
                FROM products p
                INNER JOIN categories c ON c.id = p.category_id";
        $params = [];

        if ($statusFilter !== null && in_array($statusFilter, ['available', 'sold', 'archived', 'hidden'], true)) {
            $sql .= " WHERE p.availability_status = :st";
            $params[':st'] = $statusFilter;
        }

        $sql .= " ORDER BY p.created_at DESC";
        return Database::fetchAll($sql, $params);
    }

    public static function findById(int $id, bool $includeNonAvailable = false): ?array
    {
        $sql = "SELECT p.*, c.name_fr AS category_name_fr, c.name_en AS category_name_en, c.slug AS category_slug
                FROM products p
                INNER JOIN categories c ON c.id = p.category_id
                WHERE p.id = :id";
        if (!$includeNonAvailable) {
            $sql .= " AND p.availability_status = 'available'";
        }
        $sql .= " LIMIT 1";

        $product = Database::fetchOne($sql, [':id' => $id]);
        if (!$product) {
            return null;
        }

        $product['images'] = self::getImages((int)$product['id']);
        return $product;
    }

    public static function getImages(int $productId): array
    {
        $imgs = Database::fetchAll(
            "SELECT * FROM product_images WHERE product_id = :pid ORDER BY is_primary DESC, sort_order ASC, id ASC",
            [':pid' => $productId]
        );
        if (empty($imgs)) {
            return [[
                'id'         => 0,
                'product_id' => $productId,
                'image_path' => 'assets/images/placeholder-product.svg',
                'alt_text'   => 'DRL CRÉATIONS',
                'is_primary' => 1,
            ]];
        }
        return $imgs;
    }

    /**
     * Formate l'affichage du prix selon les 3 modes de tarification (fixed, negotiable, on_request).
     */
    public static function formatPriceDisplay(array $product, string $lang = 'fr'): string
    {
        $mode  = (string)($product['pricing_mode'] ?? 'fixed');
        $price = $product['price'] !== null ? (float)$product['price'] : null;
        $curr  = (string)($product['currency'] ?? 'FCFA');

        if ($mode === 'on_request' || $price === null || $price <= 0) {
            return $lang === 'en' ? 'Price on request' : 'Prix sur demande';
        }

        $formatted = number_format($price, 0, ',', ' ') . ' ' . $curr;
        if ($mode === 'negotiable') {
            return $formatted . ($lang === 'en' ? ' (Negotiable)' : ' (Prix négociable)');
        }
        return $formatted;
    }

    /**
     * Ajoute un nouvel article avec transaction SQL et galerie photographique.
     */
    public static function create(array $data, array $uploadedImagePaths = []): int
    {
        Database::beginTransaction();
        try {
            $ref = !empty($data['reference']) ? strtoupper(trim((string)$data['reference'])) : ('DRL-BTQ-' . random_int(100, 999));
            $slug = self::slugify((string)$data['name_fr'] . '-' . $ref);
            $pricingMode = in_array($data['pricing_mode'] ?? '', ['fixed', 'negotiable', 'on_request'], true)
                ? $data['pricing_mode']
                : 'fixed';
            $price = ($pricingMode === 'on_request' || $data['price'] === '' || $data['price'] === null)
                ? null
                : (float)$data['price'];

            Database::query(
                "INSERT INTO products
                 (category_id, reference, slug, name_fr, name_en, description_fr, description_en, fabric_details, sizes_available, price, currency, pricing_mode, availability_status, is_featured, created_at)
                 VALUES
                 (:cat, :ref, :slug, :nfr, :nen, :dfr, :den, :fab, :sizes, :price, :curr, :pmode, :status, :feat, NOW())",
                [
                    ':cat'    => (int)$data['category_id'],
                    ':ref'    => $ref,
                    ':slug'   => $slug,
                    ':nfr'    => trim((string)$data['name_fr']),
                    ':nen'    => trim((string)($data['name_en'] ?: $data['name_fr'])),
                    ':dfr'    => trim((string)$data['description_fr']),
                    ':den'    => trim((string)($data['description_en'] ?: $data['description_fr'])),
                    ':fab'    => trim((string)($data['fabric_details'] ?? '')),
                    ':sizes'  => trim((string)($data['sizes_available'] ?? 'S, M, L, Sur-mesure')),
                    ':price'  => $price,
                    ':curr'   => trim((string)($data['currency'] ?? 'FCFA')),
                    ':pmode'  => $pricingMode,
                    ':status' => in_array($data['availability_status'] ?? '', ['available', 'sold', 'archived', 'hidden'], true)
                        ? $data['availability_status']
                        : 'available',
                    ':feat'   => !empty($data['is_featured']) ? 1 : 0,
                ]
            );

            $productId = (int)Database::lastInsertId();

            foreach ($uploadedImagePaths as $idx => $path) {
                Database::query(
                    "INSERT INTO product_images (product_id, image_path, alt_text, is_primary, sort_order)
                     VALUES (:pid, :path, :alt, :prim, :sort)",
                    [
                        ':pid'  => $productId,
                        ':path' => $path,
                        ':alt'  => trim((string)$data['name_fr']),
                        ':prim' => $idx === 0 ? 1 : 0,
                        ':sort' => $idx + 1,
                    ]
                );
            }

            Database::commit();
            return $productId;
        } catch (Throwable $e) {
            Database::rollBack();
            throw $e;
        }
    }

    /**
     * Met à jour un article existant et ajoute d'éventuelles nouvelles photos.
     */
    public static function update(int $id, array $data, array $newImagePaths = []): bool
    {
        Database::beginTransaction();
        try {
            $pricingMode = in_array($data['pricing_mode'] ?? '', ['fixed', 'negotiable', 'on_request'], true)
                ? $data['pricing_mode']
                : 'fixed';
            $price = ($pricingMode === 'on_request' || $data['price'] === '' || $data['price'] === null)
                ? null
                : (float)$data['price'];

            Database::query(
                "UPDATE products
                 SET category_id = :cat,
                     reference = :ref,
                     name_fr = :nfr,
                     name_en = :nen,
                     description_fr = :dfr,
                     description_en = :den,
                     fabric_details = :fab,
                     sizes_available = :sizes,
                     price = :price,
                     pricing_mode = :pmode,
                     availability_status = :status,
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
                    ':fab'    => trim((string)($data['fabric_details'] ?? '')),
                    ':sizes'  => trim((string)($data['sizes_available'] ?? 'S, M, L, Sur-mesure')),
                    ':price'  => $price,
                    ':pmode'  => $pricingMode,
                    ':status' => in_array($data['availability_status'] ?? '', ['available', 'sold', 'archived', 'hidden'], true)
                        ? $data['availability_status']
                        : 'available',
                    ':feat'   => !empty($data['is_featured']) ? 1 : 0,
                    ':id'     => $id,
                ]
            );

            foreach ($newImagePaths as $idx => $path) {
                Database::query(
                    "INSERT INTO product_images (product_id, image_path, alt_text, is_primary, sort_order)
                     VALUES (:pid, :path, :alt, 0, :sort)",
                    [
                        ':pid'  => $id,
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

    /**
     * Change le statut de disponibilité (available, sold, archived, hidden).
     */
    public static function updateStatus(int $id, string $status): bool
    {
        if (!in_array($status, ['available', 'sold', 'archived', 'hidden'], true)) {
            return false;
        }
        Database::query(
            "UPDATE products SET availability_status = :status, updated_at = NOW() WHERE id = :id",
            [':status' => $status, ':id' => $id]
        );
        return true;
    }

    public static function delete(int $id): bool
    {
        Database::query("DELETE FROM products WHERE id = :id", [':id' => $id]);
        return true;
    }

    private static function slugify(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/i', '-', $text);
        return trim((string)$text, '-') ?: ('article-' . bin2hex(random_bytes(4)));
    }
}
