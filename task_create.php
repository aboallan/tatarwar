<?php
$pageTitle = 'Create Task';
$pageDescription = 'Add a new assignment and notify the responsible department.';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/functions.php';

$errors = [];
$formData = [
    'title' => '',
    'department_id' => '',
    'priority' => 'Medium',
    'due_date' => '',
    'description' => '',
];

$departments = $pdo->query('SELECT id, name FROM departments ORDER BY name ASC')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['title'] = trim($_POST['title'] ?? '');
    $formData['department_id'] = (int)($_POST['department_id'] ?? 0);
    $formData['priority'] = $_POST['priority'] ?? 'Medium';
    $formData['due_date'] = $_POST['due_date'] ?? '';
    $formData['description'] = trim($_POST['description'] ?? '');

    if ($formData['title'] === '') {
        $errors[] = 'Task title is required.';
    }

    if ($formData['department_id'] <= 0) {
        $errors[] = 'Choose a department to own this task.';
    }

    if (!in_array($formData['priority'], ['High', 'Medium', 'Low'], true)) {
        $formData['priority'] = 'Medium';
    }

    if ($formData['due_date'] !== '') {
        $d = DateTime::createFromFormat('Y-m-d', $formData['due_date']);
        if (!$d || $d->format('Y-m-d') !== $formData['due_date']) {
            $errors[] = 'Provide a valid due date (YYYY-MM-DD).';
        }
    }

    if (!$errors) {
        $stmt = $pdo->prepare('INSERT INTO tasks (title, description, department_id, priority, status, due_date, created_at, updated_at)
            VALUES (:title, :description, :department_id, :priority, :status, :due_date, NOW(), NOW())');
        $stmt->execute([
            'title' => $formData['title'],
            'description' => $formData['description'],
            'department_id' => $formData['department_id'],
            'priority' => $formData['priority'],
            'status' => 'Pending',
            'due_date' => $formData['due_date'] ?: null,
        ]);

        header('Location: tasks.php?created=1');
        exit;
    }
}

include __DIR__ . '/includes/header.php';
?>
<section class="form-page">
    <article class="panel form-primary">
        <header class="form-header">
            <h2>Task details</h2>
            <p>Provide enough context so departments can execute without delays.</p>
        </header>
        <?php if ($errors): ?>
            <div class="alert error">
                <?= implode('<br>', array_map('sanitize', $errors)); ?>
            </div>
        <?php endif; ?>
        <form method="post" class="task-form" novalidate>
            <div class="form-group">
                <label for="title">Task title</label>
                <input type="text" id="title" name="title" value="<?= sanitize($formData['title']); ?>" required placeholder="e.g. Update emergency drill playbook">
            </div>
            <div class="form-grid">
                <div class="form-group">
                    <label for="department_id">Department</label>
                    <select id="department_id" name="department_id" required>
                        <option value="">Select department</option>
                        <?php foreach ($departments as $department): ?>
                            <option value="<?= (int)$department['id']; ?>" <?= (int)$department['id'] === (int)$formData['department_id'] ? 'selected' : ''; ?>>
                                <?= sanitize($department['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="priority">Priority</label>
                    <div class="priority-pills">
                        <?php foreach (['High', 'Medium', 'Low'] as $priority): ?>
                            <label class="pill-option">
                                <input type="radio" name="priority" value="<?= $priority; ?>" <?= $formData['priority'] === $priority ? 'checked' : ''; ?>>
                                <span><?= $priority; ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="form-group">
                    <label for="due_date">Due date</label>
                    <input type="date" id="due_date" name="due_date" value="<?= sanitize($formData['due_date']); ?>">
                </div>
            </div>
            <div class="form-group">
                <label for="description">Brief</label>
                <textarea id="description" name="description" rows="5" placeholder="Outline deliverables, attachments, and expected outcomes."><?= sanitize($formData['description']); ?></textarea>
            </div>
            <div class="form-actions">
                <a href="tasks.php" class="ghost-action">Cancel</a>
                <button type="submit" class="primary-action">Save task</button>
            </div>
        </form>
    </article>
    <aside class="panel form-sidebar">
        <h2>Planning checklist</h2>
        <ul class="form-tips">
            <li><strong>Set clear ownership:</strong> choose the department that will lead execution.</li>
            <li><strong>Clarify urgency:</strong> use High for critical escalations, Medium for standard work, and Low for flexible items.</li>
            <li><strong>Add due dates:</strong> deadlines power the new calendar view and trigger reminder insights.</li>
        </ul>
        <div class="calendar-preview">
            <span class="calendar-preview-month"><?= date('F Y'); ?></span>
            <p>New tasks with due dates are automatically surfaced on the calendar.</p>
        </div>
    </aside>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
