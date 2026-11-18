<?php
session_start();

function requiere_login() {
    if (empty($_SESSION['usuario'])) { header('Location: login.php'); exit; }
}

function requiere_rol($rol) {
    requiere_login();
    if ($_SESSION['usuario']['rol'] !== $rol) { header('Location: login.php'); exit; }
}

function ir_a_home() {
    $dest = ($_SESSION['usuario']['rol'] === 'admin') ? 'home_admin.php' : 'home_cliente.php';
    header("Location: $dest");
    exit;
}