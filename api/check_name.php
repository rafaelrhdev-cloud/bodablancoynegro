<?php
/**
 * api/check_name.php
 * Verifica que el nombre exista en la lista de invitados, que no haya
 * confirmado ya, y que el dispositivo no haya sido usado antes.
 */

require __DIR__ . '/config.php';

header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'message' => 'Método no permitido.'], 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$name = trim((string)($input['name'] ?? ''));
$deviceId = trim((string)($input['device_id'] ?? ''));

if ($name === '') {
    json_response(['ok' => false, 'message' => 'Escribe el nombre de tu invitación.']);
}
if ($deviceId === '' || !preg_match('/^[a-zA-Z0-9\-]{8,64}$/', $deviceId)) {
    json_response(['ok' => false, 'message' => 'No pudimos identificar tu dispositivo. Intenta recargar la página.']);
}

try {
    $pdo = db();

    // 1. ¿Este dispositivo ya fue usado para confirmar (sin importar el nombre)?
    $stmt = $pdo->prepare('SELECT id FROM device_locks WHERE device_id = ? LIMIT 1');
    $stmt->execute([$deviceId]);
    if ($stmt->fetch()) {
        json_response(['ok' => false, 'message' => 'Ya se registró una confirmación desde este dispositivo o navegador.']);
    }

    // 2. ¿Existe el invitado?
    $normalized = normalize_name($name);
    $stmt = $pdo->prepare('SELECT id, full_name, allowed_passes, confirmed FROM guests WHERE full_name_normalized = ? LIMIT 1');
    $stmt->execute([$normalized]);
    $guest = $stmt->fetch();

    if (!$guest) {
        json_response(['ok' => false, 'message' => 'No encontramos ese nombre en la lista de invitados. Verifica que esté escrito igual que en tu invitación.']);
    }

    if ((int)$guest['confirmed'] === 1) {
        json_response(['ok' => false, 'message' => 'Esta invitación ya fue confirmada previamente. Si crees que es un error, contacta a los novios.']);
    }

    json_response([
        'ok' => true,
        'guest_name' => $guest['full_name'],
        'allowed_passes' => min((int)$guest['allowed_passes'], 5),
    ]);

} catch (Throwable $e) {
    json_response(['ok' => false, 'message' => 'Error del servidor. Intenta más tarde.'], 500);
}
