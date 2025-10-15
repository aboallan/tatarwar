<?php
$pageTitle = 'Departments';
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
    SUM(CASE WHEN t.status = "Completed" THEN 1 ELSE 0 END) AS completed_count
    FROM departments d
    LEFT JOIN tasks t ON t.department_id = d.id
    GROUP BY d.id
    ORDER BY d.name ASC')->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<section class="grid" style="grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));">
    <article class="card form-card">
        <h2>Add Department</h2>
        <p class="text-muted">Create a new department to assign work</p>
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
    </article>
    <article class="card">
        <div class="metric-header">
            <h2>Departments list</h2>
            <span class="metric-pill"><span class="dot"></span><?= number_format(count($departments)); ?> units</span>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Department</th>
                        <th>Email</th>
                        <th>Total tasks</th>
                        <th>Completed</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($departments as $department): ?>
                        <tr>
                            <td><?= sanitize($department['name']); ?></td>
                            <td><?= $department['email'] ? sanitize($department['email']) : '-'; ?></td>
                            <td><span class="table-number"><?= number_format((int)$department['tasks_count']); ?></span></td>
                            <td><span class="table-number muted"><?= number_format((int)$department['completed_count']); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
