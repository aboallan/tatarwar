<?php
session_start();

require_once __DIR__ . '/data.php';

$flash = $_SESSION['flash'] ?? [];
unset($_SESSION['flash']);

$departments = meeting_departments();
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meeting Brief Platform · Create Account</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body class="app app--auth page--register">
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
            <h1>Issue an accreditation code</h1>
            <p>Register your department lead to generate a unique accreditation code and unlock the attendance dashboard.</p>
            <a class="link" href="index.php">Return to overview</a>
        </section>
        <section class="auth auth--card" aria-labelledby="register-heading">
            <header class="auth__header">
                <h2 id="register-heading">Create an account</h2>
                <p>Provide the required details to coordinate meeting attendance.</p>
            </header>
            <form id="auth-register" action="auth.php" method="post" class="auth__form" novalidate>
                <input type="hidden" name="action" value="register">
                <div class="field">
                    <label for="register-name">Full name</label>
                    <input id="register-name" name="name" type="text" autocomplete="name" required>
                </div>
                <div class="field">
                    <label for="register-department">Department</label>
                    <select id="register-department" name="department" required>
                        <option value="" disabled selected>Select a department</option>
                        <?php foreach ($departments as $department): ?>
                            <option value="<?= htmlspecialchars($department, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($department, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="register-email">Corporate email</label>
                    <input id="register-email" name="email" type="email" autocomplete="email" required>
                </div>
                <div class="field">
                    <label for="register-password">Password</label>
                    <input id="register-password" name="password" type="password" autocomplete="new-password" minlength="8" required>
                </div>
                <div class="field">
                    <label for="register-confirm">Confirm password</label>
                    <input id="register-confirm" name="confirm" type="password" autocomplete="new-password" minlength="8" required>
                </div>
                <button type="submit" class="btn btn--primary btn--full">Create account &amp; issue code</button>
            </form>
            <footer class="auth__footer">
                <span>Already registered?</span>
                <a class="link" href="login.php">Sign in here</a>
            </footer>
        </section>
    </main>

    <script src="script.js" defer></script>
</body>
</html>
