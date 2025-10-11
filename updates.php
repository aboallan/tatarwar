<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if ($currentUser === null) {
    header('Location: login.php');
    exit;
}

$pageTitle = 'Progress Updates | Municipal PMO Suite';
$activeNav = 'updates';

$tasks = fetchTasks($pdo);
$recentUpdates = fetchRecentUpdates($pdo);

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/flash.php';
?>
<main class="page-main updates-main">
    <section class="card" id="update-form">
        <header class="card-header">
            <h2>Log a progress update</h2>
            <p class="card-subtitle">Document percentage completion, risks, and blockers.</p>
        </header>
        <form method="post" class="stacked-form" novalidate>
            <input type="hidden" name="action" value="log_task_update">
            <input type="hidden" name="redirect" value="updates.php">
            <label>
                Task
                <select name="task_id" required>
                    <option value="">Select task</option>
                    <?php foreach ($tasks as $task): ?>
                        <option value="<?php echo (int) $task['id']; ?>"><?php echo escape($task['title']); ?> (<?php echo escape($task['department_name']); ?>)</option>
                    <?php endforeach; ?>
                </select>
            </label>
            <div class="form-grid">
                <label>
                    Progress (%)
                    <input type="number" name="progress_percent" min="0" max="100" step="1" value="0" required>
                </label>
                <label>
                    Risk level
                    <select name="risk_level">
                        <option value="">No change</option>
                        <?php foreach (allowedRiskLevels() as $risk): ?>
                            <option value="<?php echo escape($risk); ?>"><?php echo escape($risk); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    Reporter
                    <input type="text" name="created_by" placeholder="Name of person logging the update">
                </label>
            </div>
            <label>
                Update summary
                <textarea name="update_summary" rows="3" required placeholder="Summarize key progress since the last report."></textarea>
            </label>
            <label>
                Risk note (optional)
                <textarea name="risk_note" rows="3" placeholder="Flag emerging risks or mitigation plans."></textarea>
            </label>
            <label>
                Blocker note (optional)
                <textarea name="blocker_note" rows="3" placeholder="Document blockers requiring escalation."></textarea>
            </label>
            <button type="submit" class="primary-btn">Submit update</button>
        </form>
    </section>

    <section class="card updates-timeline">
        <header class="card-header">
            <h2>Recent updates</h2>
            <p class="card-subtitle">Latest signals from the field.</p>
        </header>
        <?php if (empty($recentUpdates)): ?>
            <p class="empty-state">No updates recorded yet. Fill out the form above to start the history.</p>
        <?php else: ?>
            <ul class="timeline">
                <?php foreach ($recentUpdates as $update): ?>
                    <li class="timeline-entry">
                        <header class="timeline-header">
                            <h3><?php echo escape($update['task_title']); ?></h3>
                            <span class="timeline-progress"><?php echo (int) $update['progress_percent']; ?>%</span>
                        </header>
                        <p class="timeline-meta">
                            <?php echo escape($update['department_name']); ?>
                            <?php if ($update['project_name']): ?>
                                &middot; Project: <?php echo escape($update['project_name']); ?>
                            <?php endif; ?>
                            &middot; Logged on <?php echo escape(formatDate(substr($update['created_at'], 0, 10))); ?>
                            <?php if ($update['created_by']): ?>
                                &middot; By <?php echo escape($update['created_by']); ?>
                            <?php endif; ?>
                        </p>
                        <p class="timeline-summary"><?php echo escape($update['update_summary']); ?></p>
                        <?php if ($update['risk_note']): ?>
                            <p class="timeline-note"><strong>Risk:</strong> <?php echo escape($update['risk_note']); ?></p>
                        <?php endif; ?>
                        <?php if ($update['blocker_note']): ?>
                            <p class="timeline-note"><strong>Blocker:</strong> <?php echo escape($update['blocker_note']); ?></p>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
