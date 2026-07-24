<?php
require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/functions.php';
require_role('tutor');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('tutor/availability.php');
}

verify_csrf();

$availabilityId = (int) ($_POST['id'] ?? 0);

$stmt = $pdo->prepare(
    'DELETE ta
     FROM tutor_availability ta
     JOIN tutor_profiles tp ON tp.id = ta.tutor_id
     WHERE ta.id = ? AND tp.user_id = ?'
);
$stmt->execute([
    $availabilityId,
    current_user()['id'],
]);

set_flash('success', 'Availability slot deleted.');
redirect('tutor/availability.php');
