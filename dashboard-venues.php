<?php
$activeView = 'venues';
$viewTitle = 'Meeting venues';
$viewLede = 'Explore approved rooms and virtual channels to assign your delegates accordingly.';
$viewActions = [
    ['href' => 'dashboard-agenda.php', 'label' => 'Back to agenda', 'class' => 'btn btn--ghost'],
];

$renderMain = function (array $context) {
    extract($context);
    ?>
    <section class="panel">
        <header class="panel__header">
            <div>
                <h2>Venue directory</h2>
                <p>Assign teams to rooms based on capacity and facilitation support.</p>
            </div>
        </header>
        <div class="locations-grid">
            <?php foreach ($locationsGrid as $location): ?>
                <article class="location-card">
                    <header>
                        <h3><?= htmlspecialchars($location['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <span class="badge">Capacity <?= htmlspecialchars((string) $location['capacity'], ENT_QUOTES, 'UTF-8') ?></span>
                    </header>
                    <p><?= htmlspecialchars($location['description'], ENT_QUOTES, 'UTF-8') ?></p>
                    <footer>
                        <span><?= htmlspecialchars($location['type'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span><?= htmlspecialchars($location['support'], ENT_QUOTES, 'UTF-8') ?></span>
                    </footer>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="panel">
        <header class="panel__header">
            <div>
                <h2>Status breakdown</h2>
                <p>Snapshot of confirmations across the organisation.</p>
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
