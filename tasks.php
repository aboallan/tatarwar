<?php
$pageTitle = 'Tasks';
$pageDescription = 'Track assignments with a tidy roster and classic controls.';
require_once __DIR__ . '/includes/functions.php';
require_login();
require_once __DIR__ . '/db.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_status') {
        $taskId = (int)($_POST['task_id'] ?? 0);
        $status = $_POST['status'] ?? 'Pending';

        if ($taskId > 0 && in_array($status, ['Pending', 'In Progress', 'Completed'], true)) {
            $update = $pdo->prepare('UPDATE tasks SET status = :status, updated_at = NOW() WHERE id = :id');
            $update->execute([
                'status' => $status,
                'id' => $taskId,
            ]);
            $success = 'Task status updated.';
        }
    } elseif ($action === 'delete') {
        $taskId = (int)($_POST['task_id'] ?? 0);
        if ($taskId > 0) {
            $pdo->prepare('DELETE FROM notifications WHERE task_id = :id')->execute(['id' => $taskId]);
            $pdo->prepare('DELETE FROM tasks WHERE id = :id')->execute(['id' => $taskId]);
            $success = 'Task deleted.';
        }
    }
}

if (!$success && isset($_GET['created']) && $_GET['created'] === '1') {
    $success = 'Task created successfully.';
}

$tasksStmt = $pdo->query('SELECT t.*, d.name AS department_name,
    (SELECT DATE_FORMAT(n.created_at, "%b %e, %Y %H:%i") FROM notifications n WHERE n.task_id = t.id ORDER BY n.created_at DESC LIMIT 1) AS last_reminder
    FROM tasks t
    JOIN departments d ON t.department_id = d.id
    ORDER BY t.due_date IS NULL, t.due_date ASC, t.created_at DESC');
$tasks = $tasksStmt->fetchAll();

$statusCounts = [
    'Pending' => 0,
    'In Progress' => 0,
    'Completed' => 0,
];

$statusSummaryStmt = $pdo->query("SELECT status, COUNT(*) AS total FROM tasks GROUP BY status");
foreach ($statusSummaryStmt as $row) {
    $status = $row['status'] ?? '';
    if (isset($statusCounts[$status])) {
        $statusCounts[$status] = (int)$row['total'];
    }
}

$overdueCount = (int)$pdo->query("SELECT COUNT(*) FROM tasks WHERE status != 'Completed' AND due_date IS NOT NULL AND due_date < CURDATE()")
    ->fetchColumn();

$nextDueTask = $pdo->query("SELECT t.title, t.due_date, d.name AS department_name
    FROM tasks t
    JOIN departments d ON d.id = t.department_id
    WHERE t.status != 'Completed' AND t.due_date IS NOT NULL AND t.due_date >= CURDATE()
    ORDER BY t.due_date ASC
    LIMIT 1")
    ->fetch();

include __DIR__ . '/includes/header.php';
?>
<?php if ($success): ?>
    <div class="alert success global-alert"><?= sanitize($success); ?></div>
<?php endif; ?>
<section class="summary-cards">
    <article class="summary-card">
        <span class="summary-label">Pending</span>
        <span class="summary-value"><?= number_format($statusCounts['Pending']); ?></span>
        <span class="summary-footnote">Awaiting kickoff</span>
    </article>
    <article class="summary-card">
        <span class="summary-label">In progress</span>
        <span class="summary-value"><?= number_format($statusCounts['In Progress']); ?></span>
        <span class="summary-footnote"><?= $nextDueTask ? sanitize(format_date($nextDueTask['due_date'])) : 'No due dates'; ?></span>
    </article>
    <article class="summary-card">
        <span class="summary-label">Completed</span>
        <span class="summary-value"><?= number_format($statusCounts['Completed']); ?></span>
        <span class="summary-footnote">Recently closed work</span>
    </article>
    <article class="summary-card">
        <span class="summary-label">Overdue</span>
        <span class="summary-value"><?= number_format($overdueCount); ?></span>
        <span class="summary-footnote">Requires follow-up</span>
    </article>
</section>

<section class="panel classic-panel">
    <header class="panel-header">
        <h2>Task roster</h2>
        <span><?= count($tasks); ?> tasks listed</span>
    </header>
    <?php if (!$tasks): ?>
        <div class="empty-note">No tasks have been added yet. Use the Create Task page to get started.</div>
    <?php else: ?>
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th scope="col">Task</th>
                        <th scope="col">Department</th>
                        <th scope="col">Priority</th>
                        <th scope="col">Due date</th>
                        <th scope="col">Status</th>
                        <th scope="col">Last reminder</th>
                        <th scope="col" class="actions-col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tasks as $task): ?>
                        <?php
                        $priorityClass = strtolower($task['priority'] ?? 'medium');
                        if (!in_array($priorityClass, ['high', 'medium', 'low'], true)) {
                            $priorityClass = 'medium';
                        }
                        $dueStatus = analyze_due_status($task['due_date'], $task['status']);
                        $stampId = 'reminder-stamp-' . (int)$task['id'];
                        ?>
                        <tr>
                            <td>
                                <strong><?= sanitize($task['title']); ?></strong>
                                <?php if (!empty($task['description'])): ?>
                                    <p class="table-note"><?= sanitize($task['description']); ?></p>
                                <?php endif; ?>
                            </td>
                            <td><?= sanitize($task['department_name']); ?></td>
                            <td><span class="priority-chip <?= $priorityClass; ?>"><?= sanitize($task['priority']); ?></span></td>
                            <td>
                                <span class="due-chip <?= sanitize($dueStatus['class']); ?>"><?= sanitize(format_date($task['due_date'])); ?></span>
                            </td>
                            <td>
                                <form method="post" class="inline-form">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="task_id" value="<?= (int)$task['id']; ?>">
                                    <select name="status" onchange="this.form.submit()">
                                        <?php foreach (['Pending', 'In Progress', 'Completed'] as $status): ?>
                                            <option value="<?= $status; ?>" <?= $task['status'] === $status ? 'selected' : ''; ?>><?= $status; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                            </td>
                            <td>
                                <span id="<?= $stampId; ?>" class="reminder-stamp"><?= $task['last_reminder'] ? sanitize($task['last_reminder']) : '—'; ?></span>
                            </td>
                            <td class="actions-col">
                                <div class="action-stack">
                                    <button type="button" class="ghost-action reminder-button" data-task-id="<?= (int)$task['id']; ?>" data-target="#<?= $stampId; ?>">Send reminder</button>
                                    <form method="post" class="inline-form" onsubmit="return confirm('Delete this task?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="task_id" value="<?= (int)$task['id']; ?>">
                                        <button type="submit" class="ghost-action danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
