<?php
session_start();

require_once __DIR__ . '/repository.php';

if (isset($_SESSION['user']['id'])) {
    header('Location: dashboard.php');
    exit;
}

$flash = $_SESSION['flash'] ?? [];
unset($_SESSION['flash']);
$repositoryErrors = meeting_repository_errors();
$repositoryConnected = meeting_repository_connected();
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meeting Brief Platform · Sign In</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body class="app app--auth page--login">
    <div class="app__backdrop" aria-hidden="true"></div>

    <?php if (!empty($flash)): ?>
        <aside class="toast toast--<?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8') ?>" role="alert">
            <div class="toast__header">
                <strong><?= htmlspecialchars($flash['title'], ENT_QUOTES, 'UTF-8') ?></strong>
                <button type="button" class="toast__close" data-dismiss-toast aria-label="Close">×</button>
            </div>
            <p><?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?></p>
        </aside>
    <?php endif; ?>

    <main class="auth-shell auth-shell--split">
        <section class="auth-intro">
            <div class="auth-intro__brand">
                <span class="sidebar__icon">🗂️</span>
                <div>
                    <strong>Meeting Portal</strong>
                    <span>Department Coordination</span>
                </div>
            </div>
            <h1>Welcome back</h1>
            <p>Sign in to retrieve your accreditation code, confirm attendance, and stay aligned with every department.</p>
            <a class="link" href="index.php">Return to overview</a>
        </section>
        <section class="auth auth--card" aria-labelledby="sign-in-heading">
            <header class="auth__header">
                <h2 id="sign-in-heading">Sign in to continue</h2>
                <p>Enter your credentials to access the coordination dashboard.</p>
            </header>
            <?php if (!$repositoryConnected && !empty($repositoryErrors)): ?>
                <div class="alert alert--error">
                    <strong>Database connection required</strong>
                    <ul>
                        <?php foreach ($repositoryErrors as $error): ?>
                            <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="alert__hint">Finish the MySQL setup before attempting to sign in.</p>
                </div>
            <?php endif; ?>
            <form action="auth.php" method="post" class="auth__form">
                <input type="hidden" name="action" value="login">
                <div class="field">
                    <label for="login-email">Email address</label>
                    <input id="login-email" name="email" type="email" autocomplete="email" required>
                </div>
                <div class="field">
                    <label for="login-password">Password</label>
                    <input id="login-password" name="password" type="password" autocomplete="current-password" required>
                </div>
                <button type="submit" class="btn btn--primary btn--full">Sign In</button>
            </form>
            <div class="empty-state">
                <strong>Default coordinator account</strong>
                <span>Use <code>admin@company.com</code> with the password <code>Portal@2024!</code> after importing the database.</span>
            </div>
            <footer class="auth__footer">
                <span>Need an account?</span>
                <a class="link" href="register.php">Create one now</a>
            </footer>
        </section>
    </main>

    <script src="script.js" defer></script>
</body>
</html>
