<?php
session_start();

require_once __DIR__ . '/data.php';

$flash = $_SESSION['flash'] ?? [];
unset($_SESSION['flash']);

$departments = meeting_departments();
$todayMeetings = meeting_today_agenda();
$attendanceBoard = meeting_attendance_board();
$attendanceLabels = meeting_attendance_labels();
$summaryHighlights = meeting_summary_highlights();
$locationsGrid = meeting_locations_grid();

$user = $_SESSION['user'] ?? [];
$hasAccount = isset($user['id']);
$uniqueCode = $user['unique_code'] ?? '';
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meeting Brief Platform · Overview</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body class="app app--landing">
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
            <a href="#overview">Overview</a>
            <a href="#agenda">Agenda</a>
            <a href="#summary">Highlights</a>
            <a href="#locations">Venues</a>
        </nav>
        <div class="app-header__cta">
            <a class="btn btn--ghost" href="register.php">Create Account</a>
            <?php if ($hasAccount): ?>
                <a class="btn btn--primary" href="dashboard.php">Open Dashboard</a>
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

        <section class="content" id="overview">
            <div class="content__hero">
                <div>
                    <span class="content__eyebrow">Tuesday, 25 July 2024 · 09:00 AM - 02:30 PM</span>
                    <h1>Unified briefing for every department</h1>
                    <p>
                        Welcome to the command center that gathers agendas, attendance statuses, and venue readiness for the institutional coordination meeting. Access the dedicated sign-in and registration pages to manage your participation seamlessly.
                    </p>
                    <div class="content__actions">
                        <a class="btn btn--primary" href="dashboard.php">View Dashboard</a>
                        <a class="btn btn--ghost" href="register.php">Create Account</a>
                    </div>
                </div>
                <div class="content__card">
                    <header>
                        <strong>Accreditation Code</strong>
                        <?php if ($uniqueCode): ?>
                            <button type="button" class="link" data-copy="<?= htmlspecialchars($uniqueCode, ENT_QUOTES, 'UTF-8') ?>">Copy Code</button>
                        <?php endif; ?>
                    </header>
                    <div class="content__code">
                        <?php if ($uniqueCode): ?>
                            <span><?= htmlspecialchars($uniqueCode, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php else: ?>
                            <span class="content__placeholder">Sign in to reveal the accreditation code assigned to you.</span>
                        <?php endif; ?>
                    </div>
                    <footer>
                        <span>The accreditation code is required when confirming attendance on the dashboard.</span>
                    </footer>
                </div>
            </div>

            <section class="cards-grid cards-grid--wide" id="agenda">
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
                        <h2>Attendance &amp; accreditations</h2>
                        <p>Registration, sign-in, and attendance updates now live on dedicated pages.</p>
                    </div>
                </header>
                <div class="panel__body panel__body--columns">
                    <div class="summary">
                        <h3>Participation checklist</h3>
                        <ul>
                            <li>Create an account to receive your personalized accreditation code.</li>
                            <li>Sign in to review department responsibilities and confirm attendance.</li>
                            <li>Visit the dashboard to submit attendance responses or summaries at any time.</li>
                        </ul>
                    </div>
                    <div class="summary summary--cta">
                        <h3>Quick access</h3>
                        <a class="btn btn--primary btn--full" href="login.php">Sign In</a>
                        <a class="btn btn--ghost btn--full" href="register.php">Create Account</a>
                        <a class="btn btn--accent btn--full" href="dashboard.php#update">Manage Attendance</a>
                    </div>
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
