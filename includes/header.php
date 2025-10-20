<?php
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = $pageTitle ?? 'Task Management Hub';
$pageDescription = $pageDescription ?? 'Coordinate departmental workstreams in one place.';
$workspaceUpdatedAt = date('M j, Y');
$requireAuth = $requireAuth ?? true;

ensure_session();
$currentUser = current_user();

if ($requireAuth && !$currentUser) {
    header('Location: login.php');
    exit;
}

$userName = $currentUser['name'] ?? 'Guest';
$userRole = isset($currentUser['role']) ? user_role_label($currentUser['role']) : '';
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($pageTitle); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="app-body">
    <div class="app-shell">
        <aside class="sidebar" aria-label="Primary">
            <div class="sidebar-brand">
                <span class="brand-icon" aria-hidden="true">TM</span>
                <div class="brand-copy">
                    <span class="brand-title">TaskMaster</span>
                    <span class="brand-subtitle">City Coordination</span>
                </div>
            </div>
            <nav class="sidebar-nav" role="navigation">
                <a href="index.php" class="nav-link <?= $currentPage === 'index.php' ? 'active' : ''; ?>">Dashboard</a>
                <a href="tasks.php" class="nav-link <?= $currentPage === 'tasks.php' ? 'active' : ''; ?>">Tasks</a>
                <a href="task_create.php" class="nav-link <?= $currentPage === 'task_create.php' ? 'active' : ''; ?>">Create Task</a>
                <a href="departments.php" class="nav-link <?= $currentPage === 'departments.php' ? 'active' : ''; ?>">Departments</a>
                <a href="calendar.php" class="nav-link <?= $currentPage === 'calendar.php' ? 'active' : ''; ?>">Calendar</a>
                <a href="reminders.php" class="nav-link <?= $currentPage === 'reminders.php' ? 'active' : ''; ?>">Reminders</a>
            </nav>
            <div class="sidebar-footer">
                <strong>Workspace tips</strong>
                <p>Assign clear due dates so the calendar and reminders stay aligned.</p>
            </div>
        </aside>
        <div class="main-area">
            <header class="topbar">
                <div class="topbar-brand">
                    <span class="topbar-eyebrow">City Task Office</span>
                    <strong>TaskMaster Console</strong>
                </div>
                <div class="topbar-actions">
                    <span class="status-pill" role="status">
                        <span class="status-dot"></span>
                        Synced <?= sanitize($workspaceUpdatedAt); ?>
                    </span>
                    <div class="user-chip">
                        <span class="user-name"><?= sanitize($userName); ?></span>
                        <?php if ($userRole): ?>
                            <span class="user-role"><?= sanitize($userRole); ?></span>
                        <?php endif; ?>
                    </div>
                    <a href="logout.php" class="ghost-action">Log out</a>
                    <a href="task_create.php" class="primary-action">New task</a>
                </div>
            </header>
            <div class="page-header">
                <div class="page-header-copy">
                    <h1><?= sanitize($pageTitle); ?></h1>
                    <?php if (!empty($pageDescription)): ?>
                        <p><?= sanitize($pageDescription); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <main class="page-content">
