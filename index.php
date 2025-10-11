<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/app.php';

$pdo = getDatabaseConnection();
$currentUser = getAuthenticatedUser($pdo);

$destination = $currentUser === null ? 'login.php' : 'dashboard.php';
header('Location: ' . $destination);
exit;
