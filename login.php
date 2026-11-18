<?php
require __DIR__ . '/includes/conexion.php';
require __DIR__ . '/includes/auth.php';

if (!empty($_SESSION['usuario'])) { ir_a_home(); }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    $st = $pdo->prepare('SELECT id, nombre, email, password, rol FROM usuarios WHERE email = ?');
    $st->execute([$email]);
    $u = $st->fetch(PDO::FETCH_ASSOC);

    if ($u && password_verify($pass, $u['password'])) {
        session_regenerate_id(true);
        $_SESSION['usuario'] = [
            'id' => $u['id'], 'nombre' => $u['nombre'],
            'email' => $u['email'], 'rol' => $u['rol']
        ];
        ir_a_home();
    }
    $error = 'Correo o contraseña incorrectos.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Iniciar sesión | Kōcha Café</title>
  <link href="https://fonts.googleapis.com/css2?family=Italianno&family=Inter:wght@400;500;600&family=Playfair+Display:ital,wght@0,500;0,700;1,500;1,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
  <main class="auth auth-login">
    <section class="auth-hero" aria-label="Kōcha Café">
      <div class="poster">
        <img class="poster-decoration" src="img/bebidas/matcha-latte2.png" alt="Matcha ceremonial con batidor de bambú">
        <h1 class="logo" aria-label="Kōcha CAFÉ">
          <span class="logo-name"><span>Kō</span><em>cha</em></span>
          <span class="logo-cafe">CAFÉ</span>
        </h1>
        <p class="poster-title">Tu momento,<br><span>en un vaso.</span></p>
        <p class="poster-copy">Matcha, café y boba para disfrutar a tu manera.</p>
        <img class="drink" src="img/bebidas/matcha-latte.png" alt="Latte de matcha">
      </div>
    </section>
    <section class="auth-panel">
      <form class="card" method="post">
        <h2>Qué gusto verte</h2>
        <p class="sub">Inicia sesión y vuelve a tu pausa favorita.</p>
        <?php if ($error): ?><div class="msg error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <label for="email">Correo</label>
        <input type="email" id="email" name="email" required>
        <label for="password">Contraseña</label>
        <input type="password" id="password" name="password" required>
        <button class="btn" type="submit">Entrar</button>
        <p class="alt">¿Aún no tienes cuenta? <a href="registro.php">Regístrate</a></p>
      </form>
    </section>
  </main>
</body>
</html>