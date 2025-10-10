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
    'agenda' => 'Agenda focus',
    'updates' => 'Attendance & briefing updates',
    'summary' => 'Executive summary',
    'venues' => 'Meeting venues',
    'attendance' => 'Attendance roster',
];

$viewConfig = [
    'agenda' => [
        'title' => 'Agenda focus',
        'lede' => 'Review the sessions locked for the coordination meeting and add new topics when needed.',
        'actions' => [
            ['href' => '#add-meeting', 'label' => 'Add meeting', 'class' => 'btn btn--primary'],
            ['href' => 'dashboard.php?view=updates#post-update', 'label' => 'Post update', 'class' => 'btn btn--ghost'],
        ],
    ],
    'updates' => [
        'title' => 'Attendance & briefing updates',
        'lede' => 'Confirm participation details, log department notes, and keep everyone in sync.',
        'actions' => [
            ['href' => '#attendance-update', 'label' => 'Update attendance', 'class' => 'btn btn--primary'],
            ['href' => '#post-update', 'label' => 'Share briefing note', 'class' => 'btn btn--ghost'],
        ],
    ],
    'summary' => [
        'title' => 'Executive summary',
        'lede' => 'Track the highlights shaping the meeting narrative and monitor incoming confirmations.',
        'actions' => [
            ['href' => 'dashboard.php?view=attendance', 'label' => 'View roster', 'class' => 'btn btn--ghost'],
        ],
    ],
    'venues' => [
        'title' => 'Meeting venues',
        'lede' => 'Explore approved rooms and virtual channels to assign your delegates accordingly.',
        'actions' => [
            ['href' => 'dashboard.php?view=agenda', 'label' => 'Back to agenda', 'class' => 'btn btn--ghost'],
        ],
    ],
    'attendance' => [
        'title' => 'Attendance roster',
        'lede' => 'See how each department is responding and coordinate final confirmations.',
        'actions' => [
            ['href' => 'dashboard.php?view=updates#attendance-update', 'label' => 'Manage responses', 'class' => 'btn btn--ghost'],
        ],
    ],
];

$requestedView = strtolower((string) ($_GET['view'] ?? 'agenda'));
$view = array_key_exists($requestedView, $viewNavigation) ? $requestedView : 'agenda';
$currentView = $viewConfig[$view];

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
            <nav class="sidebar__section">
                <span class="sidebar__title">Overview</span>
                <ul class="sidebar__list sidebar__list--actions">
                    <?php foreach ($viewNavigation as $key => $label): ?>
                        <li>
                            <a class="<?= $view === $key ? 'is-active' : '' ?>" href="dashboard.php?view=<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
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
                <?php if (!$hasProfile): ?>
                    <a class="btn btn--ghost btn--full" href="login.php">Sign In</a>
                <?php endif; ?>
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
                    <h1><?= htmlspecialchars($currentView['title'], ENT_QUOTES, 'UTF-8') ?></h1>
                    <p class="workspace__lede"><?= htmlspecialchars($currentView['lede'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <div class="workspace__actions">
                    <?php foreach ($currentView['actions'] as $action): ?>
                        <a class="<?= htmlspecialchars($action['class'], ENT_QUOTES, 'UTF-8') ?>" href="<?= htmlspecialchars($action['href'], ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($action['label'], ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </header>

            <div class="workspace__grid">
                <section class="workspace__main">
                    <?php if ($view === 'agenda'): ?>
                        <div class="chip-group" role="tablist" aria-label="Agenda filters">
                            <button type="button" class="chip is-active">All meetings</button>
                            <button type="button" class="chip">Strategic</button>
                            <button type="button" class="chip">Operations</button>
                            <button type="button" class="chip">Workshops</button>
                        </div>

                        <div class="meeting-feed">
                            <?php foreach ($todayMeetings as $slot): ?>
                                <article class="meeting-card">
                                    <header>
                                        <span class="meeting-card__time"><?= htmlspecialchars($slot['time'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="badge badge--<?= strtolower(htmlspecialchars($slot['tag'], ENT_QUOTES, 'UTF-8')) ?>"><?= htmlspecialchars($slot['tag'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </header>
                                    <h3><?= htmlspecialchars($slot['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                                    <p><?= htmlspecialchars($slot['summary'], ENT_QUOTES, 'UTF-8') ?></p>
                                    <footer>
                                        <span><?= htmlspecialchars($slot['department'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <span><?= htmlspecialchars($slot['location'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </footer>
                                </article>
                            <?php endforeach; ?>
                        </div>

                        <section class="panel panel--form" id="add-meeting">
                            <header class="panel__header">
                                <div>
                                    <h2>Add meeting</h2>
                                    <p>Capture a new session so it appears in the shared agenda immediately.</p>
                                </div>
                            </header>
                            <div class="panel__body">
                                <?php if (!$hasProfile): ?>
                                    <div class="empty-state">
                                        <strong>Sign in to add meetings</strong>
                                        <span>Use the coordinator account or create one to publish new agenda items.</span>
                                    </div>
                                <?php elseif (!$repositoryConnected): ?>
                                    <div class="empty-state">
                                        <strong>Connect the database</strong>
                                        <span>Import <code>database.sql</code> and update <code>config.php</code> to enable agenda publishing.</span>
                                    </div>
                                <?php else: ?>
                                    <form action="manage.php" method="post" class="update">
                                        <input type="hidden" name="action" value="create_meeting">
                                        <div class="grid">
                                            <div class="field">
                                                <label for="meeting-title">Title</label>
                                                <input id="meeting-title" name="title" type="text" placeholder="e.g., Operations readiness review" required>
                                            </div>
                                            <div class="field">
                                                <label for="meeting-date">Date</label>
                                                <input id="meeting-date" name="scheduled_date" type="date" value="<?= htmlspecialchars($defaultMeetingDate, ENT_QUOTES, 'UTF-8') ?>" required>
                                            </div>
                                            <div class="field">
                                                <label for="meeting-time">Time</label>
                                                <input id="meeting-time" name="scheduled_time" type="time" value="<?= htmlspecialchars($defaultMeetingTime, ENT_QUOTES, 'UTF-8') ?>" required>
                                            </div>
                                            <div class="field">
                                                <label for="meeting-tag">Tag</label>
                                                <input id="meeting-tag" name="tag" type="text" placeholder="Strategic, Workshop, Update" required>
                                            </div>
                                            <div class="field">
                                                <label for="meeting-department">Department</label>
                                                <select id="meeting-department" name="department" required<?= empty($departments) ? ' disabled' : '' ?>>
                                                    <option value=""><?= empty($departments) ? 'Add departments in the database first' : 'Select department' ?></option>
                                                    <?php foreach ($departments as $department): ?>
                                                        <option value="<?= htmlspecialchars($department, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($department, ENT_QUOTES, 'UTF-8') ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="field">
                                                <label for="meeting-location">Location</label>
                                                <select id="meeting-location" name="location" required<?= empty($locations) ? ' disabled' : '' ?>>
                                                    <option value=""><?= empty($locations) ? 'Add venues in the database first' : 'Choose location' ?></option>
                                                    <?php foreach ($locations as $location): ?>
                                                        <option value="<?= htmlspecialchars($location, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($location, ENT_QUOTES, 'UTF-8') ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="field">
                                            <label for="meeting-summary">Summary</label>
                                            <textarea id="meeting-summary" name="summary" rows="4" maxlength="600" data-counter placeholder="Key talking points and desired outcomes"></textarea>
                                        </div>
                                        <button type="submit" class="btn btn--primary">Save meeting</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </section>
                    <?php elseif ($view === 'updates'): ?>
                        <section class="panel">
                            <header class="panel__header">
                                <div>
                                    <h2>Briefing updates</h2>
                                    <p>Latest notes submitted by coordinators and department leads.</p>
                                </div>
                            </header>
                            <div class="timeline">
                                <?php foreach ($updatesFeed as $update): ?>
                                    <?php
                                    $timestamp = $update['created_at'] ?? '';
                                    try {
                                        $postedAt = $timestamp ? (new DateTimeImmutable($timestamp))->format('d M Y · h:i A') : 'Recently';
                                    } catch (Exception $exception) {
                                        $postedAt = $timestamp ?: 'Recently';
                                    }
                                    ?>
                                    <article class="timeline__item">
                                        <header>
                                            <h3><?= htmlspecialchars($update['headline'], ENT_QUOTES, 'UTF-8') ?></h3>
                                            <span><?= htmlspecialchars($postedAt, ENT_QUOTES, 'UTF-8') ?></span>
                                        </header>
                                        <p><?= nl2br(htmlspecialchars($update['body'], ENT_QUOTES, 'UTF-8')) ?></p>
                                        <footer>
                                            <?php if (!empty($update['department'])): ?>
                                                <span><?= htmlspecialchars($update['department'], ENT_QUOTES, 'UTF-8') ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($update['author'])): ?>
                                                <span>By <?= htmlspecialchars($update['author'], ENT_QUOTES, 'UTF-8') ?></span>
                                            <?php endif; ?>
                                        </footer>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        </section>

                        <section class="panel" id="attendance-update">
                            <header class="panel__header">
                                <div>
                                    <h2>Attendance response</h2>
                                    <p>Confirm your participation details and share the summary you will brief.</p>
                                </div>
                            </header>
                            <div class="panel__body">
                                <?php if (!$repositoryConnected): ?>
                                    <div class="empty-state">
                                        <strong>Connect the database</strong>
                                        <span>Import <code>database.sql</code> to enable saving attendance responses.</span>
                                    </div>
                                <?php else: ?>
                                    <form class="update" action="auth.php" method="post">
                                        <input type="hidden" name="action" value="update">
                                        <div class="grid">
                                            <div class="field">
                                                <label for="update-code">Accreditation code</label>
                                                <input id="update-code" name="unique_code" type="text" value="<?= htmlspecialchars($userRecord['unique_code'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="e.g., RM8F2A6C4" required>
                                            </div>
                                            <div class="field">
                                                <label for="update-status">Attendance status</label>
                                                <select id="update-status" name="attendance_status" required>
                                                    <?php foreach ($attendanceLabels as $value => $label): ?>
                                                        <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>"<?= $userRecord['attendance_status'] === $value ? ' selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="field">
                                                <label for="update-location">Preferred location</label>
                                                <select id="update-location" name="location_preference"<?= empty($locations) ? ' disabled' : '' ?>>
                                                    <option value=""><?= empty($locations) ? 'Add venues in the database first' : 'To be coordinated later' ?></option>
                                                    <?php foreach ($locations as $location): ?>
                                                        <option value="<?= htmlspecialchars($location, ENT_QUOTES, 'UTF-8') ?>"<?= $userRecord['location_preference'] === $location ? ' selected' : '' ?>><?= htmlspecialchars($location, ENT_QUOTES, 'UTF-8') ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <fieldset class="field field--inline">
                                                <legend>Participation scope</legend>
                                                <label class="chip">
                                                    <input type="radio" name="department_scope" value="all"<?= $userRecord['department_scope'] !== 'specific' ? ' checked' : '' ?>>
                                                    <span>All departments</span>
                                                </label>
                                                <label class="chip">
                                                    <input type="radio" name="department_scope" value="specific"<?= $userRecord['department_scope'] === 'specific' ? ' checked' : '' ?>>
                                                    <span>Specific department</span>
                                                </label>
                                            </fieldset>
                                            <div class="field" data-scope-target>
                                                <label for="update-focus">Target department</label>
                                                <select id="update-focus" name="department_focus"<?= $userRecord['department_scope'] === 'specific' && !empty($departments) ? '' : ' disabled' ?>>
                                                    <option value=""><?= empty($departments) ? 'Add departments in the database first' : 'Select a department' ?></option>
                                                    <?php foreach ($departments as $department): ?>
                                                        <option value="<?= htmlspecialchars($department, ENT_QUOTES, 'UTF-8') ?>"<?= $userRecord['department_focus'] === $department ? ' selected' : '' ?>><?= htmlspecialchars($department, ENT_QUOTES, 'UTF-8') ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="field">
                                            <label for="update-summary">Meeting summary</label>
                                            <textarea id="update-summary" name="meeting_summary" rows="4" maxlength="600" data-counter><?= htmlspecialchars($userRecord['meeting_summary'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                                            <small class="field__hint">Share the key decisions or discussion points you plan to emphasize.</small>
                                        </div>
                                        <button type="submit" class="btn btn--primary">Save updates</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </section>

                        <section class="panel panel--form" id="post-update">
                            <header class="panel__header">
                                <div>
                                    <h2>Share a meeting update</h2>
                                    <p>Publish logistics notes or briefing changes for all attendees.</p>
                                </div>
                            </header>
                            <div class="panel__body">
                                <?php if (!$hasProfile): ?>
                                    <div class="empty-state">
                                        <strong>Sign in to share updates</strong>
                                        <span>Use the dashboard sign-in to publish coordination briefings.</span>
                                    </div>
                                <?php elseif (!$repositoryConnected): ?>
                                    <div class="empty-state">
                                        <strong>Connect the database</strong>
                                        <span>Import <code>database.sql</code> so new updates can be stored.</span>
                                    </div>
                                <?php else: ?>
                                    <form action="manage.php" method="post" class="update">
                                        <input type="hidden" name="action" value="post_update">
                                        <div class="grid">
                                            <div class="field">
                                                <label for="update-headline">Headline</label>
                                                <input id="update-headline" name="headline" type="text" placeholder="e.g., Updated arrival protocol" required>
                                            </div>
                                            <div class="field">
                                                <label for="update-department">Department</label>
                                                <select id="update-department" name="department"<?= empty($departments) ? ' disabled' : '' ?>>
                                                    <option value=""><?= empty($departments) ? 'Add departments in the database first' : 'Applies to all' ?></option>
                                                    <?php foreach ($departments as $department): ?>
                                                        <option value="<?= htmlspecialchars($department, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($department, ENT_QUOTES, 'UTF-8') ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="field">
                                                <label for="update-author">Author</label>
                                                <input id="update-author" name="author" type="text" placeholder="e.g., Meeting Secretariat">
                                            </div>
                                        </div>
                                        <div class="field">
                                            <label for="update-body">Details</label>
                                            <textarea id="update-body" name="body" rows="4" maxlength="600" data-counter placeholder="Outline the change, the reason, and the expected action." required></textarea>
                                        </div>
                                        <button type="submit" class="btn btn--primary">Publish update</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </section>
                    <?php elseif ($view === 'summary'): ?>
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
                    <?php elseif ($view === 'venues'): ?>
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
                                            <span><?= htmlspecialchars((string) $location['capacity'], ENT_QUOTES, 'UTF-8') ?> seats</span>
                                        </header>
                                        <p>Facilitated by <?= htmlspecialchars($location['facilitator'], ENT_QUOTES, 'UTF-8') ?></p>
                                        <?php if (!empty($location['notes'])): ?>
                                            <p class="location-card__notes"><?= htmlspecialchars($location['notes'], ENT_QUOTES, 'UTF-8') ?></p>
                                        <?php endif; ?>
                                        <footer>
                                            <span>Hybrid ready</span>
                                            <span>Support on standby</span>
                                        </footer>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php elseif ($view === 'attendance'): ?>
                        <section class="panel">
                            <header class="panel__header">
                                <div>
                                    <h2>Department roster</h2>
                                    <p>Live responses from every invited unit.</p>
                                </div>
                            </header>
                            <div class="table">
                                <div class="table__head">
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
                    <?php endif; ?>
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
