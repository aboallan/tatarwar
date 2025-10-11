<?php
$activeView = 'updates';
$viewTitle = 'Attendance & briefing updates';
$viewLede = 'Confirm participation details, log department notes, and keep everyone in sync.';
$viewActions = [
    ['href' => '#attendance-update', 'label' => 'Update attendance', 'class' => 'btn btn--primary'],
    ['href' => '#post-update', 'label' => 'Share briefing note', 'class' => 'btn btn--ghost'],
];

$renderMain = function (array $context) {
    extract($context);
    ?>
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
                        <small class="field__hint">Share the key decisions or discussion points you plan to emphasise.</small>
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
    <?php
};

require __DIR__ . '/dashboard-template.php';
