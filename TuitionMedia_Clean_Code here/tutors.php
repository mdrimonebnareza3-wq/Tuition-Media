<?php
$pageTitle = 'Find Tutors';
require __DIR__ . '/includes/header.php';

$q = trim($_GET['q'] ?? '');
$location = trim($_GET['location'] ?? '');
$mode = trim($_GET['mode'] ?? '');
$minSalaryRaw = trim($_GET['min_salary'] ?? '');
$maxSalaryRaw = trim($_GET['max_salary'] ?? '');
$minSalary = normalized_money($minSalaryRaw);
$maxSalary = normalized_money($maxSalaryRaw);

if ($minSalary !== null && $maxSalary !== null && $minSalary > $maxSalary) {
    [$minSalary, $maxSalary] = [$maxSalary, $minSalary];
}

$sql = "SELECT tp.*, u.name, u.phone,
        (SELECT COALESCE(AVG(r.rating), 0) FROM reviews r WHERE r.tutor_id = tp.id) AS avg_rating
        FROM tutor_profiles tp
        JOIN users u ON u.id = tp.user_id
        WHERE tp.approval_status = 'approved' AND u.status = 'active'";
$params = [];

if ($q !== '') {
    $sql .= ' AND (tp.subjects LIKE ? OR tp.classes LIKE ? OR u.name LIKE ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like);
}
if ($location !== '') {
    $sql .= ' AND tp.location LIKE ?';
    $params[] = '%' . $location . '%';
}
if ($mode !== '') {
    $sql .= ' AND tp.teaching_mode = ?';
    $params[] = $mode;
}
if ($minSalary !== null) {
    $sql .= ' AND tp.expected_salary >= ?';
    $params[] = $minSalary;
}
if ($maxSalary !== null) {
    $sql .= ' AND tp.expected_salary <= ?';
    $params[] = $maxSalary;
}

$sql .= ' ORDER BY avg_rating DESC, tp.updated_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
?>
<div class="container py-5">
    <div class="mb-4">
        <h1 class="section-title">Find Tutors</h1>
        <p class="text-muted">Search by subject, class, tutor name, location, teaching mode and expected salary range.</p>
    </div>

    <form class="filter-box shadow-soft p-4 mb-4" method="get">
        <div class="row g-3">
            <div class="col-lg-4"><label class="form-label">Subject, Class or Tutor</label><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Mathematics, Class 9 or tutor name"></div>
            <div class="col-lg-3"><label class="form-label">Location</label><input class="form-control" name="location" value="<?= e($location) ?>" placeholder="Cumilla"></div>
            <div class="col-lg-2"><label class="form-label">Teaching Mode</label><select class="form-select" name="mode"><option value="">Any mode</option><?php foreach (['Home Tutoring','Online','Both'] as $x): ?><option value="<?= e($x) ?>" <?= $mode === $x ? 'selected' : '' ?>><?= e($x) ?></option><?php endforeach; ?></select></div>
            <div class="col-lg-1 col-md-3"><label class="form-label">Min Salary</label><input type="number" min="0" step="500" class="form-control" name="min_salary" value="<?= e($minSalaryRaw) ?>" placeholder="5000"></div>
            <div class="col-lg-1 col-md-3"><label class="form-label">Max Salary</label><input type="number" min="0" step="500" class="form-control" name="max_salary" value="<?= e($maxSalaryRaw) ?>" placeholder="10000"></div>
            <div class="col-lg-1 col-md-3 d-grid align-self-end"><button class="btn btn-primary">Search</button></div>
            <div class="col-md-3 d-grid"><a class="btn btn-outline-secondary" href="<?= url('tutors.php') ?>">Reset Filters</a></div>
        </div>
    </form>

    <div class="row g-4">
        <?php if (!$rows): ?><div class="col-12"><div class="card empty-state shadow-soft">No approved tutors matched your search.</div></div><?php endif; ?>
        <?php foreach ($rows as $t): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow-soft p-4">
                    <div class="d-flex gap-3"><div class="avatar-circle"><?= e(strtoupper(substr($t['name'], 0, 1))) ?></div><div><h5 class="mb-1"><?= e($t['name']) ?></h5><div class="rating-stars">★ <?= number_format((float)$t['avg_rating'], 1) ?></div></div></div>
                    <hr>
                    <p class="mb-2"><strong>Subjects:</strong> <?= e($t['subjects']) ?></p>
                    <p class="mb-2"><strong>Classes:</strong> <?= e($t['classes']) ?></p>
                    <p class="mb-2"><strong>Area:</strong> <?= e($t['location']) ?></p>
                    <p class="mb-3"><strong>Expected Salary:</strong> ৳<?= number_format((float)$t['expected_salary']) ?></p>
                    <a class="btn btn-outline-primary mt-auto" href="<?= url('tutor_view.php?id=' . $t['id']) ?>">View Full Profile</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
