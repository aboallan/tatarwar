<?php
session_start();

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/repository.php';

$flash = $_SESSION['flash'] ?? [];
unset($_SESSION['flash']);

$departments = meeting_departments();
$locations = meeting_locations();
$attendanceLabels = meeting_attendance_labels();
$todayMeetings = meeting_today_agenda();
$attendanceBoard = meeting_attendance_board();
$summaryHighlights = meeting_summary_highlights();
$locationsGrid = meeting_locations_grid();
$updatesFeed = meeting_updates_feed();
$repositoryErrors = meeting_repository_errors();
$repositoryConnected = meeting_repository_connected();

$viewNavigation = [
    'agenda' => ['label' => 'Agenda focus', 'href' => 'dashboard-agenda.php'],
    'updates' => ['label' => 'Attendance & briefing updates', 'href' => 'dashboard-updates.php'],
    'summary' => ['label' => 'Executive summary', 'href' => 'dashboard-summary.php'],
    'venues' => ['label' => 'Meeting venues', 'href' => 'dashboard-venues.php'],
    'attendance' => ['label' => 'Attendance roster', 'href' => 'dashboard-attendance.php'],
];

$activeView = $activeView ?? 'agenda';
if (!array_key_exists($activeView, $viewNavigation)) {
    $activeView = 'agenda';
}

$now = new DateTimeImmutable('now');
$defaultMeetingDate = $now->format('Y-m-d');
$defaultMeetingTime = $now->format('H:i');

$userSession = $_SESSION['user'] ?? [];

$userRecord = [
    'id' => $userSession['id'] ?? null,
    'name' => $userSession['name'] ?? null,
    'email' => $userSession['email'] ?? null,
    'department' => $userSession['department'] ?? null,
    'unique_code' => $userSession['unique_code'] ?? null,
    'attendance_status' => $userSession['attendance_status'] ?? 'pending',
    'location_preference' => $userSession['location_preference'] ?? '',
    'department_scope' => $userSession['department_scope'] ?? 'all',
    'department_focus' => $userSession['department_focus'] ?? '',
    'meeting_summary' => $userSession['meeting_summary'] ?? '',
];

try {
    $pdo = get_pdo();
    if ($userRecord['id']) {
        $stmt = $pdo->prepare('SELECT name, email, department, unique_code, attendance_status, location_preference, department_scope, department_focus, meeting_summary FROM meeting_users WHERE id = ? LIMIT 1');
        $stmt->execute([$userRecord['id']]);
        if ($row = $stmt->fetch()) {
            $userRecord = array_merge($userRecord, $row);
        }
    } elseif (!empty($userRecord['unique_code'])) {
        $stmt = $pdo->prepare('SELECT name, email, department, unique_code, attendance_status, location_preference, department_scope, department_focus, meeting_summary FROM meeting_users WHERE unique_code = ? LIMIT 1');
        $stmt->execute([$userRecord['unique_code']]);
        if ($row = $stmt->fetch()) {
            $userRecord = array_merge($userRecord, $row);
        }
    }
} catch (PDOException $exception) {
    // Keep session values if the database is not reachable.
}

$hasProfile = !empty($userRecord['name']);

$viewTitle = $viewTitle ?? $viewNavigation[$activeView]['label'];
$viewLede = $viewLede ?? '';
$viewActions = $viewActions ?? [];

if (!isset($renderMain) || !is_callable($renderMain)) {
    $renderMain = function () {
        echo '<section class="panel"><div class="panel__body"><p>No content configured for this view.</p></div></section>';
    };
}

$context = [
    'departments' => $departments,
    'locations' => $locations,
    'attendanceLabels' => $attendanceLabels,
    'todayMeetings' => $todayMeetings,
    'attendanceBoard' => $attendanceBoard,
    'summaryHighlights' => $summaryHighlights,
    'locationsGrid' => $locationsGrid,
    'updatesFeed' => $updatesFeed,
    'repositoryErrors' => $repositoryErrors,
    'repositoryConnected' => $repositoryConnected,
    'defaultMeetingDate' => $defaultMeetingDate,
    'defaultMeetingTime' => $defaultMeetingTime,
    'hasProfile' => $hasProfile,
    'userRecord' => $userRecord,
];
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meeting Brief Platform · Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body class="app app--dashboard">
    <div class="app__backdrop" aria-hidden="true"></div>

    <?php if (!empty($flash)): ?>
        <aside class="toast toast--<?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8') ?>" role="alert">
            <div class="toast__header">
                <strong><?= htmlspecialchars($flash['title'], ENT_QUOTES, 'UTF-8') ?></strong>
                <button type="button" class="toast__close" data-dismiss-toast aria-label="Close">×</button>
            </div>
            <p><?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?></p>
        </aside>
    <?php endif; ?>

    <main class="workspace workspace--dashboard">
        <aside class="workspace__sidebar" aria-label="Dashboard navigation">
            <div class="sidebar__logo">
                <span class="sidebar__icon">🗂️</span>
                <div>
                    <strong>Meeting Portal</strong>
                    <span>Department Coordination</span>
                </div>
            </div>

            <div class="sidebar__profile">
                <div class="sidebar__profile-info">
                    <strong><?= $hasProfile ? htmlspecialchars($userRecord['name'], ENT_QUOTES, 'UTF-8') : 'Guest access' ?></strong>
                    <span><?= $hasProfile ? htmlspecialchars($userRecord['department'] ?? 'Lead delegate', ENT_QUOTES, 'UTF-8') : 'Sign in to manage attendance' ?></span>
                </div>
                <?php if ($hasProfile): ?>
                    <form action="auth.php" method="post">
                        <input type="hidden" name="action" value="logout">
                        <button type="submit" class="btn btn--ghost btn--slim">Sign Out</button>
                    </form>
                <?php else: ?>
                    <div class="sidebar__profile-actions">
                        <a class="btn btn--ghost btn--slim" href="login.php">Sign In</a>
                        <a class="btn btn--secondary btn--slim" href="register.php">Register</a>
                    </div>
                <?php endif; ?>
            </div>

            <nav class="sidebar__section">
                <span class="sidebar__title">Workspace</span>
                <ul class="sidebar__list sidebar__list--actions">
                    <?php foreach ($viewNavigation as $key => $item): ?>
                        <li>
                            <a class="<?= $activeView === $key ? 'is-active' : '' ?>" href="<?= htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>

            <div class="sidebar__section">
                <span class="sidebar__title">Departments</span>
                <ul class="sidebar__list">
                    <?php foreach ($departments as $department): ?>
                        <li><span><?= htmlspecialchars($department, ENT_QUOTES, 'UTF-8') ?></span></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <footer class="sidebar__footer">
                <a class="btn btn--secondary btn--full" href="index.php">Return to Overview</a>
            </footer>
        </aside>

        <div class="workspace__content">
            <?php if (!$repositoryConnected && !empty($repositoryErrors)): ?>
                <div class="alert alert--error">
                    <strong>Database connection required</strong>
                    <ul>
                        <?php foreach ($repositoryErrors as $error): ?>
                            <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="alert__hint">Import <code>database.sql</code>, confirm the tables exist, and update <code>config.php</code> with your MySQL credentials to enable dashboard actions.</p>
                </div>
            <?php endif; ?>
            <header class="workspace__header">
                <div>
                    <p class="eyebrow">Tuesday, 25 July 2024 · 09:00 AM – 02:30 PM</p>
                    <h1><?= htmlspecialchars($viewTitle, ENT_QUOTES, 'UTF-8') ?></h1>
                    <?php if (!empty($viewLede)): ?>
                        <p class="workspace__lede"><?= htmlspecialchars($viewLede, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                </div>
                <div class="workspace__actions">
                    <?php foreach ($viewActions as $action): ?>
                        <a class="<?= htmlspecialchars($action['class'] ?? 'btn', ENT_QUOTES, 'UTF-8') ?>" href="<?= htmlspecialchars($action['href'], ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($action['label'], ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </header>

            <div class="workspace__grid">
                <section class="workspace__main">
                    <?php call_user_func($renderMain, $context); ?>
                </section>

                <aside class="workspace__rail">
                    <section class="card">
                        <header>
                            <div>
                                <h2>Welcome<?= $hasProfile ? ', ' . htmlspecialchars($userRecord['name'], ENT_QUOTES, 'UTF-8') : '' ?></h2>
                                <p><?= $hasProfile ? htmlspecialchars($userRecord['department'] ?? 'Lead delegate', ENT_QUOTES, 'UTF-8') : 'Sign in to manage attendance' ?></p>
                            </div>
                        </header>
                        <div class="card__code">
                            <?php if (!empty($userRecord['unique_code'])): ?>
                                <span class="label">Accreditation code</span>
                                <div class="code-row">
                                    <strong><?= htmlspecialchars($userRecord['unique_code'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    <button type="button" class="link" data-copy="<?= htmlspecialchars($userRecord['unique_code'], ENT_QUOTES, 'UTF-8') ?>">Copy</button>
                                </div>
                            <?php else: ?>
                                <span class="placeholder">Register to receive an accreditation code.</span>
                            <?php endif; ?>
                        </div>
                    </section>

                    <section class="card">
                        <header>
                            <div>
                                <h2>Quick stats</h2>
                                <p>Realtime meeting pulse</p>
                            </div>
                        </header>
                        <ul class="stat-list">
                            <li>
                                <strong><?= htmlspecialchars((string) count($departments), ENT_QUOTES, 'UTF-8') ?></strong>
                                <span>Departments invited</span>
                            </li>
                            <li>
                                <strong><?= htmlspecialchars((string) count($todayMeetings), ENT_QUOTES, 'UTF-8') ?></strong>
                                <span>Agenda items</span>
                            </li>
                            <li>
                                <strong><?= htmlspecialchars((string) count($updatesFeed), ENT_QUOTES, 'UTF-8') ?></strong>
                                <span>Updates logged</span>
                            </li>
                        </ul>
                    </section>

                    <section class="card">
                        <header>
                            <div>
                                <h2>Latest notes</h2>
                                <p>Highlights from departments</p>
                            </div>
                        </header>
                        <ul class="note-list">
                            <?php foreach (array_slice($summaryHighlights, 0, 6) as $highlight): ?>
                                <li><?= htmlspecialchars($highlight, ENT_QUOTES, 'UTF-8') ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                </aside>
            </div>
        </div>
    </main>

    <script src="script.js" defer></script>
</body>
</html>
