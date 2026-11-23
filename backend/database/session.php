<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,  // Prevents JS access to session cookie (XSS protection)
        'cookie_samesite' => 'Lax', // Mitigates CSRF
        'use_strict_mode' => true,  // Rejects uninitialized session IDs
        // 'cookie_secure' => true, // Uncomment when running over HTTPS in production
    ]);
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

if (!function_exists('auth')) {
    function auth(): ?array
    {
        return isset($_SESSION['user']) && is_array($_SESSION['user'])
            ? $_SESSION['user']
            : null;
    }
}

if (!function_exists('e')) {
    function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('url')) {
    function url(string $path = '/'): string
    {
        $base = '/final_nooma';
        return rtrim($base, '/') . '/' . ltrim($path, '/');
    }
}