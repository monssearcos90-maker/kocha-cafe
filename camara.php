<?php
require __DIR__ . '/includes/conexion.php';
require __DIR__ . '/includes/auth.php';
requiere_login();

$pdo->exec("CREATE TABLE IF NOT EXISTS fotos (
  id SERIAL PRIMARY KEY,
  usuario_id INT NOT NULL REFERENCES usuarios(id) ON DELETE CASCADE,
  imagen TEXT NOT NULL,
  creado_en TIMESTAMP DEFAULT NOW()
)");

if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(16)); }
$uid = (int)$_SESSION['usuario']['id'];
$h = function ($t) { return htmlspecialchars((string)$t); };
$mensaje = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) { die('Solicitud no válida.'); }
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'guardar') {
        $img  = $_POST['imagen'] ?? '';
        $pref = 'data:image/jpeg;base64,';
        $ok = false;
        if (strncmp($img, $pref, strlen($pref)) === 0 && strlen($img) < 600000) {
            $bin  = base64_decode(substr($img, strlen($pref)), true);
            $info = $bin ? @getimagesizefromstring($bin) : false;
            if ($info && $info['mime'] === 'image/jpeg') {
                $pdo->prepare('INSERT INTO fotos (usuario_id, imagen) VALUES (?, ?)')->execute([$uid, $img]);
                $ok = true;
            }
        }
        $_SESSION['flash'] = $ok ? 'Foto guardada en tu galería.' : 'No se pudo guardar la foto.';
    }

    if ($accion === 'borrar') {
        $pdo->prepare('DELETE FROM fotos WHERE id = ? AND usuario_id = ?')->execute([(int)($_POST['id'] ?? 0), $uid]);
        $_SESSION['flash'] = 'Foto eliminada.';
    }
    header('Location: camara.php'); exit;
}

$st = $pdo->prepare('SELECT id, imagen, creado_en FROM fotos WHERE usuario_id = ? ORDER BY id DESC LIMIT 12');
$st->execute([$uid]);
$fotos = $st->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Cámara | Kōcha Café</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
<?php include __DIR__ . '/includes/menu.php'; ?>

<main class="pagina">
  <h1>Tómate una foto con tu bebida</h1>
  <?php if ($mensaje): ?><div class="msg ok"><?= $h($mensaje) ?></div><?php endif; ?>
  <p id="aviso" class="sub">Pulsa «Abrir cámara» y acepta el permiso del navegador.</p>

  <div class="camara-grid">
    <div class="visor">
      <video id="video" autoplay playsinline muted></video>
      <span id="placeholder">La cámara está apagada</span>
    </div>
    <div class="visor">
      <img id="foto" alt="Tu foto" hidden>
      <span id="vacio">Aquí aparecerá tu foto</span>
    </div>
  </div>
  <canvas id="canvas" hidden></canvas>

  <div class="camara-botones">
    <button type="button" class="btn-pill" id="abrir">Abrir cámara</button>
    <button type="button" class="btn-pill" id="tomar" disabled>Tomar foto</button>
    <a class="btn-borde" id="descargar" download="kocha-foto.jpg" hidden>Descargar</a>
    <form method="post" id="form-guardar">
      <input type="hidden" name="csrf" value="<?= $h($_SESSION['csrf']) ?>">
      <input type="hidden" name="accion" value="guardar">
      <input type="hidden" name="imagen" id="imagen">
      <button type="submit" class="btn-borde" id="guardar" hidden>Guardar en mi galería</button>
    </form>
  </div>

  <h2 class="subtitulo">Mi galería</h2>
  <?php if (!$fotos): ?>
    <p class="sub">Todavía no tienes fotos guardadas.</p>
  <?php else: ?>
    <div class="galeria">
      <?php foreach ($fotos as $f): ?>
        <figure>
          <img src="<?= $h($f['imagen']) ?>" alt="Foto del <?= $h(date('d/m/Y', strtotime($f['creado_en']))) ?>">
          <form method="post" onsubmit="return confirm('¿Eliminar esta foto?')">
            <input type="hidden" name="csrf" value="<?= $h($_SESSION['csrf']) ?>">
            <input type="hidden" name="accion" value="borrar">
            <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
            <button type="submit" class="btn-mini rojo">Borrar</button>
          </form>
        </figure>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>

<script>
  const video = document.getElementById('video'), canvas = document.getElementById('canvas');
  const foto = document.getElementById('foto'), aviso = document.getElementById('aviso');
  const btnAbrir = document.getElementById('abrir'), btnTomar = document.getElementById('tomar');
  const btnDescargar = document.getElementById('descargar'), btnGuardar = document.getElementById('guardar');
  let stream = null;

  btnAbrir.addEventListener('click', async () => {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      aviso.textContent = 'Tu navegador no permite usar la cámara.';
      return;
    }
    try {
      stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false });
      video.srcObject = stream;
      document.getElementById('placeholder').hidden = true;
      btnTomar.disabled = false;
      btnAbrir.textContent = 'Cámara activa';
      btnAbrir.disabled = true;
      aviso.textContent = 'Listo. Pulsa «Tomar foto».';
    } catch (e) {
      aviso.textContent = e.name === 'NotAllowedError'
        ? 'No diste permiso para usar la cámara. Actívalo en el candado de la barra de direcciones.'
        : 'No se encontró una cámara disponible.';
    }
  });

  btnTomar.addEventListener('click', () => {
    const w = Math.min(640, video.videoWidth || 640);
    const h = Math.round(w * (video.videoHeight || 480) / (video.videoWidth || 640));
    canvas.width = w; canvas.height = h;
    canvas.getContext('2d').drawImage(video, 0, 0, w, h);
    const datos = canvas.toDataURL('image/jpeg', 0.7);

    foto.src = datos; foto.hidden = false;
    document.getElementById('vacio').hidden = true;
    document.getElementById('imagen').value = datos;
    btnDescargar.href = datos; btnDescargar.hidden = false;
    btnGuardar.hidden = false;
    aviso.textContent = '¡Foto lista! Guárdala o descárgala.';
  });

  window.addEventListener('beforeunload', () => {
    if (stream) stream.getTracks().forEach(t => t.stop());
  });
</script>
</body>
</html>