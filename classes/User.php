<?php
declare(strict_types=1);

require_once __DIR__ . '/Database.php';

/**
 * Classe User — Gestion des profils clients, demandes personnalisées et administration des comptes
 */
class User
{
    public static function findById(int $id): ?array
    {
        return Database::fetchOne(
            "SELECT id, first_name, last_name, email, phone, avatar, role, oauth_provider, email_verified_at, preferred_lang, preferred_theme, is_active, last_login_at, created_at
             FROM users WHERE id = :id LIMIT 1",
            [':id' => $id]
        );
    }

    public static function findByEmail(string $email): ?array
    {
        return Database::fetchOne(
            "SELECT * FROM users WHERE email = :email LIMIT 1",
            [':email' => strtolower(trim($email))]
        );
    }

    public static function updateProfile(int $userId, string $firstName, string $lastName, string $phone): bool
    {
        $stmt = Database::query(
            "UPDATE users
             SET first_name = :first_name, last_name = :last_name, phone = :phone, updated_at = NOW()
             WHERE id = :id",
            [
                ':first_name' => trim($firstName),
                ':last_name'  => trim($lastName),
                ':phone'      => trim($phone),
                ':id'         => $userId,
            ]
        );
        if (isset($_SESSION['user']) && (int)$_SESSION['user']['id'] === $userId) {
            $_SESSION['user']['first_name'] = trim($firstName);
            $_SESSION['user']['last_name']  = trim($lastName);
            $_SESSION['user']['phone']      = trim($phone);
        }
        return $stmt->rowCount() >= 0;
    }

    public static function updateAvatar(int $userId, string $avatarPath): bool
    {
        Database::query(
            "UPDATE users SET avatar = :avatar, updated_at = NOW() WHERE id = :id",
            [':avatar' => $avatarPath, ':id' => $userId]
        );
        if (isset($_SESSION['user']) && (int)$_SESSION['user']['id'] === $userId) {
            $_SESSION['user']['avatar'] = $avatarPath;
        }
        return true;
    }

    public static function updatePreferences(int $userId, string $lang, string $theme): bool
    {
        $lang = in_array($lang, ['fr', 'en'], true) ? $lang : 'fr';
        $theme = in_array($theme, ['light', 'dark'], true) ? $theme : 'light';
        Database::query(
            "UPDATE users SET preferred_lang = :lang, preferred_theme = :theme, updated_at = NOW() WHERE id = :id",
            [':lang' => $lang, ':theme' => $theme, ':id' => $userId]
        );
        return true;
    }

    public static function updatePassword(int $userId, string $currentPassword, string $newPassword): array
    {
        $user = Database::fetchOne("SELECT password_hash FROM users WHERE id = :id LIMIT 1", [':id' => $userId]);
        if (!$user) {
            return ['success' => false, 'error' => 'Utilisateur introuvable.'];
        }
        if (!empty($user['password_hash']) && !password_verify($currentPassword, (string)$user['password_hash'])) {
            return ['success' => false, 'error' => 'Le mot de passe actuel est incorrect.'];
        }
        if (strlen($newPassword) < 8) {
            return ['success' => false, 'error' => 'Le nouveau mot de passe doit contenir au moins 8 caractères.'];
        }
        $newHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        Database::query(
            "UPDATE users SET password_hash = :hash, updated_at = NOW() WHERE id = :id",
            [':hash' => $newHash, ':id' => $userId]
        );
        return ['success' => true];
    }

    public static function getClientRequests(int $userId, string $email): array
    {
        return Database::fetchAll(
            "SELECT r.*, c.name_fr AS creation_name, c.reference AS creation_ref,
                    p.name_fr AS product_name, p.reference AS product_ref
             FROM custom_requests r
             LEFT JOIN creations c ON c.id = r.creation_id
             LEFT JOIN products p ON p.id = r.product_id
             WHERE r.user_id = :uid OR r.email = :email
             ORDER BY r.created_at DESC",
            [':uid' => $userId, ':email' => $email]
        );
    }

    public static function getAllClients(): array
    {
        return Database::fetchAll(
            "SELECT u.id, u.first_name, u.last_name, u.email, u.phone, u.avatar, u.role,
                    u.oauth_provider, u.email_verified_at, u.is_active, u.last_login_at, u.created_at,
                    (SELECT COUNT(*) FROM favorites f WHERE f.user_id = u.id) AS favorites_count,
                    (SELECT COUNT(*) FROM custom_requests cr WHERE cr.user_id = u.id) AS requests_count
             FROM users u
             ORDER BY u.created_at DESC"
        );
    }

    public static function toggleActiveStatus(int $userId): bool
    {
        Database::query(
            "UPDATE users SET is_active = IF(is_active = 1, 0, 1), updated_at = NOW() WHERE id = :id AND role != 'admin'",
            [':id' => $userId]
        );
        return true;
    }

    public static function deleteClient(int $userId): bool
    {
        Database::query("DELETE FROM users WHERE id = :id AND role != 'admin'", [':id' => $userId]);
        return true;
    }
}
