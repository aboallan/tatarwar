<?php
$pageTitle = 'Dashboard';
$pageDescription = 'Overview of all departments and task assignments.';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/functions.php';

$totalTasks = (int)$pdo->query('SELECT COUNT(*) FROM tasks')->fetchColumn();
$inProgressTasks = (int)$pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 'In Progress'")->fetchColumn();
$completedTasks = (int)$pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 'Completed'")->fetchColumn();
$overdueTasks = (int)$pdo->query("SELECT COUNT(*) FROM tasks WHERE status != 'Completed' AND due_date IS NOT NULL AND due_date < CURDATE()")
    ->fetchColumn();
$departmentCount = (int)$pdo->query('SELECT COUNT(*) FROM departments')->fetchColumn();

$upcomingTasks = $pdo->query("SELECT t.id, t.title, t.due_date, d.name AS department_name, t.priority\n    FROM tasks t\n    JOIN departments d ON d.id = t.department_id\n    WHERE t.status != 'Completed' AND t.due_date IS NOT NULL AND t.due_date >= CURDATE()\n    ORDER BY t.due_date ASC\n    LIMIT 6")->fetchAll();

$recentReminders = $pdo->query("SELECT n.message, DATE_FORMAT(n.created_at, '%b %e, %Y %H:%i') AS created_at,\n    t.title AS task_title\n    FROM notifications n\n    JOIN tasks t ON t.id = n.task_id\n    ORDER BY n.created_at DESC\n    LIMIT 5")->fetchAll();

$departmentPerformance = $pdo->query("SELECT d.name,\n    COUNT(t.id) AS total_tasks,\n    SUM(CASE WHEN t.status = 'Completed' THEN 1 ELSE 0 END) AS completed_tasks,\n    SUM(CASE WHEN t.status = 'In Progress' THEN 1 ELSE 0 END) AS in_progress_tasks,\n    SUM(CASE WHEN t.due_date IS NOT NULL AND t.due_date < CURDATE() AND t.status != 'Completed' THEN 1 ELSE 0 END) AS overdue_tasks\n    FROM departments d\n    LEFT JOIN tasks t ON t.department_id = d.id\n    GROUP BY d.id\n    ORDER BY d.name ASC\n    LIMIT 6")->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<section class="metrics-grid">
    <article class="panel metric-card">
        <span class="metric-label">Total Tasks</span>
        <span class="metric-value"><?= number_format($totalTasks); ?></span>
        <span class="metric-footnote">Across all departments</span>
    </article>
    <article class="panel metric-card">
        <span class="metric-label">In Progress</span>
        <span class="metric-value"><?= number_format($inProgressTasks); ?></span>
        <span class="metric-footnote"><?= number_format($completedTasks); ?> completed</span>
    </article>
    <article class="panel metric-card">
        <span class="metric-label">Overdue</span>
        <span class="metric-value"><?= number_format($overdueTasks); ?></span>
        <span class="metric-footnote">Requires attention</span>
    </article>
    <article class="panel metric-card">
        <span class="metric-label">Departments</span>
        <span class="metric-value"><?= number_format($departmentCount); ?></span>
        <span class="metric-footnote">Active divisions</span>
    </article>
</section>

<?php if ($overdueTasks > 0): ?>
    <section class="panel alert-card">
        <div>
            <h2>Overdue tasks need attention</h2>
            <p><?= number_format($overdueTasks); ?> assignments have slipped beyond their due dates.</p>
        </div>
        <a href="tasks.php" class="link-button">Review tasks</a>
    </section>
<?php endif; ?>

<section class="split-grid">
    <article class="panel">
        <div class="panel-header">
            <h2>Upcoming Deadlines</h2>
            <span class="panel-subtitle">Next <?= $upcomingTasks ? count($upcomingTasks) : 0; ?> due dates</span>
        </div>
        <?php if (!$upcomingTasks): ?>
            <div class="empty-note">No upcoming due dates found.</div>
        <?php else: ?>
            <ul class="deadline-list">
                <?php foreach ($upcomingTasks as $task): ?>
                    <?php
                    $priorityClass = strtolower($task['priority']);
                    if (!in_array($priorityClass, ['high', 'medium', 'low'], true)) {
                        $priorityClass = 'medium';
                    }
                    ?>
                    <li class="deadline-item">
                        <div>
                            <strong><?= sanitize($task['title']); ?></strong>
                            <div class="task-meta">
                                <span><?= sanitize($task['department_name']); ?></span>
                                <span class="priority-badge <?= $priorityClass; ?>"><?= sanitize($task['priority']); ?></span>
                            </div>
                        </div>
                        <span class="badge warning">Due <?= sanitize(format_date($task['due_date'])); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </article>
    <article class="panel">
        <div class="panel-header">
            <h2>Department Performance</h2>
            <span class="panel-subtitle">Completion rate</span>
        </div>
        <?php if (!$departmentPerformance): ?>
            <div class="empty-note">Add departments to track performance.</div>
        <?php else: ?>
            <ul class="performance-list">
                <?php foreach ($departmentPerformance as $department): ?>
                    <?php
                    $total = (int)$department['total_tasks'];
                    $completed = (int)$department['completed_tasks'];
                    $inProgress = (int)$department['in_progress_tasks'];
                    $overdue = (int)$department['overdue_tasks'];
                    $rate = $total > 0 ? round(($completed / $total) * 100) : 0;
                    ?>
                    <li class="performance-row">
                        <header>
                            <strong><?= sanitize($department['name']); ?></strong>
                            <span class="badge neutral"><?= $rate; ?>% complete</span>
                        </header>
                        <div class="progress-track">
                            <span class="progress-fill" style="width: <?= $rate; ?>%"></span>
                        </div>
                        <div class="progress-meta">
                            <span><?= number_format($completed); ?> completed · <?= number_format($inProgress); ?> in progress</span>
                            <span><?= number_format($overdue); ?> overdue</span>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </article>
</section>

<section class="panel">
    <div class="panel-header">
        <h2>Reminder Activity</h2>
        <span class="panel-subtitle">Latest alerts to departments</span>
    </div>
    <?php if (!$recentReminders): ?>
        <div class="empty-note">No reminders have been issued yet.</div>
    <?php else: ?>
        <ul class="activity-list">
            <?php foreach ($recentReminders as $reminder): ?>
                <li class="activity-item">
                    <div>
                        <strong><?= sanitize($reminder['task_title']); ?></strong>
                        <div class="task-meta">
                            <span><?= sanitize($reminder['message']); ?></span>
                        </div>
                    </div>
                    <span class="badge neutral"><?= sanitize($reminder['created_at']); ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
