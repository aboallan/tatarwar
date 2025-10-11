<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if ($currentUser === null) {
    header('Location: login.php');
    exit;
}

$pageTitle = 'Projects Portfolio | Municipal PMO Suite';
$activeNav = 'projects';

$departments = fetchDepartments($pdo);
$projects = fetchProjects($pdo);

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/flash.php';
?>
<main class="page-main projects-main">
    <section class="card" id="project-form">
        <header class="card-header">
            <h2>Register a project</h2>
            <p class="card-subtitle">Capture sponsor, strategic alignment, and timeline details.</p>
        </header>
        <form method="post" class="stacked-form" novalidate>
            <input type="hidden" name="action" value="create_project">
            <input type="hidden" name="redirect" value="projects.php">
            <div class="form-grid">
                <label>
                    Project name
                    <input type="text" name="name" required placeholder="e.g., Digital Permitting Platform">
                </label>
                <label>
                    Sponsor
                    <input type="text" name="sponsor" placeholder="Which directorate is sponsoring?">
                </label>
                <label>
                    Strategic theme
                    <input type="text" name="strategic_theme" placeholder="e.g., Smart City">
                </label>
                <label>
                    Start date
                    <input type="date" name="start_date">
                </label>
                <label>
                    Target date
                    <input type="date" name="target_date">
                </label>
                <label>
                    Status
                    <select name="status">
                        <option value="Active">Active</option>
                        <option value="On Hold">On Hold</option>
                        <option value="Completed">Completed</option>
                        <option value="Archived">Archived</option>
                    </select>
                </label>
            </div>
            <label>
                Description
                <textarea name="description" rows="4" placeholder="Describe objectives, scope, and expected outcomes."></textarea>
            </label>
            <button type="submit" class="primary-btn">Add project</button>
        </form>
    </section>

    <section class="card" id="project-catalog">
        <header class="card-header">
            <h2>Portfolio catalog</h2>
            <p class="card-subtitle">All projects tracked across departments.</p>
        </header>
        <?php if (empty($projects)): ?>
            <p class="empty-state">No initiatives registered yet. Add your first project using the form above.</p>
        <?php else: ?>
            <div class="project-grid">
                <?php foreach ($projects as $project): ?>
                    <?php
                        $totalTasks = (int) $project['total_tasks'];
                        $completed = (int) $project['completed_tasks'];
                        $progress = $totalTasks > 0 ? (int) round(($completed / $totalTasks) * 100) : 0;
                    ?>
                    <article class="project-card">
                        <header>
                            <div class="project-meta">
                                <h3><?php echo escape($project['name']); ?></h3>
                                <span class="status-pill status-<?php echo strtolower(str_replace(' ', '-', $project['status'])); ?>"><?php echo escape($project['status']); ?></span>
                            </div>
                            <?php if ($project['strategic_theme']): ?>
                                <p class="project-theme">Theme: <?php echo escape($project['strategic_theme']); ?></p>
                            <?php endif; ?>
                        </header>
                        <p class="project-description"><?php echo escape($project['description'] ?: 'No description provided yet.'); ?></p>
                        <dl class="project-facts">
                            <div>
                                <dt>Sponsor</dt>
                                <dd><?php echo escape($project['sponsor'] ?: '—'); ?></dd>
                            </div>
                            <div>
                                <dt>Timeline</dt>
                                <dd><?php echo escape(formatDate($project['start_date'])); ?> → <?php echo escape(formatDate($project['target_date'])); ?></dd>
                            </div>
                        </dl>
                        <div class="project-progress">
                            <div class="progress-track">
                                <div class="progress-fill" style="width: <?php echo $progress; ?>%"></div>
                            </div>
                            <span class="progress-label"><?php echo $progress; ?>% complete (<?php echo $completed; ?> of <?php echo $totalTasks; ?> tasks)</span>
                        </div>
                        <footer class="project-footer">
                            <span class="project-stat">Tasks: <?php echo $totalTasks; ?></span>
                            <span class="project-stat">At-risk: <?php echo (int) $project['at_risk_tasks']; ?></span>
                            <span class="project-stat">Last update: <?php echo $project['last_update_at'] ? escape(formatDate(substr($project['last_update_at'], 0, 10))) : '—'; ?></span>
                        </footer>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
