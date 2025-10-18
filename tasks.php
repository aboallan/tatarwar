<?php
$pageTitle = 'Tasks';
$pageDescription = 'Manage and track work across every department.';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/functions.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_status') {
        $taskId = (int)($_POST['task_id'] ?? 0);
        $status = $_POST['status'] ?? 'Pending';

        if ($taskId > 0) {
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

$departments = $pdo->query('SELECT id, name FROM departments ORDER BY name ASC')->fetchAll();

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

$statusGroups = [
    'Pending' => [],
    'In Progress' => [],
    'Completed' => [],
    'Overdue' => [],
];

foreach ($tasks as $task) {
    $isOverdue = !empty($task['due_date']) && $task['status'] !== 'Completed' && strtotime($task['due_date']) < strtotime(date('Y-m-d'));
    if ($isOverdue) {
        $statusGroups['Overdue'][] = $task;
    } elseif (isset($statusGroups[$task['status']])) {
        $statusGroups[$task['status']][] = $task;
    } else {
        $statusGroups['Pending'][] = $task;
    }
}

include __DIR__ . '/includes/header.php';
?>
<?php if ($success): ?>
    <div class="alert success global-alert"><?= sanitize($success); ?></div>
<?php endif; ?>
<section class="metrics-grid">
    <article class="panel metric-card">
        <span class="metric-label">Pending</span>
        <span class="metric-value"><?= number_format($statusCounts['Pending']); ?></span>
        <span class="metric-footnote">Awaiting kickoff</span>
    </article>
    <article class="panel metric-card">
        <span class="metric-label">In Progress</span>
        <span class="metric-value"><?= number_format($statusCounts['In Progress']); ?></span>
        <span class="metric-footnote"><?= $nextDueTask ? sanitize(format_date($nextDueTask['due_date'])) : 'No due dates'; ?></span>
    </article>
    <article class="panel metric-card">
        <span class="metric-label">Completed</span>
        <span class="metric-value"><?= number_format($statusCounts['Completed']); ?></span>
        <span class="metric-footnote">Recently closed work</span>
    </article>
    <article class="panel metric-card">
        <span class="metric-label">Overdue</span>
        <span class="metric-value"><?= number_format($overdueCount); ?></span>
        <span class="metric-footnote">Requires follow-up</span>
    </article>
</section>

<section class="panel">
    <div class="panel-header">
        <h2>Board view</h2>
        <span class="panel-subtitle">Status lanes for quick scanning</span>
    </div>
    <div class="task-board">
        <?php
        $laneOrder = ['Pending', 'In Progress', 'Completed', 'Overdue'];
        foreach ($laneOrder as $lane):
            $tasksInLane = $statusGroups[$lane];
        ?>
            <div class="board-column">
                <header>
                    <h2><?= sanitize($lane); ?></h2>
                    <span class="badge neutral"><?= count($tasksInLane); ?></span>
                </header>
                <div class="column-body">
                    <?php if (!$tasksInLane): ?>
                        <div class="empty-note">No tasks in this lane.</div>
                    <?php else: ?>
                        <?php foreach ($tasksInLane as $task): ?>
                            <?php
                            $priorityClass = strtolower($task['priority']);
                            if (!in_array($priorityClass, ['high', 'medium', 'low'], true)) {
                                $priorityClass = 'medium';
                            }
                            ?>
                            <article class="task-card">
                                <div>
                                    <h3><?= sanitize($task['title']); ?></h3>
                                    <div class="task-meta">
                                        <span><?= sanitize($task['department_name']); ?></span>
                                        <?php if (!empty($task['due_date'])): ?>
                                            <span>Due <?= sanitize(format_date($task['due_date'])); ?></span>
                                        <?php endif; ?>
                                        <span class="priority-badge <?= $priorityClass; ?>"><?= sanitize($task['priority']); ?></span>
                                    </div>
                                </div>
                                <?php if (!empty($task['description'])): ?>
                                    <p class="task-description"><?= sanitize($task['description']); ?></p>
                                <?php endif; ?>
                                <div class="card-actions">
                                    <form method="post" class="inline-form">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="task_id" value="<?= (int)$task['id']; ?>">
                                        <select name="status">
                                            <option value="Pending" <?= $task['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="In Progress" <?= $task['status'] === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                                            <option value="Completed" <?= $task['status'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                        </select>
                                        <button type="submit" class="secondary">Update</button>
                                    </form>
                                    <div class="action-row">
                                        <button type="button" class="secondary reminder-button" data-task-id="<?= (int)$task['id']; ?>">Send reminder</button>
                                        <form method="post" onsubmit="return confirm('Delete this task?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="task_id" value="<?= (int)$task['id']; ?>">
                                            <button type="submit" class="secondary">Delete</button>
                                        </form>
                                    </div>
                                    <span class="reminder-stamp">Last reminder: <?= $task['last_reminder'] ? sanitize($task['last_reminder']) : 'Not sent yet'; ?></span>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
