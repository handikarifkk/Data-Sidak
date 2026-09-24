-- ==========================================================
-- LaporanSidak — Struktur Database MySQL / MariaDB
-- Import file ini melalui phpMyAdmin (http://localhost/phpmyadmin)
-- atau lewat: mysql -u root -p < laporan_sidak.sql
-- ==========================================================

CREATE DATABASE IF NOT EXISTS laporan_sidak
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE laporan_sidak;

-- --------------------------------------------------------
-- Tabel utama: data hasil sidak
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS sidak (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  no                VARCHAR(20)  NOT NULL DEFAULT '',
  tanggal           DATE         NOT NULL,
  department        VARCHAR(255) NOT NULL,
  lokasi            VARCHAR(255) NOT NULL,
  mandor            VARCHAR(255) NOT NULL DEFAULT '',
  kasie             VARCHAR(255) NOT NULL DEFAULT '',
  temuan            TEXT,
  verifikasi        TEXT,
  tgl_verifikasi    DATE         NULL,
  status            VARCHAR(20)  NOT NULL DEFAULT '',   -- '', 'Open', 'Proses', 'Selesai'
  corrective_action TEXT,
  pic               VARCHAR(255) NOT NULL DEFAULT '',
  created_at        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_tanggal (tanggal),
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabel foto dokumentasi (relasi 1 sidak -> banyak foto)
-- File fisik disimpan di folder uploads/sidak/, tabel ini
-- hanya menyimpan referensinya (bukan base64).
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS sidak_photos (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sidak_id    INT UNSIGNED NOT NULL,
  photo_path  VARCHAR(500) NOT NULL,   -- contoh: uploads/sidak/ab12cd34....jpg
  width       INT UNSIGNED NULL,
  height      INT UNSIGNED NULL,
  sort_order  INT UNSIGNED NOT NULL DEFAULT 0,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_sidak_photos_sidak
    FOREIGN KEY (sidak_id) REFERENCES sidak(id)
    ON DELETE CASCADE,
  INDEX idx_sidak_id (sidak_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabel pengaturan laporan (single-row, id selalu 1)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS settings (
  id          TINYINT UNSIGNED NOT NULL PRIMARY KEY DEFAULT 1,
  petugas     VARCHAR(255) NOT NULL DEFAULT '',
  periode     VARCHAR(50)  NOT NULL DEFAULT '',
  lokasi_ttd  VARCHAR(255) NOT NULL DEFAULT '',
  role1       VARCHAR(255) NOT NULL DEFAULT '',
  role2       VARCHAR(255) NOT NULL DEFAULT '',
  role3       VARCHAR(255) NOT NULL DEFAULT '',
  updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Nilai default pengaturan, sama seperti nilai default versi localStorage lama
INSERT INTO settings (id, petugas, periode, lokasi_ttd, role1, role2, role3)
VALUES (
  1,
  'HRBP, Security , System Mutu',
  '',
  'Terbanggi Besar',
  'HRBP Wilayah',
  'People Partner',
  'Sub-Dep Head'
)
ON DUPLICATE KEY UPDATE id = id;
