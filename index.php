<?php
session_start();
require_once __DIR__ . '/config.php';

$flash = $_SESSION['flash'] ?? [];
$activeTab = $flash['context'] ?? 'login';
unset($_SESSION['flash']);

$departments = [
    'General Administration of Information Technology',
    'Cybersecurity Department',
    'Investment Agency',
    'Public Gardens and Beautification Department',
    'Internal Audit Department',
    'Operations and Emergency Department',
    'Security and Safety Department',
    'Central Unit for Plan Approvals',
    'Land Management Department',
    'Environmental Health Department',
    'Public Cleaning Department',
    'Central City Municipality',
    'North Municipality',
];

$locations = [
    'Innovation Hall - Headquarters',
    'Executive Meeting Hall - Tower A',
    'Command & Control Center - Third Floor',
    'Virtual Platform via Microsoft Teams',
];

$attendanceLabels = [
    'pending' => 'Pending Confirmation',
    'attend' => 'Attending',
    'decline' => 'Declined',
];

$userRecord = [
    'id' => $_SESSION['user']['id'] ?? null,
    'name' => $_SESSION['user']['name'] ?? null,
    'email' => $_SESSION['user']['email'] ?? null,
    'department' => $_SESSION['user']['department'] ?? null,
    'unique_code' => $_SESSION['user']['unique_code'] ?? null,
    'attendance_status' => $_SESSION['user']['attendance_status'] ?? 'pending',
    'location_preference' => $_SESSION['user']['location_preference'] ?? '',
    'department_scope' => $_SESSION['user']['department_scope'] ?? 'all',
    'department_focus' => $_SESSION['user']['department_focus'] ?? '',
    'meeting_summary' => $_SESSION['user']['meeting_summary'] ?? '',
];

if ($userRecord['id']) {
    try {
        $pdo = get_pdo();
        $stmt = $pdo->prepare('SELECT name, email, department, unique_code, attendance_status, location_preference, department_scope, department_focus, meeting_summary FROM meeting_users WHERE id = ? LIMIT 1');
        $stmt->execute([$userRecord['id']]);
        if ($row = $stmt->fetch()) {
            $userRecord = array_merge($userRecord, $row);
        }
    } catch (PDOException $exception) {
        // fallback to session values silently
    }
}

$todayMeetings = [
    [
        'title' => 'Digital Transformation Roadmap Review',
        'time' => '09:30 AM',
        'tag' => 'Strategic',
        'department' => 'General Administration of Information Technology',
        'location' => 'Innovation Hall - Headquarters',
        'summary' => 'Align the digital roadmap with current infrastructure projects and cybersecurity initiatives.',
    ],
    [
        'title' => 'Cybersecurity Preparedness Assessment',
        'time' => '11:15 AM',
        'tag' => 'Risk',
        'department' => 'Cybersecurity Department',
        'location' => 'Command & Control Center - Third Floor',
        'summary' => 'Present penetration test results and assign remediation actions with a clear timeline.',
    ],
    [
        'title' => 'Activating Joint Investment Projects',
        'time' => '01:00 PM',
        'tag' => 'Executive',
        'department' => 'Investment Agency',
        'location' => 'Executive Meeting Hall - Tower A',
        'summary' => 'Review cross-functional investment opportunities and confirm the funding schedule.',
    ],
];

$attendanceBoard = [
    [
        'department' => 'General Administration of Information Technology',
        'status' => 'attend',
        'representatives' => 12,
        'location' => 'Innovation Hall - Headquarters',
    ],
    [
        'department' => 'Cybersecurity Department',
        'status' => 'attend',
        'representatives' => 8,
        'location' => 'Command & Control Center - Third Floor',
    ],
    [
        'department' => 'Investment Agency',
        'status' => 'pending',
        'representatives' => 6,
        'location' => 'Executive Meeting Hall - Tower A',
    ],
    [
        'department' => 'Public Gardens and Beautification Department',
        'status' => 'decline',
        'representatives' => 4,
        'location' => 'Virtual Platform via Microsoft Teams',
    ],
];

$summaryHighlights = [
    'Interactive deck for the digital transformation roadmap is 92% complete.',
    'Attendance confirmed by 9 departments so far with unified logistics in place.',
    'Departments are requested to submit final remarks by Monday at 12:00 PM.',
];

$locationsGrid = array_map(static function (string $location, int $index): array {
    $capacity = [220, 30, 45, 500][$index] ?? 40;
    $facilitator = [
        'Innovation Hall - Headquarters' => 'Eng. Nasser Al-Hamzani',
        'Executive Meeting Hall - Tower A' => 'Ms. Sarah Al-Humeidhi',
        'Command & Control Center - Third Floor' => 'Eng. Ibrahim Al-Dughaither',
        'Virtual Platform via Microsoft Teams' => 'Ms. Lama Al-Assaf',
    ][$location] ?? 'Coordination Team';

    return [
        'name' => $location,
        'capacity' => $capacity,
        'facilitator' => $facilitator,
    ];
}, $locations, array_keys($locations));

$activeTab = in_array($activeTab, ['login', 'register'], true) ? $activeTab : 'login';
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meeting Brief Platform</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body class="app">
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
            <a href="#dashboard">Meeting Dashboard</a>
            <a href="#attendance">Attendance & Accreditations</a>
            <a href="#locations">Meeting Venues</a>
            <a href="#summary">Meeting Summary</a>
        </nav>
        <div class="app-header__cta">
            <a class="btn btn--primary" href="#auth-card">Confirm Attendance</a>
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
                        A unified hub connecting every department with real-time visibility into attendance, venue preferences, and executive highlights. Crafted to mirror a modern institutional experience just like the showcased example.
                    </p>
                    <div class="content__actions">
                        <a class="btn btn--primary" href="#auth-card">Manage Attendance</a>
                        <a class="btn btn--ghost" href="#summary">View Summary</a>
                    </div>
                </div>
                <div class="content__card">
                    <header>
                        <strong>Accreditation Code</strong>
                        <button type="button" class="link" data-copy="<?= htmlspecialchars($userRecord['unique_code'] ?? '', ENT_QUOTES, 'UTF-8') ?>">Copy Code</button>
                    </header>
                    <div class="content__code">
                        <?php if (!empty($userRecord['unique_code'])): ?>
                            <span><?= htmlspecialchars($userRecord['unique_code'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php else: ?>
                            <span class="content__placeholder">Create an account to receive your personalized accreditation code.</span>
                        <?php endif; ?>
                    </div>
                    <footer>
                        <span>The accreditation code is the official reference for confirming registration and updating attendance status.</span>
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

            <section class="panel" id="attendance">
                <header class="panel__header">
                    <div>
                        <h2>Attendance & Accreditations</h2>
                        <p>Manage sign-ins, create new accounts, and update attendance status using the accreditation code.</p>
                    </div>
                </header>
                <div class="panel__body">
                    <div class="auth" id="auth-card">
                        <div class="auth__tabs" role="tablist">
                            <button class="auth__tab<?= $activeTab === 'login' ? ' auth__tab--active' : '' ?>" data-target="auth-login" role="tab" aria-selected="<?= $activeTab === 'login' ? 'true' : 'false' ?>">Sign In</button>
                            <button class="auth__tab<?= $activeTab === 'register' ? ' auth__tab--active' : '' ?>" data-target="auth-register" role="tab" aria-selected="<?= $activeTab === 'register' ? 'true' : 'false' ?>">Create Account</button>
                        </div>
                        <div class="auth__panels">
                            <form id="auth-login" class="auth__panel<?= $activeTab === 'login' ? ' auth__panel--active' : '' ?>" action="auth.php" method="post">
                                <input type="hidden" name="action" value="login">
                                <div class="field">
                                    <label for="login-email">Email address</label>
                                    <input id="login-email" name="email" type="email" autocomplete="email" required>
                                </div>
                                <div class="field">
                                    <label for="login-password">Password</label>
                                    <input id="login-password" name="password" type="password" autocomplete="current-password" required>
                                </div>
                                <button type="submit" class="btn btn--primary btn--full">Sign In</button>
                            </form>

                            <form id="auth-register" class="auth__panel<?= $activeTab === 'register' ? ' auth__panel--active' : '' ?>" action="auth.php" method="post" novalidate>
                                <input type="hidden" name="action" value="register">
                                <div class="field">
                                    <label for="register-name">Full name</label>
                                    <input id="register-name" name="name" type="text" autocomplete="name" required>
                                </div>
                                <div class="field">
                                    <label for="register-department">Department</label>
                                    <select id="register-department" name="department" required>
                                        <option value="" disabled selected>Select a department</option>
                                        <?php foreach ($departments as $department): ?>
                                            <option value="<?= htmlspecialchars($department, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($department, ENT_QUOTES, 'UTF-8') ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="field">
                                    <label for="register-email">Corporate email</label>
                                    <input id="register-email" name="email" type="email" autocomplete="email" required>
                                </div>
                                <div class="field">
                                    <label for="register-password">Password</label>
                                    <input id="register-password" name="password" type="password" autocomplete="new-password" minlength="8" required>
                                </div>
                                <div class="field">
                                    <label for="register-confirm">Confirm password</label>
                                    <input id="register-confirm" name="confirm" type="password" autocomplete="new-password" minlength="8" required>
                                </div>
                                <button type="submit" class="btn btn--primary btn--full">Create account &amp; issue code</button>
                            </form>
                        </div>
                    </div>

                    <form class="update" action="auth.php" method="post">
                        <input type="hidden" name="action" value="update">
                        <div class="update__header">
                            <h3>Update attendance status</h3>
                            <p>Use your approved accreditation code to set attendance, preferred venue, and participation scope.</p>
                        </div>
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
                                    <dd><?= htmlspecialchars($attendanceLabels[$row['status']], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($row['representatives'], ENT_QUOTES, 'UTF-8') ?> representatives · <?= htmlspecialchars($row['location'], ENT_QUOTES, 'UTF-8') ?></dd>
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
                        <h2>Attendance & regrets roster</h2>
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
