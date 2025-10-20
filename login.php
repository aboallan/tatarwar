<?php
require_once __DIR__ . '/includes/functions.php';
ensure_session();

if (current_user()) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/db.php';

$errors = [];
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$registeredMessage = get_flash('registered');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }

    if ($password === '') {
        $errors[] = 'Enter your password.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT id, name, email, password_hash, role FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $errors[] = 'Invalid email or password.';
        } else {
            $_SESSION['user'] = [
                'id' => (int)$user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role'],
            ];

            header('Location: index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign in · Task Management Hail Region Municipality</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">
    <div class="auth-shell">
        <section class="auth-illustration">
            <div class="auth-illustration-inner">
                <img class="auth-masthead-logo" src="assets/img/hail-region-municipality-logo.svg" alt="Task Management Hail Region Municipality">
                <h1>Task Management · Hail Region Municipality</h1>
                <p>Coordinate president, manager, and employee workflows in one classic command center.</p>
            </div>
        </section>
        <div class="auth-panel">
            <header class="auth-panel-header">
                <div class="auth-logo">
                    <img src="assets/img/hail-region-municipality-logo.svg" alt="Hail Region Municipality logo">
                </div>
                <div>
                    <h2>Sign in to continue</h2>
                    <p>Access the Task Management Hail Region Municipality workspace.</p>
                </div>
            </header>
            <div class="auth-panel-body">
                <?php if ($registeredMessage): ?>
                    <div class="alert success"><?= sanitize($registeredMessage); ?></div>
                <?php endif; ?>
                <?php if ($errors): ?>
                    <div class="alert error"><?= implode('<br>', array_map('sanitize', $errors)); ?></div>
                <?php endif; ?>
                <form method="post" class="classic-form" novalidate>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?= sanitize($email); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="primary-action">Sign in</button>
                    </div>
                </form>
            </div>
            <div class="auth-actions">
                <span class="auth-note">Need an account?</span>
                <a href="register.php" class="ghost-action">Create one</a>
            </div>
        </div>
    </div>
</body>
</html>
