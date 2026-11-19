<?php
require __DIR__ . '/includes/conexion.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/tablas.php';
requiere_login();

if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(16)); }
$uid = (int)$_SESSION['usuario']['id'];
$esAdmin = $_SESSION['usuario']['rol'] === 'admin';
$h = function ($t) { return htmlspecialchars((string)$t); };

function responder($datos) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        http_response_code(403);
        responder(['error' => 'Solicitud no válida.']);
    }
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'enviar') {
        $texto = trim($_POST['texto'] ?? '');
        $largo = mb_strlen($texto);
        if ($largo < 1 || $largo > 300) { responder(['error' => 'Escribe un mensaje de hasta 300 caracteres.']); }
        if (time() - ($_SESSION['ultimo_msg'] ?? 0) < 2) { responder(['error' => 'Espera un momento antes de enviar otro mensaje.']); }
        $pdo->prepare('INSERT INTO mensajes (usuario_id, texto) VALUES (?, ?)')->execute([$uid, $texto]);
        $_SESSION['ultimo_msg'] = time();
        responder(['ok' => true]);
    }

    if ($accion === 'borrar') {
        $id = (int)($_POST['id'] ?? 0);
        if ($esAdmin) {
            $pdo->prepare('DELETE FROM mensajes WHERE id = ?')->execute([$id]);
        } else {
            $pdo->prepare('DELETE FROM mensajes WHERE id = ? AND usuario_id = ?')->execute([$id, $uid]);
        }
        responder(['ok' => true]);
    }
    responder(['error' => 'Acción no válida.']);
}

if (isset($_GET['ajax'])) {
    $filas = $pdo->query('SELECT m.id, m.texto, m.creado_en, m.usuario_id, u.nombre, u.rol
        FROM (SELECT * FROM mensajes ORDER BY id DESC LIMIT 50) m
        JOIN usuarios u ON u.id = m.usuario_id
        ORDER BY m.id ASC')->fetchAll(PDO::FETCH_ASSOC);

    $utc = new DateTimeZone('UTC');
    $mx  = new DateTimeZone('America/Mexico_City');
    $salida = [];
    foreach ($filas as $f) {
        $hora = (new DateTime($f['creado_en'], $utc))->setTimezone($mx)->format('d/m H:i');
        $salida[] = [
            'id'         => (int)$f['id'],
            'nombre'     => $f['nombre'],
            'rol'        => $f['rol'],
            'texto'      => $f['texto'],
            'hora'       => $hora,
            'mio'        => (int)$f['usuario_id'] === $uid,
            'puedeBorrar'=> $esAdmin || (int)$f['usuario_id'] === $uid,
        ];
    }
    responder(['mensajes' => $salida]);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Blog | Kōcha Café</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
<?php if ($esAdmin): ?>
<header class="admin-nav">
  <a class="logo" href="home_admin.php">Kōcha<small>CAFÉ</small></a>
  <nav>
    <a href="home_admin.php">Panel</a>
    <a href="productos.php">Productos</a>
    <a href="blog.php" class="activo">Blog</a>
  </nav>
  <a class="btn-pill" href="logout.php">Cerrar sesión</a>
</header>
<?php else: include __DIR__ . '/includes/menu.php'; endif; ?>

<main class="pagina">
  <h1>Blog de Kōcha Café</h1>
  <p class="sub">Comparte tu bebida favorita, recomendaciones o saluda a la comunidad.</p>

  <section class="panel chat">
    <div id="mensajes" class="mensajes" aria-live="polite"></div>
    <form id="form-chat" class="chat-form" autocomplete="off">
      <input type="text" id="texto" maxlength="300" placeholder="Escribe un mensaje…" required>
      <button type="submit" class="btn">Enviar</button>
    </form>
    <p id="aviso" class="sub"></p>
  </section>
</main>

<script>
  const csrf = '<?= $h($_SESSION['csrf']) ?>';
  const lista = document.getElementById('mensajes');
  const form = document.getElementById('form-chat');
  const entrada = document.getElementById('texto');
  const aviso = document.getElementById('aviso');

  function crear(m) {
    const div = document.createElement('div');
    div.className = 'msg-chat' + (m.mio ? ' mio' : '');
    const cab = document.createElement('div');
    cab.className = 'msg-cab';
    const autor = document.createElement('b');
    autor.textContent = m.nombre + (m.rol === 'admin' ? ' · Kōcha' : '');
    const hora = document.createElement('small');
    hora.textContent = m.hora;
    cab.append(autor, hora);
    const txt = document.createElement('p');
    txt.textContent = m.texto;
    div.append(cab, txt);
    if (m.puedeBorrar) {
      const b = document.createElement('button');
      b.type = 'button'; b.className = 'btn-mini rojo'; b.textContent = 'Borrar';
      b.addEventListener('click', () => borrar(m.id));
      div.append(b);
    }
    return div;
  }

  function pintar(mensajes) {
    const abajo = lista.scrollHeight - lista.scrollTop - lista.clientHeight < 80;
    if (!mensajes.length) {
      const vacio = document.createElement('p');
      vacio.className = 'sub'; vacio.textContent = 'Todavía no hay mensajes. ¡Sé el primero en escribir!';
      lista.replaceChildren(vacio);
      return;
    }
    lista.replaceChildren(...mensajes.map(crear));
    if (abajo) { lista.scrollTop = lista.scrollHeight; }
  }

  async function cargar() {
    try {
      const r = await fetch('blog.php?ajax=1', { cache: 'no-store' });
      const d = await r.json();
      pintar(d.mensajes || []);
    } catch (e) { /* si falla, lo intenta en el siguiente ciclo */ }
  }

  async function enviarDatos(datos) {
    const fd = new FormData();
    Object.entries(datos).forEach(([k, v]) => fd.append(k, v));
    fd.append('csrf', csrf);
    const r = await fetch('blog.php', { method: 'POST', body: fd });
    return r.json();
  }

  form.addEventListener('submit', async e => {
    e.preventDefault();
    const texto = entrada.value.trim();
    if (!texto) { return; }
    const d = await enviarDatos({ accion: 'enviar', texto });
    if (d.error) { aviso.textContent = d.error; return; }
    aviso.textContent = '';
    entrada.value = '';
    await cargar();
    lista.scrollTop = lista.scrollHeight;
  });

  async function borrar(id) {
    if (!confirm('¿Borrar este mensaje?')) { return; }
    await enviarDatos({ accion: 'borrar', id });
    cargar();
  }

  cargar().then(() => { lista.scrollTop = lista.scrollHeight; });
  setInterval(() => { if (!document.hidden) { cargar(); } }, 4000);
</script>
</body>
</html>