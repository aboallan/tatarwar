<?php
session_start();
require_once __DIR__ . '/config.php';

$departments = [
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

$locations = [
    'قاعة الابتكار - المقر الرئيسي',
    'قاعة الاجتماعات التنفيذية - برج A',
    'مركز القيادة والتحكم - الطابق الثالث',
    'المنصة الافتراضية عبر Microsoft Teams',
];

function redirect_with_flash(string $type, string $title, string $message, string $context = 'login'): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'title' => $title,
        'message' => $message,
        'context' => $context,
    ];
    header('Location: index.php#auth-card');
    exit;
}

$action = $_POST['action'] ?? '';
if (!in_array($action, ['login', 'register', 'update'], true)) {
    redirect_with_flash('error', 'طلب غير معروف', 'الرجاء استخدام النماذج المخصصة في المنصة.');
}

try {
    $pdo = get_pdo();
} catch (PDOException $exception) {
    redirect_with_flash('error', 'تعذر الاتصال بقاعدة البيانات', 'يرجى التأكد من إعدادات الاتصال في ملف config.php ثم إعادة المحاولة.', $action);
}

function generate_unique_code(PDO $pdo): string
{
    do {
        $candidate = 'RM' . strtoupper(bin2hex(random_bytes(4)));
        $stmt = $pdo->prepare('SELECT id FROM meeting_users WHERE unique_code = ? LIMIT 1');
        $stmt->execute([$candidate]);
        $exists = $stmt->fetch();
    } while ($exists);

    return $candidate;
}

if ($action === 'register') {
    $name = trim($_POST['name'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if ($name === '' || $department === '' || $email === '' || $password === '') {
        redirect_with_flash('error', 'حقول ناقصة', 'يرجى تعبئة جميع الحقول المطلوبة لإنشاء الحساب.', 'register');
    }

    if (!in_array($department, $departments, true)) {
        redirect_with_flash('error', 'قسم غير معروف', 'يرجى اختيار القسم من القائمة المعتمدة.', 'register');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        redirect_with_flash('error', 'بريد إلكتروني غير صالح', 'يرجى إدخال بريد إلكتروني مؤسسي صحيح.', 'register');
    }

    if ($password !== $confirm) {
        redirect_with_flash('error', 'كلمتا المرور غير متطابقتين', 'يرجى التأكد من تطابق كلمة المرور مع التأكيد.', 'register');
    }

    if (strlen($password) < 8) {
        redirect_with_flash('error', 'كلمة المرور قصيرة', 'يجب ألا تقل كلمة المرور عن 8 أحرف لزيادة الأمان.', 'register');
    }

    $stmt = $pdo->prepare('SELECT id FROM meeting_users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        redirect_with_flash('error', 'البريد مستخدم مسبقاً', 'هذا البريد الإلكتروني مسجل بالفعل. يرجى تسجيل الدخول أو استخدام بريد آخر.', 'register');
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $uniqueCode = generate_unique_code($pdo);

    $insert = $pdo->prepare('INSERT INTO meeting_users (name, department, email, password_hash, unique_code) VALUES (?, ?, ?, ?, ?)');
    $insert->execute([$name, $department, $email, $hash, $uniqueCode]);

    $message = sprintf('تم إنشاء الحساب بنجاح. رمز الاعتماد المعتمد: %s — يرجى الاحتفاظ به لتأكيد حضورك.', $uniqueCode);
    redirect_with_flash('success', 'حساب جديد جاهز', $message, 'login');
}

if ($action === 'login') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        redirect_with_flash('error', 'البيانات مطلوبة', 'يرجى إدخال البريد الإلكتروني وكلمة المرور.', 'login');
    }

    $stmt = $pdo->prepare('SELECT id, name, password_hash, unique_code, department, attendance_status, location_preference, department_scope, department_focus, meeting_summary FROM meeting_users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        redirect_with_flash('error', 'بيانات غير صحيحة', 'تعذر التحقق من البيانات المدخلة. يرجى المحاولة مرة أخرى.', 'login');
    }

    $_SESSION['user'] = [
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $email,
        'unique_code' => $user['unique_code'],
        'department' => $user['department'],
        'attendance_status' => $user['attendance_status'],
        'location_preference' => $user['location_preference'],
        'department_scope' => $user['department_scope'],
        'department_focus' => $user['department_focus'],
        'meeting_summary' => $user['meeting_summary'],
    ];

    $message = sprintf('تم تسجيل الدخول بنجاح. رمز الاعتماد الخاص بك: %s.', $user['unique_code']);
    redirect_with_flash('success', 'أهلاً بعودتك', $message, 'login');
}

if ($action === 'update') {
    $uniqueCode = strtoupper(trim($_POST['unique_code'] ?? ''));
    $attendance = $_POST['attendance_status'] ?? 'pending';
    $location = trim($_POST['location_preference'] ?? '');
    $scope = $_POST['department_scope'] ?? 'all';
    $focus = trim($_POST['department_focus'] ?? '');
    $summary = trim($_POST['meeting_summary'] ?? '');

    if ($uniqueCode === '') {
        redirect_with_flash('error', 'رمز الاعتماد مطلوب', 'يرجى إدخال رمز الاعتماد المعتمد لإدارة حضورك.', 'login');
    }

    if (!in_array($attendance, ['pending', 'attend', 'decline'], true)) {
        redirect_with_flash('error', 'حالة غير صالحة', 'يرجى اختيار حالة الحضور من القائمة.', 'login');
    }

    if ($location !== '' && !in_array($location, $locations, true)) {
        redirect_with_flash('error', 'موقع غير معروف', 'يرجى اختيار موقع الاجتماع من القائمة المعتمدة.', 'login');
    }

    if (!in_array($scope, ['all', 'specific'], true)) {
        redirect_with_flash('error', 'نطاق غير صالح', 'يرجى تحديد ما إذا كان الحضور يشمل كل الأقسام أو قسماً محدداً.', 'login');
    }

    if ($scope === 'specific') {
        if (!in_array($focus, $departments, true)) {
            redirect_with_flash('error', 'قسم غير موجود', 'يرجى اختيار القسم المستهدف من القائمة.', 'login');
        }
    } else {
        $focus = '';
    }

    $stmt = $pdo->prepare('UPDATE meeting_users SET attendance_status = ?, location_preference = ?, department_scope = ?, department_focus = ?, meeting_summary = ?, responded_at = NOW() WHERE unique_code = ?');
    $stmt->execute([$attendance, $location, $scope, $focus, $summary === '' ? null : $summary, $uniqueCode]);

    if ($stmt->rowCount() === 0) {
        redirect_with_flash('error', 'تعذر العثور على الحساب', 'لم يتم العثور على سجل مطابق لرمز الاعتماد المدخل.', 'login');
    }

    if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
        $_SESSION['user'] = [
            'unique_code' => $uniqueCode,
        ];
    }

    $_SESSION['user']['unique_code'] = $uniqueCode;
    $_SESSION['user']['attendance_status'] = $attendance;
    $_SESSION['user']['location_preference'] = $location;
    $_SESSION['user']['department_scope'] = $scope;
    $_SESSION['user']['department_focus'] = $focus;
    $_SESSION['user']['meeting_summary'] = $summary;

    redirect_with_flash('success', 'تم تحديث الحضور', 'تم حفظ تفضيلاتك وملخص الاجتماع بنجاح.', 'login');
}
