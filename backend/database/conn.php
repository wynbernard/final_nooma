<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $host = '127.0.0.1';
        $name = 'final_nooma'; // Replace with your actual database name
        $user = 'root';     // Replace with your DB username
        $pass = '';         // Replace with your DB password

        $dsn = "mysql:host={$host};dbname={$name};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false, // Enforces real prepared statements
        ];

        $pdo = new PDO($dsn, $user, $pass, $options);
    }

    return $pdo;
}