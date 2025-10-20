<?php
$pageTitle = 'Departments';
$pageDescription = 'Maintain department records and workload summaries.';
require_once __DIR__ . '/includes/functions.php';
require_login();
require_once __DIR__ . '/db.php';

$errors = [];
$success = '';

$defaultDepartments = [
    'Office of the Secretary General',
    'General Department of Information Technology',
    'Cybersecurity Department',
    'Investment Agency',
    'General Department of Gardens and Landscaping',
    'General Department of Internal Audits',
    'General Department of Operations and Emergencies',
    'Department of Safety and Security',
    'Central Unit for Plan Approval',
    'Department of Land Affairs',
    'Environmental Health Department',
    'General Department of Cleaning',
    'Central City Municipality',
    'Northern Municipality',
];

$count = (int)$pdo->query('SELECT COUNT(*) FROM departments')->fetchColumn();
if ($count === 0) {
    $insert = $pdo->prepare('INSERT INTO departments (name) VALUES (:name)');
    foreach ($defaultDepartments as $departmentName) {
        $insert->execute(['name' => $departmentName]);
    }
    $success = 'Default departments were added automatically.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if ($name === '') {
        $errors[] = 'Department name is required.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('INSERT INTO departments (name, email) VALUES (:name, :email)');
        $stmt->execute([
            'name' => $name,
            'email' => $email ?: null,
        ]);
        $success = 'Department created successfully.';
    }
}

$departments = $pdo->query('SELECT d.*, COUNT(t.id) AS tasks_count,
    SUM(CASE WHEN t.status = "Completed" THEN 1 ELSE 0 END) AS completed_count,
    SUM(CASE WHEN t.status = "In Progress" THEN 1 ELSE 0 END) AS in_progress_count,
    SUM(CASE WHEN t.status = "Pending" THEN 1 ELSE 0 END) AS pending_count,
    SUM(CASE WHEN t.due_date IS NOT NULL AND t.due_date < CURDATE() AND t.status != "Completed" THEN 1 ELSE 0 END) AS overdue_count
    FROM departments d
    LEFT JOIN tasks t ON t.department_id = d.id
    GROUP BY d.id
    ORDER BY d.name ASC')->fetchAll();

$totalDepartments = count($departments);
$withContacts = 0;
$activeWorkloads = 0;

foreach ($departments as $department) {
    if (!empty($department['email'])) {
        $withContacts++;
    }
    if ((int)$department['tasks_count'] > 0) {
        $activeWorkloads++;
    }
}

include __DIR__ . '/includes/header.php';
?>
<?php if ($success && !$errors): ?>
    <div class="alert success global-alert"><?= sanitize($success); ?></div>
<?php endif; ?>
<?php if ($errors): ?>
    <div class="alert error global-alert">
        <?= implode('<br>', array_map('sanitize', $errors)); ?>
    </div>
<?php endif; ?>
<section class="summary-cards">
    <article class="summary-card">
        <span class="summary-label">Active units</span>
        <span class="summary-value"><?= number_format($totalDepartments); ?></span>
        <span class="summary-footnote">Participating in the workspace</span>
    </article>
    <article class="summary-card">
        <span class="summary-label">Contacts listed</span>
        <span class="summary-value"><?= number_format($withContacts); ?></span>
        <span class="summary-footnote">Teams with escalation emails</span>
    </article>
    <article class="summary-card">
        <span class="summary-label">Active workloads</span>
        <span class="summary-value"><?= number_format($activeWorkloads); ?></span>
        <span class="summary-footnote">Departments with assigned tasks</span>
    </article>
</section>

<section class="panel classic-panel">
    <header class="panel-header">
        <h2>Add department</h2>
        <span>Create a new division record</span>
    </header>
    <form method="post" class="classic-form">
        <div class="form-row">
            <div class="form-group">
                <label for="name">Department name</label>
                <input type="text" id="name" name="name" required placeholder="e.g. Transportation Affairs">
            </div>
            <div class="form-group">
                <label for="email">Email (optional)</label>
                <input type="email" id="email" name="email" placeholder="team@example.com">
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="primary-action">Add department</button>
        </div>
    </form>
</section>

<section class="panel classic-panel">
    <header class="panel-header">
        <h2>Department directory</h2>
        <span>Task distribution by team</span>
    </header>
    <?php if (!$departments): ?>
        <div class="empty-note">No departments available. Add one to get started.</div>
    <?php else: ?>
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th scope="col">Department</th>
                        <th scope="col">Contact</th>
                        <th scope="col">Pending</th>
                        <th scope="col">In progress</th>
                        <th scope="col">Completed</th>
                        <th scope="col">Overdue</th>
                        <th scope="col">Completion rate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($departments as $department): ?>
                        <?php
                        $totalTasks = (int)$department['tasks_count'];
                        $completed = (int)$department['completed_count'];
                        $inProgress = (int)$department['in_progress_count'];
                        $pending = (int)$department['pending_count'];
                        $overdue = (int)$department['overdue_count'];
                        $rate = $totalTasks > 0 ? round(($completed / max($totalTasks, 1)) * 100) : 0;
                        ?>
                        <tr>
                            <td><?= sanitize($department['name']); ?></td>
                            <td><?= $department['email'] ? sanitize($department['email']) : '—'; ?></td>
                            <td><?= number_format($pending); ?></td>
                            <td><?= number_format($inProgress); ?></td>
                            <td><?= number_format($completed); ?></td>
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
<?php include __DIR__ . '/includes/footer.php'; ?>
