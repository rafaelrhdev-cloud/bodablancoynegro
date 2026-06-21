<?php
/**
 * admin/setup.php
 * Ejecuta este archivo UNA SOLA VEZ desde el navegador para crear tu
 * usuario administrador. Después de crear el primer admin, este
 * script se bloquea automáticamente por seguridad.
 *
 * Por seguridad extra: elimina este archivo del servidor después de usarlo.
 */
require __DIR__ . '/../api/config.php';

$pdo = db();
$count = (int)$pdo->query('SELECT COUNT(*) FROM admin_users')->fetchColumn();

$error = '';
$success = false;

if ($count > 0) {
    $blocked = true;
} else {
    $blocked = false;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm'] ?? '';

        if (strlen($username) < 3) {
            $error = 'El usuario debe tener al menos 3 caracteres.';
        } elseif (strlen($password) < 8) {
            $error = 'La contraseña debe tener al menos 8 caracteres.';
        } elseif ($password !== $confirm) {
            $error = 'Las contraseñas no coinciden.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare('INSERT INTO admin_users (username, password_hash) VALUES (?, ?)');
            $stmt->execute([$username, $hash]);
            $success = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Configuración inicial — Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="assets/admin.css">
</head>
<body class="admin-auth">
  <div class="admin-auth-card">
    <h1>Configuración inicial</h1>

    <?php if ($blocked && !$success): ?>
      <p class="warn">Ya existe un usuario administrador configurado. Por seguridad, este formulario está desactivado.</p>
      <p><a href="login.php">Ir a iniciar sesión →</a></p>
    <?php elseif ($success): ?>
      <p class="ok">Usuario administrador creado correctamente.</p>
      <p><a href="login.php">Iniciar sesión →</a></p>
      <p class="warn small">Por seguridad, elimina este archivo (admin/setup.php) del servidor ahora.</p>
    <?php else: ?>
      <?php if ($error): ?><p class="warn"><?= htmlspecialchars($error) ?></p><?php endif; ?>
      <form method="post">
        <label>Usuario</label>
        <input type="text" name="username" required minlength="3">
        <label>Contraseña</label>
        <input type="password" name="password" required minlength="8">
        <label>Confirmar contraseña</label>
        <input type="password" name="confirm" required minlength="8">
        <button type="submit">Crear administrador</button>
      </form>
    <?php endif; ?>
  </div>
</body>
</html>
