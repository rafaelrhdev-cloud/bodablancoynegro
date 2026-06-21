-- ============================================================
-- Esquema de base de datos — Invitación de boda
-- Importa este archivo en phpMyAdmin / consola MySQL al instalar
-- ============================================================

CREATE TABLE IF NOT EXISTS guests (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(150) NOT NULL,
  full_name_normalized VARCHAR(150) NOT NULL,
  allowed_passes TINYINT UNSIGNED NOT NULL DEFAULT 1,
  confirmed TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_name_norm (full_name_normalized)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS confirmations (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  guest_id INT UNSIGNED NOT NULL,
  guest_name VARCHAR(150) NOT NULL,
  num_passes TINYINT UNSIGNED NOT NULL,
  device_id VARCHAR(64) NOT NULL,
  ip_address VARCHAR(64) DEFAULT NULL,
  user_agent VARCHAR(255) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (guest_id) REFERENCES guests(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS device_locks (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  device_id VARCHAR(64) NOT NULL,
  guest_id INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_device (device_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admin_users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(60) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- NOTA: el usuario administrador NO se crea aquí.
-- Después de importar esta base de datos, abre una sola vez:
--   /admin/setup.php
-- desde el navegador para crear tu usuario y contraseña de forma
-- segura (el script se autodesactiva después de usarse una vez).
-- ============================================================

-- ============================================================
-- Ejemplo de invitados (BORRA estos ejemplos y agrega los reales
-- desde el panel de administración: /admin/)
-- ============================================================
INSERT INTO guests (full_name, full_name_normalized, allowed_passes) VALUES
('Familia García López', 'familia garcia lopez', 5),
('Juan Pérez', 'juan perez', 2),
('Mariana Torres', 'mariana torres', 1)
ON DUPLICATE KEY UPDATE full_name = full_name;
