<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

const MEETING_REPOSITORY_CONTEXT_LABELS = [
    'departments' => 'department list',
    'venues' => 'meeting venues',
    'agenda' => 'agenda items',
    'attendance' => 'attendance roster',
    'highlights' => 'executive highlights',
    'updates' => 'briefing updates',
];

const MEETING_FALLBACK_DEPARTMENTS = [
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

const MEETING_FALLBACK_LOCATIONS_GRID = [
    [
        'name' => 'Innovation Hall - Headquarters',
        'capacity' => 220,
        'facilitator' => 'Eng. Nasser Al-Hamzani',
        'notes' => 'Primary plenary space with hybrid meeting capability.',
    ],
    [
        'name' => 'Executive Meeting Hall - Tower A',
        'capacity' => 30,
        'facilitator' => 'Ms. Sarah Al-Humeidhi',
        'notes' => 'Reserved for executive briefings and strategy alignment.',
    ],
    [
        'name' => 'Command & Control Center - Third Floor',
        'capacity' => 45,
        'facilitator' => 'Eng. Ibrahim Al-Dughaither',
        'notes' => 'Equipped with live monitoring dashboards for operations.',
    ],
    [
        'name' => 'Virtual Platform via Microsoft Teams',
        'capacity' => 500,
        'facilitator' => 'Ms. Lama Al-Assaf',
        'notes' => 'Digital access with simultaneous translation available.',
    ],
];

const MEETING_FALLBACK_AGENDA = [
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

const MEETING_FALLBACK_ATTENDANCE = [
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

const MEETING_FALLBACK_HIGHLIGHTS = [
    'Interactive deck for the digital transformation roadmap is 92% complete.',
    'Attendance confirmed by 9 departments so far with unified logistics in place.',
    'Departments are requested to submit final remarks by Monday at 12:00 PM.',
];

const MEETING_FALLBACK_UPDATES = [
    [
        'headline' => 'Logistics confirmation',
        'body' => 'Transportation shuttles will run every 20 minutes between the central garage and the headquarters entrance.',
        'department' => 'Operations and Emergency Department',
        'author' => 'Logistics Desk',
        'created_at' => '2024-07-21 10:00:00',
    ],
    [
        'headline' => 'Presentation deadline',
        'body' => 'Submit final presentation decks by Monday at noon to be included in the consolidated briefing.',
        'department' => 'General Administration of Information Technology',
        'author' => 'Meeting Secretariat',
        'created_at' => '2024-07-20 09:30:00',
    ],
];

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
    return MEETING_FALLBACK_DEPARTMENTS;
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
    return array_map(static fn (array $venue) => $venue['name'], MEETING_FALLBACK_LOCATIONS_GRID);
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
    return MEETING_FALLBACK_AGENDA;
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
    return MEETING_FALLBACK_ATTENDANCE;
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
    return MEETING_FALLBACK_HIGHLIGHTS;
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
    return MEETING_FALLBACK_LOCATIONS_GRID;
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
    return MEETING_FALLBACK_UPDATES;
}
