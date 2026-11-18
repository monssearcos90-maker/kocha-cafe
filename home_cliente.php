<?php
require __DIR__ . '/includes/auth.php';
requiere_rol('cliente');

$categorias = [
  'matcha' => ['nombre' => 'Matcha',        'hero' => 'matcha-latte.png'],
  'cafes'  => ['nombre' => 'Cafés',         'hero' => 'iced-coffee.png'],
  'creamy' => ['nombre' => 'Creamy Dreams', 'hero' => 'taro-latte.png'],
];

// [categoría, nombre, archivo, precio]  (los precios son de ejemplo)
$productos = [
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

function img_bebida($archivo) { return 'img/bebidas/' . rawurlencode($archivo); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Kōcha Café</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/estilos.css">
  <script src="js/carrusel.js" defer></script>
</head>
<body>

<header class="nav">
  <a href="home_cliente.php"><img class="nav-logo" src="img/logo/logo.png" alt="Kōcha Café"></a>
  <nav>
    <a href="#inicio">Inicio</a>
    <a href="#menu">Menú</a>
    <a href="#contacto">Contacto</a>
  </nav>
  <a class="btn-pill" href="logout.php">Cerrar sesión</a>
</header>

<section class="hero" id="inicio" data-cat="matcha">
  <div class="hero-texto">
    <h1>Sip.<br>Relax.<br><span>Repeat.</span></h1>
    <p>Hola, <?= htmlspecialchars($_SESSION['usuario']['nombre']) ?>. Matcha, café y bebidas cremosas hechas para tu momento.</p>
    <div class="hero-botones">
      <a class="btn-pill" href="#menu">Ver menú</a>
      <a class="btn-borde" href="#menu">Ordenar</a>
    </div>
    <div class="hero-tabs">
      <?php $i = 0; foreach ($categorias as $clave => $c): ?>
        <button type="button" data-cat="<?= $clave ?>" class="<?= $i++ === 0 ? 'activo' : '' ?>"><?= $c['nombre'] ?></button>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="hero-escena">
    <div class="circulo"></div>
    <span class="vertical" id="vertical">MATCHA</span>
    <?php $i = 0; foreach ($categorias as $clave => $c): ?>
      <img class="hero-bebida <?= $i++ === 0 ? 'activa' : '' ?>" data-cat="<?= $clave ?>" src="<?= img_bebida($c['hero']) ?>" alt="<?= $c['nombre'] ?>">
    <?php endforeach; ?>
  </div>
</section>

<section class="favoritos" id="menu">
  <div class="fav-cab">
    <div>
      <small>BEST SELLERS</small>
      <h2>Tus favoritos<br>aquí mismo</h2>
    </div>
    <div class="filtros">
      <button type="button" data-filtro="todos" class="activo">Todos</button>
      <?php foreach ($categorias as $clave => $c): ?>
        <button type="button" data-filtro="<?= $clave ?>"><?= $c['nombre'] ?></button>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="carrusel-wrap">
    <button type="button" class="flecha" id="prev" aria-label="Anterior">‹</button>
    <div class="carrusel" id="carrusel">
      <?php foreach ($productos as [$cat, $nombre, $archivo, $precio]): ?>
        <article class="tarjeta" data-cat="<?= $cat ?>">
          <div class="tarjeta-img"><img src="<?= img_bebida($archivo) ?>" alt="<?= htmlspecialchars($nombre) ?>" loading="lazy"></div>
          <h3><?= htmlspecialchars($nombre) ?></h3>
          <div class="tarjeta-pie">
            <span>$<?= number_format($precio, 2) ?></span>
            <button type="button" class="mas" aria-label="Agregar">+</button>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
    <button type="button" class="flecha" id="next" aria-label="Siguiente">›</button>
  </div>
</section>

<section class="banner">
  <img src="img/banners/escape-diario.png" alt="Tu escape diario">
</section>

<footer class="pie" id="contacto">
  <span>© 2026 Kōcha Café</span>
  <span>Sip. Relax. Repeat.</span>
</footer>

</body>
</html>