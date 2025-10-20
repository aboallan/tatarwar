<?php
require_once __DIR__ . '/includes/functions.php';
ensure_session();

if (current_user()) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/db.php';

$errors = [];
$formData = [
    'name' => trim($_POST['name'] ?? ''),
    'email' => trim($_POST['email'] ?? ''),
    'password' => $_POST['password'] ?? '',
    'confirm_password' => $_POST['confirm_password'] ?? '',
    'role' => $_POST['role'] ?? 'employee',
];

$roleOptions = [
    'president' => 'President',
    'manager' => 'Manager',
    'employee' => 'Employee',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($formData['name'] === '') {
        $errors[] = 'Enter your full name.';
    }

    if ($formData['email'] === '' || !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }

    if ($formData['password'] === '') {
        $errors[] = 'Create a password.';
    }

    if ($formData['password'] !== $formData['confirm_password']) {
        $errors[] = 'Passwords do not match.';
    }

    if (!array_key_exists($formData['role'], $roleOptions)) {
        $formData['role'] = 'employee';
    }

    if (!$errors) {
        $existing = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $existing->execute(['email' => $formData['email']]);
        if ($existing->fetch()) {
            $errors[] = 'An account with this email already exists.';
        }
    }

    if (!$errors) {
        $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role, created_at) VALUES (:name, :email, :password_hash, :role, NOW())');
        $stmt->execute([
            'name' => $formData['name'],
            'email' => $formData['email'],
            'password_hash' => password_hash($formData['password'], PASSWORD_DEFAULT),
            'role' => $formData['role'],
        ]);

        set_flash('registered', 'Account created. Sign in to continue.');
        header('Location: login.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create account · Task Management Hail Region Municipality</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">
    <div class="auth-wrapper">
        <section class="auth-intro">
            <span class="auth-badge">Task Management · Hail Region Municipality</span>
            <h1>Create a collaborative workspace</h1>
            <p>Give every department visibility from planning through delivery with shared milestones.</p>
            <ul>
                <li>Role-based access for presidents, managers, and employees</li>
                <li>Deadline-driven calendar and reminder center</li>
                <li>Unified progress and completion reporting</li>
            </ul>
        </section>
        <div class="auth-card">
            <h2>Register</h2>
            <p>Invite your team to manage tasks together.</p>
            <?php if ($errors): ?>
                <div class="alert error"><?= implode('<br>', array_map('sanitize', $errors)); ?></div>
            <?php endif; ?>
            <form method="post" class="classic-form" novalidate>
                <div class="form-group">
                    <label for="name">Full name</label>
                    <input type="text" id="name" name="name" value="<?= sanitize($formData['name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?= sanitize($formData['email']); ?>" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="role">Select your role</label>
                    <select id="role" name="role">
                        <?php foreach ($roleOptions as $value => $label): ?>
                            <option value="<?= $value; ?>" <?= $formData['role'] === $value ? 'selected' : ''; ?>><?= $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" class="primary-action">Create account</button>
                </div>
            </form>
            <div class="auth-actions">
                <span class="auth-note">Already registered?</span>
                <a href="login.php" class="ghost-action">Sign in</a>
            </div>
        </div>
    </div>
</body>
</html>
