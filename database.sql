CREATE DATABASE IF NOT EXISTS rumah_sakit CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE rumah_sakit;

CREATE TABLE branches (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(20) NOT NULL UNIQUE,
  name VARCHAR(100) NOT NULL,
  address VARCHAR(255) NULL,
  is_center TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  branch_id INT UNSIGNED NOT NULL,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  full_name VARCHAR(120) NOT NULL,
  role ENUM('admin','doctor','nurse','staff') NOT NULL DEFAULT 'staff',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_users_branch FOREIGN KEY (branch_id) REFERENCES branches(id)
) ENGINE=InnoDB;

CREATE TABLE doctors (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(150) NOT NULL,
  specialty VARCHAR(100) NULL,
  license_no VARCHAR(50) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_doctors_license (license_no),
  INDEX idx_doctors_active_name (is_active, full_name)
) ENGINE=InnoDB;

CREATE TABLE patients (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  medical_record_no VARCHAR(30) NOT NULL UNIQUE,
  nik VARCHAR(30) NULL UNIQUE,
  full_name VARCHAR(150) NOT NULL,
  birth_date DATE NULL,
  gender ENUM('L','P') NULL,
  phone VARCHAR(30) NULL,
  address TEXT NULL,
  registered_branch_id INT UNSIGNED NOT NULL,
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_patients_branch FOREIGN KEY (registered_branch_id) REFERENCES branches(id),
  CONSTRAINT fk_patients_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_patients_branch (registered_branch_id), INDEX idx_patients_name (full_name)
) ENGINE=InnoDB;

CREATE TABLE medical_records (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  patient_id BIGINT UNSIGNED NOT NULL,
  branch_id INT UNSIGNED NOT NULL,
  doctor_id INT UNSIGNED NULL,
  doctor_name VARCHAR(150) NOT NULL,
  visit_date DATETIME NOT NULL,
  complaint TEXT NOT NULL,
  diagnosis TEXT NOT NULL,
  treatment TEXT NULL,
  prescription TEXT NULL,
  notes TEXT NULL,
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_records_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
  CONSTRAINT fk_records_branch FOREIGN KEY (branch_id) REFERENCES branches(id),
  CONSTRAINT fk_records_doctor FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE SET NULL,
  CONSTRAINT fk_records_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_records_patient (patient_id), INDEX idx_records_branch_date (branch_id, visit_date), INDEX idx_records_doctor (doctor_id)
) ENGINE=InnoDB;

INSERT INTO branches (code,name,address,is_center) VALUES
('PUSAT','Rumah Sakit Pusat','Jl. Kesehatan No. 1',1),
('CABANG1','Rumah Sakit Cabang 1','Jl. Melati No. 1',0),
('CABANG2','Rumah Sakit Cabang 2','Jl. Mawar No. 2',0),
('CABANG3','Rumah Sakit Cabang 3','Jl. Anggrek No. 3',0);

-- Password semua akun demo: password
INSERT INTO users (branch_id,username,password_hash,full_name,role) VALUES
(1,'pusat', '$2y$10$jJ0g4fdwpqxhV94FpETDxOViBeXkwgeC8I4kY21QnM7Ugj.yBQiYe', 'Admin Rumah Sakit Pusat','admin'),
(2,'cabang1', '$2y$10$jJ0g4fdwpqxhV94FpETDxOViBeXkwgeC8I4kY21QnM7Ugj.yBQiYe', 'Petugas Cabang 1','staff'),
(3,'cabang2', '$2y$10$jJ0g4fdwpqxhV94FpETDxOViBeXkwgeC8I4kY21QnM7Ugj.yBQiYe', 'Petugas Cabang 2','staff'),
(4,'cabang3', '$2y$10$jJ0g4fdwpqxhV94FpETDxOViBeXkwgeC8I4kY21QnM7Ugj.yBQiYe', 'Petugas Cabang 3','staff');

-- Contoh referensi dokter. Admin dapat menambah/mengaktifkan dokter dari menu Dokter.
INSERT INTO doctors (full_name, specialty, license_no) VALUES
('dr. Siti Rahma, Sp.PD', 'Penyakit Dalam', 'DOK-0001'),
('dr. Budi Santoso, Sp.A', 'Anak', 'DOK-0002');

-- Hash di atas adalah password: password
