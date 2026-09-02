-- MySQL Dump / Schema untuk EDMS RSGM Universitas Airlangga

CREATE DATABASE IF NOT EXISTS edms_rsgm;
USE edms_rsgm;

-- 1. Tabel Users (Administrator)
CREATE TABLE IF NOT EXISTS users (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabel Employees (Pegawai)
CREATE TABLE IF NOT EXISTS employees (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    nip VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    gelar VARCHAR(100) DEFAULT NULL,
    employee_type VARCHAR(100) NOT NULL, -- PNS, Non-PNS, Kontrak, Honorer
    status_kepegawaian VARCHAR(100) NOT NULL DEFAULT 'PNS', -- PNS, P3K, Pegawai Tetap (PT), Kontrak / Honorer
    is_active TINYINT(1) DEFAULT 1,
    deleted_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_deleted_at (deleted_at),
    INDEX idx_nip (nip),
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabel Documents (Kategori Dokumen Pegawai)
CREATE TABLE IF NOT EXISTS documents (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    employee_id BIGINT NOT NULL,
    document_type VARCHAR(100) NOT NULL, -- Ijazah, STR, SIP, Sertifikat, dll.
    deleted_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    INDEX idx_deleted_at (deleted_at),
    INDEX idx_employee_id (employee_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabel Document Versions (Riwayat Versi Dokumen)
CREATE TABLE IF NOT EXISTS document_versions (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    document_id BIGINT NOT NULL,
    version_number INT NOT NULL,
    document_number VARCHAR(100) DEFAULT NULL,
    start_date DATE DEFAULT NULL,
    expired_date DATE DEFAULT NULL,
    file_path VARCHAR(255) NOT NULL,
    uploaded_by BIGINT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_document_id (document_id),
    INDEX idx_expired_date (expired_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seeding akun admin default
-- Username: admin
-- Password: admin123 (hashed menggunakan password_hash dengan BCRYPT)
INSERT INTO users (name, username, password) VALUES 
('Muhammad Rizal Ramdhani, S.Kom', 'admin', '$2y$10$ECFd0KFKnqjQywqEp4lq5OgiGIeJAOAXU5wSgt4BFwwea3FxCl0QG')
ON DUPLICATE KEY UPDATE id=id;
