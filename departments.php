<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if ($currentUser === null) {
    header('Location: login.php');
    exit;
}

$pageTitle = 'Departments Directory | Municipal PMO Suite';
$activeNav = 'departments';

$departments = fetchDepartments($pdo);
$tasks = fetchTasks($pdo);
$summary = buildSummary($tasks, $departments);

$departmentStats = [];
foreach ($departments as $department) {
    $departmentStats[$department['id']] = [
        'name' => $department['name'],
        'total' => 0,
        'status' => array_fill_keys(allowedTaskStatuses(), 0),
        'high_risk' => 0,
        'next_due' => null,
    ];
}

foreach ($tasks as $task) {
    $deptId = (int) $task['department_id'];
    if (!isset($departmentStats[$deptId])) {
        continue;
    }
    $departmentStats[$deptId]['total']++;
    if (isset($departmentStats[$deptId]['status'][$task['status']])) {
        $departmentStats[$deptId]['status'][$task['status']]++;
    }
    if (in_array($task['risk_level'], ['High', 'Critical'], true)) {
        $departmentStats[$deptId]['high_risk']++;
    }
    $dueDate = DateTimeImmutable::createFromFormat('Y-m-d', $task['due_date']);
    if ($dueDate instanceof DateTimeImmutable) {
        if ($departmentStats[$deptId]['next_due'] === null || $dueDate < $departmentStats[$deptId]['next_due']) {
            $departmentStats[$deptId]['next_due'] = $dueDate;
        }
    }
}

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/flash.php';
?>
<main class="page-main departments-main">
    <section class="card">
        <header class="card-header">
            <h2>Directorate overview</h2>
            <p class="card-subtitle">Monitor task distribution and risk across every municipal office.</p>
        </header>
        <div class="department-grid">
            <?php foreach ($departmentStats as $department): ?>
                <article class="department-card">
                    <header>
                        <h3><?php echo escape($department['name']); ?></h3>
                        <span class="department-total"><?php echo $department['total']; ?> tasks</span>
                    </header>
                    <dl class="department-stats">
                        <?php foreach ($department['status'] as $status => $count): ?>
                            <div>
                                <dt><?php echo escape($status); ?></dt>
                                <dd><?php echo $count; ?></dd>
                            </div>
                        <?php endforeach; ?>
                    </dl>
                    <p class="department-risk">
                        High risk tasks: <strong><?php echo $department['high_risk']; ?></strong>
                    </p>
                    <p class="department-next">
                        Next due: <strong><?php echo $department['next_due'] ? escape($department['next_due']->format('d M Y')) : 'No upcoming due dates'; ?></strong>
                    </p>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="card">
        <header class="card-header">
            <h2>Summary snapshot</h2>
            <p class="card-subtitle">Aggregate counts from all departments.</p>
        </header>
        <div class="summary-grid">
            <div class="summary-tile">
                <span class="summary-label">Total tasks</span>
                <span class="summary-value"><?php echo $summary['total']; ?></span>
            </div>
            <?php foreach ($summary['status'] as $status => $count): ?>
                <div class="summary-tile">
                    <span class="summary-label"><?php echo escape($status); ?></span>
                    <span class="summary-value"><?php echo $count; ?></span>
                </div>
            <?php endforeach; ?>
            <?php foreach ($summary['risk'] as $risk => $count): ?>
                <div class="summary-tile">
                    <span class="summary-label"><?php echo escape($risk); ?> risk</span>
                    <span class="summary-value"><?php echo $count; ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
