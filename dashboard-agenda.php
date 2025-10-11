<?php
$activeView = 'agenda';
$viewTitle = 'Agenda focus';
$viewLede = 'Review the sessions locked for the coordination meeting and add new topics when needed.';
$viewActions = [
    ['href' => '#add-meeting', 'label' => 'Add meeting', 'class' => 'btn btn--primary'],
    ['href' => 'dashboard-updates.php#post-update', 'label' => 'Post update', 'class' => 'btn btn--ghost'],
];

$renderMain = function (array $context) {
    extract($context);
    ?>
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
    <?php
};

require __DIR__ . '/dashboard-template.php';
