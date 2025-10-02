<?php
session_start();
require_once __DIR__ . '/config.php';

$departments = [
    'General Administration of Information Technology',
    'Cybersecurity Department',
    'Investment Agency',
    'Public Gardens and Beautification Department',
    'Internal Audit Department',
    'Operations and Emergency Department',
    'Security and Safety Department',
    'Central Unit for Plan Approvals',
    'Land Management Department',
    'Environmental Health Department',
    'Public Cleaning Department',
    'Central City Municipality',
    'North Municipality',
];

$locations = [
    'Innovation Hall - Headquarters',
    'Executive Meeting Hall - Tower A',
    'Command & Control Center - Third Floor',
    'Virtual Platform via Microsoft Teams',
];

function redirect_with_flash(string $type, string $title, string $message, string $context = 'login'): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'title' => $title,
        'message' => $message,
        'context' => $context,
    ];
    header('Location: index.php#auth-card');
    exit;
}

$action = $_POST['action'] ?? '';
if (!in_array($action, ['login', 'register', 'update'], true)) {
    redirect_with_flash('error', 'Unknown request', 'Please use the designated forms within the platform.');
}

try {
    $pdo = get_pdo();
} catch (PDOException $exception) {
    redirect_with_flash('error', 'Database connection failed', 'Verify the connection settings in config.php and try again.', $action);
}

function generate_unique_code(PDO $pdo): string
{
    do {
        $candidate = 'RM' . strtoupper(bin2hex(random_bytes(4)));
        $stmt = $pdo->prepare('SELECT id FROM meeting_users WHERE unique_code = ? LIMIT 1');
        $stmt->execute([$candidate]);
        $exists = $stmt->fetch();
    } while ($exists);

    return $candidate;
}

if ($action === 'register') {
    $name = trim($_POST['name'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if ($name === '' || $department === '' || $email === '' || $password === '') {
        redirect_with_flash('error', 'Missing fields', 'Please complete all required fields to create your account.', 'register');
    }

    if (!in_array($department, $departments, true)) {
        redirect_with_flash('error', 'Unknown department', 'Select a department from the approved list.', 'register');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        redirect_with_flash('error', 'Invalid email', 'Enter a valid corporate email address.', 'register');
    }

    if ($password !== $confirm) {
        redirect_with_flash('error', 'Passwords do not match', 'Ensure the password matches its confirmation.', 'register');
    }

    if (strlen($password) < 8) {
        redirect_with_flash('error', 'Password too short', 'Use at least 8 characters to strengthen security.', 'register');
    }

    $stmt = $pdo->prepare('SELECT id FROM meeting_users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        redirect_with_flash('error', 'Email already used', 'This email is already registered. Sign in or use another address.', 'register');
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $uniqueCode = generate_unique_code($pdo);

    $insert = $pdo->prepare('INSERT INTO meeting_users (name, department, email, password_hash, unique_code) VALUES (?, ?, ?, ?, ?)');
    $insert->execute([$name, $department, $email, $hash, $uniqueCode]);

    $message = sprintf('Account created successfully. Your approved accreditation code: %s — keep it safe to confirm your attendance.', $uniqueCode);
    redirect_with_flash('success', 'New account ready', $message, 'login');
}

if ($action === 'login') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        redirect_with_flash('error', 'Credentials required', 'Enter both your email address and password.', 'login');
    }

    $stmt = $pdo->prepare('SELECT id, name, password_hash, unique_code, department, attendance_status, location_preference, department_scope, department_focus, meeting_summary FROM meeting_users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        redirect_with_flash('error', 'Invalid credentials', 'We could not verify the provided details. Please try again.', 'login');
    }

    $_SESSION['user'] = [
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $email,
        'unique_code' => $user['unique_code'],
        'department' => $user['department'],
        'attendance_status' => $user['attendance_status'],
        'location_preference' => $user['location_preference'],
        'department_scope' => $user['department_scope'],
        'department_focus' => $user['department_focus'],
        'meeting_summary' => $user['meeting_summary'],
    ];

    $message = sprintf('Signed in successfully. Your accreditation code: %s.', $user['unique_code']);
    redirect_with_flash('success', 'Welcome back', $message, 'login');
}

if ($action === 'update') {
    $uniqueCode = strtoupper(trim($_POST['unique_code'] ?? ''));
    $attendance = $_POST['attendance_status'] ?? 'pending';
    $location = trim($_POST['location_preference'] ?? '');
    $scope = $_POST['department_scope'] ?? 'all';
    $focus = trim($_POST['department_focus'] ?? '');
    $summary = trim($_POST['meeting_summary'] ?? '');

    if ($uniqueCode === '') {
        redirect_with_flash('error', 'Accreditation code required', 'Provide your approved accreditation code to manage attendance.', 'login');
    }

    if (!in_array($attendance, ['pending', 'attend', 'decline'], true)) {
        redirect_with_flash('error', 'Invalid status', 'Choose a valid attendance status from the list.', 'login');
    }

    if ($location !== '' && !in_array($location, $locations, true)) {
        redirect_with_flash('error', 'Unknown location', 'Select a meeting venue from the approved list.', 'login');
    }

    if (!in_array($scope, ['all', 'specific'], true)) {
        redirect_with_flash('error', 'Invalid scope', 'Specify whether participation includes all departments or a specific one.', 'login');
    }

    if ($scope === 'specific') {
        if (!in_array($focus, $departments, true)) {
            redirect_with_flash('error', 'Department not found', 'Choose the target department from the list.', 'login');
        }
    } else {
        $focus = '';
    }

    $stmt = $pdo->prepare('UPDATE meeting_users SET attendance_status = ?, location_preference = ?, department_scope = ?, department_focus = ?, meeting_summary = ?, responded_at = NOW() WHERE unique_code = ?');
    $stmt->execute([$attendance, $location, $scope, $focus, $summary === '' ? null : $summary, $uniqueCode]);

    if ($stmt->rowCount() === 0) {
        redirect_with_flash('error', 'Account not found', 'No record matched the accreditation code provided.', 'login');
    }

    if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
        $_SESSION['user'] = [
            'unique_code' => $uniqueCode,
        ];
    }

    $_SESSION['user']['unique_code'] = $uniqueCode;
    $_SESSION['user']['attendance_status'] = $attendance;
    $_SESSION['user']['location_preference'] = $location;
    $_SESSION['user']['department_scope'] = $scope;
    $_SESSION['user']['department_focus'] = $focus;
    $_SESSION['user']['meeting_summary'] = $summary;

    redirect_with_flash('success', 'Attendance updated', 'Your preferences and meeting summary were saved successfully.', 'login');
}
