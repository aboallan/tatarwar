<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if ($currentUser !== null) {
    header('Location: dashboard.php');
    exit;
}

$pageTitle = 'Sign in to the Municipal PMO Suite';
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
                <h1>Sign in</h1>
                <p class="auth-lede">Access the command environment to coordinate projects, assignments, and cross-department execution.</p>
            </header>
            <?php require __DIR__ . '/includes/flash.php'; ?>
            <form method="post" class="auth-form" novalidate>
                <input type="hidden" name="action" value="login">
                <input type="hidden" name="redirect" value="login.php">
                <label>
                    Work Email
                    <input type="email" name="email" required placeholder="you@municipality.gov.sa" autocomplete="email">
                </label>
                <label>
                    Password
                    <input type="password" name="password" required placeholder="Enter your password" autocomplete="current-password">
                </label>
                <button type="submit" class="primary-btn">Sign in</button>
            </form>
            <p class="auth-switch">Need access? <a href="register.php">Create an account</a>.</p>
        </section>
        <aside class="auth-aside">
            <h2>Made for MAMP &amp; MacBook setups</h2>
            <p>Drop the project inside your <code>htdocs</code> folder, start the MAMP servers, and open <strong>http://localhost:8888/tatarwar</strong> to reach this login portal.</p>
            <ul class="auth-highlights">
                <li>Role-based dashboards for every directorate</li>
                <li>Portfolio analytics tailored to municipal KPIs</li>
                <li>Task, project, and risk tracking in one workspace</li>
            </ul>
        </aside>
    </main>
</body>
</html>
