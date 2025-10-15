<?php
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = $pageTitle ?? 'Task Management Hub';
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
<body>
    <header class="main-header">
        <div class="branding">
            <span class="logo-circle">TM</span>
            <div>
                <h1>Enterprise Task Management</h1>
                <p>Coordinate work across departments</p>
            </div>
        </div>
        <nav>
            <a href="index.php" class="<?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>">Dashboard</a>
            <a href="tasks.php" class="<?= basename($_SERVER['PHP_SELF']) === 'tasks.php' ? 'active' : ''; ?>">Tasks</a>
            <a href="departments.php" class="<?= basename($_SERVER['PHP_SELF']) === 'departments.php' ? 'active' : ''; ?>">Departments</a>
        </nav>
    </header>
    <main class="container">
