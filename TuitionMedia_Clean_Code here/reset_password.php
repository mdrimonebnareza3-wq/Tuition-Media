<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$error = '';
$record = null;

function findResetRecord(PDO $pdo, string $token): ?array
{
    if ($token === '') {
        return null;
    }

    $tokenHash = hash('sha256', $token);

    $stmt = $pdo->prepare(
        "SELECT pr.id, pr.user_id, u.email
         FROM password_resets pr
         INNER JOIN users u ON u.id = pr.user_id
         WHERE pr.token_hash = ?
         AND pr.used_at IS NULL
         AND pr.expires_at > NOW()
         AND u.status = 'active'
         LIMIT 1"
    );
    $stmt->execute([$tokenHash]);

    $result = $stmt->fetch();

    return $result ?: null;
}

try {
    $record = findResetRecord($pdo, $token);
} catch (PDOException $e) {
    $error = 'Password reset tables are unavailable. Import database.sql or run setup.php for a fresh installation.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === '') {
    verify_csrf();

    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (!$record) {
        $error = 'This reset link is invalid or expired.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must contain at least 6 characters.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Password confirmation does not match.';
    } else {
        try {
            $pdo->beginTransaction();

            $newPassword = password_hash($password, PASSWORD_DEFAULT);

            $pdo->prepare(
                'UPDATE users SET password = ? WHERE id = ?'
            )->execute([$newPassword, (int)$record['user_id']]);

            $pdo->prepare(
                'UPDATE password_resets SET used_at = NOW() WHERE id = ?'
            )->execute([(int)$record['id']]);

            $pdo->prepare(
                "UPDATE password_resets
                 SET used_at = NOW()
                 WHERE user_id = ?
                 AND used_at IS NULL"
            )->execute([(int)$record['user_id']]);

            $pdo->commit();

            set_flash(
                'success',
                'Password changed successfully. You can now log in.'
            );
            redirect('login.php');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error = 'Password could not be changed. Please try again.';
        }
    }
}

$pageTitle = 'Reset Password';
require __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-soft p-4 p-md-5">
                <h2 class="section-title">Reset password</h2>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= e($error) ?></div>
                <?php endif; ?>

                <?php if (!$record): ?>
                    <p class="text-muted">
                        This reset link is invalid or has expired.
                    </p>

                    <a
                        href="<?= url('forgot_password.php') ?>"
                        class="btn btn-primary"
                    >
                        Create a New Reset Link
                    </a>
                <?php else: ?>
                    <p class="text-muted">
                        Set a new password for <?= e($record['email']) ?>.
                    </p>

                    <form method="post" class="vstack gap-3">
                        <?= csrf_field() ?>

                        <input
                            type="hidden"
                            name="token"
                            value="<?= e($token) ?>"
                        >

                        <div>
                            <label class="form-label">New Password</label>
                            <input
                                type="password"
                                name="password"
                                class="form-control"
                                minlength="6"
                                autocomplete="new-password"
                                required
                            >
                        </div>

                        <div>
                            <label class="form-label">
                                Confirm New Password
                            </label>
                            <input
                                type="password"
                                name="confirm_password"
                                class="form-control"
                                minlength="6"
                                autocomplete="new-password"
                                required
                            >
                        </div>

                        <button class="btn btn-primary btn-lg">
                            Change Password
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
