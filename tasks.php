<?php
$pageTitle = 'Task Management';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/functions.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $title = trim($_POST['title'] ?? '');
        $departmentId = (int)($_POST['department_id'] ?? 0);
        $priority = $_POST['priority'] ?? 'Medium';
        $dueDate = $_POST['due_date'] ?? null;
        $description = trim($_POST['description'] ?? '');

        if ($title === '') {
            $errors[] = 'Task title is required.';
        }

        if ($departmentId <= 0) {
            $errors[] = 'Select a responsible department.';
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare('INSERT INTO tasks (title, description, department_id, priority, status, due_date, created_at, updated_at)
                VALUES (:title, :description, :department_id, :priority, :status, :due_date, NOW(), NOW())');
            $stmt->execute([
                'title' => $title,
                'description' => $description,
                'department_id' => $departmentId,
                'priority' => $priority,
                'status' => 'Pending',
                'due_date' => $dueDate ?: null,
            ]);

            $success = 'Task created successfully.';
        }
    } elseif ($action === 'update_status') {
        $taskId = (int)($_POST['task_id'] ?? 0);
        $status = $_POST['status'] ?? 'Pending';

        if ($taskId > 0) {
            $update = $pdo->prepare('UPDATE tasks SET status = :status, updated_at = NOW() WHERE id = :id');
            $update->execute([
                'status' => $status,
                'id' => $taskId,
            ]);
            $success = 'Task status updated.';
        }
    } elseif ($action === 'delete') {
        $taskId = (int)($_POST['task_id'] ?? 0);
        if ($taskId > 0) {
            $pdo->prepare('DELETE FROM notifications WHERE task_id = :id')->execute(['id' => $taskId]);
            $pdo->prepare('DELETE FROM tasks WHERE id = :id')->execute(['id' => $taskId]);
            $success = 'Task deleted.';
        }
    }
}

$departments = $pdo->query('SELECT id, name FROM departments ORDER BY name ASC')->fetchAll();

$tasksStmt = $pdo->query('SELECT t.*, d.name AS department_name,
    (SELECT DATE_FORMAT(n.created_at, "%Y-%m-%d %H:%i") FROM notifications n WHERE n.task_id = t.id ORDER BY n.created_at DESC LIMIT 1) AS last_reminder
    FROM tasks t
    JOIN departments d ON t.department_id = d.id
    ORDER BY t.due_date IS NULL, t.due_date ASC, t.created_at DESC');
$tasks = $tasksStmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<section class="grid grid-3">
    <article class="card form-card">
        <h2>Create Task</h2>
        <p class="text-muted">Assign work to departments and set due dates</p>
        <?php if ($errors): ?>
            <div class="alert error">
                <?= implode('<br>', array_map('sanitize', $errors)); ?>
            </div>
        <?php endif; ?>
        <?php if ($success && !$errors): ?>
            <div class="alert success"><?= sanitize($success); ?></div>
        <?php endif; ?>
        <form method="post">
            <input type="hidden" name="action" value="create">
            <div>
                <label for="title">Task title</label>
                <input type="text" id="title" name="title" required>
            </div>
            <div class="form-grid">
                <div>
                    <label for="department_id">Department</label>
                    <select id="department_id" name="department_id" required>
                        <option value="">Select department</option>
                        <?php foreach ($departments as $department): ?>
                            <option value="<?= (int)$department['id']; ?>"><?= sanitize($department['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="priority">Priority</label>
                    <select id="priority" name="priority">
                        <option value="High">High</option>
                        <option value="Medium" selected>Medium</option>
                        <option value="Low">Low</option>
                    </select>
                </div>
                <div>
                    <label for="due_date">Due date</label>
                    <input type="date" id="due_date" name="due_date">
                </div>
            </div>
            <div>
                <label for="description">Task description</label>
                <textarea id="description" name="description" placeholder="Add details and instructions"></textarea>
            </div>
            <button type="submit">Save task</button>
        </form>
    </article>
</section>

<section class="card">
    <div class="metric-header">
        <h2>Task log</h2>
        <span class="metric-pill"><span class="dot"></span><?= number_format(count($tasks)); ?> tasks</span>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Task</th>
                    <th>Department</th>
                    <th>Priority</th>
                    <th>Due date</th>
                    <th>Status</th>
                    <th>Last reminder</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$tasks): ?>
                <tr>
                    <td colspan="7" class="empty-state">No tasks yet. Start by adding assignments for departments.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($tasks as $task): ?>
                    <?php $statusData = analyze_due_status($task['due_date'], $task['status']); ?>
                    <tr>
                        <td>
                            <strong><?= sanitize($task['title']); ?></strong>
                            <?php if ($task['description']): ?>
                                <div class="text-muted"><?= nl2br(sanitize($task['description'])); ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?= sanitize($task['department_name']); ?></td>
                        <td><span class="tag"><?= sanitize($task['priority']); ?></span></td>
                        <td><?= sanitize(format_date($task['due_date'])); ?></td>
                        <td>
                            <span class="status-badge <?= $statusData['class']; ?>"><?= sanitize($statusData['label']); ?></span>
                        </td>
                        <td class="reminder-stamp">
                            <?= $task['last_reminder'] ? sanitize($task['last_reminder']) : 'Not sent yet'; ?>
                        </td>
                        <td>
                            <div class="table-actions">
                                <form method="post">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="task_id" value="<?= (int)$task['id']; ?>">
                                    <select name="status" onchange="this.form.submit()">
                                        <option value="Pending" <?= $task['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="In Progress" <?= $task['status'] === 'In Progress' ? 'selected' : ''; ?>>In progress</option>
                                        <option value="Completed" <?= $task['status'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                    </select>
                                </form>
                                <button type="button" class="button reminder-button" data-task-id="<?= (int)$task['id']; ?>">Send reminder</button>
                                <form method="post" onsubmit="return confirm('Are you sure you want to delete this task?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="task_id" value="<?= (int)$task['id']; ?>">
                                    <button type="submit" class="button ghost">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
