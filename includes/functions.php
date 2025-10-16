<?php
if (!ini_get('date.timezone')) {
    date_default_timezone_set('Asia/Riyadh');
}

function sanitize(string $value = null): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function analyze_due_status(?string $dueDate, string $status = 'In Progress'): array
{
    $today = new DateTimeImmutable('today');
    if (strtolower($status) === 'completed') {
        return [
            'label' => 'Completed',
            'class' => 'status-complete',
        ];
    }

    if (!$dueDate) {
        return [
            'label' => 'No due date',
            'class' => 'status-open',
        ];
    }

    try {
        $due = new DateTimeImmutable($dueDate);
    } catch (Exception $e) {
        return [
            'label' => 'Invalid due date',
            'class' => 'status-open',
        ];
    }

    if ($due < $today) {
        return [
            'label' => 'Overdue',
            'class' => 'status-overdue',
        ];
    }

    $diff = $today->diff($due)->days;

    if ($diff <= 3) {
        return [
            'label' => 'Due soon',
            'class' => 'status-warning',
        ];
    }

    return [
        'label' => 'On track',
        'class' => 'status-open',
    ];
}

function format_date(?string $date): string
{
    if (!$date) {
        return '-';
    }

    try {
        return (new DateTimeImmutable($date))->format('Y-m-d');
    } catch (Exception $e) {
        return $date;
    }
}
