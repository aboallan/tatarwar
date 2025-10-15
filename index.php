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
<section class="grid grid-3">
    <article class="card">
        <div class="metrics">
            <span class="label">Total Tasks</span>
            <span class="value"><?= $totalTasks; ?></span>
        </div>
        <p>All tasks currently registered across departments.</p>
    </article>
    <article class="card">
        <div class="metrics">
            <span class="label">Open Tasks</span>
            <span class="value"><?= $openTasks; ?></span>
        </div>
        <p>Tasks pending follow-up or in progress.</p>
    </article>
    <article class="card">
        <div class="metrics">
            <span class="label">Completed Tasks</span>
            <span class="value"><?= $completedTasks; ?></span>
        </div>
        <p>Tasks finished and confirmed by departments.</p>
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
        <h2>Overdue Tasks</h2>
        <p><strong><?= $overdueTasks; ?></strong> tasks are past their due date.</p>
        <a href="tasks.php" class="button">Review tasks and resolve delays</a>
    </article>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
