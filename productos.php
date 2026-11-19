<?php
require __DIR__ . '/includes/conexion.php';
require __DIR__ . '/includes/auth.php';
requiere_rol('admin');

$categorias = ['matcha' => 'Matcha', 'cafes' => 'Cafés', 'creamy' => 'Creamy Dreams'];
$imagenes = array_map('basename', array_merge(
    glob(__DIR__ . '/img/bebidas/*.png'),
    glob(__DIR__ . '/img/bebidas/*.jpg'),
    glob(__DIR__ . '/img/bebidas/*.webp')
));
sort($imagenes);

if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(16)); }
$mensaje = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);
$error = '';
$form = ['id' => 0, 'nombre' => '', 'categoria' => 'matcha', 'imagen' => '', 'precio' => '', 'descripcion' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) { die('Solicitud no válida.'); }
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'borrar') {
        $pdo->prepare('DELETE FROM productos WHERE id = ?')->execute([(int)($_POST['id'] ?? 0)]);
        $_SESSION['flash'] = 'Producto eliminado.';
        header('Location: productos.php'); exit;
    }

    if ($accion === 'guardar') {
        $form = [
            'id'          => (int)($_POST['id'] ?? 0),
            'nombre'      => trim($_POST['nombre'] ?? ''),
            'categoria'   => $_POST['categoria'] ?? '',
            'imagen'      => $_POST['imagen'] ?? '',
            'precio'      => $_POST['precio'] ?? '',
            'descripcion' => trim($_POST['descripcion'] ?? ''),
        ];
        if ($form['nombre'] === '' || !isset($categorias[$form['categoria']])
            || !in_array($form['imagen'], $imagenes, true)
            || !is_numeric($form['precio']) || $form['precio'] < 0) {
            $error = 'Revisa los datos: nombre, categoría, imagen y precio son obligatorios.';
        } else {
            $datos = [$form['categoria'], $form['nombre'], $form['descripcion'], $form['imagen'], $form['precio']];
            if ($form['id'] > 0) {
                $pdo->prepare('UPDATE productos SET categoria=?, nombre=?, descripcion=?, imagen=?, precio=? WHERE id=?')
                    ->execute(array_merge($datos, [$form['id']]));
                $_SESSION['flash'] = 'Producto actualizado.';
            } else {
                $pdo->prepare('INSERT INTO productos (categoria, nombre, descripcion, imagen, precio) VALUES (?,?,?,?,?)')
                    ->execute($datos);
                $_SESSION['flash'] = 'Producto agregado.';
            }
            header('Location: productos.php'); exit;
        }
    }
} elseif (isset($_GET['editar'])) {
    $st = $pdo->prepare('SELECT id, nombre, categoria, imagen, precio, descripcion FROM productos WHERE id = ?');
    $st->execute([(int)$_GET['editar']]);
    $fila = $st->fetch(PDO::FETCH_ASSOC);
    if ($fila) {
    foreach ($fila as $k => $v) { $form[$k] = ($v === null) ? '' : $v; }
}
}

$lista = $pdo->query('SELECT * FROM productos ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
$h = function ($t) { return htmlspecialchars((string)$t); };
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Productos | Kōcha Café</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
<header class="admin-nav">
  <a class="logo" href="home_admin.php">Kōcha<small>CAFÉ</small></a>
  <nav>
    <a href="home_admin.php">Panel</a>
    <a href="productos.php" class="activo">Productos</a>
  </nav>
  <a class="btn-pill" href="logout.php">Cerrar sesión</a>
</header>

<main class="admin-wrap">
  <h1>Productos</h1>
  <?php if ($mensaje): ?><div class="msg ok"><?= $h($mensaje) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="msg error"><?= $h($error) ?></div><?php endif; ?>

  <div class="admin-grid">
    <section class="panel">
      <h2><?= $form['id'] ? 'Editar bebida' : 'Nueva bebida' ?></h2>
      <form method="post">
        <input type="hidden" name="csrf" value="<?= $h($_SESSION['csrf']) ?>">
        <input type="hidden" name="accion" value="guardar">
        <input type="hidden" name="id" value="<?= (int)$form['id'] ?>">

        <label for="nombre">Nombre</label>
        <input type="text" id="nombre" name="nombre" value="<?= $h($form['nombre']) ?>" required>

        <label for="categoria">Categoría</label>
        <select id="categoria" name="categoria">
          <?php foreach ($categorias as $clave => $nombre): ?>
            <option value="<?= $clave ?>" <?= $form['categoria'] === $clave ? 'selected' : '' ?>><?= $h($nombre) ?></option>
          <?php endforeach; ?>
        </select>

        <label for="imagen">Imagen</label>
        <select id="imagen" name="imagen" required>
          <option value="">Elige una imagen…</option>
          <?php foreach ($imagenes as $img): ?>
            <option value="<?= $h($img) ?>" <?= $form['imagen'] === $img ? 'selected' : '' ?>><?= $h($img) ?></option>
          <?php endforeach; ?>
        </select>
        <div class="vista-previa"><img id="previa" alt="" <?= $form['imagen'] ? 'src="img/bebidas/' . rawurlencode($form['imagen']) . '"' : 'hidden' ?>></div>

        <label for="precio">Precio</label>
        <input type="number" id="precio" name="precio" step="0.01" min="0" value="<?= $h($form['precio']) ?>" required>

        <label for="descripcion">Descripción (opcional)</label>
        <textarea id="descripcion" name="descripcion"><?= $h($form['descripcion']) ?></textarea>

        <button class="btn" type="submit"><?= $form['id'] ? 'Guardar cambios' : 'Agregar' ?></button>
        <?php if ($form['id']): ?><p class="alt"><a href="productos.php">Cancelar edición</a></p><?php endif; ?>
      </form>
    </section>

    <section class="panel tabla-wrap">
      <h2>Menú actual (<?= count($lista) ?>)</h2>
      <table class="tabla">
        <thead><tr><th></th><th>Nombre</th><th>Categoría</th><th>Precio</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($lista as $p): ?>
          <tr>
            <td><img src="img/bebidas/<?= rawurlencode($p['imagen']) ?>" alt=""></td>
            <td><?= $h($p['nombre']) ?></td>
            <td><?= $h($categorias[$p['categoria']] ?? $p['categoria']) ?></td>
            <td>$<?= number_format((float)$p['precio'], 2) ?></td>
            <td class="acciones">
              <a class="btn-mini" href="productos.php?editar=<?= (int)$p['id'] ?>">Editar</a>
              <form method="post" onsubmit="return confirm('¿Eliminar esta bebida?')">
                <input type="hidden" name="csrf" value="<?= $h($_SESSION['csrf']) ?>">
                <input type="hidden" name="accion" value="borrar">
                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                <button class="btn-mini rojo" type="submit">Borrar</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </section>
  </div>
</main>

<script>
  const sel = document.getElementById('imagen'), previa = document.getElementById('previa');
  sel.addEventListener('change', () => {
    if (sel.value) { previa.src = 'img/bebidas/' + encodeURIComponent(sel.value); previa.hidden = false; }
    else { previa.hidden = true; }
  });
</script>
</body>
</html>