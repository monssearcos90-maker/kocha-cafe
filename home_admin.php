<?php
require __DIR__ . '/includes/conexion.php';
require __DIR__ . '/includes/auth.php';
requiere_rol('admin');
require __DIR__ . '/includes/tablas.php';

$h = function ($t) { return htmlspecialchars((string)$t); };
$nombres = ['matcha' => 'Matcha', 'cafes' => 'Cafés', 'creamy' => 'Creamy Dreams'];

$clientes  = (int)$pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'cliente'")->fetchColumn();
$productos = (int)$pdo->query('SELECT COUNT(*) FROM productos')->fetchColumn();
$promedio  = (float)$pdo->query('SELECT COALESCE(AVG(precio), 0) FROM productos')->fetchColumn();
$porCat    = $pdo->query('SELECT categoria, COUNT(*) AS total FROM productos GROUP BY categoria ORDER BY categoria')->fetchAll(PDO::FETCH_ASSOC);
$ultimos   = $pdo->query('SELECT nombre, email, rol, creado_en FROM usuarios ORDER BY id DESC LIMIT 5')->fetchAll(PDO::FETCH_ASSOC);

$max = 1;
foreach ($porCat as $f) { if ((int)$f['total'] > $max) { $max = (int)$f['total']; } }

$totalEnc    = (int)$pdo->query('SELECT COUNT(*) FROM encuestas')->fetchColumn();
$califProm   = (float)$pdo->query('SELECT COALESCE(AVG(calificacion), 0) FROM encuestas')->fetchColumn();
$recomiendan = (int)$pdo->query('SELECT COUNT(*) FROM encuestas WHERE recomienda')->fetchColumn();
$pctRec      = $totalEnc ? round($recomiendan / $totalEnc * 100) : 0;
$favoritas   = $pdo->query('SELECT bebida, COUNT(*) AS total FROM encuestas GROUP BY bebida ORDER BY total DESC')->fetchAll(PDO::FETCH_ASSOC);
$comentarios = $pdo->query("SELECT u.nombre, e.comentario FROM encuestas e JOIN usuarios u ON u.id = e.usuario_id WHERE e.comentario <> '' ORDER BY e.id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
$maxFav = 1;
foreach ($favoritas as $f) { if ((int)$f['total'] > $maxFav) { $maxFav = (int)$f['total']; } }
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Panel | Kōcha Café</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
<header class="admin-nav">
  <a class="logo" href="home_admin.php">Kōcha<small>CAFÉ</small></a>
  <nav>
    <a href="home_admin.php" class="activo">Panel</a>
    <a href="productos.php">Productos</a>
  </nav>
  <a class="btn-pill" href="logout.php">Cerrar sesión</a>
</header>

<main class="admin-wrap">
  <h1>Hola, <?= $h($_SESSION['usuario']['nombre']) ?></h1>

  <section class="stats">
    <div class="stat"><small>Clientes registrados</small><strong><?= $clientes ?></strong></div>
    <div class="stat"><small>Bebidas en el menú</small><strong><?= $productos ?></strong></div>
    <div class="stat"><small>Precio promedio</small><strong>$<?= number_format($promedio, 2) ?></strong></div>
    <div class="stat"><small>Categorías</small><strong><?= count($porCat) ?></strong></div>
  </section>

  <div class="dos-col">
    <section class="panel">
      <h2>Bebidas por categoría</h2>
      <?php foreach ($porCat as $f): ?>
        <div class="barra-fila">
          <span><?= $h($nombres[$f['categoria']] ?? $f['categoria']) ?> <b><?= (int)$f['total'] ?></b></span>
          <div class="barra"><i style="width:<?= round(((int)$f['total'] / $max) * 100) ?>%"></i></div>
        </div>
      <?php endforeach; ?>
      <p class="alt"><a href="productos.php">Administrar productos →</a></p>
    </section>

    <section class="panel tabla-wrap">
      <h2>Últimos registros</h2>
      <table class="tabla">
        <thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Fecha</th></tr></thead>
        <tbody>
        <?php foreach ($ultimos as $u): ?>
          <tr>
            <td><?= $h($u['nombre']) ?></td>
            <td><?= $h($u['email']) ?></td>
            <td><?= $h($u['rol']) ?></td>
            <td><?= $h(date('d/m/Y', strtotime($u['creado_en']))) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </section>
  </div>

  <h2 class="subtitulo">Encuesta de satisfacción</h2>
  <section class="stats">
    <div class="stat"><small>Respuestas</small><strong><?= $totalEnc ?></strong></div>
    <div class="stat"><small>Calificación promedio</small><strong><?= number_format($califProm, 1) ?>/5</strong></div>
    <div class="stat"><small>Lo recomendarían</small><strong><?= $pctRec ?>%</strong></div>
  </section>

  <div class="dos-col">
    <section class="panel">
      <h2>Bebida favorita</h2>
      <?php if (!$favoritas): ?><p class="sub">Aún no hay respuestas.</p><?php endif; ?>
      <?php foreach ($favoritas as $f): ?>
        <div class="barra-fila">
          <span><?= $h($nombres[$f['bebida']] ?? $f['bebida']) ?> <b><?= (int)$f['total'] ?></b></span>
          <div class="barra"><i style="width:<?= round(((int)$f['total'] / $maxFav) * 100) ?>%"></i></div>
        </div>
      <?php endforeach; ?>
    </section>

    <section class="panel">
      <h2>Últimos comentarios</h2>
      <?php if (!$comentarios): ?><p class="sub">Aún no hay comentarios.</p><?php endif; ?>
      <?php foreach ($comentarios as $c): ?>
        <p class="comentario"><b><?= $h($c['nombre']) ?>:</b> <?= $h($c['comentario']) ?></p>
      <?php endforeach; ?>
    </section>
  </div>
</main>
</body>
</html>