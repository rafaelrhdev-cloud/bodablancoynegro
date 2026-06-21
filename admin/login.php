<?php
require __DIR__ . '/auth.php';

if (!empty($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = db()->prepare('SELECT id, username, password_hash FROM admin_users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_user'] = $admin['username'];
        header('Location: dashboard.php');
        exit;
    }
    $error = 'Usuario o contraseña incorrectos.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Acceso administrador — Boda</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="assets/admin.css">
</head>
<body class="admin-auth">
  <div class="admin-auth-card">
    <h1>Panel del organizador</h1>
    <p class="muted">Acceso privado — solo para los novios / organizadores.</p>
    <?php if ($error): ?><p class="warn"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <form method="post">
      <label>Usuario</label>
      <input type="text" name="username" required autofocus>
      <label>Contraseña</label>
      <input type="password" name="password" required>
      <button type="submit">Entrar</button>
    </form>
  </div>
</body>
</html>
