<?php
$pageTitle = 'Manage Availability';
require __DIR__ . '/../includes/header.php';
require_role('tutor');

$stmt = $pdo->prepare('SELECT id FROM tutor_profiles WHERE user_id = ?');
$stmt->execute([current_user()['id']]);
$tid = (int)$stmt->fetchColumn();
if (!$tid) {
    set_flash('warning', 'Create your tutor profile before adding availability.');
    redirect('tutor/profile.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $day = $_POST['day_of_week'] ?? '';
    $start = $_POST['start_time'] ?? '';
    $end = $_POST['end_time'] ?? '';
    $mode = $_POST['teaching_mode'] ?? '';

    if (!in_array($day, ['Saturday','Sunday','Monday','Tuesday','Wednesday','Thursday','Friday'], true)
        || !in_array($mode, ['Home Tutoring','Online','Both'], true)
        || !$start || !$end) {
        set_flash('danger', 'Please provide a valid availability slot.');
    } elseif ($start >= $end) {
        set_flash('danger', 'End time must be later than start time.');
    } else {
        $pdo->prepare('INSERT INTO tutor_availability(tutor_id,day_of_week,start_time,end_time,teaching_mode) VALUES(?,?,?,?,?)')->execute([$tid,$day,$start,$end,$mode]);
        set_flash('success', 'Availability slot added.');
    }
    redirect('tutor/availability.php');
}

$stmt = $pdo->prepare('SELECT * FROM tutor_availability WHERE tutor_id=? ORDER BY FIELD(day_of_week,"Saturday","Sunday","Monday","Tuesday","Wednesday","Thursday","Friday"),start_time');
$stmt->execute([$tid]);
$rows = $stmt->fetchAll();
?>
<div class="container py-5"><div class="row g-4"><div class="col-lg-5"><div class="card shadow-soft p-4"><h2 class="section-title h4">Add Availability</h2><form method="post" class="vstack gap-3"><?= csrf_field() ?><div><label class="form-label">Day</label><select class="form-select" name="day_of_week" required><?php foreach(['Saturday','Sunday','Monday','Tuesday','Wednesday','Thursday','Friday'] as $d): ?><option><?= e($d) ?></option><?php endforeach; ?></select></div><div class="row g-3"><div class="col-6"><label class="form-label">Start</label><input type="time" class="form-control" name="start_time" required></div><div class="col-6"><label class="form-label">End</label><input type="time" class="form-control" name="end_time" required></div></div><div><label class="form-label">Mode</label><select class="form-select" name="teaching_mode"><option>Home Tutoring</option><option>Online</option><option>Both</option></select></div><button class="btn btn-primary">Add Slot</button></form></div></div><div class="col-lg-7"><div class="card shadow-soft p-4"><h2 class="section-title h4">Your Weekly Schedule</h2><?php if(!$rows): ?><div class="empty-state">No availability added.</div><?php endif; foreach($rows as $r): ?><div class="d-flex justify-content-between align-items-center border-bottom py-3"><div><strong><?=e($r['day_of_week'])?></strong><br><small><?=date('g:i A',strtotime($r['start_time']))?> – <?=date('g:i A',strtotime($r['end_time']))?> • <?=e($r['teaching_mode'])?></small></div><form method="post" action="<?=url('actions/delete_availability.php')?>"><?=csrf_field()?><input type="hidden" name="id" value="<?=$r['id']?>"><button class="btn btn-sm btn-outline-danger" data-confirm="Delete this slot?">Delete</button></form></div><?php endforeach; ?></div></div></div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
