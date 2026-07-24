<?php
require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/functions.php';
require_role(['student', 'guardian']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('seeker/applications.php');
}

verify_csrf();

$id = (int) ($_POST['application_id'] ?? 0);
$action = $_POST['action'] ?? '';

$stmt = $pdo->prepare(
    'SELECT a.*, t.user_id, t.title, tp.user_id AS tutor_user_id
     FROM applications a
     JOIN tuition_posts t ON t.id = a.tuition_id
     JOIN tutor_profiles tp ON tp.id = a.tutor_id
     WHERE a.id = ? AND t.user_id = ?'
);
$stmt->execute([$id, current_user()['id']]);
$app = $stmt->fetch();

if (!$app) {
    set_flash('danger', 'Application not found.');
    redirect('seeker/applications.php');
}

if ($action === 'accept' && $app['status'] === 'pending') {
    $pdo->beginTransaction();

    try {
        $pdo->prepare(
            "UPDATE applications
             SET status = 'accepted', accepted_at = NOW()
             WHERE id = ?"
        )->execute([$id]);

        $pdo->prepare(
            "UPDATE applications
             SET status = 'rejected'
             WHERE tuition_id = ? AND id <> ? AND status = 'pending'"
        )->execute([$app['tuition_id'], $id]);

        $pdo->prepare(
            "UPDATE tuition_posts SET status = 'closed' WHERE id = ?"
        )->execute([$app['tuition_id']]);

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        set_flash('danger', 'The application could not be accepted.');
        redirect('seeker/applications.php');
    }

    notify(
        $pdo,
        (int) $app['tutor_user_id'],
        'Your application was accepted for: ' . $app['title'],
        'tutor/applications.php'
    );

    set_flash(
        'success',
        'Tutor accepted. The tuition post is now closed to new applications.'
    );
} elseif ($action === 'reject' && $app['status'] === 'pending') {
    $pdo->prepare(
        "UPDATE applications SET status = 'rejected' WHERE id = ?"
    )->execute([$id]);

    notify(
        $pdo,
        (int) $app['tutor_user_id'],
        'Your application was not selected for: ' . $app['title'],
        'tutor/applications.php'
    );

    set_flash('success', 'Application rejected.');
} elseif ($action === 'complete' && $app['status'] === 'accepted') {
    $pdo->prepare(
        "UPDATE applications
         SET status = 'completed', completed_at = NOW()
         WHERE id = ?"
    )->execute([$id]);

    notify(
        $pdo,
        (int) $app['tutor_user_id'],
        'The tuition was marked completed: ' . $app['title'],
        'tutor/applications.php'
    );

    set_flash(
        'success',
        'Tuition marked as completed. You can now submit a tutor review.'
    );
} elseif ($action === 'cancel' && $app['status'] === 'accepted') {
    $reason = trim($_POST['cancellation_reason'] ?? '');

    if (strlen($reason) < 5) {
        set_flash(
            'danger',
            'Please write a short cancellation reason of at least 5 characters.'
        );
        redirect('seeker/applications.php');
    }

    $pdo->beginTransaction();

    try {
        $pdo->prepare(
            "UPDATE applications
             SET status = 'cancelled',
                 cancelled_at = NOW(),
                 cancellation_reason = ?,
                 cancelled_by_role = ?
             WHERE id = ?"
        )->execute([
            $reason,
            current_user()['role'],
            $id,
        ]);

        $pdo->prepare(
            "UPDATE tuition_posts
             SET status = 'cancelled'
             WHERE id = ?"
        )->execute([$app['tuition_id']]);

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        set_flash('danger', 'The tuition could not be cancelled.');
        redirect('seeker/applications.php');
    }

    notify(
        $pdo,
        (int) $app['tutor_user_id'],
        'The tuition was cancelled by the student/guardian: ' . $app['title'],
        'tutor/applications.php'
    );

    notify_admins(
        $pdo,
        role_label(current_user()['role']) . ' cancelled an accepted tuition: ' . $app['title'],
        'admin/applications.php'
    );

    set_flash(
        'success',
        'Tuition cancelled successfully. The tutor has been notified.'
    );
} else {
    set_flash(
        'warning',
        'That action is not available for the current application status.'
    );
}

redirect('seeker/applications.php');
