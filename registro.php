<?php
require __DIR__ . '/includes/conexion.php';
require __DIR__ . '/includes/auth.php';

$error = $ok = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $pass   = $_POST['password'] ?? '';
    $pass2  = $_POST['password2'] ?? '';

    if ($nombre === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Revisa tu nombre y tu correo.';
    } elseif (strlen($pass) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif ($pass !== $pass2) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        $st = $pdo->prepare('SELECT 1 FROM usuarios WHERE email = ?');
        $st->execute([$email]);
        if ($st->fetch()) {
            $error = 'Ese correo ya está registrado.';
        } else {
            $st = $pdo->prepare("INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, ?, 'cliente')");
            $st->execute([$nombre, $email, password_hash($pass, PASSWORD_DEFAULT)]);
            $ok = 'Cuenta creada. Ya puedes iniciar sesión.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Crear cuenta | Kōcha Café</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
  <main class="auth">
    <section class="auth-hero">
      <div class="logo">Kōcha<small>CAFÉ</small></div>
      <h1>Tu momento,<br><span>en un vaso.</span></h1>
      <p>Crea tu cuenta y descubre nuestras bebidas de matcha, café y boba.</p>
    </section>
    <section class="auth-panel">
      <form class="card" method="post">
        <h2>Crear cuenta</h2>
        <p class="sub">Solo te tomará un minuto.</p>
        <?php if ($error): ?><div class="msg error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($ok): ?><div class="msg ok"><?= htmlspecialchars($ok) ?></div><?php endif; ?>
        <label for="nombre">Nombre</label>
        <input type="text" id="nombre" name="nombre" required>
        <label for="email">Correo</label>
        <input type="email" id="email" name="email" required>
        <label for="password">Contraseña</label>
        <input type="password" id="password" name="password" required>
        <label for="password2">Repite la contraseña</label>
        <input type="password" id="password2" name="password2" required>
        <button class="btn" type="submit">Registrarme</button>
        <p class="alt">¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>
      </form>
    </section>
  </main>
</body>
</html>