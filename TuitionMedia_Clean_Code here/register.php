<?php
$pageTitle = 'Create Account';
require __DIR__ . '/includes/header.php';
if (is_logged_in()) redirect('dashboard.php');

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $errors = validate_required([
        'name' => 'Full name',
        'email' => 'Email',
        'password' => 'Password',
        'role' => 'Role',
    ]);

    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $role = $_POST['role'] ?? '';
    $password = $_POST['password'] ?? '';
    $adminCode = trim($_POST['admin_code'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if (!in_array($role, ['student', 'guardian', 'tutor', 'admin'], true)) $errors[] = 'Invalid account type.';
    if ($role === 'admin' && !hash_equals(ADMIN_REGISTRATION_CODE, $adminCode)) {
        $errors[] = 'The administrator registration code is incorrect.';
    }

    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) $errors[] = 'This email is already registered.';

    if (!$errors) {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('INSERT INTO users(name,email,phone,password,role) VALUES(?,?,?,?,?)');
            $stmt->execute([$name, $email, $phone, password_hash($password, PASSWORD_DEFAULT), $role]);
            $uid = (int)$pdo->lastInsertId();
            if ($role === 'tutor') {
                $pdo->prepare("INSERT INTO tutor_profiles(user_id,approval_status) VALUES(?,'pending')")->execute([$uid]);
                notify_admins($pdo, 'A new tutor account is waiting for profile completion and approval.', 'admin/tutors.php');
            }
            $pdo->commit();
            set_flash('success', 'Registration successful. Please log in.');
            redirect('login.php');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors[] = 'Registration could not be completed. Please try again.';
        }
    }
}
?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card shadow-soft p-4 p-md-5">
                <h2 class="section-title">Create your account</h2>
                <p class="text-muted">Choose the correct role. Tutor profiles require admin approval. Administrator registration uses a protected course-demo code.</p>

                <?php if ($errors): ?>
                    <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
                <?php endif; ?>

                <form method="post" class="row g-3" id="registrationForm">
                    <?= csrf_field() ?>
                    <div class="col-md-6"><label class="form-label">Full Name</label><input class="form-control" name="name" value="<?= post_value('name') ?>" required></div>
                    <div class="col-md-6"><label class="form-label">Phone</label><input class="form-control" name="phone" value="<?= post_value('phone') ?>"></div>
                    <div class="col-12"><label class="form-label">Email</label><input type="email" class="form-control" name="email" value="<?= post_value('email') ?>" required></div>
                    <div class="col-md-6"><label class="form-label">Password</label><input type="password" class="form-control" name="password" required></div>
                    <div class="col-md-6">
                        <label class="form-label">Account Type</label>
                        <select class="form-select" name="role" id="roleSelect" required>
                            <option value="">Choose...</option>
                            <?php foreach (['student' => 'Student', 'guardian' => 'Guardian', 'tutor' => 'Tutor', 'admin' => 'Administrator'] as $value => $label): ?>
                                <option value="<?= e($value) ?>" <?= ($_POST['role'] ?? '') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12" id="adminCodeWrap" style="display:<?= ($_POST['role'] ?? '') === 'admin' ? 'block' : 'none' ?>">
                        <label class="form-label">Administrator Registration Code</label>
                        <input class="form-control" name="admin_code" id="adminCode" value="<?= post_value('admin_code') ?>" placeholder="Enter the protected admin code">
                        <div class="form-text">For security, ordinary users cannot create an administrator account without this code.</div>
                    </div>
                    <div class="col-12"><button class="btn btn-primary btn-lg w-100">Create Account</button></div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
const roleSelect = document.getElementById('roleSelect');
const adminCodeWrap = document.getElementById('adminCodeWrap');
const adminCode = document.getElementById('adminCode');
function toggleAdminCode() {
    const isAdmin = roleSelect.value === 'admin';
    adminCodeWrap.style.display = isAdmin ? 'block' : 'none';
    adminCode.required = isAdmin;
}
roleSelect.addEventListener('change', toggleAdminCode);
toggleAdminCode();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
