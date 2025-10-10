CREATE DATABASE IF NOT EXISTS meeting_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE meeting_portal;

-- Default coordinator credentials
-- Email: admin@company.com
-- Password: Portal@2024!

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

CREATE TABLE IF NOT EXISTS meeting_departments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(160) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS meeting_agenda (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    scheduled_at DATETIME NOT NULL,
    tag VARCHAR(60) NOT NULL,
    department VARCHAR(160) NOT NULL,
    location VARCHAR(160) NOT NULL,
    summary TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_meeting_agenda_title_time (title, scheduled_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS meeting_updates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    headline VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    department VARCHAR(160) DEFAULT NULL,
    author VARCHAR(160) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS meeting_highlights (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    highlight TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS meeting_venues (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(160) NOT NULL,
    capacity INT DEFAULT 0,
    facilitator VARCHAR(160) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_meeting_venues_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS meeting_attendance_roster (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    department VARCHAR(160) NOT NULL,
    status ENUM('pending', 'attend', 'decline') DEFAULT 'pending',
    representatives INT DEFAULT 0,
    location VARCHAR(160) DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_meeting_attendance_department (department)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO meeting_departments (name)
VALUES
    ('Central City Municipality'),
    ('Central Unit for Plan Approvals'),
    ('Cybersecurity Department'),
    ('Environmental Health Department'),
    ('General Administration of Information Technology'),
    ('Internal Audit Department'),
    ('Investment Agency'),
    ('Land Management Department'),
    ('North Municipality'),
    ('Operations and Emergency Department'),
    ('Public Cleaning Department'),
    ('Public Gardens and Beautification Department'),
    ('Security and Safety Department')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO meeting_users (name, department, email, password_hash, unique_code, attendance_status)
VALUES
    (
        'System Administrator',
        'General Administration of Information Technology',
        'admin@company.com',
        '$2y$12$XDvDBBsNL5FH2SKfhQa3ee9h8D3z5ZqOE.vHcltde9McDhObOpag2',
        'ADM0000001',
        'attend'
    )
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    department = VALUES(department),
    password_hash = VALUES(password_hash),
    unique_code = VALUES(unique_code),
    attendance_status = VALUES(attendance_status);

INSERT INTO meeting_agenda (title, scheduled_at, tag, department, location, summary) VALUES
    ('Digital Transformation Roadmap Review', '2024-07-25 09:30:00', 'Strategic', 'General Administration of Information Technology', 'Innovation Hall - Headquarters', 'Align the digital roadmap with current infrastructure projects and cybersecurity initiatives.'),
    ('Cybersecurity Preparedness Assessment', '2024-07-25 11:15:00', 'Risk', 'Cybersecurity Department', 'Command & Control Center - Third Floor', 'Present penetration test results and assign remediation actions with a clear timeline.'),
    ('Activating Joint Investment Projects', '2024-07-25 13:00:00', 'Executive', 'Investment Agency', 'Executive Meeting Hall - Tower A', 'Review cross-functional investment opportunities and confirm the funding schedule.')
ON DUPLICATE KEY UPDATE
    tag = VALUES(tag),
    department = VALUES(department),
    location = VALUES(location),
    summary = VALUES(summary);

INSERT INTO meeting_updates (headline, body, department, author) VALUES
    ('Logistics confirmation', 'Transportation shuttles will run every 20 minutes between the central garage and the headquarters entrance.', 'Operations and Emergency Department', 'Logistics Desk'),
    ('Presentation deadline', 'Submit final presentation decks by Monday at noon to be included in the consolidated briefing.', 'General Administration of Information Technology', 'Meeting Secretariat');

INSERT INTO meeting_highlights (highlight) VALUES
    ('Interactive deck for the digital transformation roadmap is 92% complete.'),
    ('Attendance confirmed by 9 departments so far with unified logistics in place.'),
    ('Departments are requested to submit final remarks by Monday at 12:00 PM.');

INSERT INTO meeting_venues (name, capacity, facilitator, notes) VALUES
    ('Innovation Hall - Headquarters', 220, 'Eng. Nasser Al-Hamzani', 'Primary plenary space with hybrid meeting capability.'),
    ('Executive Meeting Hall - Tower A', 30, 'Ms. Sarah Al-Humeidhi', 'Reserved for executive briefings and strategy alignment.'),
    ('Command & Control Center - Third Floor', 45, 'Eng. Ibrahim Al-Dughaither', 'Equipped with live monitoring dashboards for operations.'),
    ('Virtual Platform via Microsoft Teams', 500, 'Ms. Lama Al-Assaf', 'Digital access with simultaneous translation available.')
ON DUPLICATE KEY UPDATE
    capacity = VALUES(capacity),
    facilitator = VALUES(facilitator),
    notes = VALUES(notes);

INSERT INTO meeting_attendance_roster (department, status, representatives, location) VALUES
    ('General Administration of Information Technology', 'attend', 12, 'Innovation Hall - Headquarters'),
    ('Cybersecurity Department', 'attend', 8, 'Command & Control Center - Third Floor'),
    ('Investment Agency', 'pending', 6, 'Executive Meeting Hall - Tower A'),
    ('Public Gardens and Beautification Department', 'decline', 4, 'Virtual Platform via Microsoft Teams')
ON DUPLICATE KEY UPDATE
    status = VALUES(status),
    representatives = VALUES(representatives),
    location = VALUES(location);
