<?php
$pageTitle = 'Dashboard';
$pageDescription = 'A classic control room for city task coordination.';
require_once __DIR__ . '/includes/functions.php';
require_login();
require_once __DIR__ . '/db.php';

$totalTasks = (int)$pdo->query('SELECT COUNT(*) FROM tasks')->fetchColumn();
$inProgressTasks = (int)$pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 'In Progress'")->fetchColumn();
$completedTasks = (int)$pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 'Completed'")->fetchColumn();
$overdueTasks = (int)$pdo->query("SELECT COUNT(*) FROM tasks WHERE status != 'Completed' AND due_date IS NOT NULL AND due_date < CURDATE()")
    ->fetchColumn();
$departmentCount = (int)$pdo->query('SELECT COUNT(*) FROM departments')->fetchColumn();

$upcomingTasks = $pdo->query("SELECT t.id, t.title, t.due_date, d.name AS department_name, t.priority\n    FROM tasks t\n    JOIN departments d ON d.id = t.department_id\n    WHERE t.status != 'Completed' AND t.due_date IS NOT NULL AND t.due_date >= CURDATE()\n    ORDER BY t.due_date ASC\n    LIMIT 8")->fetchAll();

$recentReminders = $pdo->query("SELECT n.message, DATE_FORMAT(n.created_at, '%b %e, %Y %H:%i') AS created_at,\n    t.title AS task_title\n    FROM notifications n\n    JOIN tasks t ON t.id = n.task_id\n    ORDER BY n.created_at DESC\n    LIMIT 6")->fetchAll();

$departmentPerformance = $pdo->query("SELECT d.name,\n    COUNT(t.id) AS total_tasks,\n    SUM(CASE WHEN t.status = 'Completed' THEN 1 ELSE 0 END) AS completed_tasks,\n    SUM(CASE WHEN t.status = 'In Progress' THEN 1 ELSE 0 END) AS in_progress_tasks,\n    SUM(CASE WHEN t.due_date IS NOT NULL AND t.due_date < CURDATE() AND t.status != 'Completed' THEN 1 ELSE 0 END) AS overdue_tasks\n    FROM departments d\n    LEFT JOIN tasks t ON t.department_id = d.id\n    GROUP BY d.id\n    ORDER BY d.name ASC")->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<section class="summary-cards">
    <article class="summary-card">
        <span class="summary-label">Total tasks</span>
        <span class="summary-value"><?= number_format($totalTasks); ?></span>
        <span class="summary-footnote">Across <?= number_format($departmentCount); ?> departments</span>
    </article>
    <article class="summary-card">
        <span class="summary-label">In progress</span>
        <span class="summary-value"><?= number_format($inProgressTasks); ?></span>
        <span class="summary-footnote"><?= number_format($completedTasks); ?> completed</span>
    </article>
    <article class="summary-card">
        <span class="summary-label">Overdue items</span>
        <span class="summary-value"><?= number_format($overdueTasks); ?></span>
        <span class="summary-footnote">Needing follow-up</span>
    </article>
    <article class="summary-card">
        <span class="summary-label">Departments tracked</span>
        <span class="summary-value"><?= number_format($departmentCount); ?></span>
        <span class="summary-footnote">Active city divisions</span>
    </article>
</section>

<div class="content-columns">
    <div class="column-main">
        <section class="panel classic-panel">
            <header class="panel-header">
                <h2>Upcoming deadlines</h2>
                <span><?= $upcomingTasks ? count($upcomingTasks) : 0; ?> items scheduled</span>
            </header>
            <?php if (!$upcomingTasks): ?>
                <div class="empty-note">No upcoming due dates on the books.</div>
            <?php else: ?>
                <ul class="stacked-list">
                    <?php foreach ($upcomingTasks as $task): ?>
                        <?php
                        $priorityClass = strtolower($task['priority']);
                        if (!in_array($priorityClass, ['high', 'medium', 'low'], true)) {
                            $priorityClass = 'medium';
                        }
                        ?>
                        <li class="stacked-item">
                            <div class="item-main">
                                <strong><?= sanitize($task['title']); ?></strong>
                                <span class="item-sub"><?= sanitize($task['department_name']); ?></span>
                            </div>
                            <div class="item-meta">
                                <span class="priority-chip <?= $priorityClass; ?>"><?= sanitize($task['priority']); ?></span>
                                <span class="due-chip">Due <?= sanitize(format_date($task['due_date'])); ?></span>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <section class="panel classic-panel">
            <header class="panel-header">
                <h2>Department performance</h2>
                <span>Completion by division</span>
            </header>
            <?php if (!$departmentPerformance): ?>
                <div class="empty-note">Add departments to begin tracking throughput.</div>
            <?php else: ?>
                <div class="table-scroll">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th scope="col">Department</th>
                                <th scope="col">Completed</th>
                                <th scope="col">In progress</th>
                                <th scope="col">Overdue</th>
                                <th scope="col">Completion rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($departmentPerformance as $department): ?>
                                <?php
                                $total = (int)$department['total_tasks'];
                                $completed = (int)$department['completed_tasks'];
                                $inProgress = (int)$department['in_progress_tasks'];
                                $overdue = (int)$department['overdue_tasks'];
                                $rate = $total > 0 ? round(($completed / max($total, 1)) * 100) : 0;
                                ?>
                                <tr>
                                    <td><?= sanitize($department['name']); ?></td>
                                    <td><?= number_format($completed); ?></td>
                                    <td><?= number_format($inProgress); ?></td>
                                    <td><?= number_format($overdue); ?></td>
                                    <td>
                                        <div class="progress-inline">
                                            <span class="progress-bar" style="width: <?= $rate; ?>%"></span>
                                        </div>
                                        <span class="progress-label"><?= $rate; ?>%</span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>
    <aside class="column-side">
        <?php if ($overdueTasks > 0): ?>
            <section class="panel attention-panel">
                <h2>Overdue follow-up</h2>
                <p><?= number_format($overdueTasks); ?> assignments need intervention. Review the task list to re-align owners and deadlines.</p>
                <a href="tasks.php" class="ghost-action">Go to tasks</a>
            </section>
        <?php endif; ?>

        <section class="panel classic-panel">
            <header class="panel-header">
                <h2>Reminder activity</h2>
                <span>Recent notices</span>
            </header>
            <?php if (!$recentReminders): ?>
                <div class="empty-note">No reminders have been issued yet.</div>
            <?php else: ?>
                <ul class="activity-list">
                    <?php foreach ($recentReminders as $reminder): ?>
                        <li class="activity-item">
                            <strong><?= sanitize($reminder['task_title']); ?></strong>
                            <span><?= sanitize($reminder['message']); ?></span>
                            <time><?= sanitize($reminder['created_at']); ?></time>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </aside>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
