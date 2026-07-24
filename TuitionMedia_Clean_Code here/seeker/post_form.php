<?php
$pageTitle = 'Create Tuition Post';
require __DIR__ . '/../includes/header.php';
require_role(['student','guardian']);

$uid = (int)current_user()['id'];
$id = (int)($_GET['id'] ?? 0);
$edit = null;
if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM tuition_posts WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $uid]);
    $edit = $stmt->fetch();
    if (!$edit) {
        http_response_code(404);
        exit('Post not found.');
    }
    $pageTitle = 'Edit Tuition Post';
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $fields = ['title','subject','class_level','location','salary_min','salary_max','preferred_gender','teaching_mode','days_per_week','preferred_time','description'];
    $data = [];
    foreach ($fields as $field) $data[$field] = trim($_POST[$field] ?? '');

    if ($data['title'] === '' || $data['subject'] === '' || $data['class_level'] === '' || $data['location'] === '') {
        $errors[] = 'Title, subject, class level and location are required.';
    }
    if (!is_numeric($data['salary_min']) || !is_numeric($data['salary_max'])) {
        $errors[] = 'Enter valid minimum and maximum salary amounts.';
    } elseif ((float)$data['salary_min'] > (float)$data['salary_max']) {
        $errors[] = 'Minimum salary cannot be greater than maximum salary.';
    }
    if (!in_array($data['preferred_gender'], ['Any','Male','Female'], true)) $errors[] = 'Select a valid preferred tutor gender.';
    if (!in_array($data['teaching_mode'], ['Home Tutoring','Online','Both'], true)) $errors[] = 'Select a valid teaching mode.';
    if ((int)$data['days_per_week'] < 1 || (int)$data['days_per_week'] > 7) $errors[] = 'Days per week must be between 1 and 7.';

    if (!$errors) {
        if ($edit) {
            $stmt = $pdo->prepare("UPDATE tuition_posts
                SET title=?,subject=?,class_level=?,location=?,salary_min=?,salary_max=?,preferred_gender=?,
                    teaching_mode=?,days_per_week=?,preferred_time=?,description=?,status='pending'
                WHERE id=? AND user_id=?");
            $stmt->execute([
                $data['title'], $data['subject'], $data['class_level'], $data['location'],
                (float)$data['salary_min'], (float)$data['salary_max'], $data['preferred_gender'],
                $data['teaching_mode'], (int)$data['days_per_week'], $data['preferred_time'],
                $data['description'], $id, $uid,
            ]);
            $postId = $id;
            $message = 'Tuition post updated and sent for admin approval.';
            $adminMessage = current_user()['name'] . ' updated a tuition post: ' . $data['title'];
        } else {
            $stmt = $pdo->prepare("INSERT INTO tuition_posts
                (user_id,title,subject,class_level,location,salary_min,salary_max,preferred_gender,teaching_mode,
                 days_per_week,preferred_time,description,status)
                VALUES(?,?,?,?,?,?,?,?,?,?,?,?, 'pending')");
            $stmt->execute([
                $uid, $data['title'], $data['subject'], $data['class_level'], $data['location'],
                (float)$data['salary_min'], (float)$data['salary_max'], $data['preferred_gender'],
                $data['teaching_mode'], (int)$data['days_per_week'], $data['preferred_time'], $data['description'],
            ]);
            $postId = (int)$pdo->lastInsertId();
            $message = 'Tuition post created and sent for admin approval.';
            $adminMessage = current_user()['name'] . ' created a new tuition post: ' . $data['title'];
        }

        notify($pdo, $uid, $message, 'seeker/my_posts.php');
        notify_admins(
            $pdo,
            $adminMessage . '. It is waiting for approval.',
            'admin/tuitions.php',
            'tuition',
            $postId
        );
        set_flash('success', $message);
        redirect('seeker/my_posts.php');
    }
}

$value = function (string $key, string $default = '') use ($edit): string {
    return e($_POST[$key] ?? ($edit[$key] ?? $default));
};
$selected = function (string $key, string $option, string $default = '') use ($edit): string {
    $current = $_POST[$key] ?? ($edit[$key] ?? $default);
    return $current === $option ? 'selected' : '';
};
?>
<div class="container py-5">
    <div class="card shadow-soft p-4 p-md-5">
        <h1 class="section-title h2"><?= $edit ? 'Edit Tuition Post' : 'Create Tuition Post' ?></h1>
        <p class="text-muted">Provide clear information so suitable tutors can find and apply for the opportunity.</p>
        <?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>

        <form method="post" class="row g-3">
            <?= csrf_field() ?>
            <div class="col-12"><label class="form-label">Post Title</label><input class="form-control" name="title" value="<?= $value('title') ?>" placeholder="Need a Mathematics tutor for Class 9" required></div>
            <div class="col-md-6"><label class="form-label">Subject</label><input class="form-control" name="subject" value="<?= $value('subject') ?>" required></div>
            <div class="col-md-6"><label class="form-label">Class Level</label><input class="form-control" name="class_level" value="<?= $value('class_level') ?>" required></div>
            <div class="col-md-6"><label class="form-label">Location</label><input class="form-control" name="location" value="<?= $value('location') ?>" required></div>
            <div class="col-md-3"><label class="form-label">Minimum Salary</label><input type="number" min="0" step="500" class="form-control" name="salary_min" value="<?= $value('salary_min','0') ?>" required></div>
            <div class="col-md-3"><label class="form-label">Maximum Salary</label><input type="number" min="0" step="500" class="form-control" name="salary_max" value="<?= $value('salary_max','0') ?>" required></div>
            <div class="col-md-4"><label class="form-label">Preferred Tutor Gender</label><select class="form-select" name="preferred_gender"><?php foreach (['Any','Male','Female'] as $option): ?><option value="<?= e($option) ?>" <?= $selected('preferred_gender',$option,'Any') ?>><?= e($option) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-4"><label class="form-label">Teaching Mode</label><select class="form-select" name="teaching_mode"><?php foreach (['Home Tutoring','Online','Both'] as $option): ?><option value="<?= e($option) ?>" <?= $selected('teaching_mode',$option,'Home Tutoring') ?>><?= e($option) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-4"><label class="form-label">Days per Week</label><input type="number" min="1" max="7" class="form-control" name="days_per_week" value="<?= $value('days_per_week','3') ?>" required></div>
            <div class="col-md-6"><label class="form-label">Preferred Time</label><input class="form-control" name="preferred_time" value="<?= $value('preferred_time') ?>" placeholder="6:00 PM - 8:00 PM"></div>
            <div class="col-12"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="5"><?= $value('description') ?></textarea></div>
            <div class="col-12"><button class="btn btn-primary"><?= $edit ? 'Update Post' : 'Submit Post' ?></button></div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
