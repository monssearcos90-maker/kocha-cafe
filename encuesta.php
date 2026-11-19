<?php
require __DIR__ . '/includes/conexion.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/tablas.php';
requiere_login();

if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(16)); }
$uid = (int)$_SESSION['usuario']['id'];
$h = function ($t) { return htmlspecialchars((string)$t); };

$bebidas = ['matcha' => 'Matcha', 'cafes' => 'Cafés', 'creamy' => 'Creamy Dreams'];
$frecuencias = [
    'diario'     => 'Todos los días',
    'semanal'    => 'Varias veces por semana',
    'mensual'    => 'Algunas veces al mes',
    'casi_nunca' => 'Casi nunca',
];

$mensaje = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) { die('Solicitud no válida.'); }

    $bebida = $_POST['bebida'] ?? '';
    $frec   = $_POST['frecuencia'] ?? '';
    $calif  = (int)($_POST['calificacion'] ?? 0);
    $rec    = $_POST['recomienda'] ?? '';
    $coment = trim($_POST['comentario'] ?? '');

    if (!isset($bebidas[$bebida]) || !isset($frecuencias[$frec]) || $calif < 1 || $calif > 5
        || !in_array($rec, ['si', 'no'], true)) {
        $error = 'Responde todas las preguntas para enviar la encuesta.';
    } else {
        $pdo->prepare('INSERT INTO encuestas (usuario_id, bebida, frecuencia, calificacion, recomienda, comentario)
                       VALUES (?, ?, ?, ?, ?, ?)
                       ON CONFLICT (usuario_id) DO UPDATE SET
                         bebida = EXCLUDED.bebida, frecuencia = EXCLUDED.frecuencia,
                         calificacion = EXCLUDED.calificacion, recomienda = EXCLUDED.recomienda,
                         comentario = EXCLUDED.comentario, creado_en = NOW()')
            ->execute([$uid, $bebida, $frec, $calif, $rec === 'si' ? '1' : '0', mb_substr($coment, 0, 500)]);
        $_SESSION['flash'] = '¡Gracias por responder! Tu opinión nos ayuda a mejorar.';
        header('Location: encuesta.php'); exit;
    }
}

$st = $pdo->prepare('SELECT bebida, frecuencia, calificacion, recomienda, comentario FROM encuestas WHERE usuario_id = ?');
$st->execute([$uid]);
$previa = $st->fetch(PDO::FETCH_ASSOC);

$v = [
    'bebida'     => $_POST['bebida']     ?? ($previa['bebida'] ?? ''),
    'frecuencia' => $_POST['frecuencia'] ?? ($previa['frecuencia'] ?? ''),
    'calif'      => (int)($_POST['calificacion'] ?? ($previa['calificacion'] ?? 0)),
    'rec'        => $_POST['recomienda'] ?? ($previa ? ($previa['recomienda'] ? 'si' : 'no') : ''),
    'coment'     => $_POST['comentario'] ?? ($previa['comentario'] ?? ''),
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Encuesta | Kōcha Café</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
<?php include __DIR__ . '/includes/menu.php'; ?>

<main class="pagina">
  <h1>Cuéntanos tu experiencia</h1>
  <p class="sub"><?= $previa ? 'Ya respondiste esta encuesta, pero puedes actualizar tus respuestas.' : 'Son solo 5 preguntas, te tomará un minuto.' ?></p>
  <?php if ($mensaje): ?><div class="msg ok"><?= $h($mensaje) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="msg error"><?= $h($error) ?></div><?php endif; ?>

  <form method="post" class="panel encuesta">
    <input type="hidden" name="csrf" value="<?= $h($_SESSION['csrf']) ?>">

    <p class="pregunta">1. ¿Cuál es tu tipo de bebida favorito?</p>
    <div class="opciones">
      <?php foreach ($bebidas as $clave => $nombre): ?>
        <label class="opcion"><input type="radio" name="bebida" value="<?= $clave ?>" <?= $v['bebida'] === $clave ? 'checked' : '' ?>><span><?= $h($nombre) ?></span></label>
      <?php endforeach; ?>
    </div>

    <p class="pregunta">2. ¿Con qué frecuencia visitas cafeterías?</p>
    <div class="opciones">
      <?php foreach ($frecuencias as $clave => $nombre): ?>
        <label class="opcion"><input type="radio" name="frecuencia" value="<?= $clave ?>" <?= $v['frecuencia'] === $clave ? 'checked' : '' ?>><span><?= $h($nombre) ?></span></label>
      <?php endforeach; ?>
    </div>

    <p class="pregunta">3. ¿Cómo calificas nuestra página? (1 = mala, 5 = excelente)</p>
    <div class="opciones">
      <?php for ($i = 1; $i <= 5; $i++): ?>
        <label class="opcion"><input type="radio" name="calificacion" value="<?= $i ?>" <?= $v['calif'] === $i ? 'checked' : '' ?>><span><?= $i ?> ★</span></label>
      <?php endfor; ?>
    </div>

    <p class="pregunta">4. ¿Recomendarías Kōcha Café a un amigo?</p>
    <div class="opciones">
      <label class="opcion"><input type="radio" name="recomienda" value="si" <?= $v['rec'] === 'si' ? 'checked' : '' ?>><span>Sí</span></label>
      <label class="opcion"><input type="radio" name="recomienda" value="no" <?= $v['rec'] === 'no' ? 'checked' : '' ?>><span>No</span></label>
    </div>

    <p class="pregunta">5. ¿Algo que quieras decirnos? (opcional)</p>
    <textarea name="comentario" maxlength="500" placeholder="Escribe tu comentario…"><?= $h($v['coment']) ?></textarea>

    <button class="btn" type="submit"><?= $previa ? 'Actualizar respuestas' : 'Enviar encuesta' ?></button>
  </form>
</main>
</body>
</html>