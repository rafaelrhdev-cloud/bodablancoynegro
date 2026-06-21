<?php
require __DIR__ . '/auth.php';
require_login();

$pdo = db();

// ---- Acciones (agregar / eliminar invitado) ----
$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_guest') {
        $name = trim($_POST['full_name'] ?? '');
        $passes = max(1, min(5, (int)($_POST['allowed_passes'] ?? 1)));
        if ($name !== '') {
            $stmt = $pdo->prepare('INSERT INTO guests (full_name, full_name_normalized, allowed_passes) VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE allowed_passes = VALUES(allowed_passes)');
            $stmt->execute([$name, normalize_name($name), $passes]);
            $flash = 'Invitado agregado / actualizado correctamente.';
        }
    } elseif ($action === 'delete_guest') {
        $id = (int)($_POST['guest_id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM guests WHERE id = ?');
        $stmt->execute([$id]);
        $flash = 'Invitado eliminado.';
    }
}

// ---- Datos para mostrar ----
$totalPasses = (int)$pdo->query('SELECT COALESCE(SUM(num_passes),0) FROM confirmations')->fetchColumn();
$totalConfirmed = (int)$pdo->query('SELECT COUNT(*) FROM confirmations')->fetchColumn();
$totalInvited = (int)$pdo->query('SELECT COUNT(*) FROM guests')->fetchColumn();
$totalAllowedPasses = (int)$pdo->query('SELECT COALESCE(SUM(allowed_passes),0) FROM guests')->fetchColumn();

$confirmations = $pdo->query(
    'SELECT c.guest_name, c.num_passes, c.created_at, c.ip_address
     FROM confirmations c ORDER BY c.created_at DESC'
)->fetchAll();

$guests = $pdo->query(
    'SELECT id, full_name, allowed_passes, confirmed FROM guests ORDER BY full_name ASC'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Panel del organizador — Invitados</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="assets/admin.css">
</head>
<body>
<header class="admin-header">
  <h1>Panel del organizador</h1>
  <div class="admin-header-right">
    <span>Hola, <?= htmlspecialchars(current_admin()) ?></span>
    <a href="logout.php" class="btn-ghost">Cerrar sesión</a>
  </div>
</header>

<main class="admin-main">

  <?php if ($flash): ?><p class="flash"><?= htmlspecialchars($flash) ?></p><?php endif; ?>

  <section class="stat-grid">
    <div class="stat-card"><span class="stat-num"><?= $totalConfirmed ?></span><span class="stat-label">Invitaciones confirmadas</span></div>
    <div class="stat-card"><span class="stat-num"><?= $totalPasses ?></span><span class="stat-label">Personas confirmadas (pases)</span></div>
    <div class="stat-card"><span class="stat-num"><?= $totalInvited ?></span><span class="stat-label">Invitaciones en la lista</span></div>
    <div class="stat-card"><span class="stat-num"><?= $totalAllowedPasses ?></span><span class="stat-label">Pases totales disponibles</span></div>
  </section>

  <section class="panel">
    <h2>Invitados confirmados</h2>
    <?php if (!$confirmations): ?>
      <p class="muted">Aún no hay confirmaciones registradas.</p>
    <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Invitación</th><th>Pases confirmados</th><th>Fecha</th><th>IP</th></tr></thead>
        <tbody>
          <?php foreach ($confirmations as $c): ?>
          <tr>
            <td><?= htmlspecialchars($c['guest_name']) ?></td>
            <td><?= (int)$c['num_passes'] ?></td>
            <td><?= htmlspecialchars($c['created_at']) ?></td>
            <td class="muted"><?= htmlspecialchars($c['ip_address']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </section>

  <section class="panel">
    <h2>Agregar invitación a la lista</h2>
    <form method="post" class="inline-form">
      <input type="hidden" name="action" value="add_guest">
      <input type="text" name="full_name" placeholder="Nombre exacto de la invitación" required>
      <select name="allowed_passes">
        <option value="1">1 pase</option>
        <option value="2">2 pases</option>
        <option value="3">3 pases</option>
        <option value="4">4 pases</option>
        <option value="5">5 pases (máximo)</option>
      </select>
      <button type="submit">Agregar</button>
    </form>
  </section>

  <section class="panel">
    <h2>Lista completa de invitaciones</h2>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Nombre</th><th>Pases asignados</th><th>Estado</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($guests as $g): ?>
          <tr>
            <td><?= htmlspecialchars($g['full_name']) ?></td>
            <td><?= (int)$g['allowed_passes'] ?></td>
            <td><?= $g['confirmed'] ? '<span class="badge ok">Confirmado</span>' : '<span class="badge">Pendiente</span>' ?></td>
            <td>
              <form method="post" onsubmit="return confirm('¿Eliminar esta invitación de la lista?');">
                <input type="hidden" name="action" value="delete_guest">
                <input type="hidden" name="guest_id" value="<?= (int)$g['id'] ?>">
                <button type="submit" class="btn-danger">Eliminar</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

</main>
</body>
</html>
