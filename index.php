<?php
session_start();

require_once __DIR__ . '/repository.php';

$flash = $_SESSION['flash'] ?? [];
unset($_SESSION['flash']);

$departments = meeting_departments();
$todayMeetings = meeting_today_agenda();
$attendanceBoard = meeting_attendance_board();
$attendanceLabels = meeting_attendance_labels();
$summaryHighlights = meeting_summary_highlights();
$locationsGrid = meeting_locations_grid();
$updatesFeed = meeting_updates_feed();

$user = $_SESSION['user'] ?? [];
$hasAccount = isset($user['id']);
$uniqueCode = $user['unique_code'] ?? '';
$repositoryErrors = meeting_repository_errors();
$repositoryConnected = meeting_repository_connected();
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meeting Brief Platform · Overview</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body class="app app--landing">
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

    <main class="workspace">
        <aside class="workspace__sidebar" aria-label="Primary navigation">
            <div class="sidebar__logo">
                <span class="sidebar__icon">🗂️</span>
                <div>
                    <strong>Meeting Portal</strong>
                    <span>Department Coordination</span>
                </div>
            </div>
            <nav class="sidebar__section">
                <span class="sidebar__title">Departments</span>
                <ul class="sidebar__list">
                    <li><a class="is-active" href="#">All Departments</a></li>
                    <?php foreach ($departments as $department): ?>
                        <li><a href="#overview-<?= md5($department) ?>"><?= htmlspecialchars($department, ENT_QUOTES, 'UTF-8') ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </nav>
            <div class="sidebar__section">
                <span class="sidebar__title">Shortcuts</span>
                <ul class="sidebar__list sidebar__list--actions">
                    <li><a href="dashboard.php">Attendance Dashboard</a></li>
                    <li><a href="#venues">Venue Directory</a></li>
                    <li><a href="#summary">Executive Summary</a></li>
                </ul>
            </div>
            <footer class="sidebar__footer">
                <?php if ($hasAccount): ?>
                    <a class="btn btn--secondary btn--full" href="dashboard.php">Go to Dashboard</a>
                    <form class="sidebar__logout" action="auth.php" method="post">
                        <input type="hidden" name="action" value="logout">
                        <button type="submit" class="btn btn--ghost btn--full">Sign Out</button>
                    </form>
                <?php else: ?>
                    <a class="btn btn--secondary btn--full" href="register.php">Create Account</a>
                    <a class="btn btn--ghost btn--full" href="login.php">Sign In</a>
                <?php endif; ?>
            </footer>
        </aside>

        <div class="workspace__content" id="overview">
            <?php if (!$repositoryConnected && !empty($repositoryErrors)): ?>
                <div class="alert alert--error">
                    <strong>Database connection required</strong>
                    <ul>
                        <?php foreach ($repositoryErrors as $error): ?>
                            <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="alert__hint">Import <code>database.sql</code> and update <code>config.php</code> with your MySQL credentials, then refresh the page.</p>
                </div>
            <?php endif; ?>
            <header class="workspace__header">
                <div>
                    <p class="eyebrow">Tuesday, 25 July 2024 · 09:00 AM – 02:30 PM</p>
                    <h1>Unified Meeting Overview</h1>
                    <p class="workspace__lede">
                        Review the agenda, track departmental confirmations, and explore approved venues before joining the coordination meeting.
                    </p>
                </div>
                <div class="workspace__actions">
                    <a class="btn btn--primary" href="dashboard.php#update">Schedule Meeting</a>
                    <?php if ($hasAccount): ?>
                        <a class="btn btn--ghost" href="dashboard.php">Open Dashboard</a>
                    <?php else: ?>
                        <a class="btn btn--ghost" href="register.php">Create Account</a>
                    <?php endif; ?>
                </div>
            </header>

            <div class="workspace__grid">
                <section class="workspace__main" aria-labelledby="agenda-heading">
                    <div class="chip-group" role="tablist" aria-label="Agenda filters">
                        <button type="button" class="chip is-active">All meetings</button>
                        <button type="button" class="chip">Strategic</button>
                        <button type="button" class="chip">Operations</button>
                        <button type="button" class="chip">Workshops</button>
                    </div>

                    <div class="meeting-feed">
                        <h2 id="agenda-heading" class="section-title">Today’s sessions</h2>
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

                    <section class="panel panel--wide" id="summary">
                        <header class="panel__header">
                            <div>
                                <h2>Executive highlights</h2>
                                <p>Snapshot of decisions and preparations reported ahead of the meeting.</p>
                            </div>
                        </header>
                        <div class="panel__body panel__body--split">
                            <div class="summary-list">
                                <h3>Brief highlights</h3>
                                <ul>
                                    <?php foreach ($summaryHighlights as $highlight): ?>
                                        <li><?= htmlspecialchars($highlight, ENT_QUOTES, 'UTF-8') ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <div class="summary-board">
                                <h3>Attendance board</h3>
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

                    <section class="panel panel--wide" id="venues">
                        <header class="panel__header">
                            <div>
                                <h2>Approved venues</h2>
                                <p>Explore the available rooms and remote options configured for the coordination meeting.</p>
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
                </section>

                <aside class="workspace__rail" aria-labelledby="insights-heading">
                    <section class="card" id="accreditation">
                        <header>
                            <div>
                                <h2>Accreditation code</h2>
                                <p>Required when confirming attendance</p>
                            </div>
                            <?php if ($uniqueCode): ?>
                                <button type="button" class="link" data-copy="<?= htmlspecialchars($uniqueCode, ENT_QUOTES, 'UTF-8') ?>">Copy</button>
                            <?php endif; ?>
                        </header>
                        <div class="card__code">
                            <?php if ($uniqueCode): ?>
                                <span><?= htmlspecialchars($uniqueCode, ENT_QUOTES, 'UTF-8') ?></span>
                            <?php else: ?>
                                <span class="placeholder">Sign in to reveal your personal accreditation code.</span>
                            <?php endif; ?>
                        </div>
                    </section>

                    <section class="card" aria-labelledby="insights-heading">
                        <header>
                            <div>
                                <h2 id="insights-heading">Quick stats</h2>
                                <p>Live participation pulse</p>
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
                                <span>Briefing updates</span>
                            </li>
                        </ul>
                    </section>

                    <section class="card">
                        <header>
                            <div>
                                <h2>Need access?</h2>
                                <p>Register to manage your attendance.</p>
                            </div>
                        </header>
                        <div class="card__actions">
                            <a class="btn btn--primary btn--full" href="register.php">Create account</a>
                            <a class="btn btn--ghost btn--full" href="login.php">Sign in</a>
                        </div>
                    </section>
                </aside>
            </div>
        </div>
    </main>

    <script src="script.js" defer></script>
</body>
</html>
