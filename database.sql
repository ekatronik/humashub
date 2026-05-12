-- Database Schema for Humas Hub UIN Ar-Raniry
-- Reset Database
DROP DATABASE IF EXISTS humashub;
CREATE DATABASE humashub;
USE humashub;

-- Roles Table
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL UNIQUE
);

-- Users Table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    role_id INT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id)
);

-- Modules Table
CREATE TABLE modules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    module_name VARCHAR(50) NOT NULL UNIQUE,
    display_name VARCHAR(100) NOT NULL,
    icon VARCHAR(50),
    status ENUM('active', 'inactive') DEFAULT 'active',
    sort_order INT DEFAULT 0
);

-- Clipping Categories
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
);

-- Clipping Media Names
CREATE TABLE media (
    id INT AUTO_INCREMENT PRIMARY KEY,
    media_name VARCHAR(100) NOT NULL UNIQUE,
    media_type ENUM('cetak', 'online') DEFAULT 'cetak'
);

-- Clippings Archive
CREATE TABLE clippings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    clipping_date DATE NOT NULL,
    category_id INT,
    media_id INT,
    summary TEXT,
    file_path VARCHAR(255),
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (media_id) REFERENCES media(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- ==========================================
-- DUMMY DATA
-- ==========================================

-- Roles
INSERT INTO roles (id, role_name) VALUES 
(1, 'Admin'), 
(2, 'Petugas Kliping'), 
(3, 'Petugas Foto'), 
(4, 'Petugas Link');

-- Users (Semua password adalah: admin123)
-- Hash untuk 'admin123'
SET @pass = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

INSERT INTO users (username, password, full_name, role_id) VALUES 
('admin', @pass, 'Administrator Utama', 1),
('kliping_user', @pass, 'Petugas Kliping (Dedi)', 2),
('foto_user', @pass, 'Petugas Foto (Sari)', 3);

-- Modules
INSERT INTO modules (module_name, display_name, icon, sort_order) VALUES 
('kliping', 'Arsip Kliping', 'newspaper', 1),
('link_berita', 'Link Berita Online', 'link', 2),
('foto_video', 'Foto & Video Kegiatan', 'camera', 3);

-- Clipping Categories
INSERT INTO categories (name) VALUES 
('Pendidikan'), ('Akademik'), ('Kemahasiswaan'), ('Pengabdian'), ('Infrastruktur');

-- Clipping Media
INSERT INTO media (media_name, media_type) VALUES 
('Serambi Indonesia', 'cetak'), 
('Rakyat Aceh', 'cetak'), 
('Waspada', 'cetak'), 
('Detik.com', 'online'), 
('Aceh Trend', 'online');

-- Clipping Data Dummy
INSERT INTO clippings (title, clipping_date, category_id, media_id, summary, created_by) VALUES 
('UIN Ar-Raniry Wisuda 1.500 Lulusan', '2026-05-01', 2, 1, 'Rektor UIN Ar-Raniry memimpin upacara wisuda semester genap.', 1),
('Pembangunan Gedung Baru Humas Capai 90%', '2026-05-05', 5, 2, 'Gedung baru yang berlokasi di samping Biro Rektorat hampir rampung.', 2),
('Mahasiswa UIN Ar-Raniry Juara MTQ Nasional', '2026-05-08', 3, 3, 'Prestasi gemilang diraih kontingen UIN di ajang nasional.', 2);
