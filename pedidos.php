<?php
require __DIR__ . '/includes/conexion.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/tablas.php';
requiere_rol('admin');

if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(16)); }
$h = function ($t) { return htmlspecialchars((string)$t); };

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) { die('Solicitud no válida.'); }
    $estado = $_POST['estado'] ?? '';
    if (in_array($estado, ['pendiente', 'entregado', 'cancelado'], true)) {
        $pdo->prepare('UPDATE pedidos SET estado = ? WHERE id = ?')->execute([$estado, (int)($_POST['id'] ?? 0)]);
    }
    header('Location: pedidos.php'); exit;
}

$pedidos = $pdo->query("SELECT p.id, p.total, p.estado, p.creado_en, u.nombre
    FROM pedidos p JOIN usuarios u ON u.id = p.usuario_id
    ORDER BY (p.estado = 'pendiente') DESC, p.id DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
$stItems = $pdo->prepare('SELECT nombre, cantidad FROM pedido_items WHERE pedido_id = ?');
$utc = new DateTimeZone('UTC');
$mx  = new DateTimeZone('America/Mexico_City');

function boton_estado($id, $estado, $texto, $clase, $csrf) {
    return '<form method="post" class="inline"><input type="hidden" name="csrf" value="' . $csrf . '">'
         . '<input type="hidden" name="id" value="' . (int)$id . '"><input type="hidden" name="estado" value="' . $estado . '">'
         . '<button type="submit" class="' . $clase . '">' . $texto . '</button></form>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pedidos | Kōcha Café</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
<header class="admin-nav">
  <a class="logo" href="home_admin.php">Kōcha<small>CAFÉ</small></a>
  <nav>
    <a href="home_admin.php">Panel</a>
    <a href="productos.php">Productos</a>
    <a href="pedidos.php" class="activo">Pedidos</a>
    <a href="blog.php">Blog</a>
  </nav>
  <a class="btn-pill" href="logout.php">Cerrar sesión</a>
</header>

<main class="admin-wrap">
  <h1>Pedidos</h1>
  <?php if (!$pedidos): ?><p class="sub">Todavía no hay pedidos.</p><?php endif; ?>

  <div class="pedidos">
    <?php foreach ($pedidos as $p): ?>
      <?php $stItems->execute([$p['id']]); $lineas = $stItems->fetchAll(PDO::FETCH_ASSOC); ?>
      <article class="pedido">
        <div class="pedido-cab">
          <b>#<?= (int)$p['id'] ?> · <?= $h($p['nombre']) ?></b>
          <span class="estado <?= $h($p['estado']) ?>"><?= $h($p['estado']) ?></span>
        </div>
        <small><?= $h((new DateTime($p['creado_en'], $utc))->setTimezone($mx)->format('d/m/Y H:i')) ?></small>
        <ul>
          <?php foreach ($lineas as $l): ?><li><?= (int)$l['cantidad'] ?> × <?= $h($l['nombre']) ?></li><?php endforeach; ?>
        </ul>
        <p><b>Total: $<?= number_format((float)$p['total'], 2) ?></b></p>
        <div class="acciones">
          <?php if ($p['estado'] !== 'entregado'): ?><?= boton_estado($p['id'], 'entregado', 'Marcar entregado', 'btn-mini', $h($_SESSION['csrf'])) ?><?php endif; ?>
          <?php if ($p['estado'] !== 'cancelado'): ?><?= boton_estado($p['id'], 'cancelado', 'Cancelar', 'btn-mini rojo', $h($_SESSION['csrf'])) ?><?php endif; ?>
          <?php if ($p['estado'] !== 'pendiente'): ?><?= boton_estado($p['id'], 'pendiente', 'Volver a pendiente', 'btn-mini', $h($_SESSION['csrf'])) ?><?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</main>
</body>
</html>