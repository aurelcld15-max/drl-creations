<?php
declare(strict_types=1);

require_once __DIR__ . '/app.php';

/**
 * DRL CRÉATIONS — Sécurité des sessions, en-têtes HTTP, CSRF, XSS et Upload MIME
 */

if (session_status() === PHP_SESSION_NONE) {
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_name('DRL_SESSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// En-têtes de sécurité HTTP
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-XSS-Protection: 1; mode=block');
}

// Régénération périodique de l'ID de session contre la fixation de session
if (!isset($_SESSION['_last_regen'])) {
    $_SESSION['_last_regen'] = time();
} elseif (time() - (int)$_SESSION['_last_regen'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['_last_regen'] = time();
}

/**
 * Génère ou retourne le jeton CSRF de la session courante.
 */
function csrf_token(): string
{
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return (string)$_SESSION['_csrf_token'];
}

/**
 * Retourne un champ HTML hidden contenant le jeton CSRF.
 */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Vérifie la validité du jeton CSRF transmis en POST ou dans l'en-tête X-CSRF-Token.
 */
function verify_csrf(?string $token = null): bool
{
    $token = $token ?? ($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
    if (empty($_SESSION['_csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals((string)$_SESSION['_csrf_token'], (string)$token);
}

/**
 * Échappement strict HTML anti-XSS.
 */
function e(mixed $value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Validation sécurisée et enregistrement d'une image téléversée (vérification MIME réelle via finfo + getimagesize).
 *
 * @param array<string,mixed> $file Entrée issue de $_FILES
 * @param string $subDir Sous-dossier dans uploads/ ('products', 'creations', 'profiles')
 * @param int $maxSizeBytes Taille maximale (par défaut 5 Mo)
 * @return array{success:bool, path?:string, error?:string}
 */
function secure_image_upload(array $file, string $subDir, int $maxSizeBytes = 5242880): array
{
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'error' => 'Paramètres de fichier invalides.'];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Erreur lors du transfert du fichier (code ' . (int)$file['error'] . ').'];
    }
    if ((int)$file['size'] <= 0 || (int)$file['size'] > $maxSizeBytes) {
        return ['success' => false, 'error' => 'La taille du fichier dépasse la limite autorisée (max 5 Mo).'];
    }
    if (!is_uploaded_file((string)$file['tmp_name'])) {
        return ['success' => false, 'error' => 'Fichier téléversé non authentifié.'];
    }

    // Vérification MIME réelle via finfo
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $realMime = $finfo->file((string)$file['tmp_name']);
    $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($allowedMimes[$realMime])) {
        return ['success' => false, 'error' => "Format d'image non autorisé. Utilisez JPG, PNG ou WEBP."];
    }

    // Double vérification structurelle de l'image
    $imgInfo = @getimagesize((string)$file['tmp_name']);
    if ($imgInfo === false || $imgInfo[0] < 20 || $imgInfo[1] < 20) {
        return ['success' => false, 'error' => 'Le fichier ne correspond pas à une image valide.'];
    }

    $allowedSubDirs = ['products', 'creations', 'profiles'];
    if (!in_array($subDir, $allowedSubDirs, true)) {
        return ['success' => false, 'error' => 'Dossier de destination non autorisé.'];
    }

    $targetDir = DRL_ROOT . '/uploads/' . $subDir;
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $ext = $allowedMimes[$realMime];
    $uniqueName = 'drl_' . $subDir . '_' . bin2hex(random_bytes(12)) . '.' . $ext;
    $destination = $targetDir . '/' . $uniqueName;

    if (!move_uploaded_file((string)$file['tmp_name'], $destination)) {
        return ['success' => false, 'error' => "Impossible d'enregistrer l'image sur le serveur."];
    }

    @chmod($destination, 0644);
    if ((string)env('SUPABASE_URL', '') !== '') {
        require_once DRL_ROOT . '/classes/SupabaseClient.php';
        $accessToken = (string)($_SESSION['supabase_access_token'] ?? '');
        $supabaseUid = (string)($_SESSION['supabase_uid'] ?? '');
        if ($accessToken === '' || $supabaseUid === '') {
            unlink($destination);
            return ['success' => false, 'error' => 'Votre session Supabase a expiré. Reconnectez-vous avant de téléverser une image.'];
        }

        $objectPath = $subDir === 'profiles'
            ? $subDir . '/' . rawurlencode($supabaseUid) . '/' . $uniqueName
            : $subDir . '/' . $uniqueName;
        try {
            $publicUrl = SupabaseClient::upload($destination, $objectPath, (string)$realMime, $accessToken);
            if (!unlink($destination)) {
                error_log('[DRL Supabase Storage] Impossible de supprimer le fichier temporaire ' . $destination);
            }
            return ['success' => true, 'path' => $publicUrl];
        } catch (Throwable $e) {
            error_log('[DRL Supabase Storage upload] ' . $e->getMessage());
            unlink($destination);
            return ['success' => false, 'error' => 'Le transfert vers Supabase Storage a échoué. Vérifiez la configuration du bucket et réessayez.'];
        }
    }

    return [
        'success' => true,
        'path'    => 'uploads/' . $subDir . '/' . $uniqueName,
    ];
}
