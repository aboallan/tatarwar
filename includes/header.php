<?php
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = $pageTitle ?? 'Task Management Hub';
$pageDescription = $pageDescription ?? 'Coordinate departmental workstreams in one place.';
$workspaceUpdatedAt = date('M j, Y');
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($pageTitle); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="app-shell">
        <aside class="sidebar" aria-label="Primary">
            <div class="sidebar-brand">
                <span class="brand-icon" aria-hidden="true">TF</span>
                <div class="brand-copy">
                    <span class="brand-title">TaskFlow</span>
                    <span class="brand-subtitle">Department Manager</span>
                </div>
            </div>
            <nav class="sidebar-nav" role="navigation">
                <a href="index.php" class="nav-link <?= $currentPage === 'index.php' ? 'active' : ''; ?>">Dashboard</a>
                <a href="tasks.php" class="nav-link <?= $currentPage === 'tasks.php' ? 'active' : ''; ?>">Tasks</a>
                <a href="departments.php" class="nav-link <?= $currentPage === 'departments.php' ? 'active' : ''; ?>">Departments</a>
                <span class="nav-link muted" aria-disabled="true">Calendar</span>
            </nav>
        </aside>
        <div class="main-area">
            <header class="page-header">
                <div class="page-titles">
                    <h1><?= sanitize($pageTitle); ?></h1>
                    <?php if (!empty($pageDescription)): ?>
                        <p><?= sanitize($pageDescription); ?></p>
                    <?php endif; ?>
                </div>
                <div class="header-actions">
                    <span class="status-pill" role="status">
                        <span class="status-dot"></span>
                        Updated <?= sanitize($workspaceUpdatedAt); ?>
                    </span>
                    <a href="tasks.php" class="primary-action">New Task</a>
                </div>
            </header>
            <main class="page-content">
