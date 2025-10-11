<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if ($currentUser === null) {
    header('Location: login.php');
    exit;
}

$pageTitle = 'Tasks & Assignments | Municipal PMO Suite';
$activeNav = 'tasks';

$departments = fetchDepartments($pdo);
$projects = fetchProjects($pdo);
$tasks = fetchTasks($pdo);

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/flash.php';
?>
<main class="page-main tasks-main">
    <section class="card" id="task-form">
        <header class="card-header">
            <h2>Create a task</h2>
            <p class="card-subtitle">Assign work to a department with timeline, risk, and impact context.</p>
        </header>
        <form method="post" class="stacked-form" novalidate>
            <input type="hidden" name="action" value="create_task">
            <input type="hidden" name="redirect" value="tasks.php">
            <div class="form-grid">
                <label>
                    Task title
                    <input type="text" name="title" required placeholder="e.g., Launch emergency response drill">
                </label>
                <label>
                    Department
                    <select name="department_id" required>
                        <option value="">Select department</option>
                        <?php foreach ($departments as $department): ?>
                            <option value="<?php echo (int) $department['id']; ?>"><?php echo escape($department['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    Project (optional)
                    <select name="project_id">
                        <option value="">No linked project</option>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?php echo (int) $project['id']; ?>"><?php echo escape($project['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    Assignee
                    <input type="text" name="assignee" placeholder="Person responsible">
                </label>
                <label>
                    Due date
                    <input type="date" name="due_date" required>
                </label>
                <label>
                    Status
                    <select name="status">
                        <?php foreach (allowedTaskStatuses() as $status): ?>
                            <option value="<?php echo escape($status); ?>"><?php echo escape($status); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    Priority
                    <select name="priority">
                        <?php foreach (allowedPriorities() as $priority): ?>
                            <option value="<?php echo escape($priority); ?>"><?php echo escape($priority); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    Risk level
                    <select name="risk_level">
                        <?php foreach (allowedRiskLevels() as $risk): ?>
                            <option value="<?php echo escape($risk); ?>"><?php echo escape($risk); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    Impact level
                    <select name="impact_level">
                        <?php foreach (allowedImpactLevels() as $impact): ?>
                            <option value="<?php echo escape($impact); ?>"><?php echo escape($impact); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    Effort level
                    <select name="effort_level">
                        <?php foreach (allowedEffortLevels() as $effort): ?>
                            <option value="<?php echo escape($effort); ?>"><?php echo escape($effort); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
            <label>
                Description
                <textarea name="description" rows="4" placeholder="What is the desired outcome? Include context, collaborators, and dependencies."></textarea>
            </label>
            <button type="submit" class="primary-btn">Create task</button>
        </form>
    </section>

    <section class="card" id="task-list">
        <header class="card-header">
            <div>
                <h2>Task board</h2>
                <p class="card-subtitle">Filter assignments by department, status, priority, or project.</p>
            </div>
            <div class="filters">
                <input type="search" id="search" placeholder="Search tasks or assignees">
                <select id="filter-department">
                    <option value="all">All departments</option>
                    <?php foreach ($departments as $department): ?>
                        <option value="<?php echo (int) $department['id']; ?>"><?php echo escape($department['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="filter-project">
                    <option value="all">All projects</option>
                    <?php foreach ($projects as $project): ?>
                        <option value="<?php echo (int) $project['id']; ?>"><?php echo escape($project['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="filter-status">
                    <option value="all">All statuses</option>
                    <?php foreach (allowedTaskStatuses() as $status): ?>
                        <option value="<?php echo escape($status); ?>"><?php echo escape($status); ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="filter-priority">
                    <option value="all">All priorities</option>
                    <?php foreach (allowedPriorities() as $priority): ?>
                        <option value="<?php echo escape($priority); ?>"><?php echo escape($priority); ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="filter-risk">
                    <option value="all">All risk levels</option>
                    <?php foreach (allowedRiskLevels() as $risk): ?>
                        <option value="<?php echo escape($risk); ?>"><?php echo escape($risk); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </header>
        <?php if (empty($tasks)): ?>
            <p class="empty-state">No tasks yet. Use the form above to plan the next milestones.</p>
        <?php else: ?>
            <div class="table-wrapper">
                <table id="tasks-table">
                    <thead>
                        <tr>
                            <th>Task</th>
                            <th>Department</th>
                            <th>Project</th>
                            <th>Assignee</th>
                            <th>Due</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Risk</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tasks as $task): ?>
                            <tr data-department="<?php echo (int) $task['department_id']; ?>" data-status="<?php echo escape($task['status']); ?>" data-priority="<?php echo escape($task['priority']); ?>" data-project="<?php echo $task['project_id'] ?? 'none'; ?>" data-risk="<?php echo escape($task['risk_level']); ?>">
                                <td>
                                    <strong><?php echo escape($task['title']); ?></strong>
                                    <?php if ($task['description']): ?>
                                        <p class="description"><?php echo escape($task['description']); ?></p>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo escape($task['department_name']); ?></td>
                                <td><?php echo $task['project_name'] ? escape($task['project_name']) : '—'; ?></td>
                                <td><?php echo $task['assignee'] ? escape($task['assignee']) : '—'; ?></td>
                                <td>
                                    <time class="due-date" datetime="<?php echo escape($task['due_date']); ?>"><?php echo escape(formatDate($task['due_date'])); ?></time>
                                    <span class="due-indicator"></span>
                                </td>
                                <td>
                                    <form method="post" class="inline-form">
                                        <input type="hidden" name="action" value="update_task_status">
                                        <input type="hidden" name="redirect" value="tasks.php">
                                        <input type="hidden" name="task_id" value="<?php echo (int) $task['id']; ?>">
                                        <select name="status" onchange="this.form.submit()">
                                            <?php foreach (allowedTaskStatuses() as $status): ?>
                                                <option value="<?php echo escape($status); ?>"<?php echo $status === $task['status'] ? ' selected' : ''; ?>><?php echo escape($status); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                                </td>
                                <td><span class="badge priority-<?php echo strtolower($task['priority']); ?>"><?php echo escape($task['priority']); ?></span></td>
                                <td><span class="badge risk-<?php echo strtolower(str_replace(' ', '-', $task['risk_level'])); ?>"><?php echo escape($task['risk_level']); ?></span></td>
                                <td>
                                    <form method="post" class="inline-form" onsubmit="return confirm('Delete this task?');">
                                        <input type="hidden" name="action" value="delete_task">
                                        <input type="hidden" name="redirect" value="tasks.php">
                                        <input type="hidden" name="task_id" value="<?php echo (int) $task['id']; ?>">
                                        <button type="submit" class="ghost-btn">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
