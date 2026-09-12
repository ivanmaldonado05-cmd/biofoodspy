-- Biofoods — esquema de base de datos (MySQL)
-- Importar una vez en phpMyAdmin (Hostinger) o se crea solo al primer pedido (db.php).

CREATE TABLE IF NOT EXISTS orders (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  name          VARCHAR(160) NOT NULL,
  email         VARCHAR(160) NOT NULL,
  phone         VARCHAR(60)  NOT NULL,
  delivery      ENUM('envio','retiro') NOT NULL DEFAULT 'envio',
  city          VARCHAR(120) NULL,
  address       VARCHAR(255) NULL,
  location_lat  DECIMAL(10,7) NULL,
  location_lng  DECIMAL(10,7) NULL,
  subtotal      INT NOT NULL DEFAULT 0,
  shipping      INT NOT NULL DEFAULT 0,
  total         INT NOT NULL DEFAULT 0,
  payment       ENUM('transferencia','tarjeta') NOT NULL DEFAULT 'transferencia',
  status        ENUM('pending','paid','done','cancel') NOT NULL DEFAULT 'pending',
  proof_file    VARCHAR(255) NULL,
  source        VARCHAR(120) NULL,
  note          TEXT NULL,
  items         JSON NULL,
  bancard_process_id VARCHAR(120) NULL,
  bancard_ref        VARCHAR(120) NULL,
  INDEX idx_created (created_at),
  INDEX idx_status (status),
  INDEX idx_source (source)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
