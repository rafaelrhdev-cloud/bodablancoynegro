<?php
require_once __DIR__ . '/../api/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_login(): void {
    if (empty($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit;
    }
}

function current_admin(): ?string {
    return $_SESSION['admin_user'] ?? null;
}
