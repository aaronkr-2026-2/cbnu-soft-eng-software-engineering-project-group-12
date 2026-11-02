-- Run this ONCE on an existing database (it is safe to re-run).
-- New installs get these tables from db.sql automatically.
USE korean_app;

CREATE TABLE IF NOT EXISTS user_words (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  korean VARCHAR(100) NOT NULL,
  romanization VARCHAR(100) NULL,
  meaning VARCHAR(200) NOT NULL,
  example_ko VARCHAR(255) NULL,
  example_en VARCHAR(255) NULL,
  created_at DATETIME NOT NULL,
  KEY idx_user_korean (user_id, korean),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_files (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  kind ENUM('pdf','image') NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  stored_name VARCHAR(64) NOT NULL,
  mime VARCHAR(100) NOT NULL,
  size_bytes BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL,
  KEY idx_user_kind (user_id, kind, created_at),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
