<?php
require __DIR__ . '/includes/conexion.php';
$pdo->exec(file_get_contents(__DIR__ . '/database.sql'));
echo 'Tablas creadas correctamente.';