<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/functions.php';

$totalTasks = (int)$pdo->query('SELECT COUNT(*) FROM tasks')->fetchColumn();
$openTasks = (int)$pdo->query("SELECT COUNT(*) FROM tasks WHERE status IN ('Pending', 'In Progress')")->fetchColumn();
$completedTasks = (int)$pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 'Completed'")->fetchColumn();
$overdueTasks = (int)$pdo->query("SELECT COUNT(*) FROM tasks WHERE status != 'Completed' AND due_date IS NOT NULL AND due_date < CURDATE()")
    ->fetchColumn();

$upcomingTasks = $pdo->query("SELECT t.id, t.title, t.due_date, d.name AS department_name
    FROM tasks t
    JOIN departments d ON d.id = t.department_id
    WHERE t.status != 'Completed' AND t.due_date IS NOT NULL AND t.due_date >= CURDATE()
    ORDER BY t.due_date ASC
    LIMIT 6")->fetchAll();

$latestNotifications = $pdo->query("SELECT n.message, DATE_FORMAT(n.created_at, '%Y-%m-%d %H:%i') AS created_at,
    t.title AS task_title
    FROM notifications n
    JOIN tasks t ON t.id = n.task_id
    ORDER BY n.created_at DESC
    LIMIT 5")->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<section class="grid grid-3 stat-grid">
    <article class="card stat-card">
        <span class="stat-icon icon-total" aria-hidden="true">Σ</span>
        <div class="stat-content">
            <span class="label">Total Tasks</span>
            <div class="stat-value"><?= number_format($totalTasks); ?></div>
            <p class="stat-description">All assignments currently tracked across departments.</p>
        </div>
    </article>
    <article class="card stat-card">
        <span class="stat-icon icon-open" aria-hidden="true">⏳</span>
        <div class="stat-content">
            <span class="label">Open Tasks</span>
            <div class="stat-value"><?= number_format($openTasks); ?></div>
            <p class="stat-description">In-progress or awaiting action before their deadlines.</p>
        </div>
    </article>
    <article class="card stat-card">
        <span class="stat-icon icon-complete" aria-hidden="true">✔</span>
        <div class="stat-content">
            <span class="label">Completed Tasks</span>
            <div class="stat-value"><?= number_format($completedTasks); ?></div>
            <p class="stat-description">Tasks signed off and closed by department owners.</p>
        </div>
    </article>
</section>

<section class="grid" style="margin-top: 1.5rem; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));">
    <article class="card">
        <h2>Upcoming Deadlines</h2>
        <?php if (!$upcomingTasks): ?>
            <div class="empty-state">No upcoming due dates found.</div>
        <?php else: ?>
            <ul class="timeline">
                <?php foreach ($upcomingTasks as $task): ?>
                    <li>
                        <div>
                            <strong><?= sanitize($task['title']); ?></strong>
                            <div class="text-muted">Department: <?= sanitize($task['department_name']); ?></div>
                        </div>
                        <span class="badge">Due: <?= sanitize(format_date($task['due_date'])); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </article>
    <article class="card">
        <h2>Delivery Alerts</h2>
        <?php if (!$latestNotifications): ?>
            <div class="empty-state">No reminders sent yet.</div>
        <?php else: ?>
            <ul class="timeline">
                <?php foreach ($latestNotifications as $notification): ?>
                    <li>
                        <div>
                            <strong><?= sanitize($notification['task_title']); ?></strong>
                            <div class="text-muted"><?= sanitize($notification['message']); ?></div>
                        </div>
                        <span class="badge"><?= sanitize($notification['created_at']); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </article>
    <article class="card">
        <div class="metric-header">
            <h2>Overdue Tasks</h2>
            <span class="metric-pill"><span class="dot"></span><?= number_format($overdueTasks); ?> overdue</span>
        </div>
        <p class="stat-description">Keep delivery on track by nudging owners and reviewing blockers.</p>
        <a href="tasks.php" class="button ghost">Review tasks and resolve delays</a>
    </article>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
