<?php
// ─── JWT + CSRF stateless helpers ────────────────────────────────────────────
// Vercel is serverless — PHP sessions are not reliable across invocations.
// Auth state lives in a signed HttpOnly JWT cookie; CSRF uses HMAC time-windows.

function _auth_secret(): string {
    return getenv('AUTH_SECRET') ?: 'crimson_default_secret_change_me_32x';
}

function _b64url_enc(string $d): string {
    return rtrim(strtr(base64_encode($d), '+/', '-_'), '=');
}

function _b64url_dec(string $d): string {
    return base64_decode(strtr($d, '-_', '+/') . str_repeat('=', (4 - strlen($d) % 4) % 4));
}

function jwt_sign(array $payload): string {
    $h   = _b64url_enc(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
    $b   = _b64url_enc(json_encode($payload));
    $sig = _b64url_enc(hash_hmac('sha256', "$h.$b", _auth_secret(), true));
    return "$h.$b.$sig";
}

function jwt_verify(string $token): ?array {
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;
    [$h, $b, $sig] = $parts;
    $expected = _b64url_enc(hash_hmac('sha256', "$h.$b", _auth_secret(), true));
    if (!hash_equals($expected, $sig)) return null;
    $payload = json_decode(_b64url_dec($b), true);
    if (!$payload) return null;
    if (isset($payload['exp']) && $payload['exp'] < time()) return null;
    return $payload;
}

function auth_get_user(): ?array {
    $token = $_COOKIE['auth_token'] ?? '';
    if (!$token) return null;
    return jwt_verify($token);
}

function auth_set_cookie(array $user): void {
    $exp = time() + 8 * 3600;
    $token = jwt_sign([
        'id'      => $user['id'],
        'usuario' => $user['usuario'],
        'rol'     => $user['rol'],
        'exp'     => $exp,
    ]);
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    setcookie('auth_token', $token, [
        'expires'  => $exp,
        'path'     => '/',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
}

function auth_clear(): void {
    setcookie('auth_token', '', [
        'expires'  => time() - 3600,
        'path'     => '/',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
}

// Stateless CSRF — valid for the current and previous 2-hour window.
// No server-side storage needed; works across Vercel function instances.
function csrf_token_generate(): string {
    $window = (int)(time() / 7200);
    return hash_hmac('sha256', "csrf:$window", _auth_secret());
}

function csrf_token_verify(string $token): bool {
    if (!$token) return false;
    $window = (int)(time() / 7200);
    return hash_equals(hash_hmac('sha256', "csrf:$window", _auth_secret()), $token)
        || hash_equals(hash_hmac('sha256', "csrf:" . ($window - 1), _auth_secret()), $token);
}

// ─── Web guard ────────────────────────────────────────────────────────────────
// Skipped when AUTH_NO_GUARD is defined (used by login.php, api.php, logout.php).
if (!defined('AUTH_NO_GUARD')) {
    $__auth_user = auth_get_user();
    if (!$__auth_user) {
        header('Location: login.php');
        exit;
    }
    // Populate $_SESSION for the current request only (not persisted across requests).
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => true, 'httponly' => true, 'samesite' => 'Strict']);
        session_start();
    }
    $_SESSION['user']       = $__auth_user;
    $_SESSION['csrf_token'] = csrf_token_generate();
}
