<?php
session_start();

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/data.php';

$flash = $_SESSION['flash'] ?? [];
unset($_SESSION['flash']);

$departments = meeting_departments();
$locations = meeting_locations();
$attendanceLabels = meeting_attendance_labels();
$todayMeetings = meeting_today_agenda();
$attendanceBoard = meeting_attendance_board();
$summaryHighlights = meeting_summary_highlights();
$locationsGrid = meeting_locations_grid();

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
                    <li><a class="is-active" href="#dashboard">Dashboard</a></li>
                    <li><a href="#agenda">Agenda</a></li>
                    <li><a href="#update">Attendance</a></li>
                    <li><a href="#summary">Highlights</a></li>
                    <li><a href="#locations">Venues</a></li>
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

        <div class="workspace__content" id="dashboard">
            <header class="workspace__header">
                <div>
                    <p class="eyebrow">Tuesday, 25 July 2024 · 09:00 AM – 02:30 PM</p>
                    <h1>Meeting Control Center</h1>
                    <p class="workspace__lede">
                        Coordinate attendance, track departmental readiness, and capture decisions for the unified meeting briefing.
                    </p>
                </div>
                <div class="workspace__actions">
                    <a class="btn btn--primary" href="#update">Update Attendance</a>
                    <a class="btn btn--ghost" href="register.php">Invite a delegate</a>
                </div>
            </header>

            <div class="workspace__grid">
                <section class="workspace__main" aria-labelledby="agenda-heading">
                    <div class="chip-group" role="tablist" aria-label="Agenda filters">
                        <button type="button" class="chip is-active">All meetings</button>
                        <button type="button" class="chip">Confirmed</button>
                        <button type="button" class="chip">Pending</button>
                        <button type="button" class="chip">Workshops</button>
                    </div>

                    <div class="meeting-feed" id="agenda">
                        <h2 id="agenda-heading" class="section-title">Agenda focus</h2>
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

                    <section class="panel" id="update">
                        <header class="panel__header">
                            <div>
                                <h2>Attendance &amp; briefing updates</h2>
                                <p>Confirm your participation details and share the talking points you intend to cover.</p>
                            </div>
                        </header>
                        <div class="panel__body">
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
                                        <select id="update-location" name="location_preference">
                                            <option value="">To be coordinated later</option>
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
                                        <select id="update-focus" name="department_focus"<?= $userRecord['department_scope'] === 'specific' ? '' : ' disabled' ?>>
                                            <option value="">Select a department</option>
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
                        </div>
                    </section>

                    <section class="panel" id="summary">
                        <header class="panel__header">
                            <div>
                                <h2>Executive summary</h2>
                                <p>Key insights and departmental signals collected so far.</p>
                            </div>
                        </header>
                        <div class="panel__body panel__body--split">
                            <div class="summary-list">
                                <h3>Highlights</h3>
                                <ul>
                                    <?php foreach ($summaryHighlights as $highlight): ?>
                                        <li><?= htmlspecialchars($highlight, ENT_QUOTES, 'UTF-8') ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <div class="summary-board">
                                <h3>Latest responses</h3>
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

                    <section class="panel" id="locations">
                        <header class="panel__header">
                            <div>
                                <h2>Meeting venues</h2>
                                <p>Approved rooms and digital channels for the unified session.</p>
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
                                    <footer>
                                        <span>Hybrid ready</span>
                                        <span>Support on standby</span>
                                    </footer>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <section class="panel">
                        <header class="panel__header">
                            <div>
                                <h2>Attendance roster</h2>
                                <p>Live responses from every invited department.</p>
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
                                <strong>13</strong>
                                <span>Departments invited</span>
                            </li>
                            <li>
                                <strong>09</strong>
                                <span>Sessions today</span>
                            </li>
                            <li>
                                <strong>04</strong>
                                <span>Workshops planned</span>
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
                            <?php foreach ($summaryHighlights as $highlight): ?>
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
