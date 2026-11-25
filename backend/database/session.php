<?php
declare(strict_types=1);

if (!function_exists('isHttpsRequest')) {
    function isHttpsRequest(): bool
    {
        return (
            !empty($_SERVER['HTTPS']) &&
            strtolower((string) $_SERVER['HTTPS']) !== 'off'
        ) || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_secure' => isHttpsRequest(),
        'cookie_httponly' => true,  // Prevents JS access to session cookie (XSS protection)
        'cookie_samesite' => 'Lax', // Mitigates CSRF
        'use_strict_mode' => true,  // Rejects uninitialized session IDs
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
        $base = getenv('APP_BASE_PATH');

        if ($base === false) {
            $base = '';
            $documentRoot = realpath((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''));
            $projectRoot = realpath(dirname(__DIR__, 2));

            if ($documentRoot !== false && $projectRoot !== false) {
                $documentRoot = rtrim(str_replace('\\', '/', $documentRoot), '/');
                $projectRoot = rtrim(str_replace('\\', '/', $projectRoot), '/');

                if (strcasecmp($projectRoot, $documentRoot) === 0) {
                    $base = '';
                } elseif (stripos($projectRoot, $documentRoot . '/') === 0) {
                    $base = substr($projectRoot, strlen($documentRoot));
                }
            }
        }

        $base = trim(str_replace('\\', '/', $base), '/');
        return ($base !== '' ? '/' . $base : '') . '/' . ltrim($path, '/');
    }
}