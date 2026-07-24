<?php
require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/functions.php';
require_role(['student', 'guardian']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('seeker/my_posts.php');
}

verify_csrf();

$postId = (int) ($_POST['id'] ?? 0);

$stmt = $pdo->prepare(
    'DELETE FROM tuition_posts
     WHERE id = ? AND user_id = ?'
);
$stmt->execute([
    $postId,
    current_user()['id'],
]);

set_flash('success', 'Tuition post deleted.');
redirect('seeker/my_posts.php');
