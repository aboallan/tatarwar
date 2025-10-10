<?php

declare(strict_types=1);

require_once __DIR__ . '/seeds.php';

const DB_HOST = 'localhost';
const DB_NAME = 'meeting_portal';
const DB_USER = 'root';
const DB_PASS = 'root';

function get_pdo(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    $databaseDsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_NAME);
    $hostDsn = sprintf('mysql:host=%s;charset=utf8mb4', DB_HOST);

    try {
        $pdo = new PDO($databaseDsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $exception) {
        if ((int) $exception->getCode() !== 1049) {
            throw $exception;
        }

        $bootstrapPdo = new PDO($hostDsn, DB_USER, DB_PASS, $options);
        $bootstrapPdo->exec(sprintf(
            'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
            DB_NAME
        ));
        $bootstrapPdo = null;

        $pdo = new PDO($databaseDsn, DB_USER, DB_PASS, $options);
    }

    meeting_initialize_schema($pdo);

    return $pdo;
}

function meeting_initialize_schema(PDO $pdo): void
{
    static $initialized = false;
    if ($initialized) {
        return;
    }

    $pdo->exec('CREATE TABLE IF NOT EXISTS meeting_users (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(120) NOT NULL,
        department VARCHAR(120) NOT NULL,
        email VARCHAR(160) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        unique_code CHAR(10) NOT NULL UNIQUE,
        attendance_status ENUM(\'pending\', \'attend\', \'decline\') DEFAULT \'pending\',
        location_preference VARCHAR(160) DEFAULT \'\',
        department_scope ENUM(\'all\', \'specific\') DEFAULT \'all\',
        department_focus VARCHAR(160) DEFAULT \'\',
        meeting_summary TEXT NULL,
        responded_at TIMESTAMP NULL DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

    $pdo->exec('CREATE TABLE IF NOT EXISTS meeting_departments (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(160) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

    $pdo->exec('CREATE TABLE IF NOT EXISTS meeting_agenda (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        scheduled_at DATETIME NOT NULL,
        tag VARCHAR(60) NOT NULL,
        department VARCHAR(160) NOT NULL,
        location VARCHAR(160) NOT NULL,
        summary TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_meeting_agenda_title_time (title, scheduled_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

    $pdo->exec('CREATE TABLE IF NOT EXISTS meeting_updates (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        headline VARCHAR(255) NOT NULL,
        body TEXT NOT NULL,
        department VARCHAR(160) DEFAULT NULL,
        author VARCHAR(160) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

    $pdo->exec('CREATE TABLE IF NOT EXISTS meeting_highlights (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        highlight TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

    $pdo->exec('CREATE TABLE IF NOT EXISTS meeting_venues (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(160) NOT NULL,
        capacity INT DEFAULT 0,
        facilitator VARCHAR(160) DEFAULT NULL,
        notes TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_meeting_venues_name (name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

    $pdo->exec('CREATE TABLE IF NOT EXISTS meeting_attendance_roster (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        department VARCHAR(160) NOT NULL,
        status ENUM(\'pending\', \'attend\', \'decline\') DEFAULT \'pending\',
        representatives INT DEFAULT 0,
        location VARCHAR(160) DEFAULT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_meeting_attendance_department (department)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

    $departmentStmt = $pdo->prepare('INSERT INTO meeting_departments (name) VALUES (?)
        ON DUPLICATE KEY UPDATE name = VALUES(name)');
    foreach (MEETING_SEED_DEPARTMENTS as $department) {
        $departmentStmt->execute([$department]);
    }

    $admin = MEETING_SEED_ADMIN_USER;
    $adminStmt = $pdo->prepare('INSERT INTO meeting_users
        (name, department, email, password_hash, unique_code, attendance_status)
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            name = VALUES(name),
            department = VALUES(department),
            password_hash = VALUES(password_hash),
            unique_code = VALUES(unique_code),
            attendance_status = VALUES(attendance_status)');
    $adminStmt->execute([
        $admin['name'],
        $admin['department'],
        $admin['email'],
        $admin['password_hash'],
        $admin['unique_code'],
        $admin['attendance_status'],
    ]);

    $agendaStmt = $pdo->prepare('INSERT INTO meeting_agenda (title, scheduled_at, tag, department, location, summary)
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            tag = VALUES(tag),
            department = VALUES(department),
            location = VALUES(location),
            summary = VALUES(summary)');
    foreach (MEETING_SEED_AGENDA as $agenda) {
        $agendaStmt->execute([
            $agenda['title'],
            $agenda['scheduled_at'],
            $agenda['tag'],
            $agenda['department'],
            $agenda['location'],
            $agenda['summary'],
        ]);
    }

    $venueStmt = $pdo->prepare('INSERT INTO meeting_venues (name, capacity, facilitator, notes)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            capacity = VALUES(capacity),
            facilitator = VALUES(facilitator),
            notes = VALUES(notes)');
    foreach (MEETING_SEED_VENUES as $venue) {
        $venueStmt->execute([
            $venue['name'],
            $venue['capacity'],
            $venue['facilitator'],
            $venue['notes'],
        ]);
    }

    $attendanceStmt = $pdo->prepare('INSERT INTO meeting_attendance_roster (department, status, representatives, location)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            status = VALUES(status),
            representatives = VALUES(representatives),
            location = VALUES(location)');
    foreach (MEETING_SEED_ATTENDANCE as $record) {
        $attendanceStmt->execute([
            $record['department'],
            $record['status'],
            $record['representatives'],
            $record['location'],
        ]);
    }

    $updatesCount = (int) $pdo->query('SELECT COUNT(*) FROM meeting_updates')->fetchColumn();
    if ($updatesCount === 0) {
        $updateStmt = $pdo->prepare('INSERT INTO meeting_updates (headline, body, department, author, created_at)
            VALUES (?, ?, ?, ?, ?)');
        foreach (MEETING_SEED_UPDATES as $update) {
            $updateStmt->execute([
                $update['headline'],
                $update['body'],
                $update['department'],
                $update['author'],
                $update['created_at'],
            ]);
        }
    }

    $highlightsCount = (int) $pdo->query('SELECT COUNT(*) FROM meeting_highlights')->fetchColumn();
    if ($highlightsCount === 0) {
        $highlightStmt = $pdo->prepare('INSERT INTO meeting_highlights (highlight) VALUES (?)');
        foreach (MEETING_SEED_HIGHLIGHTS as $highlight) {
            $highlightStmt->execute([$highlight]);
        }
    }

    $initialized = true;
}
