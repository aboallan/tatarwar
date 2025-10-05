<?php

declare(strict_types=1);

function meeting_departments(): array
{
    return [
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
}

function meeting_locations(): array
{
    return [
        'Innovation Hall - Headquarters',
        'Executive Meeting Hall - Tower A',
        'Command & Control Center - Third Floor',
        'Virtual Platform via Microsoft Teams',
    ];
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
    return [
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
}

function meeting_attendance_board(): array
{
    return [
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
}

function meeting_summary_highlights(): array
{
    return [
        'Interactive deck for the digital transformation roadmap is 92% complete.',
        'Attendance confirmed by 9 departments so far with unified logistics in place.',
        'Departments are requested to submit final remarks by Monday at 12:00 PM.',
    ];
}

function meeting_locations_grid(): array
{
    $locations = meeting_locations();
    $capacities = [220, 30, 45, 500];
    $facilitators = [
        'Innovation Hall - Headquarters' => 'Eng. Nasser Al-Hamzani',
        'Executive Meeting Hall - Tower A' => 'Ms. Sarah Al-Humeidhi',
        'Command & Control Center - Third Floor' => 'Eng. Ibrahim Al-Dughaither',
        'Virtual Platform via Microsoft Teams' => 'Ms. Lama Al-Assaf',
    ];

    $grid = [];
    foreach ($locations as $index => $location) {
        $grid[] = [
            'name' => $location,
            'capacity' => $capacities[$index] ?? 40,
            'facilitator' => $facilitators[$location] ?? 'Coordination Team',
        ];
    }

    return $grid;
}
