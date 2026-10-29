<?php
declare(strict_types=1);

function database_connection(): PDO
{
    static $connection = null;

    if ($connection instanceof PDO) {
        return $connection;
    }

    $host = getenv('SCHOOL_DB_HOST') ?: '127.0.0.1';
    $port = getenv('SCHOOL_DB_PORT') ?: '3306';
    $database = getenv('SCHOOL_DB_NAME') ?: 'school_website';
    $username = getenv('SCHOOL_DB_USER') ?: 'root';
    $password = getenv('SCHOOL_DB_PASSWORD');
    $password = $password === false ? '' : $password;

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $host,
        $port,
        $database
    );

    $connection = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $connection;
}