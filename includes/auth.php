<?php
declare(strict_types=1);

const SESSION_IDLE_LIMIT = 1800;

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('school_website_session');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();
}

if (isset($_SESSION['user_id'])) {
    $lastActivity = (int) ($_SESSION['last_activity'] ?? 0);
    if ($lastActivity > 0 && time() - $lastActivity > SESSION_IDLE_LIMIT) {
        $_SESSION = [];
        session_regenerate_id(true);
    } else {
        $_SESSION['last_activity'] = time();
    }
}

function csrf_token(): string
{
    if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function require_staff_api(): void
{
    if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'staff'], true)) {
        json_response(401, ['error' => 'Please sign in with an authorized staff account.']);
    }
}

function require_admin_page(): void
{
    if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'staff'], true)) {
        header('Location: ../login.php');
        exit;
    }
}

function verify_csrf_token(): void
{
    $provided = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($provided) || !hash_equals(csrf_token(), $provided)) {
        json_response(419, ['error' => 'Your security token expired. Refresh the page and try again.']);
    }
}