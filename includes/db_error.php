<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database connection issue</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="db-error-page">
    <section class="db-error-card" role="alert" aria-live="assertive">
        <header>
            <h1>We couldn’t reach your MySQL database</h1>
            <p>The workspace UI is ready, but the data layer still needs to be configured. Follow the steps below and refresh when you’re ready.</p>
        </header>
        <?php if (!empty($dbError)): ?>
            <div class="db-error-details"><?= sanitize($dbError); ?></div>
        <?php endif; ?>
        <ol class="db-error-steps">
            <li><strong>Start MySQL in MAMP</strong> and confirm the server is running.</li>
            <li><strong>Import <code>database/schema.sql</code></strong> into your MySQL instance (use phpMyAdmin or the MySQL CLI).</li>
            <li><strong>Update credentials if needed</strong> by editing <code>db.php</code> to match your MAMP username and password.</li>
            <li>Reload this page once the database is available—the dashboard, tasks, and departments will populate automatically.</li>
        </ol>
        <div class="db-error-actions">
            <a href="database/schema.sql" download>Download schema</a>
            <a href="README.md" target="_blank" rel="noopener">Open setup guide</a>
        </div>
    </section>
</body>
</html>
