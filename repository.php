<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

/**
 * Execute a callback and return a fallback value if the database is unavailable.
 *
 * @template TValue
 * @param callable():TValue $callback
 * @param TValue $fallback
 * @return TValue
 */
function meeting_try(callable $callback, $fallback)
{
    try {
        return $callback();
    } catch (\Throwable $throwable) {
        return $fallback;
    }
}

function meeting_departments(): array
{
    return meeting_try(function () {
        $pdo = get_pdo();
        $stmt = $pdo->query('SELECT name FROM meeting_departments ORDER BY name');
        $rows = $stmt->fetchAll();

        return array_map(static fn ($row) => $row['name'], $rows);
    }, []);
}

function meeting_locations(): array
{
    return meeting_try(function () {
        $pdo = get_pdo();
        $stmt = $pdo->query('SELECT name FROM meeting_venues ORDER BY name');
        $rows = $stmt->fetchAll();

        return array_map(static fn ($row) => $row['name'], $rows);
    }, []);
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
    }, []);
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
    }, []);
}

function meeting_summary_highlights(): array
{
    return meeting_try(function () {
        $pdo = get_pdo();
        $stmt = $pdo->query('SELECT highlight FROM meeting_highlights ORDER BY created_at DESC');
        $rows = $stmt->fetchAll();

        return array_map(static fn ($row) => $row['highlight'], $rows);
    }, []);
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
    }, []);
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
    }, []);
}
