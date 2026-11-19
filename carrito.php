<?php
require __DIR__ . '/includes/conexion.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/tablas.php';
requiere_rol('cliente');

if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(16)); }
if (!isset($_SESSION['carrito']) || !is_array($_SESSION['carrito'])) { $_SESSION['carrito'] = []; }
$uid = (int)$_SESSION['usuario']['id'];
$h = function ($t) { return htmlspecialchars((string)$t); };

function cargar_carrito($pdo) {
    $ids = array_keys($_SESSION['carrito']);
    if (!$ids) { return []; }
    $marcas = implode(',', array_fill(0, count($ids), '?'));
    $st = $pdo->prepare("SELECT id, nombre, imagen, precio FROM productos WHERE id IN ($marcas)");
    $st->execute($ids);
    $items = [];
    $nuevo = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $p) {
        $p['cantidad'] = (int)$_SESSION['carrito'][$p['id']];
        $items[] = $p;
        $nuevo[(int)$p['id']] = $p['cantidad'];
    }
    $_SESSION['carrito'] = $nuevo;
    return $items;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) { http_response_code(403); die('Solicitud no válida.'); }
    $accion = $_POST['accion'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($accion === 'agregar') {
        $st = $pdo->prepare('SELECT 1 FROM productos WHERE id = ?');
        $st->execute([$id]);
        $ok = (bool)$st->fetchColumn();
        if ($ok) { $_SESSION['carrito'][$id] = min(20, ($_SESSION['carrito'][$id] ?? 0) + 1); }
        header('Content-Type: application/json');
        echo json_encode(['ok' => $ok, 'total' => array_sum($_SESSION['carrito'])]);
        exit;
    }

    if ($accion === 'sumar' && isset($_SESSION['carrito'][$id])) {
        $_SESSION['carrito'][$id] = min(20, $_SESSION['carrito'][$id] + 1);
    }
    if ($accion === 'restar' && isset($_SESSION['carrito'][$id])) {
        $_SESSION['carrito'][$id]--;
        if ($_SESSION['carrito'][$id] <= 0) { unset($_SESSION['carrito'][$id]); }
    }
    if ($accion === 'quitar') { unset($_SESSION['carrito'][$id]); }
    if ($accion === 'vaciar') { $_SESSION['carrito'] = []; }

    if ($accion === 'comprar') {
        $items = cargar_carrito($pdo);
        if (!$items) {
            $_SESSION['flash_error'] = 'Tu carrito está vacío.';
        } else {
            $total = 0;
            foreach ($items as $it) { $total += $it['precio'] * $it['cantidad']; }
            try {
                $pdo->beginTransaction();
                $st = $pdo->prepare('INSERT INTO pedidos (usuario_id, total) VALUES (?, ?) RETURNING id');
                $st->execute([$uid, $total]);
                $pid = (int)$st->fetchColumn();
                $ins = $pdo->prepare('INSERT INTO pedido_items (pedido_id, nombre, precio, cantidad) VALUES (?, ?, ?, ?)');
                foreach ($items as $it) { $ins->execute([$pid, $it['nombre'], $it['precio'], $it['cantidad']]); }
                $pdo->commit();
                $_SESSION['carrito'] = [];
                $_SESSION['flash'] = '¡Pedido #' . $pid . ' realizado! Pásalo a recoger a la tienda.';
            } catch (Exception $e) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                $_SESSION['flash_error'] = 'No se pudo realizar el pedido. Inténtalo de nuevo.';
            }
        }
    }
    header('Location: carrito.php'); exit;
}

$mensaje = $_SESSION['flash'] ?? '';
$error   = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash'], $_SESSION['flash_error']);

$items = cargar_carrito($pdo);
$total = 0;
foreach ($items as $it) { $total += $it['precio'] * $it['cantidad']; }

$st = $pdo->prepare('SELECT id, total, estado, creado_en FROM pedidos WHERE usuario_id = ? ORDER BY id DESC LIMIT 5');
$st->execute([$uid]);
$pedidos = $st->fetchAll(PDO::FETCH_ASSOC);
$stItems = $pdo->prepare('SELECT nombre, cantidad FROM pedido_items WHERE pedido_id = ?');
$utc = new DateTimeZone('UTC');
$mx  = new DateTimeZone('America/Mexico_City');

function boton_carrito($accion, $id, $texto, $clase, $csrf) {
    return '<form method="post" class="inline"><input type="hidden" name="csrf" value="' . $csrf . '">'
         . '<input type="hidden" name="accion" value="' . $accion . '"><input type="hidden" name="id" value="' . (int)$id . '">'
         . '<button type="submit" class="' . $clase . '">' . $texto . '</button></form>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Carrito | Kōcha Café</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
<?php include __DIR__ . '/includes/menu.php'; ?>

<main class="pagina">
  <h1>Tu carrito</h1>
  <?php if ($mensaje): ?><div class="msg ok"><?= $h($mensaje) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="msg error"><?= $h($error) ?></div><?php endif; ?>

  <?php if (!$items): ?>
    <p class="sub">Tu carrito está vacío. <a href="home_cliente.php#menu" style="color:var(--verde);font-weight:600">Ver el menú</a></p>
  <?php else: ?>
    <section class="panel tabla-wrap">
      <table class="tabla">
        <thead><tr><th></th><th>Bebida</th><th>Precio</th><th>Cantidad</th><th>Subtotal</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($items as $it): ?>
          <tr>
            <td><img src="img/bebidas/<?= rawurlencode($it['imagen']) ?>" alt=""></td>
            <td><?= $h($it['nombre']) ?></td>
            <td>$<?= number_format((float)$it['precio'], 2) ?></td>
            <td class="cantidad">
              <?= boton_carrito('restar', $it['id'], '−', 'mas', $h($_SESSION['csrf'])) ?>
              <b><?= (int)$it['cantidad'] ?></b>
              <?= boton_carrito('sumar', $it['id'], '+', 'mas', $h($_SESSION['csrf'])) ?>
            </td>
            <td>$<?= number_format($it['precio'] * $it['cantidad'], 2) ?></td>
            <td><?= boton_carrito('quitar', $it['id'], 'Quitar', 'btn-mini rojo', $h($_SESSION['csrf'])) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <div class="carrito-total">
        <span>Total</span><strong>$<?= number_format($total, 2) ?></strong>
      </div>
      <div class="carrito-botones">
        <?= boton_carrito('vaciar', 0, 'Vaciar carrito', 'btn-mini', $h($_SESSION['csrf'])) ?>
        <?= boton_carrito('comprar', 0, 'Realizar pedido', 'btn-pill', $h($_SESSION['csrf'])) ?>
      </div>
      <p class="sub">El pago se hace en la tienda al recoger tu pedido.</p>
    </section>
  <?php endif; ?>

  <?php if ($pedidos): ?>
    <h2 class="subtitulo">Mis pedidos</h2>
    <div class="pedidos">
      <?php foreach ($pedidos as $p): ?>
        <?php $stItems->execute([$p['id']]); $lineas = $stItems->fetchAll(PDO::FETCH_ASSOC); ?>
        <article class="pedido">
          <div class="pedido-cab">
            <b>Pedido #<?= (int)$p['id'] ?></b>
            <small><?= $h((new DateTime($p['creado_en'], $utc))->setTimezone($mx)->format('d/m/Y H:i')) ?></small>
            <span class="estado <?= $h($p['estado']) ?>"><?= $h($p['estado']) ?></span>
          </div>
          <ul>
            <?php foreach ($lineas as $l): ?><li><?= (int)$l['cantidad'] ?> × <?= $h($l['nombre']) ?></li><?php endforeach; ?>
          </ul>
          <p><b>Total: $<?= number_format((float)$p['total'], 2) ?></b></p>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>
</body>
</html>