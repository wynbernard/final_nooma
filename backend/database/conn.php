<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $localConfig = [];
        $localConfigPath = __DIR__ . '/config.local.php';
        if (is_file($localConfigPath)) {
            $loadedConfig = require $localConfigPath;
            if (!is_array($loadedConfig)) {
                throw new RuntimeException('Database config.local.php must return an array.');
            }
            $localConfig = $loadedConfig;
        }

        $setting = static function (string $environmentName, string $configName, $default = null) use ($localConfig) {
            $environmentValue = getenv($environmentName);
            if ($environmentValue !== false) {
                return $environmentValue;
            }

            return $localConfig[$configName] ?? $default;
        };

        $host = $setting('DB_HOST', 'host');
        $name = $setting('DB_NAME', 'database');
        $user = $setting('DB_USER', 'username');
        $pass = $setting('DB_PASSWORD', 'password');
        $port = filter_var(
            $setting('DB_PORT', 'port', 3306),
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1, 'max_range' => 65535]]
        );

        if (
            !is_string($host) || trim($host) === '' ||
            !is_string($name) || trim($name) === '' ||
            !is_string($user) || trim($user) === '' ||
            !is_string($pass) ||
            $port === false
        ) {
            throw new RuntimeException(
                'Database configuration is incomplete. Set DB_HOST, DB_NAME, DB_USER, and DB_PASSWORD, or add backend/database/config.local.php.'
            );
        }

        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false, // Enforces real prepared statements
        ];

        $pdo = new PDO($dsn, $user, $pass, $options);
    }

    return $pdo;
}