<?php
/**
 * Configuración de conexión a MySQL.
 * Ajusta estos valores con los datos que te dé tu hosting.
 * No subas este archivo con credenciales reales a un repositorio público.
 */

// ---- EDITA ESTOS VALORES AL CONTRATAR TU HOSTING ----
define('DB_HOST', 'localhost');
define('DB_NAME', 'wedding_invitation');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');
// ------------------------------------------------------

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}

/** Normaliza un nombre para comparar sin importar mayúsculas, acentos o espacios extra. */
function normalize_name(string $name): string {
    $name = trim($name);
    $name = preg_replace('/\s+/', ' ', $name);
    $name = mb_strtolower($name, 'UTF-8');
    $replacements = [
        'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n','ü'=>'u',
    ];
    $name = strtr($name, $replacements);
    return $name;
}

function json_response(array $payload, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function client_ip(): string {
    return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}
