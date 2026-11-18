<?php
require __DIR__ . '/includes/conexion.php';

$email = getenv('ADMIN_EMAIL');
$pass  = getenv('ADMIN_PASSWORD');
if (!$email || !$pass) {
    die('Faltan las variables ADMIN_EMAIL y ADMIN_PASSWORD.');
}

$hash = password_hash($pass, PASSWORD_DEFAULT);
$st = $pdo->prepare('SELECT id FROM usuarios WHERE email = ?');
$st->execute([$email]);

if ($st->fetch()) {
    $pdo->prepare("UPDATE usuarios SET rol = 'admin', password = ? WHERE email = ?")
        ->execute([$hash, $email]);
} else {
    $pdo->prepare("INSERT INTO usuarios (nombre, email, password, rol) VALUES ('Administrador', ?, ?, 'admin')")
        ->execute([$email, $hash]);
}
echo 'Administrador listo.';