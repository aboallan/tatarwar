<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if ($currentUser === null) {
    header('Location: login.php');
    exit;
}

$pageTitle = 'Executive Dashboard | Municipal PMO Suite';
$activeNav = 'dashboard';

$departments = fetchDepartments($pdo);
$projects = fetchProjects($pdo);
$tasks = fetchTasks($pdo);
$summary = buildSummary($tasks, $departments);
$portfolioSnapshot = buildPortfolioSnapshot($projects, $tasks, $summary);
$upcomingAlerts = findUpcomingAlerts($tasks);
$recentUpdates = fetchRecentUpdates($pdo);

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/flash.php';
?>
<main class="page-main dashboard-main">
    <section class="analytics-grid">
        <article class="card metric-card">
            <h2>Active programs</h2>
            <p class="metric-value"><?php echo $portfolioSnapshot['active_projects']; ?></p>
            <p class="metric-hint">Projects currently running or paused across all agencies.</p>
        </article>
        <article class="card metric-card">
            <h2>Departments engaged</h2>
            <p class="metric-value"><?php echo $portfolioSnapshot['engaged_departments']; ?></p>
            <p class="metric-hint">Directorates currently active across all assignments.</p>
        </article>
        <article class="card metric-card">
            <h2>Completion rate</h2>
            <p class="metric-value"><?php echo $portfolioSnapshot['completion_rate']; ?>%</p>
            <p class="metric-hint">Share of tasks marked complete vs. overall assignments.</p>
        </article>
        <article class="card metric-card">
            <h2>At-risk tasks</h2>
            <p class="metric-value"><?php echo $portfolioSnapshot['at_risk_tasks']; ?></p>
            <p class="metric-hint">Items tagged High or Critical risk needing intervention.</p>
        </article>
    </section>

    <section class="card status-card">
        <header class="card-header">
            <h2>Status &amp; risk distribution</h2>
            <p class="card-subtitle">Track execution load across departments.</p>
        </header>
        <div class="status-grid">
            <?php foreach ($summary['status'] as $status => $count): ?>
                <div class="status-tile">
                    <span class="status-label"><?php echo escape($status); ?></span>
                    <span class="status-count"><?php echo $count; ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="risk-summary">
            <?php foreach ($summary['risk'] as $riskLevel => $count): ?>
                <div class="risk-row">
                    <span class="risk-name"><?php echo escape($riskLevel); ?></span>
                    <div class="risk-bar">
                        <div class="risk-fill <?php echo getRiskClass($riskLevel); ?>" style="width: <?php echo $summary['total'] > 0 ? round(($count / $summary['total']) * 100) : 0; ?>%"></div>
                    </div>
                    <span class="risk-count"><?php echo $count; ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="department-load">
            <h3>Department load</h3>
            <ul class="department-list">
                <?php foreach ($summary['by_department'] as $departmentSummary): ?>
                    <li class="department-item">
                        <span class="department-name"><?php echo escape($departmentSummary['name']); ?></span>
                        <div class="department-progress">
                            <?php
                                $percent = $summary['peak_department_tasks'] > 0
                                    ? (int) round(($departmentSummary['count'] / $summary['peak_department_tasks']) * 100)
                                    : 0;
                            ?>
                            <div class="department-fill" style="width: <?php echo $percent; ?>%"></div>
                        </div>
                        <span class="department-count"><?php echo $departmentSummary['count']; ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </section>

    <section class="dual-grid">
        <article class="card alerts-card">
            <header class="card-header">
                <h2>Overdue alerts</h2>
                <p class="card-subtitle">Assignments past deadline</p>
            </header>
            <?php if (empty($upcomingAlerts['overdue'])): ?>
                <p class="empty-state">No overdue tasks. Great job keeping everything on track.</p>
            <?php else: ?>
                <ul class="alert-list">
                    <?php foreach ($upcomingAlerts['overdue'] as $task): ?>
                        <li class="alert-item">
                            <div>
                                <p class="alert-title"><?php echo escape($task['title']); ?></p>
                                <p class="alert-meta">Department: <?php echo escape($task['department_name']); ?> &middot; <?php echo escape($task['priority']); ?> priority</p>
                            </div>
                            <span class="alert-chip overdue"><?php echo escape(describeDueDifference($task['days_diff'])); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </article>
        <article class="card alerts-card">
            <header class="card-header">
                <h2>Due soon</h2>
                <p class="card-subtitle">Monitor upcoming deadlines</p>
            </header>
            <?php if (empty($upcomingAlerts['upcoming'])): ?>
                <p class="empty-state">No tasks due in the next five days.</p>
            <?php else: ?>
                <ul class="alert-list">
                    <?php foreach ($upcomingAlerts['upcoming'] as $task): ?>
                        <li class="alert-item">
                            <div>
                                <p class="alert-title"><?php echo escape($task['title']); ?></p>
                                <p class="alert-meta"><?php echo escape($task['department_name']); ?> &middot; Due <?php echo escape(formatDate($task['due_date'])); ?></p>
                            </div>
                            <span class="alert-chip soon"><?php echo escape(describeDueDifference($task['days_diff'])); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </article>
    </section>

    <section class="card updates-card">
        <header class="card-header">
            <h2>Latest progress updates</h2>
            <p class="card-subtitle">Recent field reports from directorates.</p>
        </header>
        <?php if (empty($recentUpdates)): ?>
            <p class="empty-state">No updates logged yet. Visit the Updates workspace to submit a status report.</p>
        <?php else: ?>
            <ul class="updates-list">
                <?php foreach ($recentUpdates as $update): ?>
                    <li class="update-item">
                        <div class="update-headline">
                            <h3><?php echo escape($update['task_title']); ?></h3>
                            <span class="update-progress"><?php echo (int) $update['progress_percent']; ?>%</span>
                        </div>
                        <p class="update-summary"><?php echo escape($update['update_summary']); ?></p>
                        <p class="update-meta">
                            <?php echo escape($update['department_name']); ?>
                            <?php if ($update['project_name']): ?>
                                &middot; Project: <?php echo escape($update['project_name']); ?>
                            <?php endif; ?>
                            <?php if ($update['created_by']): ?>
                                &middot; Reporter: <?php echo escape($update['created_by']); ?>
                            <?php endif; ?>
                            &middot; <?php echo escape(formatDate(substr($update['created_at'], 0, 10))); ?>
                        </p>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
