<?php
$activeView = 'summary';
$viewTitle = 'Executive summary';
$viewLede = 'Track the highlights shaping the meeting narrative and monitor incoming confirmations.';
$viewActions = [
    ['href' => 'dashboard-attendance.php', 'label' => 'View roster', 'class' => 'btn btn--ghost'],
];

$renderMain = function (array $context) {
    extract($context);
    ?>
    <section class="panel">
        <header class="panel__header">
            <div>
                <h2>Highlights</h2>
                <p>Signals guiding executive decision making.</p>
            </div>
        </header>
        <div class="panel__body panel__body--split">
            <div class="summary-list">
                <h3>Key points</h3>
                <ul>
                    <?php foreach ($summaryHighlights as $highlight): ?>
                        <li><?= htmlspecialchars($highlight, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="summary-board">
                <h3>Department sentiment</h3>
                <ul>
                    <?php foreach ($attendanceBoard as $row): ?>
                        <li>
                            <span><?= htmlspecialchars($row['department'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="badge badge--<?= htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($attendanceLabels[$row['status']], ENT_QUOTES, 'UTF-8') ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </section>

    <section class="panel">
        <header class="panel__header">
            <div>
                <h2>Update log</h2>
                <p>Recent additions that influence the executive story.</p>
            </div>
        </header>
        <div class="timeline timeline--compact">
            <?php foreach (array_slice($updatesFeed, 0, 4) as $update): ?>
                <div class="timeline__item">
                    <header>
                        <h3><?= htmlspecialchars($update['headline'], ENT_QUOTES, 'UTF-8') ?></h3>
                    </header>
                    <p><?= htmlspecialchars(mb_strimwidth($update['body'], 0, 160, '…'), ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
};

require __DIR__ . '/dashboard-template.php';
