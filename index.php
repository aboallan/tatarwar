<?php
session_start();
require_once __DIR__ . '/config.php';

$flash = $_SESSION['flash'] ?? [];
$activeTab = $flash['context'] ?? 'login';
unset($_SESSION['flash']);

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

$attendanceLabels = [
    'pending' => 'قيد التأكيد',
    'attend' => 'سيحضر',
    'decline' => 'اعتذار',
];

$userRecord = [
    'id' => $_SESSION['user']['id'] ?? null,
    'name' => $_SESSION['user']['name'] ?? null,
    'email' => $_SESSION['user']['email'] ?? null,
    'department' => $_SESSION['user']['department'] ?? null,
    'unique_code' => $_SESSION['user']['unique_code'] ?? null,
    'attendance_status' => $_SESSION['user']['attendance_status'] ?? 'pending',
    'location_preference' => $_SESSION['user']['location_preference'] ?? '',
    'department_scope' => $_SESSION['user']['department_scope'] ?? 'all',
    'department_focus' => $_SESSION['user']['department_focus'] ?? '',
    'meeting_summary' => $_SESSION['user']['meeting_summary'] ?? '',
];

if ($userRecord['id']) {
    try {
        $pdo = get_pdo();
        $stmt = $pdo->prepare('SELECT name, email, department, unique_code, attendance_status, location_preference, department_scope, department_focus, meeting_summary FROM meeting_users WHERE id = ? LIMIT 1');
        $stmt->execute([$userRecord['id']]);
        if ($row = $stmt->fetch()) {
            $userRecord = array_merge($userRecord, $row);
        }
    } catch (PDOException $exception) {
        // fallback to session values silently
    }
}

$todayMeetings = [
    [
        'title' => 'مراجعة خارطة التحول الرقمي',
        'time' => '09:30 ص',
        'tag' => 'استراتيجي',
        'department' => 'General Administration of Information Technology',
        'location' => 'قاعة الابتكار - المقر الرئيسي',
        'summary' => 'مواءمة خارطة الطريق الرقمية مع مشاريع البنية التحتية الحالية وخطط الأمن السيبراني.',
    ],
    [
        'title' => 'تقييم جاهزية الأمن السيبراني',
        'time' => '11:15 ص',
        'tag' => 'مخاطر',
        'department' => 'Cybersecurity Department',
        'location' => 'مركز القيادة والتحكم - الطابق الثالث',
        'summary' => 'عرض نتائج اختبارات الاختراق وتوزيع مهام التحسين مع خطة زمنية واضحة.',
    ],
    [
        'title' => 'تنشيط المشاريع الاستثمارية المشتركة',
        'time' => '01:00 م',
        'tag' => 'تنفيذي',
        'department' => 'Investment Agency',
        'location' => 'قاعة الاجتماعات التنفيذية - برج A',
        'summary' => 'مراجعة الفرص الاستثمارية المتقاطعة مع الإدارات التشغيلية وجدول التمويل.',
    ],
];

$attendanceBoard = [
    [
        'department' => 'General Administration of Information Technology',
        'status' => 'attend',
        'representatives' => 12,
        'location' => 'قاعة الابتكار - المقر الرئيسي',
    ],
    [
        'department' => 'Cybersecurity Department',
        'status' => 'attend',
        'representatives' => 8,
        'location' => 'مركز القيادة والتحكم - الطابق الثالث',
    ],
    [
        'department' => 'Investment Agency',
        'status' => 'pending',
        'representatives' => 6,
        'location' => 'قاعة الاجتماعات التنفيذية - برج A',
    ],
    [
        'department' => 'Public Gardens and Beautification Department',
        'status' => 'decline',
        'representatives' => 4,
        'location' => 'المنصة الافتراضية عبر Microsoft Teams',
    ],
];

$summaryHighlights = [
    'تم إنهاء إعداد العرض التفاعلي الخاص بخارطة التحول الرقمي بنسبة 92%.',
    'تأكيد حضور 9 إدارات حتى الآن، مع تنسيق لوجستي موحد.',
    'يُطلب من الإدارات رفع الملاحظات النهائية قبل يوم الاثنين الساعة 12 مساءً.',
];

$locationsGrid = array_map(static function (string $location, int $index): array {
    $capacity = [220, 30, 45, 500][$index] ?? 40;
    $facilitator = [
        'قاعة الابتكار - المقر الرئيسي' => 'م. ناصر الهمزاني',
        'قاعة الاجتماعات التنفيذية - برج A' => 'أ. سارة الحميضي',
        'مركز القيادة والتحكم - الطابق الثالث' => 'م. إبراهيم الدغيثر',
        'المنصة الافتراضية عبر Microsoft Teams' => 'أ. لمى العساف',
    ][$location] ?? 'فريق التنسيق';

    return [
        'name' => $location,
        'capacity' => $capacity,
        'facilitator' => $facilitator,
    ];
}, $locations, array_keys($locations));

$activeTab = in_array($activeTab, ['login', 'register'], true) ? $activeTab : 'login';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>منصة رسالة الاجتماع</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body class="app">
    <div class="app__backdrop" aria-hidden="true"></div>
    <header class="app-header">
        <div class="app-header__brand">
            <span class="app-header__icon">🗂️</span>
            <div>
                <strong>منصة التنسيق المؤسسي</strong>
                <span>رسالة الاجتماع الشاملة 2024</span>
            </div>
        </div>
        <nav class="app-header__nav">
            <a href="#dashboard">لوحة الاجتماع</a>
            <a href="#attendance">الحضور والاعتمادات</a>
            <a href="#locations">أماكن الاجتماع</a>
            <a href="#summary">ملخص الاجتماع</a>
        </nav>
        <div class="app-header__cta">
            <a class="btn btn--primary" href="#auth-card">تأكيد الحضور</a>
        </div>
    </header>

    <?php if (!empty($flash)): ?>
        <aside class="toast toast--<?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8') ?>" role="alert">
            <div class="toast__header">
                <strong><?= htmlspecialchars($flash['title'], ENT_QUOTES, 'UTF-8') ?></strong>
                <button type="button" class="toast__close" data-dismiss-toast aria-label="إغلاق">×</button>
            </div>
            <p><?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?></p>
        </aside>
    <?php endif; ?>

    <main class="layout">
        <aside class="sidebar">
            <section class="sidebar__panel">
                <header>
                    <h2>الأقسام المشاركة</h2>
                    <span><?= count($departments) ?> إدارة</span>
                </header>
                <ul class="department-list">
                    <?php foreach ($departments as $department): ?>
                        <li>
                            <span class="department-list__badge"></span>
                            <span><?= htmlspecialchars($department, ENT_QUOTES, 'UTF-8') ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>

            <section class="sidebar__panel">
                <header>
                    <h2>مؤشرات سريعة</h2>
                </header>
                <div class="stats">
                    <article class="stat">
                        <strong>13</strong>
                        <span>إدارة مدعوة</span>
                    </article>
                    <article class="stat">
                        <strong>09</strong>
                        <span>جلسات رئيسية</span>
                    </article>
                    <article class="stat">
                        <strong>04</strong>
                        <span>ورش تخصصية</span>
                    </article>
                </div>
            </section>

            <section class="sidebar__panel">
                <header>
                    <h2>جلسات اليوم</h2>
                    <span>توقيت الرياض</span>
                </header>
                <ul class="agenda-mini">
                    <?php foreach ($todayMeetings as $slot): ?>
                        <li>
                            <div>
                                <strong><?= htmlspecialchars($slot['time'], ENT_QUOTES, 'UTF-8') ?></strong>
                                <span><?= htmlspecialchars($slot['title'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <span class="agenda-mini__tag"><?= htmlspecialchars($slot['tag'], ENT_QUOTES, 'UTF-8') ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        </aside>

        <section class="content" id="dashboard">
            <div class="content__hero">
                <div>
                    <span class="content__eyebrow">الثلاثاء، 25 يوليو 2024 · 09:00 ص - 02:30 م</span>
                    <h1>لوحة قيادة رسالة الاجتماع</h1>
                    <p>
                        منصة تجمع جميع الإدارات تحت هوية موحدة مع متابعة فورية للحضور، المواقع، والملخصات التنفيذية. صممت بعناية لتعكس المستوى المؤسسي الحديث كما في النموذج المعروض.
                    </p>
                    <div class="content__actions">
                        <a class="btn btn--primary" href="#auth-card">إدارة الحضور</a>
                        <a class="btn btn--ghost" href="#summary">عرض الملخص</a>
                    </div>
                </div>
                <div class="content__card">
                    <header>
                        <strong>رمز الاعتماد</strong>
                        <button type="button" class="link" data-copy="<?= htmlspecialchars($userRecord['unique_code'] ?? '', ENT_QUOTES, 'UTF-8') ?>">نسخ الرمز</button>
                    </header>
                    <div class="content__code">
                        <?php if (!empty($userRecord['unique_code'])): ?>
                            <span><?= htmlspecialchars($userRecord['unique_code'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php else: ?>
                            <span class="content__placeholder">سجل حسابك ليتم إصدار رمز اعتماد خاص بك.</span>
                        <?php endif; ?>
                    </div>
                    <footer>
                        <span>رمز الاعتماد هو المرجع الرسمي للموافقة على التسجيل وتحديث حالة الحضور.</span>
                    </footer>
                </div>
            </div>

            <section class="cards-grid cards-grid--wide">
                <?php foreach ($todayMeetings as $slot): ?>
                    <article class="meeting-card">
                        <header>
                            <span class="meeting-card__time"><?= htmlspecialchars($slot['time'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="meeting-card__tag"><?= htmlspecialchars($slot['tag'], ENT_QUOTES, 'UTF-8') ?></span>
                        </header>
                        <h3><?= htmlspecialchars($slot['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <p><?= htmlspecialchars($slot['summary'], ENT_QUOTES, 'UTF-8') ?></p>
                        <footer>
                            <span>القسم المسؤول: <?= htmlspecialchars($slot['department'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span>الموقع: <?= htmlspecialchars($slot['location'], ENT_QUOTES, 'UTF-8') ?></span>
                        </footer>
                    </article>
                <?php endforeach; ?>
            </section>

            <section class="panel" id="attendance">
                <header class="panel__header">
                    <div>
                        <h2>الحضور والاعتمادات</h2>
                        <p>إدارة تسجيل الدخول، إنشاء حسابات جديدة، وتحديث حالة الحضور عبر رمز الاعتماد.</p>
                    </div>
                </header>
                <div class="panel__body">
                    <div class="auth" id="auth-card">
                        <div class="auth__tabs" role="tablist">
                            <button class="auth__tab<?= $activeTab === 'login' ? ' auth__tab--active' : '' ?>" data-target="auth-login" role="tab" aria-selected="<?= $activeTab === 'login' ? 'true' : 'false' ?>">تسجيل الدخول</button>
                            <button class="auth__tab<?= $activeTab === 'register' ? ' auth__tab--active' : '' ?>" data-target="auth-register" role="tab" aria-selected="<?= $activeTab === 'register' ? 'true' : 'false' ?>">تسجيل جديد</button>
                        </div>
                        <div class="auth__panels">
                            <form id="auth-login" class="auth__panel<?= $activeTab === 'login' ? ' auth__panel--active' : '' ?>" action="auth.php" method="post">
                                <input type="hidden" name="action" value="login">
                                <div class="field">
                                    <label for="login-email">البريد الإلكتروني</label>
                                    <input id="login-email" name="email" type="email" autocomplete="email" required>
                                </div>
                                <div class="field">
                                    <label for="login-password">كلمة المرور</label>
                                    <input id="login-password" name="password" type="password" autocomplete="current-password" required>
                                </div>
                                <button type="submit" class="btn btn--primary btn--full">دخول</button>
                            </form>

                            <form id="auth-register" class="auth__panel<?= $activeTab === 'register' ? ' auth__panel--active' : '' ?>" action="auth.php" method="post" novalidate>
                                <input type="hidden" name="action" value="register">
                                <div class="field">
                                    <label for="register-name">الاسم الكامل</label>
                                    <input id="register-name" name="name" type="text" autocomplete="name" required>
                                </div>
                                <div class="field">
                                    <label for="register-department">القسم</label>
                                    <select id="register-department" name="department" required>
                                        <option value="" disabled selected>اختر القسم</option>
                                        <?php foreach ($departments as $department): ?>
                                            <option value="<?= htmlspecialchars($department, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($department, ENT_QUOTES, 'UTF-8') ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="field">
                                    <label for="register-email">البريد الإلكتروني المؤسسي</label>
                                    <input id="register-email" name="email" type="email" autocomplete="email" required>
                                </div>
                                <div class="field">
                                    <label for="register-password">كلمة المرور</label>
                                    <input id="register-password" name="password" type="password" autocomplete="new-password" minlength="8" required>
                                </div>
                                <div class="field">
                                    <label for="register-confirm">تأكيد كلمة المرور</label>
                                    <input id="register-confirm" name="confirm" type="password" autocomplete="new-password" minlength="8" required>
                                </div>
                                <button type="submit" class="btn btn--primary btn--full">إنشاء الحساب وإصدار الرمز</button>
                            </form>
                        </div>
                    </div>

                    <form class="update" action="auth.php" method="post">
                        <input type="hidden" name="action" value="update">
                        <div class="update__header">
                            <h3>تحديث حالة الحضور</h3>
                            <p>استخدم رمز الاعتماد المعتمد لتحديد حالة حضورك، الموقع المفضل، ونطاق مشاركة القسم.</p>
                        </div>
                        <div class="grid">
                            <div class="field">
                                <label for="update-code">رمز الاعتماد</label>
                                <input id="update-code" name="unique_code" type="text" value="<?= htmlspecialchars($userRecord['unique_code'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="مثل: RM8F2A6C4" required>
                            </div>
                            <div class="field">
                                <label for="update-status">حالة الحضور</label>
                                <select id="update-status" name="attendance_status" required>
                                    <?php foreach ($attendanceLabels as $value => $label): ?>
                                        <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>"<?= $userRecord['attendance_status'] === $value ? ' selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="field">
                                <label for="update-location">الموقع المفضل</label>
                                <select id="update-location" name="location_preference">
                                    <option value="">سيتم التنسيق لاحقاً</option>
                                    <?php foreach ($locations as $location): ?>
                                        <option value="<?= htmlspecialchars($location, ENT_QUOTES, 'UTF-8') ?>"<?= $userRecord['location_preference'] === $location ? ' selected' : '' ?>><?= htmlspecialchars($location, ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <fieldset class="field field--inline">
                                <legend>نطاق المشاركة</legend>
                                <label class="chip">
                                    <input type="radio" name="department_scope" value="all"<?= $userRecord['department_scope'] !== 'specific' ? ' checked' : '' ?>>
                                    <span>كل الأقسام</span>
                                </label>
                                <label class="chip">
                                    <input type="radio" name="department_scope" value="specific"<?= $userRecord['department_scope'] === 'specific' ? ' checked' : '' ?>>
                                    <span>قسم محدد</span>
                                </label>
                            </fieldset>
                            <div class="field" data-scope-target>
                                <label for="update-focus">القسم المستهدف</label>
                                <select id="update-focus" name="department_focus"<?= $userRecord['department_scope'] === 'specific' ? '' : ' disabled' ?>>
                                    <option value="">اختر القسم</option>
                                    <?php foreach ($departments as $department): ?>
                                        <option value="<?= htmlspecialchars($department, ENT_QUOTES, 'UTF-8') ?>"<?= $userRecord['department_focus'] === $department ? ' selected' : '' ?>><?= htmlspecialchars($department, ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="field">
                            <label for="update-summary">ملخص الاجتماع</label>
                            <textarea id="update-summary" name="meeting_summary" rows="4" maxlength="600" data-counter><?= htmlspecialchars($userRecord['meeting_summary'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                            <small class="field__hint">شارك أبرز القرارات أو الملاحظات التي سيتم التركيز عليها.</small>
                        </div>
                        <button type="submit" class="btn btn--accent">حفظ التحديثات</button>
                    </form>
                </div>
            </section>

            <section class="panel" id="summary">
                <header class="panel__header">
                    <div>
                        <h2>ملخص تنفيذي سريع</h2>
                        <p>أبرز الملاحظات والقرارات المبدئية التي تم جمعها حتى الآن.</p>
                    </div>
                </header>
                <div class="panel__body panel__body--columns">
                    <div class="summary">
                        <h3>ملخص اليوم</h3>
                        <ul>
                            <?php foreach ($summaryHighlights as $item): ?>
                                <li><?= htmlspecialchars($item, ENT_QUOTES, 'UTF-8') ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="summary summary--outline">
                        <h3>آخر تحديثات الأقسام</h3>
                        <dl>
                            <?php foreach ($attendanceBoard as $row): ?>
                                <div>
                                    <dt><?= htmlspecialchars($row['department'], ENT_QUOTES, 'UTF-8') ?></dt>
                                    <dd><?= htmlspecialchars($attendanceLabels[$row['status']], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($row['representatives'], ENT_QUOTES, 'UTF-8') ?> ممثلين · <?= htmlspecialchars($row['location'], ENT_QUOTES, 'UTF-8') ?></dd>
                                </div>
                            <?php endforeach; ?>
                        </dl>
                    </div>
                </div>
            </section>

            <section class="panel" id="locations">
                <header class="panel__header">
                    <div>
                        <h2>أماكن الاجتماع</h2>
                        <p>خيارات الأماكن المعتمدة للحضور الميداني أو الافتراضي.</p>
                    </div>
                </header>
                <div class="locations">
                    <?php foreach ($locationsGrid as $location): ?>
                        <article class="location-card">
                            <header>
                                <h3><?= htmlspecialchars($location['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                                <span>سعة <?= htmlspecialchars((string) $location['capacity'], ENT_QUOTES, 'UTF-8') ?> شخص</span>
                            </header>
                            <p>منسق الجلسة: <?= htmlspecialchars($location['facilitator'], ENT_QUOTES, 'UTF-8') ?></p>
                            <footer>
                                <span>جاهز للعرض التفاعلي</span>
                                <span>البث المباشر متاح</span>
                            </footer>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="panel">
                <header class="panel__header">
                    <div>
                        <h2>قائمة الحضور والاعتذار</h2>
                        <p>متابعة مباشرة لاستجابة الأقسام للدعوة الرسمية.</p>
                    </div>
                </header>
                <div class="table">
                    <div class="table__head">
                        <span>القسم</span>
                        <span>الحالة</span>
                        <span>عدد الحضور</span>
                        <span>الموقع</span>
                    </div>
                    <div class="table__body">
                        <?php foreach ($attendanceBoard as $row): ?>
                            <div class="table__row">
                                <span><?= htmlspecialchars($row['department'], ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="badge badge--<?= htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($attendanceLabels[$row['status']], ENT_QUOTES, 'UTF-8') ?></span>
                                <span><?= htmlspecialchars((string) $row['representatives'], ENT_QUOTES, 'UTF-8') ?></span>
                                <span><?= htmlspecialchars($row['location'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        </section>
    </main>

    <script src="script.js" defer></script>
</body>
</html>
