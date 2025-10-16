<?php
$pageTitle = 'Departments';
$pageDescription = 'Manage departments and view their task performance.';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/functions.php';

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
<section class="metrics-grid">
    <article class="panel metric-card">
        <span class="metric-label">Active units</span>
        <span class="metric-value"><?= number_format($totalDepartments); ?></span>
        <span class="metric-footnote">Participating in the workspace</span>
    </article>
    <article class="panel metric-card">
        <span class="metric-label">Contacts listed</span>
        <span class="metric-value"><?= number_format($withContacts); ?></span>
        <span class="metric-footnote">Teams with escalation emails</span>
    </article>
    <article class="panel metric-card">
        <span class="metric-label">Active workloads</span>
        <span class="metric-value"><?= number_format($activeWorkloads); ?></span>
        <span class="metric-footnote">Departments with assigned tasks</span>
    </article>
</section>

<section class="panel form-panel">
    <h2>Add Department</h2>
    <p class="panel-subtitle">Create a new department to assign work</p>
    <?php if ($errors): ?>
        <div class="alert error">
            <?= implode('<br>', array_map('sanitize', $errors)); ?>
        </div>
    <?php endif; ?>
    <?php if ($success && !$errors): ?>
        <div class="alert success"><?= sanitize($success); ?></div>
    <?php endif; ?>
    <form method="post">
        <div>
            <label for="name">Department name</label>
            <input type="text" id="name" name="name" required>
        </div>
        <div>
            <label for="email">Email (optional)</label>
            <input type="email" id="email" name="email" placeholder="team@example.com">
        </div>
        <button type="submit">Add department</button>
    </form>
</section>

<section class="department-grid">
    <?php if (!$departments): ?>
        <div class="panel empty-note">No departments available. Add one to get started.</div>
    <?php else: ?>
        <?php $colors = ['blue', 'pink', 'orange', 'green', 'yellow']; ?>
        <?php foreach ($departments as $index => $department): ?>
            <?php
            $totalTasks = (int)$department['tasks_count'];
            $completed = (int)$department['completed_count'];
            $inProgress = (int)$department['in_progress_count'];
            $pending = (int)$department['pending_count'];
            $overdue = (int)$department['overdue_count'];
            $rate = $totalTasks > 0 ? round(($completed / $totalTasks) * 100) : 0;
            $color = $colors[$index % count($colors)];
            ?>
            <article class="panel department-card">
                <div class="department-header">
                    <div class="department-identity">
                        <span class="department-dot <?= $color; ?>" aria-hidden="true"></span>
                        <div>
                            <h2><?= sanitize($department['name']); ?></h2>
                            <p><?= $department['email'] ? sanitize($department['email']) : 'No contact listed'; ?></p>
                        </div>
                    </div>
                    <span class="badge neutral"><?= number_format($totalTasks); ?> tasks</span>
                </div>
                <div class="department-stats">
                    <div>
                        <span><?= number_format($pending); ?></span>
                        Pending
                    </div>
                    <div>
                        <span><?= number_format($inProgress); ?></span>
                        In progress
                    </div>
                    <div>
                        <span><?= number_format($completed); ?></span>
                        Completed
                    </div>
                    <div>
                        <span><?= number_format($overdue); ?></span>
                        Overdue
                    </div>
                </div>
                <div class="progress-wrap">
                    <div class="progress-track">
                        <span class="progress-fill" style="width: <?= $rate; ?>%"></span>
                    </div>
                    <span class="progress-label">Completion rate <?= $rate; ?>%</span>
                </div>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
