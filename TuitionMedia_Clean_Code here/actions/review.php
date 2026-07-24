<?php
require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/functions.php';
require_role(['student','guardian']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('seeker/applications.php');
verify_csrf();

$applicationId = (int)($_POST['application_id'] ?? 0);
$rating = max(1, min(5, (int)($_POST['rating'] ?? 5)));
$comment = trim($_POST['comment'] ?? '');
if ($comment === '') {
    set_flash('danger', 'Please write a review comment.');
    redirect('seeker/applications.php');
}

$stmt = $pdo->prepare("SELECT a.*, t.user_id, tp.user_id AS tutor_user_id
    FROM applications a
    JOIN tuition_posts t ON t.id = a.tuition_id
    JOIN tutor_profiles tp ON tp.id = a.tutor_id
    WHERE a.id = ? AND t.user_id = ? AND a.status = 'completed' AND a.completed_at IS NOT NULL");
$stmt->execute([$applicationId, current_user()['id']]);
$app = $stmt->fetch();

if (!$app) {
    set_flash('danger', 'A review can be submitted only after the tuition is marked completed.');
    redirect('seeker/applications.php');
}

try {
    $pdo->prepare('INSERT INTO reviews(tuition_id,reviewer_id,tutor_id,rating,comment) VALUES(?,?,?,?,?)')
        ->execute([$app['tuition_id'], current_user()['id'], $app['tutor_id'], $rating, $comment]);
    notify($pdo, (int)$app['tutor_user_id'], 'You received a new tutor review.', 'tutor/applications.php');
    set_flash('success', 'Review submitted.');
} catch (PDOException $e) {
    set_flash('warning', 'You already reviewed this tutor for this tuition.');
}

redirect('seeker/applications.php');
