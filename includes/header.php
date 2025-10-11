<?php
declare(strict_types=1);
/** @var string $pageTitle */
/** @var string $activeNav */
/** @var array|null $currentUser */
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
<body class="app-shell">
    <header class="site-header">
        <div class="header-inner">
            <div class="brand-block">
                <a href="dashboard.php" class="brand">Municipal PMO Suite</a>
                <p class="brand-subtitle">Citywide project and operations control</p>
            </div>
            <nav class="primary-nav" aria-label="Primary">
                <a class="nav-link<?php echo $activeNav === 'dashboard' ? ' active' : ''; ?>" href="dashboard.php">Dashboard</a>
                <a class="nav-link<?php echo $activeNav === 'projects' ? ' active' : ''; ?>" href="projects.php">Projects</a>
                <a class="nav-link<?php echo $activeNav === 'tasks' ? ' active' : ''; ?>" href="tasks.php">Tasks</a>
                <a class="nav-link<?php echo $activeNav === 'departments' ? ' active' : ''; ?>" href="departments.php">Departments</a>
                <a class="nav-link<?php echo $activeNav === 'updates' ? ' active' : ''; ?>" href="updates.php">Updates</a>
            </nav>
            <div class="header-actions">
                <?php if ($currentUser !== null): ?>
                    <span class="user-chip">Signed in as <?php echo escape($currentUser['name']); ?></span>
                    <form method="post" class="logout-form">
                        <input type="hidden" name="action" value="logout">
                        <input type="hidden" name="redirect" value="login.php">
                        <button type="submit" class="ghost-btn">Sign out</button>
                    </form>
                <?php else: ?>
                    <a class="ghost-btn" href="login.php">Sign in</a>
                    <a class="primary-btn" href="register.php">Create account</a>
                <?php endif; ?>
            </div>
        </div>
    </header>
    <div class="page-content">
