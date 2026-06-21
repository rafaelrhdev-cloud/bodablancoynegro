<?php
/**
 * api/confirm.php
 * Registra la confirmación de asistencia.
 * Reglas:
 *  - Máximo 5 pases por invitación (tope absoluto, sin importar lo configurado).
 *  - No se puede exceder el número de pases asignado al invitado.
 *  - Un mismo dispositivo (device_id) solo puede confirmar una vez en total.
 *  - Un mismo invitado solo puede confirmarse una vez.
 */

require __DIR__ . '/config.php';

const HARD_PASS_CAP = 5;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'message' => 'Método no permitido.'], 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$name = trim((string)($input['name'] ?? ''));
$deviceId = trim((string)($input['device_id'] ?? ''));
$passes = (int)($input['passes'] ?? 0);

if ($name === '' || $deviceId === '' || $passes < 1) {
    json_response(['ok' => false, 'message' => 'Datos incompletos.']);
}
if (!preg_match('/^[a-zA-Z0-9\-]{8,64}$/', $deviceId)) {
    json_response(['ok' => false, 'message' => 'No pudimos identificar tu dispositivo.']);
}
if ($passes > HARD_PASS_CAP) {
    $passes = HARD_PASS_CAP;
}

try {
    $pdo = db();
    $pdo->beginTransaction();

    // Bloqueo por dispositivo: una sola confirmación por navegador/dispositivo.
    $stmt = $pdo->prepare('SELECT id FROM device_locks WHERE device_id = ? LIMIT 1 FOR UPDATE');
    $stmt->execute([$deviceId]);
    if ($stmt->fetch()) {
        $pdo->rollBack();
        json_response(['ok' => false, 'message' => 'Ya se registró una confirmación desde este dispositivo o navegador.']);
    }

    // Validar invitado y bloquear la fila para evitar condiciones de carrera.
    $normalized = normalize_name($name);
    $stmt = $pdo->prepare('SELECT id, full_name, allowed_passes, confirmed FROM guests WHERE full_name_normalized = ? LIMIT 1 FOR UPDATE');
    $stmt->execute([$normalized]);
    $guest = $stmt->fetch();

    if (!$guest) {
        $pdo->rollBack();
        json_response(['ok' => false, 'message' => 'No encontramos esa invitación.']);
    }
    if ((int)$guest['confirmed'] === 1) {
        $pdo->rollBack();
        json_response(['ok' => false, 'message' => 'Esta invitación ya fue confirmada previamente.']);
    }

    $maxAllowed = min((int)$guest['allowed_passes'], HARD_PASS_CAP);
    if ($passes > $maxAllowed) {
        $passes = $maxAllowed;
    }

    // Registrar confirmación
    $stmt = $pdo->prepare('INSERT INTO confirmations (guest_id, guest_name, num_passes, device_id, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $guest['id'],
        $guest['full_name'],
        $passes,
        $deviceId,
        client_ip(),
        substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
    ]);

    // Marcar invitado como confirmado
    $stmt = $pdo->prepare('UPDATE guests SET confirmed = 1 WHERE id = ?');
    $stmt->execute([$guest['id']]);

    // Bloquear este dispositivo para siempre (1 confirmación por dispositivo)
    $stmt = $pdo->prepare('INSERT INTO device_locks (device_id, guest_id) VALUES (?, ?)');
    $stmt->execute([$deviceId, $guest['id']]);

    $pdo->commit();

    json_response(['ok' => true, 'passes' => $passes]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response(['ok' => false, 'message' => 'Error del servidor. Intenta más tarde.'], 500);
}
