CREATE DATABASE IF NOT EXISTS meeting_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE meeting_portal;

CREATE TABLE IF NOT EXISTS meeting_users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    department VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    unique_code CHAR(10) NOT NULL UNIQUE,
    attendance_status ENUM('pending', 'attend', 'decline') DEFAULT 'pending',
    location_preference VARCHAR(160) DEFAULT '',
    department_scope ENUM('all', 'specific') DEFAULT 'all',
    department_focus VARCHAR(160) DEFAULT '',
    meeting_summary TEXT NULL,
    responded_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO meeting_users (name, department, email, password_hash, unique_code, attendance_status)
VALUES
    (
        'مسؤول النظام',
        'General Administration of Information Technology',
        'admin@company.com',
        '$2y$12$91.F9ZjKoD9RgZbuJLXUM.qBLmNkIzH.5CoRY9GrZJkGljYu7GNlC',
        'ADM0000001',
        'attend'
    );
