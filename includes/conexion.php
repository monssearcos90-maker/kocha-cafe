<?php
$url = parse_url(getenv('DATABASE_URL'));
$dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s',
    $url['host'], $url['port'] ?? 5432, ltrim($url['path'], '/'));
try {
    $pdo = new PDO($dsn, $url['user'], $url['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die('Error de conexión a la base de datos.');
}