<?php
session_start();

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/repository.php';

function manage_redirect(string $type, string $title, string $message, string $target): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'title' => $title,
        'message' => $message,
    ];

    header('Location: ' . $target);
    exit;
}

$action = $_POST['action'] ?? '';
if (!in_array($action, ['create_meeting', 'post_update'], true)) {
    manage_redirect('error', 'Unknown request', 'Please submit updates from the dashboard forms provided.', 'dashboard.php');
}

if (empty($_SESSION['user'])) {
    manage_redirect('error', 'Sign in required', 'Please sign in to manage meeting content.', 'login.php');
}

try {
    $pdo = get_pdo();
} catch (PDOException $exception) {
    manage_redirect('error', 'Database unavailable', 'Unable to reach the database. Please try again later.', 'dashboard.php');
}

$departments = meeting_departments();
$locations = meeting_locations();

if ($action === 'create_meeting') {
    $title = trim($_POST['title'] ?? '');
    $date = trim($_POST['scheduled_date'] ?? '');
    $time = trim($_POST['scheduled_time'] ?? '');
    $tag = trim($_POST['tag'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $summary = trim($_POST['summary'] ?? '');

    if ($title === '' || $date === '' || $time === '' || $tag === '' || $department === '' || $location === '') {
        manage_redirect('error', 'Missing information', 'Fill in all mandatory fields before saving the meeting.', 'dashboard.php?view=agenda#add-meeting');
    }

    if (!in_array($department, $departments, true)) {
        manage_redirect('error', 'Department not recognised', 'Select a department from the official list.', 'dashboard.php?view=agenda#add-meeting');
    }

    if (!in_array($location, $locations, true)) {
        manage_redirect('error', 'Location not recognised', 'Choose an approved meeting venue.', 'dashboard.php?view=agenda#add-meeting');
    }

    try {
        $scheduledAt = new DateTimeImmutable(sprintf('%s %s', $date, $time));
    } catch (Exception $exception) {
        manage_redirect('error', 'Invalid schedule', 'Provide a valid date and time for the session.', 'dashboard.php?view=agenda#add-meeting');
    }

    $stmt = $pdo->prepare('INSERT INTO meeting_agenda (title, scheduled_at, tag, department, location, summary) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $title,
        $scheduledAt->format('Y-m-d H:i:s'),
        $tag,
        $department,
        $location,
        $summary === '' ? null : $summary,
    ]);

    manage_redirect('success', 'Meeting added', 'The new agenda item has been published to the dashboard.', 'dashboard.php?view=agenda');
}

if ($action === 'post_update') {
    $headline = trim($_POST['headline'] ?? '');
    $body = trim($_POST['body'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $author = trim($_POST['author'] ?? '');

    if ($headline === '' || $body === '') {
        manage_redirect('error', 'Missing details', 'Provide both a headline and the update details.', 'dashboard.php?view=updates#post-update');
    }

    if ($department !== '' && !in_array($department, $departments, true)) {
        manage_redirect('error', 'Unknown department', 'Select the department the update applies to.', 'dashboard.php?view=updates#post-update');
    }

    $stmt = $pdo->prepare('INSERT INTO meeting_updates (headline, body, department, author) VALUES (?, ?, ?, ?)');
    $stmt->execute([
        $headline,
        $body,
        $department === '' ? null : $department,
        $author === '' ? null : $author,
    ]);

    manage_redirect('success', 'Update shared', 'Your meeting update is now visible to the coordination team.', 'dashboard.php?view=updates');
}

manage_redirect('error', 'Unhandled request', 'Please retry your action.', 'dashboard.php');
