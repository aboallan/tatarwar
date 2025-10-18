<?php
$pageTitle = 'Calendar';
$pageDescription = 'View all task deadlines in calendar format.';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/functions.php';

$today = new DateTimeImmutable('today');
$monthParam = $_GET['month'] ?? $today->format('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $monthParam)) {
    $monthParam = $today->format('Y-m');
}
[$year, $month] = array_map('intval', explode('-', $monthParam));
if ($month < 1 || $month > 12) {
    $year = (int)$today->format('Y');
    $month = (int)$today->format('n');
}

try {
    $currentMonth = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
} catch (Exception $exception) {
    $currentMonth = new DateTimeImmutable('first day of this month');
}

$start = $currentMonth;
if ($currentMonth->format('w') !== '0') {
    $start = $currentMonth->modify('last sunday');
}
$end = $start->modify('+41 days');

$eventsStmt = $pdo->prepare('SELECT ce.event_date, ce.title, ce.priority, ce.status, d.name AS department_name
    FROM calendar_events ce
    JOIN departments d ON d.id = ce.department_id
    WHERE ce.event_date BETWEEN :start AND :end
    ORDER BY ce.event_date ASC, FIELD(ce.priority, "High", "Medium", "Low"), ce.title ASC');
$eventsStmt->execute([
    'start' => $start->format('Y-m-d'),
    'end' => $end->format('Y-m-d'),
]);
$events = $eventsStmt->fetchAll();

$eventsByDate = [];
$monthKey = $currentMonth->format('Y-m');
$monthStats = [
    'total' => 0,
    'high' => 0,
    'medium' => 0,
    'low' => 0,
    'pending' => 0,
];
$legend = [];

foreach ($events as $event) {
    $date = $event['event_date'];
    $eventsByDate[$date][] = $event;

    if (strpos($date, $monthKey) === 0) {
        $monthStats['total']++;
        $priority = strtolower($event['priority'] ?? 'medium');
        if (isset($monthStats[$priority])) {
            $monthStats[$priority]++;
        }
        if (strtolower($event['status']) !== 'completed') {
            $monthStats['pending']++;
        }
    }

    $legend[$event['department_name']] = true;
}

ksort($legend);

$prevMonth = $currentMonth->modify('-1 month')->format('Y-m');
$nextMonth = $currentMonth->modify('+1 month')->format('Y-m');

function calendar_tag_class(string $label): string
{
    static $palette = [
        'tag-blue',
        'tag-purple',
        'tag-green',
        'tag-amber',
        'tag-pink',
        'tag-teal',
        'tag-indigo',
        'tag-coral',
    ];
    $index = abs(crc32($label)) % count($palette);
    return $palette[$index];
}

include __DIR__ . '/includes/header.php';
?>
<section class="calendar-hero panel">
    <div>
        <h2><?= $currentMonth->format('F Y'); ?></h2>
        <p>Stay ahead of deadlines with a single view of due dates and department commitments.</p>
    </div>
    <div class="calendar-nav">
        <a class="ghost-action" href="calendar.php?month=<?= sanitize($prevMonth); ?>">Prev</a>
        <span class="calendar-nav-current"><?= $currentMonth->format('F Y'); ?></span>
        <a class="ghost-action" href="calendar.php?month=<?= sanitize($nextMonth); ?>">Next</a>
    </div>
</section>

<section class="calendar-layout">
    <aside class="panel calendar-meta">
        <h3>Quick stats</h3>
        <ul>
            <li><span>Items this month</span><strong><?= number_format($monthStats['total']); ?></strong></li>
            <li><span>High priority</span><strong><?= number_format($monthStats['high']); ?></strong></li>
            <li><span>Medium priority</span><strong><?= number_format($monthStats['medium']); ?></strong></li>
            <li><span>Open statuses</span><strong><?= number_format($monthStats['pending']); ?></strong></li>
        </ul>
        <h3>Department colors</h3>
        <ul class="legend">
            <?php if (!$legend): ?>
                <li>No events scheduled.</li>
            <?php else: ?>
                <?php foreach (array_keys($legend) as $departmentName): ?>
                    <li>
                        <span class="legend-dot <?= calendar_tag_class($departmentName); ?>"></span>
                        <?= sanitize($departmentName); ?>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </aside>
    <div class="panel calendar-grid">
        <header class="calendar-grid-head">
            <?php foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $day): ?>
                <span><?= $day; ?></span>
            <?php endforeach; ?>
        </header>
        <div class="calendar-grid-body">
            <?php for ($offset = 0; $offset < 42; $offset++): ?>
                <?php
                $date = $start->modify('+' . $offset . ' days');
                $dateKey = $date->format('Y-m-d');
                $isCurrentMonth = $date->format('m') === $currentMonth->format('m');
                $isToday = $dateKey === $today->format('Y-m-d');
                $cellClasses = [];
                if (!$isCurrentMonth) {
                    $cellClasses[] = 'muted';
                }
                if ($isToday) {
                    $cellClasses[] = 'today';
                }
                $cellClass = $cellClasses ? ' ' . implode(' ', $cellClasses) : '';
                ?>
                <div class="calendar-cell<?= $cellClass; ?>">
                    <div class="calendar-cell-header">
                        <span class="calendar-date-number"><?= $date->format('j'); ?></span>
                    </div>
                    <div class="calendar-events">
                        <?php if (!empty($eventsByDate[$dateKey])): ?>
                            <?php foreach ($eventsByDate[$dateKey] as $event): ?>
                                <?php
                                $priorityClass = 'priority-' . strtolower($event['priority'] ?? 'medium');
                                $tagClass = calendar_tag_class($event['department_name']);
                                ?>
                                <article class="calendar-event <?= $tagClass; ?> <?= $priorityClass; ?>">
                                    <span class="event-title"><?= sanitize($event['title']); ?></span>
                                    <span class="event-meta"><?= sanitize($event['department_name']); ?> · <?= sanitize($event['priority']); ?></span>
                                </article>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span class="empty-slot">—</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endfor; ?>
        </div>
    </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
