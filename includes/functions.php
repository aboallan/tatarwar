<?php
if (!ini_get('date.timezone')) {
    date_default_timezone_set('Asia/Riyadh');
}

function ensure_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function sanitize(?string $value = null): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function current_user(): ?array
{
    ensure_session();
    return $_SESSION['user'] ?? null;
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        header('Location: login.php');
        exit;
    }

    return $user;
}

function user_role_label(string $role): string
{
    return match (strtolower($role)) {
        'president' => 'President',
        'manager' => 'Manager',
        'employee' => 'Employee',
        default => ucfirst($role),
    };
}

function set_flash(string $key, string $message): void
{
    ensure_session();
    $_SESSION['flash'][$key] = $message;
}

function get_flash(string $key): ?string
{
    ensure_session();
    if (!isset($_SESSION['flash'][$key])) {
        return null;
    }

    $message = $_SESSION['flash'][$key];
    unset($_SESSION['flash'][$key]);

    return $message;
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
