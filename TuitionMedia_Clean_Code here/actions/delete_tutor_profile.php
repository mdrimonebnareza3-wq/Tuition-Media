<?php
require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/functions.php';
require_role('tutor');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('tutor/profile.php');
verify_csrf();

$uid = (int)current_user()['id'];
$stmt = $pdo->prepare('SELECT id FROM tutor_profiles WHERE user_id = ?');
$stmt->execute([$uid]);
$profileId = $stmt->fetchColumn();

if (!$profileId) {
    set_flash('warning', 'No tutor profile was found to delete.');
    redirect('tutor/profile.php');
}

$pdo->prepare('DELETE FROM tutor_profiles WHERE id = ? AND user_id = ?')->execute([(int)$profileId, $uid]);
notify($pdo, $uid, 'Your tutor profile was deleted. You may create a new one at any time.', 'tutor/profile.php');
notify_admins($pdo, current_user()['name'] . ' deleted a tutor profile.', 'admin/users.php');
set_flash('success', 'Tutor profile deleted. Your user account is still active.');
redirect('dashboard.php');
