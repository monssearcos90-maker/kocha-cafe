<?php
require __DIR__ . '/includes/auth.php';
requiere_login();

$videos = [
    ['Una historia de amor', 'Un amor que empezó con ilusión y terminó en despedida.', 'https://www.youtube.com/watch?v=hYyQI-CCe20'],
];

function youtube_id($url) {
    $patron = '~(?:youtu\.be/|youtube\.com/(?:watch\?(?:[^&]*&)*v=|embed/|shorts/|live/))([A-Za-z0-9_-]{11})~';
    return preg_match($patron, $url, $m) ? $m[1] : null;
}
$h = function ($t) { return htmlspecialchars((string)$t); };
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Videos | Kōcha Café</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
<?php include __DIR__ . '/includes/menu.php'; ?>

<main class="pagina">
  <section class="camara-hero">
    <div>
      <small>MIRA CÓMO LO HACEMOS</small>
      <h1>Videos de Kōcha Café</h1>
      <p class="sub">Nuestras bebidas, nuestro espacio y todo lo que hay detrás de cada vaso.</p>
    </div>
    <div class="polaroids">
      <figure><img src="img/bebidas/matcha-latte.png" alt="Matcha latte"></figure>
      <figure><img src="img/bebidas/taro-latte2.png" alt="Taro latte"></figure>
    </div>
  </section>

  <div class="videos-grid">
    <?php $hay = false; foreach ($videos as [$titulo, $desc, $enlace]): ?>
      <?php $id = youtube_id($enlace); if (!$id) { continue; } $hay = true; ?>
      <article class="video-card">
        <div class="video-marco">
          <iframe src="https://www.youtube-nocookie.com/embed/<?= $h($id) ?>"
                  title="<?= $h($titulo) ?>" loading="lazy" allowfullscreen
                  allow="accelerometer; encrypted-media; gyroscope; picture-in-picture"
                  referrerpolicy="strict-origin-when-cross-origin"></iframe>
        </div>
        <h3><?= $h($titulo) ?></h3>
        <p><?= $h($desc) ?></p>
      </article>
    <?php endforeach; ?>
  </div>

  <?php if (!$hay): ?>
    <p class="sub">Aún no hay videos. Agrega los enlaces de YouTube en la lista <code>$videos</code> de <code>videos.php</code>.</p>
  <?php endif; ?>
</main>
</body>
</html>