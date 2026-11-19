<?php
require __DIR__ . '/includes/conexion.php';

$pdo->exec("ALTER TABLE productos ADD COLUMN IF NOT EXISTS categoria VARCHAR(20) NOT NULL DEFAULT 'matcha'");

if ($pdo->query('SELECT COUNT(*) FROM productos')->fetchColumn() == 0) {
    $lista = [
        ['matcha', 'Blueberry Matcha',     'blueberry-matcha.png',     70],
        ['matcha', 'Coconut Matcha',       'coconut.matcha.png',       70],
        ['matcha', 'Mango Matcha',         'mango-matcha.png',         70],
        ['matcha', 'Strawberry Matcha',    'strawberry-matcha.png',    75],
        ['matcha', 'Rose Matcha',          'rose-matcha.png',          75],
        ['cafes',  'Iced Vanilla Latte',   'Iced-vanilla-latte.png',   65],
        ['cafes',  'Caramel Macchiato',    'caramel-macchiato.png',    68],
        ['cafes',  'Brown Sugar Latte',    'brown-sugar-latte.png',    68],
        ['cafes',  'Honey Cinnamon Latte', 'honey-cinnamon-latte.png', 70],
        ['cafes',  'Spanish Latte',        'spanish-latte.png',        65],
        ['creamy', 'Taro Latte',           'taro-latte.png',           72],
        ['creamy', 'Pistachio Latte',      'pistachio-latte.png',      78],
        ['creamy', 'Strawberry Cream',     'strawberry-cream.png',     72],
        ['creamy', 'Cookies & Cream',      'cookies-&-cream.png',      75],
        ['creamy', 'Chai Latte',           'chai-latte.png',           68],
    ];
    $st = $pdo->prepare('INSERT INTO productos (categoria, nombre, imagen, precio) VALUES (?, ?, ?, ?)');
    foreach ($lista as $p) { $st->execute($p); }
}
echo 'Migración lista.';