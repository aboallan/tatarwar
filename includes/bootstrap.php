<?php
declare(strict_types=1);

require_once __DIR__ . '/app.php';

$pdo = getDatabaseConnection();
handlePostRequests($pdo);
$currentUser = getAuthenticatedUser($pdo);
$flashMessages = consumeFlashMessages();
