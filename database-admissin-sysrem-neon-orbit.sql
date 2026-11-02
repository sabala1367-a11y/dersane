-- Dersane: mfumo wa usajili + maombi. Endesha kwenye phpMyAdmin (tab ya SQL).
CREATE DATABASE IF NOT EXISTS neon_orbit CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE neon_orbit;

CREATE TABLE IF NOT EXISTS users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name     VARCHAR(100) NOT NULL,
  email         VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('user','admin') NOT NULL DEFAULT 'user',
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
-- Kama jedwali la users tayari lilikuwepo (bila role), mstari huu unaliongezea (MariaDB/XAMPP):
ALTER TABLE users ADD COLUMN IF NOT EXISTS role ENUM('user','admin') NOT NULL DEFAULT 'user' AFTER password_hash;

CREATE TABLE IF NOT EXISTS applications (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id           INT UNSIGNED NOT NULL UNIQUE,          -- ombi moja kwa kila mtumiaji
  -- Taarifa binafsi
  first_name        VARCHAR(60)  NOT NULL,
  middle_name       VARCHAR(60)  NULL,
  last_name         VARCHAR(60)  NOT NULL,
  gender            VARCHAR(20)  NOT NULL,
  dob               DATE         NOT NULL,
  nationality       VARCHAR(40)  NOT NULL,
  special_needs     VARCHAR(255) NULL,
  -- Mawasiliano
  phone             VARCHAR(20)  NOT NULL,
  email             VARCHAR(150) NOT NULL,
  region            VARCHAR(60)  NOT NULL,
  district          VARCHAR(80)  NOT NULL,
  emergency_name    VARCHAR(100) NOT NULL,
  emergency_relation VARCHAR(30) NOT NULL,
  emergency_phone   VARCHAR(20)  NOT NULL,
  -- Elimu
  csee_school       VARCHAR(150) NOT NULL,
  csee_index        VARCHAR(25)  NOT NULL,
  csee_year         SMALLINT     NOT NULL,
  csee_subjects     TEXT         NOT NULL,                 -- "Kiswahili: B, Hisabati: C, ..."
  acsee_school      VARCHAR(150) NULL,
  acsee_index       VARCHAR(25)  NULL,
  acsee_combination VARCHAR(20)  NULL,
  -- Kozi na ufadhili
  course            VARCHAR(150) NOT NULL,
  college           VARCHAR(150) NOT NULL,
  study_level       VARCHAR(30)  NOT NULL,
  funding           VARCHAR(60)  NOT NULL,
  source            VARCHAR(60)  NULL,
  -- Uamuzi wa admin
  status            ENUM('pending','admitted','rejected') NOT NULL DEFAULT 'pending',
  admission_no      VARCHAR(20)  NULL UNIQUE,
  house_location    VARCHAR(100) NULL,
  reject_reason     TEXT         NULL,
  submitted_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  decided_at        DATETIME     NULL,
  decided_by        INT UNSIGNED NULL,
  INDEX idx_status (status),
  CONSTRAINT fk_app_user  FOREIGN KEY (user_id)    REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_app_admin FOREIGN KEY (decided_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Kutengeneza admin: jisajili kawaida kwenye ukurasa wa register kisha endesha (badilisha barua pepe):
-- UPDATE users SET role='admin' WHERE email='admin@mfano.com';
