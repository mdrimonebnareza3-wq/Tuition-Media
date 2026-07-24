<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare(
        'SELECT id, name, email, phone, password, role, status
         FROM users
         WHERE email = ?
         LIMIT 1'
    );
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        if ($user['status'] !== 'active') {
            $error = 'Your account is blocked. Please contact the administrator.';
        } else {
            unset($user['password']);
            $_SESSION['user'] = $user;
            set_flash('success', 'Welcome back, ' . $user['name'] . '!');
            redirect('dashboard.php');
        }
    } else {
        $error = 'Incorrect email or password.';
    }
}

$pageTitle = 'Login';
require __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-soft p-4 p-md-5">
                <h2 class="section-title">Welcome back</h2>
                <p class="text-muted">Log in to access your dashboard.</p>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= e($error) ?></div>
                <?php endif; ?>

                <form method="post" class="vstack gap-3">
                    <?= csrf_field() ?>

                    <div>
                        <label class="form-label">Email</label>
                        <input
                            type="email"
                            name="email"
                            class="form-control"
                            value="<?= e($_POST['email'] ?? '') ?>"
                            autocomplete="email"
                            required
                        >
                    </div>

                    <div>
                        <label class="form-label">Password</label>
                        <input
                            type="password"
                            name="password"
                            class="form-control"
                            autocomplete="current-password"
                            required
                        >
                        <div class="text-end mt-2">
                            <a href="<?= url('forgot_password.php') ?>">
                                Forgot password?
                            </a>
                        </div>
                    </div>

                    <button class="btn btn-primary btn-lg">Login</button>

                    <p class="text-center mb-0">
                        No account?
                        <a href="<?= url('register.php') ?>">Register now</a>
                    </p>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
