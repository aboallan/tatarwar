<?php
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['message' => 'Request method not allowed']);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true) ?? [];
$taskId = isset($payload['taskId']) ? (int) $payload['taskId'] : 0;

if (!$taskId) {
    http_response_code(400);
    echo json_encode(['message' => 'Unknown task']);
    exit;
}

require_once __DIR__ . '/db.php';

$taskStmt = $pdo->prepare('SELECT id, title, due_date FROM tasks WHERE id = :id');
$taskStmt->execute(['id' => $taskId]);
$task = $taskStmt->fetch();

if (!$task) {
    http_response_code(404);
    echo json_encode(['message' => 'Task not found']);
    exit;
}

$message = sprintf('Reminder for task "%s". Due date: %s', $task['title'], $task['due_date'] ?: 'No due date');

$insert = $pdo->prepare('INSERT INTO notifications (task_id, message, created_at) VALUES (:task_id, :message, NOW())');
$insert->execute([
    'task_id' => $taskId,
    'message' => $message,
]);

$lastReminder = $pdo->prepare('SELECT DATE_FORMAT(created_at, "%Y-%m-%d %H:%i") as created_at FROM notifications WHERE task_id = :task_id ORDER BY created_at DESC LIMIT 1');
$lastReminder->execute(['task_id' => $taskId]);
$stamp = $lastReminder->fetchColumn();

echo json_encode([
    'message' => 'Reminder sent successfully',
    'lastReminder' => $stamp,
]);
