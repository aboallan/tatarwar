<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/seeds.php';

const MEETING_REPOSITORY_CONTEXT_LABELS = [
    'departments' => 'department list',
    'venues' => 'meeting venues',
    'agenda' => 'agenda items',
    'attendance' => 'attendance roster',
    'highlights' => 'executive highlights',
    'updates' => 'briefing updates',
];

function meeting_seeded_agenda_rows(): array
{
    return array_map(static function (array $item): array {
        $date = new DateTimeImmutable($item['scheduled_at']);

        return [
            'title' => $item['title'],
            'time' => $date->format('h:i A'),
            'tag' => $item['tag'],
            'department' => $item['department'],
            'location' => $item['location'],
            'summary' => $item['summary'],
        ];
    }, MEETING_SEED_AGENDA);
}

if (!isset($GLOBALS['MEETING_REPOSITORY_STATE'])) {
    $GLOBALS['MEETING_REPOSITORY_STATE'] = [
        'connected' => true,
        'errors' => [],
    ];
}

/**
 * Execute a callback and return a fallback value if the database is unavailable.
 *
 * @template TValue
 * @param callable():TValue $callback
 * @param TValue $fallback
 * @param string $context
 * @return TValue
 */
function meeting_try(callable $callback, $fallback, string $context)
{
    try {
        return $callback();
    } catch (\Throwable $throwable) {
        meeting_repository_record_error($context, $throwable);
        return $fallback;
    }
}

function meeting_repository_state(): array
{
    return $GLOBALS['MEETING_REPOSITORY_STATE'] ?? ['connected' => true, 'errors' => []];
}

function meeting_repository_connected(): bool
{
    $state = meeting_repository_state();
    return $state['connected'];
}

function meeting_repository_errors(): array
{
    $state = meeting_repository_state();
    return array_values($state['errors']);
}

function meeting_repository_record_error(string $context, \Throwable $throwable): void
{
    $state = meeting_repository_state();
    $state['connected'] = false;

    $label = MEETING_REPOSITORY_CONTEXT_LABELS[$context] ?? $context;
    $state['errors'][$context] = sprintf(
        'Unable to load the %s from the database. Confirm your connection settings in config.php and seed data from database.sql.',
        $label
    );

    $GLOBALS['MEETING_REPOSITORY_STATE'] = $state;

    error_log(sprintf('[Meeting Portal] %s error: %s', ucfirst($context), $throwable->getMessage()));
}

function meeting_departments(): array
{
    return meeting_try(function () {
        $pdo = get_pdo();
        $stmt = $pdo->query('SELECT name FROM meeting_departments ORDER BY name');
        $rows = $stmt->fetchAll();

        return array_map(static fn ($row) => $row['name'], $rows);
    }, meeting_fallback_departments(), 'departments');
}

function meeting_fallback_departments(): array
{
    return MEETING_SEED_DEPARTMENTS;
}

function meeting_locations(): array
{
    return meeting_try(function () {
        $pdo = get_pdo();
        $stmt = $pdo->query('SELECT name FROM meeting_venues ORDER BY name');
        $rows = $stmt->fetchAll();

        return array_map(static fn ($row) => $row['name'], $rows);
    }, meeting_fallback_locations(), 'venues');
}

function meeting_fallback_locations(): array
{
    return array_map(static fn (array $venue) => $venue['name'], MEETING_SEED_VENUES);
}

function meeting_attendance_labels(): array
{
    return [
        'pending' => 'Pending Confirmation',
        'attend' => 'Attending',
        'decline' => 'Declined',
    ];
}

function meeting_today_agenda(): array
{
    return meeting_try(function () {
        $pdo = get_pdo();
        $stmt = $pdo->query('SELECT title, scheduled_at, tag, department, location, summary FROM meeting_agenda ORDER BY scheduled_at');
        $rows = $stmt->fetchAll();

        return array_map(static function ($row) {
            $time = (new DateTimeImmutable($row['scheduled_at']))->format('h:i A');

            return [
                'title' => $row['title'],
                'time' => $time,
                'tag' => $row['tag'],
                'department' => $row['department'],
                'location' => $row['location'],
                'summary' => $row['summary'] ?? '',
            ];
        }, $rows);
    }, meeting_fallback_agenda(), 'agenda');
}

function meeting_fallback_agenda(): array
{
    return meeting_seeded_agenda_rows();
}

function meeting_attendance_board(): array
{
    return meeting_try(function () {
        $pdo = get_pdo();
        $stmt = $pdo->query('SELECT department, status, representatives, location FROM meeting_attendance_roster ORDER BY department');
        $rows = $stmt->fetchAll();

        return array_map(static fn ($row) => [
            'department' => $row['department'],
            'status' => $row['status'],
            'representatives' => (int) $row['representatives'],
            'location' => $row['location'] ?? '',
        ], $rows);
    }, meeting_fallback_attendance(), 'attendance');
}

function meeting_fallback_attendance(): array
{
    return MEETING_SEED_ATTENDANCE;
}

function meeting_summary_highlights(): array
{
    return meeting_try(function () {
        $pdo = get_pdo();
        $stmt = $pdo->query('SELECT highlight FROM meeting_highlights ORDER BY created_at DESC');
        $rows = $stmt->fetchAll();

        return array_map(static fn ($row) => $row['highlight'], $rows);
    }, meeting_fallback_highlights(), 'highlights');
}

function meeting_fallback_highlights(): array
{
    return MEETING_SEED_HIGHLIGHTS;
}

function meeting_locations_grid(): array
{
    return meeting_try(function () {
        $pdo = get_pdo();
        $stmt = $pdo->query('SELECT name, capacity, facilitator, notes FROM meeting_venues ORDER BY name');
        $rows = $stmt->fetchAll();

        return array_map(static fn ($row) => [
            'name' => $row['name'],
            'capacity' => (int) $row['capacity'],
            'facilitator' => $row['facilitator'] ?? 'Coordination Team',
            'notes' => $row['notes'] ?? '',
        ], $rows);
    }, meeting_fallback_locations_grid(), 'venues');
}

function meeting_fallback_locations_grid(): array
{
    return MEETING_SEED_VENUES;
}

function meeting_updates_feed(): array
{
    return meeting_try(function () {
        $pdo = get_pdo();
        $stmt = $pdo->query('SELECT headline, body, department, author, created_at FROM meeting_updates ORDER BY created_at DESC');
        $rows = $stmt->fetchAll();

        return array_map(static fn ($row) => [
            'headline' => $row['headline'],
            'body' => $row['body'],
            'department' => $row['department'] ?? '',
            'author' => $row['author'] ?? '',
            'created_at' => $row['created_at'],
        ], $rows);
    }, meeting_fallback_updates(), 'updates');
}

function meeting_fallback_updates(): array
{
    return array_map(static function (array $update): array {
        return [
            'headline' => $update['headline'],
            'body' => $update['body'],
            'department' => $update['department'],
            'author' => $update['author'],
            'created_at' => $update['created_at'],
        ];
    }, MEETING_SEED_UPDATES);
}
