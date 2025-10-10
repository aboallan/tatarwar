<?php

declare(strict_types=1);

const MEETING_SEED_DEPARTMENTS = [
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

const MEETING_SEED_VENUES = [
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

const MEETING_SEED_AGENDA = [
    [
        'title' => 'Digital Transformation Roadmap Review',
        'scheduled_at' => '2024-07-25 09:30:00',
        'tag' => 'Strategic',
        'department' => 'General Administration of Information Technology',
        'location' => 'Innovation Hall - Headquarters',
        'summary' => 'Align the digital roadmap with current infrastructure projects and cybersecurity initiatives.',
    ],
    [
        'title' => 'Cybersecurity Preparedness Assessment',
        'scheduled_at' => '2024-07-25 11:15:00',
        'tag' => 'Risk',
        'department' => 'Cybersecurity Department',
        'location' => 'Command & Control Center - Third Floor',
        'summary' => 'Present penetration test results and assign remediation actions with a clear timeline.',
    ],
    [
        'title' => 'Activating Joint Investment Projects',
        'scheduled_at' => '2024-07-25 13:00:00',
        'tag' => 'Executive',
        'department' => 'Investment Agency',
        'location' => 'Executive Meeting Hall - Tower A',
        'summary' => 'Review cross-functional investment opportunities and confirm the funding schedule.',
    ],
];

const MEETING_SEED_ATTENDANCE = [
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

const MEETING_SEED_HIGHLIGHTS = [
    'Interactive deck for the digital transformation roadmap is 92% complete.',
    'Attendance confirmed by 9 departments so far with unified logistics in place.',
    'Departments are requested to submit final remarks by Monday at 12:00 PM.',
];

const MEETING_SEED_UPDATES = [
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

const MEETING_SEED_ADMIN_USER = [
    'name' => 'System Administrator',
    'department' => 'General Administration of Information Technology',
    'email' => 'admin@company.com',
    'password_hash' => '$2y$12$XDvDBBsNL5FH2SKfhQa3ee9h8D3z5ZqOE.vHcltde9McDhObOpag2',
    'unique_code' => 'ADM0000001',
    'attendance_status' => 'attend',
];
