<?php
require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/functions.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('dashboard.php');
}

verify_csrf();

$type = $_POST['type'] ?? '';
$action = $_POST['action'] ?? '';
$id = (int)($_POST['id'] ?? 0);
$returnTo = $_POST['return_to'] ?? '';
$notificationId = (int)($_POST['notification_id'] ?? 0);

if ($type === 'user' && in_array($action, ['block', 'activate'], true)) {
    $status = $action === 'block' ? 'blocked' : 'active';

    $pdo->prepare(
        'UPDATE users SET status = ? WHERE id = ? AND role <> "admin"'
    )->execute([$status, $id]);

    set_flash('success', 'User status updated.');
    redirect('admin/users.php');
}

if ($type === 'tutor' && in_array($action, ['approve', 'reject'], true)) {
    $status = $action === 'approve' ? 'approved' : 'rejected';

    $stmt = $pdo->prepare('SELECT user_id FROM tutor_profiles WHERE id = ?');
    $stmt->execute([$id]);
    $userId = $stmt->fetchColumn();

    $pdo->prepare(
        'UPDATE tutor_profiles SET approval_status = ? WHERE id = ?'
    )->execute([$status, $id]);

    if ($userId) {
        notify(
            $pdo,
            (int)$userId,
            'Your tutor profile was ' . $status . '.',
            'tutor/profile.php'
        );
    }

    set_flash('success', 'Tutor profile ' . $status . '.');
    redirect('admin/tutors.php');
}

if ($type === 'tuition' && in_array($action, ['approve', 'reject'], true)) {
    $status = $action === 'approve' ? 'approved' : 'rejected';

    $stmt = $pdo->prepare(
        'SELECT user_id, title FROM tuition_posts WHERE id = ?'
    );
    $stmt->execute([$id]);
    $post = $stmt->fetch();

    if (!$post) {
        set_flash('danger', 'Tuition post was not found.');
        redirect($returnTo === 'notifications.php' ? 'notifications.php' : 'admin/tuitions.php');
    }

    $pdo->prepare(
        'UPDATE tuition_posts SET status = ? WHERE id = ?'
    )->execute([$status, $id]);

    notify(
        $pdo,
        (int)$post['user_id'],
        'Your tuition post "' . $post['title'] . '" was ' . $status . '.',
        'seeker/my_posts.php',
        'tuition',
        $id
    );

    if ($notificationId > 0) {
        $pdo->prepare(
            'UPDATE notifications
             SET message = ?, entity_type = "tuition", entity_id = ?, is_read = 1
             WHERE id = ? AND user_id = ?'
        )->execute([
            'Tuition post "' . $post['title'] . '" was ' . $status . ' by you.',
            $id,
            $notificationId,
            (int)current_user()['id'],
        ]);
    }

    set_flash('success', 'Tuition post ' . $status . '.');
    redirect($returnTo === 'notifications.php' ? 'notifications.php' : 'admin/tuitions.php');
}

set_flash('danger', 'Invalid admin action.');
redirect('dashboard.php');
