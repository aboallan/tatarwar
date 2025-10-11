<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function getDatabaseConnection(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dbPath = __DIR__ . '/../database/app.sqlite';
    $initialize = !file_exists($dbPath);

    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($initialize) {
        initializeDatabase($pdo);
    } else {
        ensureSchema($pdo);
    }

    return $pdo;
}

function initializeDatabase(PDO $pdo): void
{
    $pdo->exec('PRAGMA foreign_keys = ON');
    $schema = file_get_contents(__DIR__ . '/../database/schema.sql');
    if ($schema === false) {
        throw new RuntimeException('Unable to read schema.sql file.');
    }
    $pdo->exec($schema);

    $departments = [
        'Office of the Secretary',
        'General Directorate of Information Technology',
        'Cybersecurity Department',
        'Investment Agency',
        'General Directorate of Gardens and Beautification',
        'General Directorate of Internal Auditing',
        'General Directorate of Operations and Emergencies',
        'Security and Safety Department',
        'Central Unit for Plan Approval',
        'Land Department',
        'Environmental Health Department',
        'General Directorate of Cleanliness',
        'Downtown Municipality',
        'North Municipality',
    ];

    $stmt = $pdo->prepare('INSERT OR IGNORE INTO departments (name) VALUES (:name)');
    foreach ($departments as $name) {
        $stmt->execute([':name' => $name]);
    }

    $projects = [
        [
            'name' => 'Smart City Command Platform',
            'sponsor' => 'Office of the Secretary',
            'description' => 'Unifies cross-department reporting and situational awareness across the municipality.',
            'strategic_theme' => 'Digital Transformation',
            'start_date' => '2024-01-15',
            'target_date' => '2024-12-20',
            'status' => 'Active',
        ],
        [
            'name' => 'Clean & Green 2030',
            'sponsor' => 'General Directorate of Cleanliness',
            'description' => 'City-wide waste reduction and beautification program with measurable impact goals.',
            'strategic_theme' => 'Sustainability',
            'start_date' => '2023-11-01',
            'target_date' => '2025-03-30',
            'status' => 'Active',
        ],
        [
            'name' => 'Emergency Response Modernization',
            'sponsor' => 'General Directorate of Operations and Emergencies',
            'description' => 'Upgrade dispatch technology and joint response playbooks for critical incidents.',
            'strategic_theme' => 'Public Safety',
            'start_date' => '2024-02-05',
            'target_date' => '2024-10-15',
            'status' => 'On Hold',
        ],
    ];

    $projectStmt = $pdo->prepare('INSERT OR IGNORE INTO projects (name, sponsor, description, strategic_theme, start_date, target_date, status) VALUES (:name, :sponsor, :description, :strategic_theme, :start_date, :target_date, :status)');
    foreach ($projects as $project) {
        $projectStmt->execute([
            ':name' => $project['name'],
            ':sponsor' => $project['sponsor'],
            ':description' => $project['description'],
            ':strategic_theme' => $project['strategic_theme'],
            ':start_date' => $project['start_date'],
            ':target_date' => $project['target_date'],
            ':status' => $project['status'],
        ]);
    }
}

function ensureSchema(PDO $pdo): void
{
    $pdo->exec('PRAGMA foreign_keys = ON');
    $schema = file_get_contents(__DIR__ . '/../database/schema.sql');
    if ($schema === false) {
        throw new RuntimeException('Unable to read schema.sql file.');
    }
    $pdo->exec($schema);

    ensureColumn($pdo, 'tasks', 'project_id', 'INTEGER');
    ensureColumn($pdo, 'tasks', 'risk_level', "TEXT NOT NULL DEFAULT 'Moderate'");
    ensureColumn($pdo, 'tasks', 'impact_level', "TEXT NOT NULL DEFAULT 'Medium'");
    ensureColumn($pdo, 'tasks', 'effort_level', "TEXT NOT NULL DEFAULT 'Medium'");
}

function ensureColumn(PDO $pdo, string $table, string $column, string $definition): void
{
    $stmt = $pdo->query('PRAGMA table_info(' . $table . ')');
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 1);
    if (!in_array($column, $columns, true)) {
        $pdo->exec('ALTER TABLE ' . $table . ' ADD COLUMN ' . $column . ' ' . $definition);
    }
}

function handlePostRequests(PDO $pdo): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        return;
    }

    $action = $_POST['action'] ?? '';
    $anchor = '';
    $redirectTarget = trim((string) ($_POST['redirect'] ?? ''));

    switch ($action) {
        case 'register':
            if ($redirectTarget === '') {
                $redirectTarget = 'register.php';
            }
            $anchor = '';
            
            $name = trim($_POST['name'] ?? '');
            $emailInput = trim($_POST['email'] ?? '');
            $email = filter_var($emailInput, FILTER_VALIDATE_EMAIL);
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if ($name === '' || $email === false) {
                addFlashMessage('error', 'Please provide your name and a valid email address.');
                break;
            }

            if (strlen($password) < 8) {
                addFlashMessage('error', 'Password must be at least 8 characters long.');
                break;
            }

            if ($password !== $confirmPassword) {
                addFlashMessage('error', 'Passwords do not match.');
                break;
            }

            $normalizedEmail = strtolower((string) $email);

            try {
                $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash) VALUES (:name, :email, :password_hash)');
                $stmt->execute([
                    ':name' => $name,
                    ':email' => $normalizedEmail,
                    ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
                ]);
                addFlashMessage('success', 'Account created successfully. You can now sign in.');
                $redirectTarget = 'login.php';
                $anchor = '';
            } catch (PDOException $exception) {
                if ($exception->getCode() === '23000') {
                    addFlashMessage('error', 'An account with this email already exists.');
                } else {
                    addFlashMessage('error', 'Unable to create account at the moment. Please try again.');
                }
            }
            break;

        case 'login':
            if ($redirectTarget === '') {
                $redirectTarget = 'login.php';
            }
            $anchor = '';
            $emailInput = trim($_POST['email'] ?? '');
            $email = filter_var($emailInput, FILTER_VALIDATE_EMAIL);
            $password = $_POST['password'] ?? '';

            if ($email === false || $password === '') {
                addFlashMessage('error', 'Enter both email and password to sign in.');
                break;
            }

            $normalizedEmail = strtolower((string) $email);
            $stmt = $pdo->prepare('SELECT id, name, password_hash FROM users WHERE email = :email');
            $stmt->execute([':email' => $normalizedEmail]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int) $user['id'];
                addFlashMessage('success', 'Welcome back, ' . $user['name'] . '!');
                $redirectTarget = 'dashboard.php';
                $anchor = '';
            } else {
                addFlashMessage('error', 'Incorrect email or password.');
            }
            break;

        case 'logout':
            $_SESSION = [];
            session_regenerate_id(true);
            addFlashMessage('success', 'You have been signed out.');
            $redirectTarget = 'login.php';
            break;

        case 'create_task':
            if ($redirectTarget === '') {
                $redirectTarget = 'tasks.php';
            }
            $anchor = '#task-form';
            if (!isAuthenticated()) {
                addFlashMessage('error', 'Please sign in to manage tasks.');
                break;
            }

            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $departmentId = (int) ($_POST['department_id'] ?? 0);
            $projectId = (int) ($_POST['project_id'] ?? 0);
            $assignee = trim($_POST['assignee'] ?? '');
            $dueDate = $_POST['due_date'] ?? '';
            $status = $_POST['status'] ?? 'Pending';
            $priority = $_POST['priority'] ?? 'Normal';
            $riskLevel = $_POST['risk_level'] ?? 'Moderate';
            $impactLevel = $_POST['impact_level'] ?? 'Medium';
            $effortLevel = $_POST['effort_level'] ?? 'Medium';

            if ($title === '' || $departmentId <= 0 || !isValidDate($dueDate)) {
                addFlashMessage('error', 'Task title, department, and due date are required.');
                break;
            }

            if (!in_array($status, allowedTaskStatuses(), true)) {
                addFlashMessage('error', 'Invalid task status provided.');
                break;
            }

            if (!in_array($priority, allowedPriorities(), true)) {
                addFlashMessage('error', 'Invalid priority selection.');
                break;
            }

            if (!in_array($riskLevel, allowedRiskLevels(), true) || !in_array($impactLevel, allowedImpactLevels(), true) || !in_array($effortLevel, allowedEffortLevels(), true)) {
                addFlashMessage('error', 'Invalid risk, impact, or effort level.');
                break;
            }

            $projectValue = $projectId > 0 ? $projectId : null;

            try {
                $stmt = $pdo->prepare('INSERT INTO tasks (title, description, department_id, project_id, assignee, due_date, status, priority, risk_level, impact_level, effort_level) VALUES (:title, :description, :department_id, :project_id, :assignee, :due_date, :status, :priority, :risk_level, :impact_level, :effort_level)');
                $stmt->execute([
                    ':title' => $title,
                    ':description' => $description,
                    ':department_id' => $departmentId,
                    ':project_id' => $projectValue,
                    ':assignee' => $assignee,
                    ':due_date' => $dueDate,
                    ':status' => $status,
                    ':priority' => $priority,
                    ':risk_level' => $riskLevel,
                    ':impact_level' => $impactLevel,
                    ':effort_level' => $effortLevel,
                ]);
                addFlashMessage('success', 'Task created successfully.');
            } catch (PDOException $exception) {
                addFlashMessage('error', 'Unable to create task right now. Please try again.');
            }
            break;

        case 'update_task_status':
            if ($redirectTarget === '') {
                $redirectTarget = 'tasks.php';
            }
            $anchor = '#task-list';
            if (!isAuthenticated()) {
                addFlashMessage('error', 'Please sign in to manage tasks.');
                break;
            }

            $taskId = (int) ($_POST['task_id'] ?? 0);
            $status = $_POST['status'] ?? 'Pending';
            if ($taskId > 0 && in_array($status, allowedTaskStatuses(), true)) {
                $stmt = $pdo->prepare('UPDATE tasks SET status = :status WHERE id = :id');
                $stmt->execute([
                    ':status' => $status,
                    ':id' => $taskId,
                ]);
                addFlashMessage('success', 'Task status updated.');
            }
            break;

        case 'delete_task':
            if ($redirectTarget === '') {
                $redirectTarget = 'tasks.php';
            }
            $anchor = '#task-list';
            if (!isAuthenticated()) {
                addFlashMessage('error', 'Please sign in to manage tasks.');
                break;
            }

            $taskId = (int) ($_POST['task_id'] ?? 0);
            if ($taskId > 0) {
                $stmt = $pdo->prepare('DELETE FROM tasks WHERE id = :id');
                $stmt->execute([':id' => $taskId]);
                addFlashMessage('success', 'Task deleted.');
            }
            break;

        case 'create_project':
            if ($redirectTarget === '') {
                $redirectTarget = 'projects.php';
            }
            $anchor = '#project-form';
            if (!isAuthenticated()) {
                addFlashMessage('error', 'Please sign in to manage projects.');
                break;
            }

            $name = trim($_POST['name'] ?? '');
            $strategicTheme = trim($_POST['strategic_theme'] ?? '');
            $sponsor = trim($_POST['sponsor'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $startDate = $_POST['start_date'] ?? '';
            $targetDate = $_POST['target_date'] ?? '';
            $status = $_POST['status'] ?? 'Active';

            if ($name === '') {
                addFlashMessage('error', 'Project name is required.');
                break;
            }

            if (!in_array($status, ['Active', 'On Hold', 'Completed', 'Archived'], true)) {
                addFlashMessage('error', 'Invalid project status.');
                break;
            }

            if ($startDate !== '' && !isValidDate($startDate)) {
                addFlashMessage('error', 'Invalid start date format.');
                break;
            }

            if ($targetDate !== '' && !isValidDate($targetDate)) {
                addFlashMessage('error', 'Invalid target date format.');
                break;
            }

            try {
                $stmt = $pdo->prepare('INSERT INTO projects (name, sponsor, description, strategic_theme, start_date, target_date, status) VALUES (:name, :sponsor, :description, :strategic_theme, :start_date, :target_date, :status)');
                $stmt->execute([
                    ':name' => $name,
                    ':sponsor' => $sponsor,
                    ':description' => $description,
                    ':strategic_theme' => $strategicTheme,
                    ':start_date' => $startDate !== '' ? $startDate : null,
                    ':target_date' => $targetDate !== '' ? $targetDate : null,
                    ':status' => $status,
                ]);
                addFlashMessage('success', 'Project created successfully.');
            } catch (PDOException $exception) {
                if ($exception->getCode() === '23000') {
                    addFlashMessage('error', 'A project with this name already exists.');
                } else {
                    addFlashMessage('error', 'Unable to create project right now. Please try again.');
                }
            }
            break;

        case 'log_task_update':
            if ($redirectTarget === '') {
                $redirectTarget = 'updates.php';
            }
            $anchor = '#update-form';
            if (!isAuthenticated()) {
                addFlashMessage('error', 'Please sign in to log progress.');
                break;
            }

            $taskId = (int) ($_POST['task_id'] ?? 0);
            $summary = trim($_POST['update_summary'] ?? '');
            $progress = (int) ($_POST['progress_percent'] ?? 0);
            $riskNote = trim($_POST['risk_note'] ?? '');
            $blockerNote = trim($_POST['blocker_note'] ?? '');
            $riskLevel = $_POST['risk_level'] ?? '';
            $reporter = trim($_POST['created_by'] ?? '');

            if ($taskId <= 0 || $summary === '') {
                addFlashMessage('error', 'Select a task and provide an update summary.');
                break;
            }

            if ($progress < 0 || $progress > 100) {
                addFlashMessage('error', 'Progress must be between 0 and 100%.');
                break;
            }

            if ($riskLevel !== '' && !in_array($riskLevel, allowedRiskLevels(), true)) {
                addFlashMessage('error', 'Invalid risk level selection.');
                break;
            }

            $riskValue = $riskLevel !== '' ? $riskLevel : null;
            $reporterValue = $reporter !== '' ? $reporter : null;

            try {
                $stmt = $pdo->prepare('INSERT INTO task_updates (task_id, update_summary, progress_percent, risk_note, blocker_note, risk_level, created_by) VALUES (:task_id, :update_summary, :progress_percent, :risk_note, :blocker_note, :risk_level, :created_by)');
                $stmt->execute([
                    ':task_id' => $taskId,
                    ':update_summary' => $summary,
                    ':progress_percent' => $progress,
                    ':risk_note' => $riskNote !== '' ? $riskNote : null,
                    ':blocker_note' => $blockerNote !== '' ? $blockerNote : null,
                    ':risk_level' => $riskValue,
                    ':created_by' => $reporterValue,
                ]);
                addFlashMessage('success', 'Progress update recorded.');
            } catch (PDOException $exception) {
                addFlashMessage('error', 'Unable to save the update right now. Please try again.');
            }
            break;

        default:
            break;
    }

    redirectAfterAction($redirectTarget, $anchor);
}

function redirectAfterAction(?string $target, string $anchor = ''): void
{
    $target = trim((string) $target);
    if ($target === '') {
        redirectToSelf($anchor);
    }

    if ($anchor !== '') {
        $target = preg_replace('/#.*/', '', $target) . $anchor;
    }

    header('Location: ' . $target);
    exit;
}
function allowedTaskStatuses(): array
{
    return ['Pending', 'In Progress', 'Completed', 'On Hold'];
}

function allowedPriorities(): array
{
    return ['Critical', 'High', 'Normal', 'Low'];
}

function allowedRiskLevels(): array
{
    return ['Low', 'Moderate', 'High', 'Critical'];
}

function allowedImpactLevels(): array
{
    return ['Low', 'Medium', 'High', 'Extreme'];
}

function allowedEffortLevels(): array
{
    return ['Light', 'Medium', 'Heavy'];
}

function addFlashMessage(string $type, string $message): void
{
    $_SESSION['flash_messages'] ??= [];
    $_SESSION['flash_messages'][] = [
        'type' => $type,
        'message' => $message,
    ];
}

function consumeFlashMessages(): array
{
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);

    return $messages;
}

function redirectToSelf(string $anchor = ''): void
{
    $uri = $_SERVER['REQUEST_URI'] ?? ($_SERVER['PHP_SELF'] ?? '/');
    $uri = strtok($uri, '#');
    $location = $uri . $anchor;
    header('Location: ' . $location);
    exit;
}

function isAuthenticated(): bool
{
    return isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] > 0;
}

function getAuthenticatedUser(PDO $pdo): ?array
{
    if (!isAuthenticated()) {
        return null;
    }

    $userId = (int) $_SESSION['user_id'];
    $stmt = $pdo->prepare('SELECT id, name, email FROM users WHERE id = :id');
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user === false) {
        unset($_SESSION['user_id']);
        return null;
    }

    return $user;
}

function fetchDepartments(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT id, name FROM departments ORDER BY name');
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchProjects(PDO $pdo): array
{
    $sql = <<<SQL
        SELECT
            p.id,
            p.name,
            p.sponsor,
            p.description,
            p.strategic_theme,
            p.start_date,
            p.target_date,
            p.status,
            COUNT(t.id) AS total_tasks,
            SUM(CASE WHEN t.status = 'Completed' THEN 1 ELSE 0 END) AS completed_tasks,
            SUM(CASE WHEN t.status != 'Completed' AND t.risk_level IN ('High', 'Critical') THEN 1 ELSE 0 END) AS at_risk_tasks,
            MAX(u.created_at) AS last_update_at
        FROM projects p
        LEFT JOIN tasks t ON t.project_id = p.id
        LEFT JOIN task_updates u ON u.task_id = t.id
        GROUP BY p.id
        ORDER BY CASE p.status WHEN 'Active' THEN 0 WHEN 'On Hold' THEN 1 WHEN 'Completed' THEN 2 ELSE 3 END,
                 CASE WHEN p.target_date IS NULL THEN 1 ELSE 0 END,
                 p.target_date
    SQL;

    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchTasks(PDO $pdo): array
{
    $sql = <<<SQL
        SELECT
            t.*,
            d.name AS department_name,
            p.name AS project_name,
            (
                SELECT progress_percent
                FROM task_updates
                WHERE task_id = t.id
                ORDER BY created_at DESC
                LIMIT 1
            ) AS latest_progress,
            (
                SELECT update_summary
                FROM task_updates
                WHERE task_id = t.id
                ORDER BY created_at DESC
                LIMIT 1
            ) AS latest_summary,
            (
                SELECT created_at
                FROM task_updates
                WHERE task_id = t.id
                ORDER BY created_at DESC
                LIMIT 1
            ) AS latest_update_at
        FROM tasks t
        JOIN departments d ON d.id = t.department_id
        LEFT JOIN projects p ON p.id = t.project_id
        ORDER BY t.due_date ASC,
                 CASE t.priority WHEN 'Critical' THEN 3 WHEN 'High' THEN 2 WHEN 'Normal' THEN 1 ELSE 0 END DESC,
                 t.created_at DESC
    SQL;

    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchRecentUpdates(PDO $pdo): array
{
    $sql = <<<SQL
        SELECT
            u.id,
            u.task_id,
            u.update_summary,
            u.progress_percent,
            u.risk_note,
            u.blocker_note,
            u.created_at,
            u.created_by,
            t.title AS task_title,
            t.risk_level,
            d.name AS department_name,
            p.name AS project_name
        FROM task_updates u
        JOIN tasks t ON t.id = u.task_id
        JOIN departments d ON d.id = t.department_id
        LEFT JOIN projects p ON p.id = t.project_id
        ORDER BY u.created_at DESC
        LIMIT 8
    SQL;

    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function buildSummary(array $tasks, array $departments): array
{
    $summary = [
        'total' => count($tasks),
        'status' => [
            'Pending' => 0,
            'In Progress' => 0,
            'Completed' => 0,
            'On Hold' => 0,
        ],
        'risk' => [
            'Low' => 0,
            'Moderate' => 0,
            'High' => 0,
            'Critical' => 0,
        ],
        'by_department' => [],
        'peak_department_tasks' => 0,
    ];

    foreach ($departments as $department) {
        $summary['by_department'][$department['id']] = [
            'name' => $department['name'],
            'count' => 0,
        ];
    }

    foreach ($tasks as $task) {
        if (isset($summary['status'][$task['status']])) {
            $summary['status'][$task['status']]++;
        }
        if (isset($summary['risk'][$task['risk_level']])) {
            $summary['risk'][$task['risk_level']]++;
        }
        if (isset($summary['by_department'][$task['department_id']])) {
            $summary['by_department'][$task['department_id']]['count']++;
            $summary['peak_department_tasks'] = max($summary['peak_department_tasks'], $summary['by_department'][$task['department_id']]['count']);
        }
    }

    uasort($summary['by_department'], static function (array $a, array $b): int {
        $comparison = $b['count'] <=> $a['count'];
        if ($comparison !== 0) {
            return $comparison;
        }

        return strcmp($a['name'], $b['name']);
    });

    return $summary;
}

function buildPortfolioSnapshot(array $projects, array $tasks, array $summary): array
{
    $activeProjects = 0;
    $engagedDepartments = [];
    $completedTasks = $summary['status']['Completed'] ?? 0;
    $totalTasks = max(1, $summary['total']);
    $atRiskTasks = ($summary['risk']['High'] ?? 0) + ($summary['risk']['Critical'] ?? 0);

    foreach ($projects as $project) {
        if ($project['status'] === 'Active' || $project['status'] === 'On Hold') {
            $activeProjects++;
        }
    }

    foreach ($tasks as $task) {
        $engagedDepartments[$task['department_id']] = true;
    }

    $completionRate = (int) round(($completedTasks / $totalTasks) * 100);

    return [
        'active_projects' => $activeProjects,
        'engaged_departments' => count($engagedDepartments),
        'completion_rate' => $completionRate,
        'at_risk_tasks' => $atRiskTasks,
    ];
}

function findUpcomingAlerts(array $tasks): array
{
    $alerts = [
        'overdue' => [],
        'upcoming' => [],
    ];

    $today = new DateTimeImmutable('today');
    $upcomingThreshold = $today->modify('+5 days');

    foreach ($tasks as $task) {
        if ($task['status'] === 'Completed') {
            continue;
        }

        $dueDate = DateTimeImmutable::createFromFormat('Y-m-d', $task['due_date']);
        if (!$dueDate) {
            continue;
        }

        $diff = (int) $today->diff($dueDate)->format('%r%a');
        $taskWithDiff = $task;
        $taskWithDiff['days_diff'] = $diff;

        if ($dueDate < $today) {
            $alerts['overdue'][] = $taskWithDiff;
        } elseif ($dueDate <= $upcomingThreshold) {
            $alerts['upcoming'][] = $taskWithDiff;
        }
    }

    return $alerts;
}

function isValidDate(string $date): bool
{
    $dateTime = DateTimeImmutable::createFromFormat('Y-m-d', $date);
    return $dateTime instanceof DateTimeImmutable && $dateTime->format('Y-m-d') === $date;
}

function getStatusLabel(string $status): string
{
    return [
        'Pending' => 'Pending',
        'In Progress' => 'In Progress',
        'Completed' => 'Completed',
        'On Hold' => 'On Hold',
    ][$status] ?? $status;
}

function getPriorityLabel(string $priority): string
{
    return [
        'Critical' => 'Critical',
        'High' => 'High',
        'Normal' => 'Normal',
        'Low' => 'Low',
    ][$priority] ?? $priority;
}

function getRiskClass(string $riskLevel): string
{
    return 'risk-' . strtolower(str_replace(' ', '-', $riskLevel));
}

function formatDate(?string $value): string
{
    if ($value === null || $value === '') {
        return 'TBD';
    }

    $date = DateTimeImmutable::createFromFormat('Y-m-d', $value);
    if (!$date) {
        return $value;
    }

    return $date->format('d M Y');
}

function describeDueDifference(int $daysDiff): string
{
    if ($daysDiff === 0) {
        return 'Due today';
    }

    if ($daysDiff > 0) {
        $plural = $daysDiff === 1 ? '' : 's';
        return 'Due in ' . $daysDiff . ' day' . $plural;
    }

    $daysOverdue = abs($daysDiff);
    $plural = $daysOverdue === 1 ? '' : 's';
    return $daysOverdue . ' day' . $plural . ' overdue';
}

function excerpt(string $value, int $length = 120): string
{
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        if (mb_strlen($value, 'UTF-8') <= $length) {
            return $value;
        }

        return rtrim(mb_substr($value, 0, $length, 'UTF-8')) . '…';
    }

    if (strlen($value) <= $length) {
        return $value;
    }

    return rtrim(substr($value, 0, $length)) . '…';
}

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
