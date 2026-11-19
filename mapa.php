<?php
require __DIR__ . '/includes/auth.php';
requiere_login();

// Cambia estas coordenadas por las de tu tienda (ver paso 5)
const TIENDA_LAT = 19.6499554;
const TIENDA_LNG = -99.1173787;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Ubicación | Kōcha Café</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css">
  <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
<?php include __DIR__ . '/includes/menu.php'; ?>

<main class="pagina">
  <h1>¿Qué tan cerca estás?</h1>
  <div class="mapa-barra">
    <button type="button" class="btn-pill" id="ubicar">Obtener mi ubicación</button>
    <p id="info" class="sub">Pulsa el botón para calcular la distancia a nuestra tienda.</p>
  </div>
  <div id="mapa"></div>
</main>

<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js"></script>
<script>
  const tienda = [<?= TIENDA_LAT ?>, <?= TIENDA_LNG ?>];
  const mapa = L.map('mapa').setView(tienda, 15);
  L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap'
  }).addTo(mapa);
  L.marker(tienda).addTo(mapa).bindPopup('<b>Kōcha Café</b><br>¡Aquí te esperamos!').openPopup();

  const info = document.getElementById('info');
  let marcaUsuario = null, linea = null;

  document.getElementById('ubicar').addEventListener('click', () => {
    if (!navigator.geolocation) {
      info.textContent = 'Tu navegador no permite obtener la ubicación.';
      return;
    }
    info.textContent = 'Buscando tu ubicación…';

    navigator.geolocation.getCurrentPosition(pos => {
      const yo = [pos.coords.latitude, pos.coords.longitude];
      const metros = mapa.distance(yo, tienda);
      const dist = metros >= 1000 ? (metros / 1000).toFixed(1) + ' km' : Math.round(metros) + ' m';
      const caminando = Math.max(1, Math.round(metros / 1000 / 5 * 60));
      const auto = Math.max(1, Math.round(metros / 1000 / 30 * 60));

      info.textContent = 'Estás a ' + dist + ' de la tienda (≈ ' + caminando + ' min caminando, ' + auto + ' min en auto). ' +
        'Tu ubicación: ' + yo[0].toFixed(5) + ', ' + yo[1].toFixed(5);

      if (marcaUsuario) { mapa.removeLayer(marcaUsuario); mapa.removeLayer(linea); }
      marcaUsuario = L.circleMarker(yo, { radius: 9, color: '#3F5A2A', fillColor: '#8FB369', fillOpacity: 1 })
        .addTo(mapa).bindPopup('Tú estás aquí');
      linea = L.polyline([yo, tienda], { color: '#3F5A2A', dashArray: '6 8' }).addTo(mapa);
      mapa.fitBounds([yo, tienda], { padding: [50, 50] });
    }, err => {
      info.textContent = err.code === 1
        ? 'No diste permiso para usar tu ubicación. Actívalo en el candado de la barra de direcciones.'
        : 'No pudimos obtener tu ubicación. Inténtalo de nuevo.';
    }, { enableHighAccuracy: true, timeout: 15000 });
  });
</script>
</body>
</html>