<?php
$activeView = 'attendance';
$viewTitle = 'Attendance roster';
$viewLede = 'See how each department is responding and coordinate final confirmations.';
$viewActions = [
    ['href' => 'dashboard-updates.php#attendance-update', 'label' => 'Manage responses', 'class' => 'btn btn--ghost'],
];

$renderMain = function (array $context) {
    extract($context);
    ?>
    <section class="panel">
        <header class="panel__header">
            <div>
                <h2>Response board</h2>
                <p>Live view of department confirmations for the upcoming meeting.</p>
            </div>
        </header>
        <div class="table">
            <div class="table__header">
                <span>Department</span>
                <span>Status</span>
                <span>Attendees</span>
                <span>Location</span>
            </div>
            <div class="table__body">
                <?php foreach ($attendanceBoard as $row): ?>
                    <div class="table__row">
                        <span><?= htmlspecialchars($row['department'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="badge badge--<?= htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($attendanceLabels[$row['status']], ENT_QUOTES, 'UTF-8') ?></span>
                        <span><?= htmlspecialchars((string) $row['representatives'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span><?= htmlspecialchars($row['location'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="panel">
        <header class="panel__header">
            <div>
                <h2>Status breakdown</h2>
                <p>Overall status of confirmations across the roster.</p>
            </div>
        </header>
        <div class="stat-list stat-list--inline">
            <?php
            $statusCounts = ['attend' => 0, 'pending' => 0, 'decline' => 0];
            foreach ($attendanceBoard as $row) {
                $status = $row['status'];
                if (!isset($statusCounts[$status])) {
                    $statusCounts[$status] = 0;
                }
                $statusCounts[$status]++;
            }
            ?>
            <div>
                <strong><?= htmlspecialchars((string) ($statusCounts['attend'] ?? 0), ENT_QUOTES, 'UTF-8') ?></strong>
                <span>Attending</span>
            </div>
            <div>
                <strong><?= htmlspecialchars((string) ($statusCounts['pending'] ?? 0), ENT_QUOTES, 'UTF-8') ?></strong>
                <span>Pending</span>
            </div>
            <div>
                <strong><?= htmlspecialchars((string) ($statusCounts['decline'] ?? 0), ENT_QUOTES, 'UTF-8') ?></strong>
                <span>Declined</span>
            </div>
        </div>
    </section>
    <?php
};

require __DIR__ . '/dashboard-template.php';
