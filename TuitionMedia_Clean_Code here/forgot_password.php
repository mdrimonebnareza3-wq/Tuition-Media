<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$error = '';
$success = '';
$resetLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $email = strtolower(trim($_POST['email'] ?? ''));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            $pdo->exec(
                "DELETE FROM password_resets
                 WHERE expires_at < NOW()
                 OR used_at IS NOT NULL"
            );

            $stmt = $pdo->prepare(
                "SELECT id
                 FROM users
                 WHERE email = ?
                 AND status = 'active'
                 LIMIT 1"
            );
            $stmt->execute([$email]);
            $userId = $stmt->fetchColumn();

            if ($userId) {
                $token = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $token);
                $expiresAt = date('Y-m-d H:i:s', time() + 1800);

                $pdo->prepare(
                    "UPDATE password_resets
                     SET used_at = NOW()
                     WHERE user_id = ?
                     AND used_at IS NULL"
                )->execute([(int)$userId]);

                $pdo->prepare(
                    "INSERT INTO password_resets
                     (user_id, token_hash, expires_at)
                     VALUES (?, ?, ?)"
                )->execute([(int)$userId, $tokenHash, $expiresAt]);

                $resetLink = url(
                    'reset_password.php?token=' . urlencode($token)
                );
            }

            $success = 'If the email is registered, a password reset link has been created.';
        } catch (PDOException $e) {
            $error = 'Password reset tables are unavailable. Import database.sql or run setup.php for a fresh installation.';
        }
    }
}

$pageTitle = 'Forgot Password';
require __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-soft p-4 p-md-5">
                <h2 class="section-title">Forgot password?</h2>
                <p class="text-muted">
                    Enter your registered email address.
                </p>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= e($error) ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?= e($success) ?></div>
                <?php endif; ?>

                <?php if ($resetLink): ?>
                    <div class="alert alert-warning">
                        <strong>Local demo reset link:</strong><br>
                        <a href="<?= e($resetLink) ?>">
                            Reset your password
                        </a>
                        <div class="small mt-2">
                            This link will expire in 30 minutes.
                        </div>
                    </div>
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

                    <button class="btn btn-primary btn-lg">
                        Create Reset Link
                    </button>

                    <p class="text-center mb-0">
                        <a href="<?= url('login.php') ?>">Back to login</a>
                    </p>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
