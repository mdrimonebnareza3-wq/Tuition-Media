<?php
$pageTitle = 'Tutor Profile Setup';
require __DIR__ . '/../includes/header.php';
require_role('tutor');

$uid = (int)current_user()['id'];
$stmt = $pdo->prepare('SELECT * FROM tutor_profiles WHERE user_id = ?');
$stmt->execute([$uid]);
$t = $stmt->fetch();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $fields = ['gender','education','university','subjects','classes','location','experience_years','expected_salary','teaching_mode','bio'];
    $data = [];
    foreach ($fields as $field) $data[$field] = trim($_POST[$field] ?? '');

    if ($data['subjects'] === '' || $data['classes'] === '' || $data['location'] === '') {
        $errors[] = 'Subjects, class levels and location are required.';
    }
    if (!in_array($data['gender'], ['Male','Female','Any'], true)) $errors[] = 'Select a valid gender option.';
    if (!in_array($data['teaching_mode'], ['Home Tutoring','Online','Both'], true)) $errors[] = 'Select a valid teaching mode.';
    if ((int)$data['experience_years'] < 0) $errors[] = 'Experience cannot be negative.';
    if ((float)$data['expected_salary'] < 0) $errors[] = 'Expected salary cannot be negative.';

    if (!$errors) {
        if ($t) {
            $stmt = $pdo->prepare("UPDATE tutor_profiles
                SET gender=?, education=?, university=?, subjects=?, classes=?, location=?,
                    experience_years=?, expected_salary=?, teaching_mode=?, bio=?, approval_status='pending'
                WHERE user_id=?");
            $stmt->execute([
                $data['gender'], $data['education'], $data['university'], $data['subjects'], $data['classes'],
                $data['location'], (int)$data['experience_years'], (float)$data['expected_salary'],
                $data['teaching_mode'], $data['bio'], $uid,
            ]);
            $message = 'Your tutor profile update was submitted for admin approval.';
            $adminMessage = current_user()['name'] . ' updated a tutor profile and it is waiting for approval.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO tutor_profiles
                (user_id, gender, education, university, subjects, classes, location, experience_years,
                 expected_salary, teaching_mode, bio, approval_status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([
                $uid, $data['gender'], $data['education'], $data['university'], $data['subjects'], $data['classes'],
                $data['location'], (int)$data['experience_years'], (float)$data['expected_salary'],
                $data['teaching_mode'], $data['bio'],
            ]);
            $message = 'Your tutor profile was created and submitted for admin approval.';
            $adminMessage = current_user()['name'] . ' created a tutor profile and it is waiting for approval.';
        }

        notify($pdo, $uid, $message, 'tutor/profile.php');
        notify_admins($pdo, $adminMessage, 'admin/tutors.php');
        set_flash('success', $message);
        redirect('tutor/profile.php');
    }
}

$value = function (string $key, string $default = '') use ($t): string {
    return e($_POST[$key] ?? ($t[$key] ?? $default));
};
$status = $t['approval_status'] ?? 'not created';
?>
<div class="container py-5">
    <div class="card shadow-soft p-4 p-md-5">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <h1 class="section-title h2">Tutor Profile</h1>
                <p class="text-muted">Create or update your professional profile. Every change is sent to the administrator for approval.</p>
            </div>
            <?= $t ? status_badge($status) : '<span class="badge text-bg-secondary">Not created</span>' ?>
        </div>

        <?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>

        <form method="post" class="row g-3">
            <?= csrf_field() ?>
            <div class="col-md-4"><label class="form-label">Gender</label><select class="form-select" name="gender"><option value="Any" <?= $value('gender','Any') === 'Any' ? 'selected' : '' ?>>Prefer not to say</option><option value="Male" <?= $value('gender') === 'Male' ? 'selected' : '' ?>>Male</option><option value="Female" <?= $value('gender') === 'Female' ? 'selected' : '' ?>>Female</option></select></div>
            <div class="col-md-4"><label class="form-label">Education</label><input class="form-control" name="education" value="<?= $value('education') ?>" placeholder="BSc in CSE"></div>
            <div class="col-md-4"><label class="form-label">Institution</label><input class="form-control" name="university" value="<?= $value('university') ?>"></div>
            <div class="col-md-6"><label class="form-label">Subjects</label><input class="form-control" name="subjects" value="<?= $value('subjects') ?>" placeholder="Mathematics, Physics, ICT" required></div>
            <div class="col-md-6"><label class="form-label">Class Levels</label><input class="form-control" name="classes" value="<?= $value('classes') ?>" placeholder="Class 6-10, HSC" required></div>
            <div class="col-md-6"><label class="form-label">Preferred Location</label><input class="form-control" name="location" value="<?= $value('location') ?>" required></div>
            <div class="col-md-3"><label class="form-label">Experience (years)</label><input type="number" min="0" class="form-control" name="experience_years" value="<?= $value('experience_years','0') ?>"></div>
            <div class="col-md-3"><label class="form-label">Expected Salary</label><input type="number" min="0" step="500" class="form-control" name="expected_salary" value="<?= $value('expected_salary','0') ?>"></div>
            <div class="col-md-4"><label class="form-label">Teaching Mode</label><select class="form-select" name="teaching_mode"><?php foreach (['Home Tutoring','Online','Both'] as $mode): ?><option value="<?= e($mode) ?>" <?= $value('teaching_mode','Home Tutoring') === $mode ? 'selected' : '' ?>><?= e($mode) ?></option><?php endforeach; ?></select></div>
            <div class="col-12"><label class="form-label">Professional Bio</label><textarea class="form-control" name="bio" rows="5"><?= $value('bio') ?></textarea></div>
            <div class="col-12"><button class="btn btn-primary"><?= $t ? 'Update Tutor Profile' : 'Create Tutor Profile' ?></button></div>
        </form>

        <?php if ($t): ?>
            <hr class="my-4">
            <div class="border border-danger-subtle rounded p-3">
                <h5 class="text-danger">Delete Tutor Profile</h5>
                <p class="text-muted mb-3">This removes the professional profile, availability, applications and reviews linked to it. Your login account will remain active and you can create a new tutor profile later.</p>
                <form method="post" action="<?= url('actions/delete_tutor_profile.php') ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn-outline-danger" data-confirm="Delete your tutor profile and all linked tutor data? This cannot be undone.">Delete Tutor Profile</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
