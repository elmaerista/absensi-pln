-- Absensi Anak Magang - schema revisi
CREATE DATABASE IF NOT EXISTS absensi_pln CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE absensi_pln;

SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS presensi;
DROP TABLE IF EXISTS perizinan;
DROP TABLE IF EXISTS akun_intern;
DROP TABLE IF EXISTS akun_mentor;
DROP TABLE IF EXISTS data_intern;
DROP TABLE IF EXISTS data_mentor;
DROP TABLE IF EXISTS lokasi_kantor;
DROP TABLE IF EXISTS jadwal_kerja;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE data_mentor (
  id_mentor INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  tanggal_lahir DATE NOT NULL,
  NIP VARCHAR(100) NOT NULL UNIQUE,
  email VARCHAR(100) NOT NULL UNIQUE,
  no_telp VARCHAR(20) NOT NULL,
  divisi VARCHAR(100) NOT NULL,
  foto VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE data_intern (
  id_intern INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  tanggal_lahir DATE NOT NULL,
  NIM VARCHAR(100) NOT NULL UNIQUE,
  email VARCHAR(100) NOT NULL UNIQUE,
  no_telp VARCHAR(20) NOT NULL,
  asal_universitas VARCHAR(100) NOT NULL,
  program_studi VARCHAR(100) NOT NULL,
  divisi VARCHAR(100) NOT NULL,
  tanggal_mulai DATE NOT NULL,
  tanggal_selesai DATE NOT NULL,
  id_mentor INT NULL,
  foto VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_intern_ke_mentor FOREIGN KEY (id_mentor)
    REFERENCES data_mentor(id_mentor)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE akun_intern (
  id_intern INT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_akun_ke_intern FOREIGN KEY (id_intern)
    REFERENCES data_intern(id_intern) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE akun_mentor (
  id_mentor INT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_akun_ke_mentor FOREIGN KEY (id_mentor)
    REFERENCES data_mentor(id_mentor) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE perizinan (
  id_perizinan INT AUTO_INCREMENT PRIMARY KEY,
  id_intern INT NOT NULL,
  tanggal_izin DATE NOT NULL,
  alasan_izin TEXT NOT NULL,
  status_approval ENUM('menunggu','disetujui','ditolak') NOT NULL DEFAULT 'menunggu',
  id_mentor INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_perizinan_ke_intern FOREIGN KEY (id_intern)
    REFERENCES data_intern(id_intern) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_perizinan_ke_mentor FOREIGN KEY (id_mentor)
    REFERENCES data_mentor(id_mentor) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT unik_izin_harian UNIQUE (id_intern, tanggal_izin)
) ENGINE=InnoDB;

CREATE TABLE presensi (
  id_presensi INT AUTO_INCREMENT PRIMARY KEY,
  id_intern INT NOT NULL,
  tanggal DATE NOT NULL,
  jam_kedatangan TIME NULL,
  jam_pulang TIME NULL,
  foto_datang VARCHAR(255) NULL,
  foto_pulang VARCHAR(255) NULL,
  latitude_datang DECIMAL(10,8) NULL,
  longitude_datang DECIMAL(11,8) NULL,
  latitude_pulang DECIMAL(10,8) NULL,
  longitude_pulang DECIMAL(11,8) NULL,
  status_kehadiran ENUM('Hadir','Izin','Alpha') NOT NULL DEFAULT 'Hadir',
  keterangan VARCHAR(255) NULL,
  id_perizinan INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_presensi_ke_intern FOREIGN KEY (id_intern)
    REFERENCES data_intern(id_intern) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_presensi_ke_perizinan FOREIGN KEY (id_perizinan)
    REFERENCES perizinan(id_perizinan) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT unik_presensi_harian UNIQUE (id_intern, tanggal)
) ENGINE=InnoDB;

CREATE TABLE lokasi_kantor (
  id_lokasi INT AUTO_INCREMENT PRIMARY KEY,
  nama_kantor VARCHAR(100) NOT NULL,
  latitude DECIMAL(10,8) NOT NULL,
  longitude DECIMAL(11,8) NOT NULL,
  radius_meter INT NOT NULL DEFAULT 100
) ENGINE=InnoDB;

CREATE TABLE jadwal_kerja (
  id_jadwal INT AUTO_INCREMENT PRIMARY KEY,
  hari ENUM('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu') NOT NULL UNIQUE,
  jam_masuk TIME NOT NULL,
  jam_pulang TIME NOT NULL
) ENGINE=InnoDB;

INSERT INTO lokasi_kantor (nama_kantor, latitude, longitude, radius_meter)
VALUES ('PLN UP3 Serpong', -6.290760829438223, 106.66449567579366, 100);

INSERT INTO jadwal_kerja (hari,jam_masuk,jam_pulang) VALUES
('Senin','07:30:00','16:30:00'),
('Selasa','07:30:00','16:30:00'),
('Rabu','07:30:00','16:30:00'),
('Kamis','07:30:00','16:30:00'),
('Jumat','07:00:00','17:00:00'),
('Sabtu','00:00:00','00:00:00'),
('Minggu','00:00:00','00:00:00');

-- View opsional untuk daftar intern per mentor/divisi
CREATE OR REPLACE VIEW v_intern_mentor AS
SELECT i.*, m.nama AS nama_mentor
FROM data_intern i
LEFT JOIN data_mentor m ON m.id_mentor=i.id_mentor;
