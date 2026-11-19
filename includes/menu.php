<?php
$enlaces = [
  ['home_cliente.php', 'Inicio',    ''],
  ['home_cliente.php', 'Menú',      '#menu'],
  ['mapa.php',         'Ubicación', ''],
  ['blog.php',         'Blog',      ''],
  ['videos.php',       'Videos',    ''],
  ['encuesta.php',     'Encuesta',  ''],
  ['camara.php',       'Cámara',    ''],
];
$actual = basename($_SERVER['SCRIPT_NAME']);
?>
<header class="nav">
  <a class="logo" href="home_cliente.php">Kōcha<small>CAFÉ</small></a>
  <nav>
    <?php foreach ($enlaces as $e): ?>
      <?php if (file_exists(__DIR__ . '/../' . $e[0])): ?>
        <a href="<?= $e[0] . $e[2] ?>" class="<?= ($actual === $e[0] && $e[2] === '') ? 'activo' : '' ?>"><?= $e[1] ?></a>
      <?php endif; ?>
    <?php endforeach; ?>
  </nav>
  <a class="btn-pill" href="logout.php">Cerrar sesión</a>
</header>