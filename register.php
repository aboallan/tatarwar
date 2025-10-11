<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if ($currentUser !== null) {
    header('Location: dashboard.php');
    exit;
}

$pageTitle = 'Create an account for the Municipal PMO Suite';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo escape($pageTitle); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body class="auth-shell">
    <main class="auth-wrapper">
        <section class="auth-panel">
            <header class="auth-header">
                <p class="eyebrow">Municipal PMO Suite</p>
                <h1>Create an account</h1>
                <p class="auth-lede">Invite members of your transformation office to collaborate on shared execution plans.</p>
            </header>
            <?php require __DIR__ . '/includes/flash.php'; ?>
            <form method="post" class="auth-form" novalidate>
                <input type="hidden" name="action" value="register">
                <input type="hidden" name="redirect" value="register.php">
                <label>
                    Full Name
                    <input type="text" name="name" required placeholder="e.g., Sara Alotaibi" autocomplete="name">
                </label>
                <label>
                    Work Email
                    <input type="email" name="email" required placeholder="name@municipality.gov.sa" autocomplete="email">
                </label>
                <div class="form-grid">
                    <label>
                        Password
                        <input type="password" name="password" required minlength="8" placeholder="Minimum 8 characters" autocomplete="new-password">
                    </label>
                    <label>
                        Confirm Password
                        <input type="password" name="confirm_password" required minlength="8" placeholder="Re-enter password" autocomplete="new-password">
                    </label>
                </div>
                <button type="submit" class="primary-btn">Create account</button>
            </form>
            <p class="auth-switch">Already have credentials? <a href="login.php">Sign in instead</a>.</p>
        </section>
        <aside class="auth-aside">
            <h2>Ready for a full municipal rollout</h2>
            <p>All departments share one SQLite database (<code>database/app.sqlite</code>) bundled with the project. MAMP automatically serves the PHP application so your MacBook can run demos without extra setup.</p>
            <ul class="auth-highlights">
                <li>Pre-loaded department directory with 14 entities</li>
                <li>Project registry with sponsor and timeline data</li>
                <li>Task risk, impact, and effort scoring for prioritization</li>
            </ul>
        </aside>
    </main>
</body>
</html>
