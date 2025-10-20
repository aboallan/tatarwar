<?php
$pageTitle = 'Tasks';
$pageDescription = 'Track assignments with a refined board and quick actions.';
require_once __DIR__ . '/includes/functions.php';
require_login();
require_once __DIR__ . '/db.php';

$errors = [];
$success = '';
$searchTerm = trim($_GET['q'] ?? '');

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

$taskQuery = 'SELECT t.*, d.name AS department_name,
    (SELECT DATE_FORMAT(n.created_at, "%b %e, %Y %H:%i") FROM notifications n WHERE n.task_id = t.id ORDER BY n.created_at DESC LIMIT 1) AS last_reminder
    FROM tasks t
    JOIN departments d ON t.department_id = d.id';

$queryParams = [];
if ($searchTerm !== '') {
    $taskQuery .= ' WHERE t.title LIKE :search OR d.name LIKE :search';
    $queryParams['search'] = '%' . $searchTerm . '%';
}

$taskQuery .= ' ORDER BY t.due_date IS NULL, t.due_date ASC, t.created_at DESC';
$tasksStmt = $pdo->prepare($taskQuery);
$tasksStmt->execute($queryParams);
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

<?php if ($nextDueTask): ?>
    <section class="page-alert deadline-alert" role="alert">
        <div class="alert-icon" aria-hidden="true">⏰</div>
        <div class="alert-body">
            <h2>Next due: <?= sanitize($nextDueTask['title']); ?></h2>
            <p><?= sanitize($nextDueTask['department_name']); ?> · Due <?= sanitize(format_date($nextDueTask['due_date'])); ?></p>
        </div>
    </section>
<?php endif; ?>

<section class="summary-cards">
    <article class="summary-card">
        <span class="summary-icon" aria-hidden="true">🕒</span>
        <div>
            <span class="summary-label">Pending</span>
            <span class="summary-value"><?= number_format($statusCounts['Pending']); ?></span>
        </div>
        <span class="summary-footnote">Awaiting kickoff</span>
    </article>
    <article class="summary-card">
        <span class="summary-icon" aria-hidden="true">🔄</span>
        <div>
            <span class="summary-label">In progress</span>
            <span class="summary-value"><?= number_format($statusCounts['In Progress']); ?></span>
        </div>
        <span class="summary-footnote"><?= $nextDueTask ? sanitize(format_date($nextDueTask['due_date'])) : 'No due dates'; ?></span>
    </article>
    <article class="summary-card">
        <span class="summary-icon" aria-hidden="true">✅</span>
        <div>
            <span class="summary-label">Completed</span>
            <span class="summary-value"><?= number_format($statusCounts['Completed']); ?></span>
        </div>
        <span class="summary-footnote">Recently closed work</span>
    </article>
    <article class="summary-card">
        <span class="summary-icon" aria-hidden="true">⚠️</span>
        <div>
            <span class="summary-label">Overdue</span>
            <span class="summary-value"><?= number_format($overdueCount); ?></span>
        </div>
        <span class="summary-footnote">Requires follow-up</span>
    </article>
</section>

<section class="panel classic-panel">
    <header class="panel-header tasks-header">
        <div>
            <h2>Task list</h2>
            <span><?= count($tasks); ?> tasks listed</span>
        </div>
        <form method="get" class="tasks-filter" role="search">
            <label for="task-search" class="sr-only">Search tasks</label>
            <input type="search" id="task-search" name="q" placeholder="Search tasks or departments" value="<?= sanitize($searchTerm); ?>">
            <?php if ($searchTerm !== ''): ?>
                <a href="tasks.php" class="ghost-action">Clear</a>
            <?php endif; ?>
        </form>
    </header>
    <?php if (!$tasks): ?>
        <div class="empty-note">No tasks found. Create a new task or adjust your search.</div>
    <?php else: ?>
        <div class="task-collection">
            <?php foreach ($tasks as $task): ?>
                <?php
                $priorityClass = strtolower($task['priority'] ?? 'medium');
                if (!in_array($priorityClass, ['high', 'medium', 'low'], true)) {
                    $priorityClass = 'medium';
                }
                $dueStatus = analyze_due_status($task['due_date'], $task['status']);
                $stampId = 'reminder-stamp-' . (int)$task['id'];
                $statusClass = 'status-' . strtolower(str_replace(' ', '-', $task['status']));
                ?>
                <article class="task-card <?= $statusClass; ?>">
                    <header class="task-card-header">
                        <div>
                            <h3><?= sanitize($task['title']); ?></h3>
                            <?php if (!empty($task['description'])): ?>
                                <p><?= sanitize($task['description']); ?></p>
                            <?php endif; ?>
                        </div>
                        <span class="priority-chip <?= $priorityClass; ?>"><?= sanitize($task['priority']); ?></span>
                    </header>
                    <div class="task-card-body">
                        <dl class="task-meta">
                            <div>
                                <dt>Department</dt>
                                <dd><?= sanitize($task['department_name']); ?></dd>
                            </div>
                            <div>
                                <dt>Due date</dt>
                                <dd><span class="due-chip <?= sanitize($dueStatus['class']); ?>"><?= sanitize(format_date($task['due_date'])); ?></span></dd>
                            </div>
                            <div>
                                <dt>Last reminder</dt>
                                <dd><span id="<?= $stampId; ?>" class="reminder-stamp"><?= $task['last_reminder'] ? sanitize($task['last_reminder']) : '—'; ?></span></dd>
                            </div>
                        </dl>
                        <div class="task-card-actions">
                            <form method="post" class="inline-form">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="task_id" value="<?= (int)$task['id']; ?>">
                                <label class="sr-only" for="status-<?= (int)$task['id']; ?>">Status</label>
                                <select id="status-<?= (int)$task['id']; ?>" name="status" onchange="this.form.submit()">
                                    <?php foreach (['Pending', 'In Progress', 'Completed'] as $status): ?>
                                        <option value="<?= $status; ?>" <?= $task['status'] === $status ? 'selected' : ''; ?>><?= $status; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                            <button type="button" class="ghost-action reminder-button" data-task-id="<?= (int)$task['id']; ?>" data-target="#<?= $stampId; ?>">Send reminder</button>
                            <form method="post" class="inline-form" onsubmit="return confirm('Delete this task?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="task_id" value="<?= (int)$task['id']; ?>">
                                <button type="submit" class="ghost-action danger">Delete</button>
                            </form>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
