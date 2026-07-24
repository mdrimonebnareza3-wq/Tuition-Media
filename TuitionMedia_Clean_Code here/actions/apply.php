<?php
require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/functions.php';
require_role('tutor');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('tuitions.php');
}

verify_csrf();

$tuitionId = (int) ($_POST['tuition_id'] ?? 0);
$message = trim($_POST['message'] ?? '');

$stmt = $pdo->prepare(
    'SELECT id, approval_status
     FROM tutor_profiles
     WHERE user_id = ?'
);
$stmt->execute([current_user()['id']]);
$tutorProfile = $stmt->fetch();

if (!$tutorProfile || $tutorProfile['approval_status'] !== 'approved') {
    set_flash('warning', 'Your tutor profile must be approved before applying.');
    redirect('tutor/profile.php');
}

$stmt = $pdo->prepare(
    "SELECT id, user_id, title
     FROM tuition_posts
     WHERE id = ? AND status = 'approved'"
);
$stmt->execute([$tuitionId]);
$tuitionPost = $stmt->fetch();

if (!$tuitionPost) {
    set_flash('danger', 'This tuition is not available.');
    redirect('tuitions.php');
}

try {
    $stmt = $pdo->prepare(
        'INSERT INTO applications (tuition_id, tutor_id, message)
         VALUES (?, ?, ?)'
    );
    $stmt->execute([
        $tuitionId,
        $tutorProfile['id'],
        $message,
    ]);

    notify(
        $pdo,
        (int) $tuitionPost['user_id'],
        current_user()['name'] . ' applied for your tuition: ' . $tuitionPost['title'],
        'seeker/applications.php'
    );

    set_flash('success', 'Application submitted successfully.');
} catch (PDOException $exception) {
    set_flash('warning', 'You have already applied to this tuition.');
}

redirect('tuition_view.php?id=' . $tuitionId);
