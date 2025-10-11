<?php
declare(strict_types=1);

session_start();

$dbPath = __DIR__ . '/database/app.sqlite';
$initialize = !file_exists($dbPath);
$pdo = new PDO('sqlite:' . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if ($initialize) {
    initializeDatabase($pdo);
} else {
    ensureSchema($pdo);
}

handlePostRequests($pdo);

$currentUser = getAuthenticatedUser($pdo);
$departments = [];
$tasks = [];
$summary = [
    'total' => 0,
    'status' => [
        'Pending' => 0,
        'In Progress' => 0,
        'Completed' => 0,
    ],
    'by_department' => [],
];
$upcomingAlerts = [
    'overdue' => [],
    'upcoming' => [],
];

if ($currentUser !== null) {
    $departments = fetchDepartments($pdo);
    $tasks = fetchTasks($pdo);
    $summary = buildSummary($tasks, $departments);
    $upcomingAlerts = findUpcomingAlerts($tasks);
}

$flashMessages = consumeFlashMessages();

function initializeDatabase(PDO $pdo): void
{
    $pdo->exec('PRAGMA foreign_keys = ON');
    $schema = file_get_contents(__DIR__ . '/database/schema.sql');
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
}

function ensureSchema(PDO $pdo): void
{
    $pdo->exec('PRAGMA foreign_keys = ON');
    $schema = file_get_contents(__DIR__ . '/database/schema.sql');
    if ($schema === false) {
        throw new RuntimeException('Unable to read schema.sql file.');
    }
    $pdo->exec($schema);
}

function handlePostRequests(PDO $pdo): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    $action = $_POST['action'] ?? '';
    $anchor = '';

    switch ($action) {
        case 'register':
            $anchor = '#register';
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

            $normalizedEmail = strtolower((string)$email);

            try {
                $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash) VALUES (:name, :email, :password_hash)');
                $stmt->execute([
                    ':name' => $name,
                    ':email' => $normalizedEmail,
                    ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
                ]);
                addFlashMessage('success', 'Account created successfully. You can now sign in.');
                $anchor = '#login';
            } catch (PDOException $exception) {
                if ($exception->getCode() === '23000') {
                    addFlashMessage('error', 'An account with this email already exists.');
                } else {
                    addFlashMessage('error', 'Unable to create account at the moment. Please try again.');
                }
            }
            break;

        case 'login':
            $anchor = '#login';
            $emailInput = trim($_POST['email'] ?? '');
            $email = filter_var($emailInput, FILTER_VALIDATE_EMAIL);
            $password = $_POST['password'] ?? '';

            if ($email === false || $password === '') {
                addFlashMessage('error', 'Enter both email and password to sign in.');
                break;
            }

            $normalizedEmail = strtolower((string)$email);
            $stmt = $pdo->prepare('SELECT id, name, password_hash FROM users WHERE email = :email');
            $stmt->execute([':email' => $normalizedEmail]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int)$user['id'];
                addFlashMessage('success', 'Welcome back, ' . $user['name'] . '!');
                $anchor = '';
            } else {
                addFlashMessage('error', 'Incorrect email or password.');
            }
            break;

        case 'logout':
            $_SESSION = [];
            session_regenerate_id(true);
            addFlashMessage('success', 'You have been signed out.');
            break;

        case 'create_task':
            $anchor = '#create-task';
            if (!isAuthenticated()) {
                addFlashMessage('error', 'Please sign in to manage tasks.');
                break;
            }

            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $departmentId = (int)($_POST['department_id'] ?? 0);
            $assignee = trim($_POST['assignee'] ?? '');
            $dueDate = $_POST['due_date'] ?? '';
            $status = $_POST['status'] ?? 'Pending';
            $priority = $_POST['priority'] ?? 'Normal';

            if ($title === '' || $departmentId <= 0 || !isValidDate($dueDate)) {
                addFlashMessage('error', 'Task title, department, and due date are required.');
                break;
            }

            try {
                $stmt = $pdo->prepare('INSERT INTO tasks (title, description, department_id, assignee, due_date, status, priority) VALUES (:title, :description, :department_id, :assignee, :due_date, :status, :priority)');
                $stmt->execute([
                    ':title' => $title,
                    ':description' => $description,
                    ':department_id' => $departmentId,
                    ':assignee' => $assignee,
                    ':due_date' => $dueDate,
                    ':status' => $status,
                    ':priority' => $priority,
                ]);
                addFlashMessage('success', 'Task created successfully.');
            } catch (PDOException $exception) {
                addFlashMessage('error', 'Unable to create task right now. Please try again.');
            }
            break;

        case 'update_task_status':
            $anchor = '#task-list';
            if (!isAuthenticated()) {
                addFlashMessage('error', 'Please sign in to manage tasks.');
                break;
            }

            $taskId = (int)($_POST['task_id'] ?? 0);
            $status = $_POST['status'] ?? 'Pending';
            $allowedStatuses = ['Pending', 'In Progress', 'Completed'];

            if ($taskId > 0 && in_array($status, $allowedStatuses, true)) {
                $stmt = $pdo->prepare('UPDATE tasks SET status = :status WHERE id = :id');
                $stmt->execute([
                    ':status' => $status,
                    ':id' => $taskId,
                ]);
                addFlashMessage('success', 'Task status updated.');
            }
            break;

        case 'delete_task':
            $anchor = '#task-list';
            if (!isAuthenticated()) {
                addFlashMessage('error', 'Please sign in to manage tasks.');
                break;
            }

            $taskId = (int)($_POST['task_id'] ?? 0);
            if ($taskId > 0) {
                $stmt = $pdo->prepare('DELETE FROM tasks WHERE id = :id');
                $stmt->execute([':id' => $taskId]);
                addFlashMessage('success', 'Task deleted.');
            }
            break;

        default:
            break;
    }

    redirectToSelf($anchor);
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
    return isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0;
}

function getAuthenticatedUser(PDO $pdo): ?array
{
    if (!isAuthenticated()) {
        return null;
    }

    $userId = (int)$_SESSION['user_id'];
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

function fetchTasks(PDO $pdo): array
{
    $sql = 'SELECT t.*, d.name AS department_name FROM tasks t JOIN departments d ON d.id = t.department_id ORDER BY due_date ASC, priority DESC';
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
        ],
        'by_department' => [],
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
        if (isset($summary['by_department'][$task['department_id']])) {
            $summary['by_department'][$task['department_id']]['count']++;
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

function findUpcomingAlerts(array $tasks): array
{
    $alerts = [
        'overdue' => [],
        'upcoming' => [],
    ];

    $today = new DateTimeImmutable('today');
    $upcomingThreshold = $today->modify('+3 days');

    foreach ($tasks as $task) {
        if ($task['status'] === 'Completed') {
            continue;
        }

        $dueDate = DateTimeImmutable::createFromFormat('Y-m-d', $task['due_date']);
        if (!$dueDate) {
            continue;
        }

        if ($dueDate < $today) {
            $alerts['overdue'][] = $task;
        } elseif ($dueDate <= $upcomingThreshold) {
            $alerts['upcoming'][] = $task;
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
    ][$status] ?? $status;
}

function getPriorityLabel(string $priority): string
{
    return [
        'High' => 'High',
        'Normal' => 'Normal',
        'Low' => 'Low',
    ][$priority] ?? $priority;
}

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department Task Management</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <header class="app-header">
        <div class="header-content">
            <div class="header-bar">
                <p class="eyebrow">Operations Control Center</p>
                <div class="header-actions">
                    <?php if ($currentUser !== null): ?>
                        <span class="user-chip">Signed in as <?php echo escape($currentUser['name']); ?></span>
                        <form method="post" class="logout-form">
                            <input type="hidden" name="action" value="logout">
                            <button type="submit" class="ghost-btn">Sign out</button>
                        </form>
                    <?php else: ?>
                        <a href="#register" class="ghost-btn">Create account</a>
                    <?php endif; ?>
                </div>
            </div>
            <h1>Department Task Management</h1>
            <p>Assign, track, and deliver tasks across divisions with clear ownership and deadlines.</p>
        </div>
    </header>

    <?php if (!empty($flashMessages)): ?>
        <div class="flash-container">
            <?php foreach ($flashMessages as $flash): ?>
                <div class="flash-message flash-<?php echo escape($flash['type']); ?>"><?php echo escape($flash['message']); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($currentUser === null): ?>
        <main class="auth-layout">
            <section class="card auth-card" id="login">
                <h2>Sign In</h2>
                <p class="card-subtitle auth-subtitle">Access the task command center with your credentials.</p>
                <form method="post" class="auth-form">
                    <input type="hidden" name="action" value="login">
                    <label>
                        Email Address
                        <input type="email" name="email" required placeholder="you@example.com" autocomplete="email">
                    </label>
                    <label>
                        Password
                        <input type="password" name="password" required placeholder="Enter your password" autocomplete="current-password">
                    </label>
                    <button type="submit" class="primary-btn">Sign In</button>
                </form>
            </section>
            <section class="card auth-card" id="register">
                <h2>Create Account</h2>
                <p class="card-subtitle auth-subtitle">Invite team members to coordinate cross-departmental tasks.</p>
                <form method="post" class="auth-form">
                    <input type="hidden" name="action" value="register">
                    <label>
                        Full Name
                        <input type="text" name="name" required placeholder="e.g., Sara Alotaibi" autocomplete="name">
                    </label>
                    <label>
                        Work Email
                        <input type="email" name="email" required placeholder="name@municipality.gov" autocomplete="email">
                    </label>
                    <label>
                        Password
                        <input type="password" name="password" required placeholder="Minimum 8 characters" autocomplete="new-password">
                    </label>
                    <label>
                        Confirm Password
                        <input type="password" name="confirm_password" required placeholder="Re-enter your password" autocomplete="new-password">
                    </label>
                    <button type="submit" class="primary-btn">Register</button>
                </form>
            </section>
        </main>
    <?php else: ?>
        <main class="layout">
            <section class="panel">
                <h2>Quick Stats</h2>
                <div class="stats-grid">
                    <article class="stat-card">
                        <h3>Total Tasks</h3>
                        <p class="stat-number"><?php echo $summary['total']; ?></p>
                    </article>
                    <?php foreach ($summary['status'] as $status => $count): ?>
                        <article class="stat-card">
                            <h3><?php echo escape(getStatusLabel($status)); ?></h3>
                            <p class="stat-number"><?php echo $count; ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>

                <h2>Deadline Alerts</h2>
                <p class="card-subtitle">Stay ahead of key deliveries.</p>
                <div class="alerts">
                    <div class="alert-group overdue">
                        <h3>Overdue</h3>
                        <?php if (empty($upcomingAlerts['overdue'])): ?>
                            <p>No overdue tasks.</p>
                        <?php else: ?>
                            <ul>
                                <?php foreach ($upcomingAlerts['overdue'] as $task): ?>
                                    <li>
                                        <strong><?php echo escape($task['title']); ?></strong>
                                        <span> · <?php echo escape($task['department_name']); ?></span>
                                        <time>Due: <?php echo escape($task['due_date']); ?></time>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                    <div class="alert-group upcoming">
                        <h3>Upcoming</h3>
                        <?php if (empty($upcomingAlerts['upcoming'])): ?>
                            <p>No upcoming deadlines within three days.</p>
                        <?php else: ?>
                            <ul>
                                <?php foreach ($upcomingAlerts['upcoming'] as $task): ?>
                                    <li>
                                        <strong><?php echo escape($task['title']); ?></strong>
                                        <span> · <?php echo escape($task['department_name']); ?></span>
                                        <time>Due: <?php echo escape($task['due_date']); ?></time>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <section class="content">
                <section class="card" id="create-task">
                    <h2>Create New Task</h2>
                    <form method="post" class="task-form">
                        <input type="hidden" name="action" value="create_task">
                        <div class="form-grid">
                            <label>
                                Task Title
                                <input type="text" name="title" required placeholder="e.g., Finalize budget proposal">
                            </label>
                            <label>
                                Assignee
                                <input type="text" name="assignee" placeholder="Person responsible">
                            </label>
                            <label>
                                Department
                                <select name="department_id" required>
                                    <option value="" disabled selected>Select department</option>
                                    <?php foreach ($departments as $department): ?>
                                        <option value="<?php echo $department['id']; ?>"><?php echo escape($department['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label>
                                Priority
                                <select name="priority">
                                    <option value="High">High</option>
                                    <option value="Normal" selected>Normal</option>
                                    <option value="Low">Low</option>
                                </select>
                            </label>
                            <label>
                                Due Date
                                <input type="date" name="due_date" required>
                            </label>
                            <label>
                                Status
                                <select name="status">
                                    <option value="Pending">Pending</option>
                                    <option value="In Progress">In Progress</option>
                                    <option value="Completed">Completed</option>
                                </select>
                            </label>
                        </div>
                        <label>
                            Task Details
                            <textarea name="description" rows="4" placeholder="Add a short task summary"></textarea>
                        </label>
                        <button type="submit" class="primary-btn">Save Task</button>
                    </form>
                </section>

                <section class="card" id="task-list">
                    <header class="card-header">
                        <div>
                            <h2>Task List</h2>
                            <p>Use the filters to segment assignments by department, status, or priority.</p>
                        </div>
                        <div class="filters">
                            <label>
                                Search
                                <input type="search" id="search" placeholder="Search by task or owner">
                            </label>
                            <label>
                                Department
                                <select id="filter-department">
                                    <option value="all">All</option>
                                    <?php foreach ($departments as $department): ?>
                                        <option value="<?php echo $department['id']; ?>"><?php echo escape($department['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label>
                                Status
                                <select id="filter-status">
                                    <option value="all">All</option>
                                    <option value="Pending">Pending</option>
                                    <option value="In Progress">In Progress</option>
                                    <option value="Completed">Completed</option>
                                </select>
                            </label>
                            <label>
                                Priority
                                <select id="filter-priority">
                                    <option value="all">All</option>
                                    <option value="High">High</option>
                                    <option value="Normal">Normal</option>
                                    <option value="Low">Low</option>
                                </select>
                            </label>
                        </div>
                    </header>

                    <div class="table-wrapper">
                        <table id="tasks-table">
                            <thead>
                                <tr>
                                    <th>Task</th>
                                    <th>Department</th>
                                    <th>Assignee</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Due Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($tasks)): ?>
                                    <tr><td colspan="7">No tasks available yet.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($tasks as $task): ?>
                                    <tr data-department="<?php echo $task['department_id']; ?>" data-status="<?php echo escape($task['status']); ?>" data-priority="<?php echo escape($task['priority']); ?>">
                                        <td>
                                            <strong><?php echo escape($task['title']); ?></strong>
                                            <?php if (!empty($task['description'])): ?>
                                                <p class="description"><?php echo nl2br(escape($task['description'])); ?></p>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo escape($task['department_name']); ?></td>
                                        <td><?php echo escape($task['assignee'] ?: '—'); ?></td>
                                        <td><span class="tag priority-<?php echo strtolower($task['priority']); ?>"><?php echo escape(getPriorityLabel($task['priority'])); ?></span></td>
                                        <td><span class="tag status-<?php echo strtolower(str_replace(' ', '-', $task['status'])); ?>"><?php echo escape(getStatusLabel($task['status'])); ?></span></td>
                                        <td>
                                            <time datetime="<?php echo escape($task['due_date']); ?>" class="due-date"><?php echo escape($task['due_date']); ?></time>
                                            <span class="badge due-indicator"></span>
                                        </td>
                                        <td class="actions">
                                            <form method="post" class="inline-form">
                                                <input type="hidden" name="action" value="update_task_status">
                                                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                                <select name="status" onchange="this.form.submit()">
                                                    <option value="Pending" <?php echo $task['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="In Progress" <?php echo $task['status'] === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                                                    <option value="Completed" <?php echo $task['status'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                                </select>
                                            </form>
                                            <form method="post" class="inline-form" onsubmit="return confirm('Are you sure you want to delete this task?');">
                                                <input type="hidden" name="action" value="delete_task">
                                                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                                <button type="submit" class="danger-btn">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </section>

            <aside class="side-column">
                <section class="card department-card" id="department-overview">
                    <h2>Departments</h2>
                    <p class="card-subtitle">Task load across teams.</p>
                    <ul class="department-list">
                        <?php foreach ($summary['by_department'] as $info): ?>
                            <li>
                                <span class="department-name"><?php echo escape($info['name']); ?></span>
                                <span class="badge"><?php echo $info['count']; ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            </aside>
        </main>
    <?php endif; ?>

    <script src="assets/scripts.js"></script>
</body>
</html>
