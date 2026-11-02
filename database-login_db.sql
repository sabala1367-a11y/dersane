-- Tengeneza database ya login
CREATE DATABASE IF NOT EXISTS login_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE login_db;

-- Futa jedwali la zamani la admin_user (kama lipo) kisha litengeneze upya
DROP TABLE IF EXISTS admin_user;

CREATE TABLE admin_user (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  email VARCHAR(150) NOT NULL,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin wa kwanza:  email = sabala1367@gmail.com   password = 123
INSERT INTO admin_user (email, password) VALUES
('sabala1367@gmail.com', '$2y$10$pIl6RMb7m3Zd7kf6GXBcWuekSpPV3kZE3vWmuhwUUMmXsEvXmyx/S');

-- Ukaguzi: hash_len LAZIMA iwe 60
SELECT id, email, LENGTH(password) AS hash_len FROM admin_user;
