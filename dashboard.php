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
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body class="app app--dashboard">
    <div class="app__backdrop" aria-hidden="true"></div>
    <header class="app-header">
        <div class="app-header__brand">
            <span class="app-header__icon">🗂️</span>
            <div>
                <strong>Institutional Coordination Hub</strong>
                <span>Comprehensive Meeting Brief 2024</span>
            </div>
        </div>
        <nav class="app-header__nav">
            <a href="#dashboard">Dashboard</a>
            <a href="#update">Attendance</a>
            <a href="#summary">Summary</a>
            <a href="#locations">Venues</a>
        </nav>
        <div class="app-header__cta">
            <a class="btn btn--ghost" href="index.php">Overview</a>
            <?php if ($hasProfile): ?>
                <a class="btn btn--primary" href="#update">Update Attendance</a>
            <?php else: ?>
                <a class="btn btn--primary" href="login.php">Sign In</a>
            <?php endif; ?>
        </div>
    </header>

    <?php if (!empty($flash)): ?>
        <aside class="toast toast--<?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8') ?>" role="alert">
            <div class="toast__header">
                <strong><?= htmlspecialchars($flash['title'], ENT_QUOTES, 'UTF-8') ?></strong>
                <button type="button" class="toast__close" data-dismiss-toast aria-label="Close">×</button>
            </div>
            <p><?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?></p>
        </aside>
    <?php endif; ?>

    <main class="layout">
        <aside class="sidebar">
            <section class="sidebar__panel">
                <header>
                    <h2>Participating Departments</h2>
                    <span><?= count($departments) ?> departments</span>
                </header>
                <ul class="department-list">
                    <?php foreach ($departments as $department): ?>
                        <li>
                            <span class="department-list__badge"></span>
                            <span><?= htmlspecialchars($department, ENT_QUOTES, 'UTF-8') ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>

            <section class="sidebar__panel">
                <header>
                    <h2>Quick Indicators</h2>
                </header>
                <div class="stats">
                    <article class="stat">
                        <strong>13</strong>
                        <span>Invited departments</span>
                    </article>
                    <article class="stat">
                        <strong>09</strong>
                        <span>Key sessions</span>
                    </article>
                    <article class="stat">
                        <strong>04</strong>
                        <span>Specialized workshops</span>
                    </article>
                </div>
            </section>

            <section class="sidebar__panel">
                <header>
                    <h2>Today's Sessions</h2>
                    <span>Riyadh time</span>
                </header>
                <ul class="agenda-mini">
                    <?php foreach ($todayMeetings as $slot): ?>
                        <li>
                            <div>
                                <strong><?= htmlspecialchars($slot['time'], ENT_QUOTES, 'UTF-8') ?></strong>
                                <span><?= htmlspecialchars($slot['title'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <span class="agenda-mini__tag"><?= htmlspecialchars($slot['tag'], ENT_QUOTES, 'UTF-8') ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        </aside>

        <section class="content" id="dashboard">
            <div class="content__hero">
                <div>
                    <span class="content__eyebrow">Tuesday, 25 July 2024 · 09:00 AM - 02:30 PM</span>
                    <h1>Meeting Brief Command Center</h1>
                    <p>
                        Track every department's engagement, confirm attendance with your accreditation code, and capture executive talking points before the meeting convenes.
                    </p>
                    <div class="content__actions">
                        <a class="btn btn--primary" href="#update">Manage Attendance</a>
                        <a class="btn btn--ghost" href="#summary">View Summary</a>
                    </div>
                </div>
                <div class="content__card">
                    <header>
                        <strong>Accreditation Code</strong>
                        <?php if (!empty($userRecord['unique_code'])): ?>
                            <button type="button" class="link" data-copy="<?= htmlspecialchars($userRecord['unique_code'], ENT_QUOTES, 'UTF-8') ?>">Copy Code</button>
                        <?php endif; ?>
                    </header>
                    <div class="content__code">
                        <?php if (!empty($userRecord['unique_code'])): ?>
                            <span><?= htmlspecialchars($userRecord['unique_code'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php else: ?>
                            <span class="content__placeholder">Sign in or register to receive your personalized accreditation code.</span>
                        <?php endif; ?>
                    </div>
                    <footer>
                        <?php if ($hasProfile && !empty($userRecord['name'])): ?>
                            <span>Welcome back, <?= htmlspecialchars($userRecord['name'], ENT_QUOTES, 'UTF-8') ?>.</span>
                        <?php else: ?>
                            <span>The accreditation code is the official reference for confirming registration and updating attendance status.</span>
                        <?php endif; ?>
                    </footer>
                </div>
            </div>

            <section class="cards-grid cards-grid--wide">
                <?php foreach ($todayMeetings as $slot): ?>
                    <article class="meeting-card">
                        <header>
                            <span class="meeting-card__time"><?= htmlspecialchars($slot['time'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="meeting-card__tag"><?= htmlspecialchars($slot['tag'], ENT_QUOTES, 'UTF-8') ?></span>
                        </header>
                        <h3><?= htmlspecialchars($slot['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <p><?= htmlspecialchars($slot['summary'], ENT_QUOTES, 'UTF-8') ?></p>
                        <footer>
                            <span>Lead department: <?= htmlspecialchars($slot['department'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span>Location: <?= htmlspecialchars($slot['location'], ENT_QUOTES, 'UTF-8') ?></span>
                        </footer>
                    </article>
                <?php endforeach; ?>
            </section>

            <section class="panel" id="update">
                <header class="panel__header">
                    <div>
                        <h2>Update attendance status</h2>
                        <p>Use your accreditation code to set attendance, preferred venue, and participation scope.</p>
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
                        <button type="submit" class="btn btn--accent">Save updates</button>
                    </form>
                </div>
            </section>

            <section class="panel" id="summary">
                <header class="panel__header">
                    <div>
                        <h2>Rapid executive summary</h2>
                        <p>Key insights and preliminary decisions compiled so far.</p>
                    </div>
                </header>
                <div class="panel__body panel__body--columns">
                    <div class="summary">
                        <h3>Today's highlights</h3>
                        <ul>
                            <?php foreach ($summaryHighlights as $item): ?>
                                <li><?= htmlspecialchars($item, ENT_QUOTES, 'UTF-8') ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="summary summary--outline">
                        <h3>Latest departmental updates</h3>
                        <dl>
                            <?php foreach ($attendanceBoard as $row): ?>
                                <div>
                                    <dt><?= htmlspecialchars($row['department'], ENT_QUOTES, 'UTF-8') ?></dt>
                                    <dd><?= htmlspecialchars($attendanceLabels[$row['status']], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars((string) $row['representatives'], ENT_QUOTES, 'UTF-8') ?> representatives · <?= htmlspecialchars($row['location'], ENT_QUOTES, 'UTF-8') ?></dd>
                                </div>
                            <?php endforeach; ?>
                        </dl>
                    </div>
                </div>
            </section>

            <section class="panel" id="locations">
                <header class="panel__header">
                    <div>
                        <h2>Meeting venues</h2>
                        <p>Approved options for on-site attendance or virtual participation.</p>
                    </div>
                </header>
                <div class="locations">
                    <?php foreach ($locationsGrid as $location): ?>
                        <article class="location-card">
                            <header>
                                <h3><?= htmlspecialchars($location['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                                <span>Capacity <?= htmlspecialchars((string) $location['capacity'], ENT_QUOTES, 'UTF-8') ?> seats</span>
                            </header>
                            <p>Session facilitator: <?= htmlspecialchars($location['facilitator'], ENT_QUOTES, 'UTF-8') ?></p>
                            <footer>
                                <span>Interactive presentation ready</span>
                                <span>Live streaming available</span>
                            </footer>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="panel">
                <header class="panel__header">
                    <div>
                        <h2>Attendance &amp; regrets roster</h2>
                        <p>Live overview of departmental responses to the official invitation.</p>
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
    </main>

    <script src="script.js" defer></script>
</body>
</html>
