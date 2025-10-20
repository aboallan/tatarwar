<?php
$pageTitle = 'Reminders';
$pageDescription = 'Send timely nudges and track reminder history by department.';
require_once __DIR__ . '/includes/functions.php';
require_login();
require_once __DIR__ . '/db.php';

$errors = [];
$success = '';

$tasks = $pdo->query('SELECT id, title FROM tasks ORDER BY title ASC')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $taskId = (int)($_POST['task_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');

    if ($taskId <= 0) {
        $errors[] = 'Choose a task to notify.';
    }

    if ($message === '') {
        $errors[] = 'Provide a reminder message.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare('INSERT INTO notifications (task_id, message, created_at) VALUES (:task_id, :message, NOW())');
        $stmt->execute([
            'task_id' => $taskId,
            'message' => $message,
        ]);
        $success = 'Reminder recorded and sent.';
    }
}

$reminders = $pdo->query('SELECT n.id, n.message, DATE_FORMAT(n.created_at, "%b %e, %Y %H:%i") AS created_at,
    t.title AS task_title, d.name AS department_name
    FROM notifications n
    JOIN tasks t ON t.id = n.task_id
    JOIN departments d ON d.id = t.department_id
    ORDER BY n.created_at DESC
    LIMIT 25')->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<?php if ($success): ?>
    <div class="alert success global-alert"><?= sanitize($success); ?></div>
<?php endif; ?>
<?php if ($errors): ?>
    <div class="alert error global-alert">
        <?= implode('<br>', array_map('sanitize', $errors)); ?>
    </div>
<?php endif; ?>
<section class="reminders-layout">
    <aside class="panel reminder-compose">
        <header>
            <h2>Create reminder</h2>
            <p>Send a quick prompt to keep tasks on schedule.</p>
        </header>
        <form method="post" class="classic-form">
            <div class="form-group">
                <label for="task_id">Task</label>
                <select id="task_id" name="task_id" required>
                    <option value="">Select task</option>
                    <?php foreach ($tasks as $task): ?>
                        <option value="<?= (int)$task['id']; ?>" <?= isset($_POST['task_id']) && (int)$_POST['task_id'] === (int)$task['id'] ? 'selected' : ''; ?>>
                            <?= sanitize($task['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="message">Reminder message</label>
                <textarea id="message" name="message" rows="5" placeholder="Highlight the actions needed or the approaching deadline." required><?= isset($_POST['message']) ? sanitize($_POST['message']) : ''; ?></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="primary-action">Send reminder</button>
            </div>
        </form>
    </aside>
    <div class="panel reminder-log">
        <header class="panel-header">
            <div>
                <h2>Reminder log</h2>
                <span>Latest notifications</span>
            </div>
        </header>
        <?php if (!$reminders): ?>
            <div class="empty-note">No reminders have been logged yet.</div>
        <?php else: ?>
            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th scope="col">Task</th>
                            <th scope="col">Department</th>
                            <th scope="col">Message</th>
                            <th scope="col">Sent at</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reminders as $reminder): ?>
                            <tr>
                                <td><?= sanitize($reminder['task_title']); ?></td>
                                <td><?= sanitize($reminder['department_name']); ?></td>
                                <td><?= sanitize($reminder['message']); ?></td>
                                <td><?= sanitize($reminder['created_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
