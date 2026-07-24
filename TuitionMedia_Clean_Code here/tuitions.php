<?php
$pageTitle = 'Find Tuitions';
require __DIR__ . '/includes/header.php';

$q = trim($_GET['q'] ?? '');
$location = trim($_GET['location'] ?? '');
$class = trim($_GET['class'] ?? '');
$mode = trim($_GET['mode'] ?? '');
$minSalaryRaw = trim($_GET['min_salary'] ?? '');
$maxSalaryRaw = trim($_GET['max_salary'] ?? '');
$minSalary = normalized_money($minSalaryRaw);
$maxSalary = normalized_money($maxSalaryRaw);

if ($minSalary !== null && $maxSalary !== null && $minSalary > $maxSalary) {
    [$minSalary, $maxSalary] = [$maxSalary, $minSalary];
}

$sql = "SELECT t.*, u.name
        FROM tuition_posts t
        JOIN users u ON u.id = t.user_id
        WHERE t.status = 'approved' AND u.status = 'active'";
$params = [];

if ($q !== '') {
    $sql .= ' AND (t.subject LIKE ? OR t.title LIKE ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like);
}
if ($location !== '') {
    $sql .= ' AND t.location LIKE ?';
    $params[] = '%' . $location . '%';
}
if ($class !== '') {
    $sql .= ' AND t.class_level LIKE ?';
    $params[] = '%' . $class . '%';
}
if ($mode !== '') {
    $sql .= ' AND t.teaching_mode = ?';
    $params[] = $mode;
}
// Salary-range overlap: a post is shown when its range overlaps the requested range.
if ($minSalary !== null) {
    $sql .= ' AND t.salary_max >= ?';
    $params[] = $minSalary;
}
if ($maxSalary !== null) {
    $sql .= ' AND t.salary_min <= ?';
    $params[] = $maxSalary;
}

$sql .= ' ORDER BY t.created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
?>
<div class="container py-5">
    <h1 class="section-title">Find Tuition Opportunities</h1>
    <p class="text-muted mb-4">Approved tuition requirements posted by students and guardians.</p>

    <form class="filter-box shadow-soft p-4 mb-4" method="get">
        <div class="row g-3">
            <div class="col-lg-3"><label class="form-label">Subject or Keyword</label><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Mathematics"></div>
            <div class="col-lg-2"><label class="form-label">Class Level</label><input class="form-control" name="class" value="<?= e($class) ?>" placeholder="Class 9"></div>
            <div class="col-lg-2"><label class="form-label">Location</label><input class="form-control" name="location" value="<?= e($location) ?>" placeholder="Cumilla"></div>
            <div class="col-lg-2"><label class="form-label">Teaching Mode</label><select class="form-select" name="mode"><option value="">Any mode</option><?php foreach (['Home Tutoring','Online','Both'] as $x): ?><option value="<?= e($x) ?>" <?= $mode === $x ? 'selected' : '' ?>><?= e($x) ?></option><?php endforeach; ?></select></div>
            <div class="col-lg-1 col-md-3"><label class="form-label">Min Salary</label><input type="number" min="0" step="500" class="form-control" name="min_salary" value="<?= e($minSalaryRaw) ?>"></div>
            <div class="col-lg-1 col-md-3"><label class="form-label">Max Salary</label><input type="number" min="0" step="500" class="form-control" name="max_salary" value="<?= e($maxSalaryRaw) ?>"></div>
            <div class="col-lg-1 col-md-3 d-grid align-self-end"><button class="btn btn-primary">Search</button></div>
            <div class="col-md-3 d-grid"><a class="btn btn-outline-secondary" href="<?= url('tuitions.php') ?>">Reset Filters</a></div>
        </div>
    </form>

    <div class="row g-4">
        <?php if (!$rows): ?><div class="col-12"><div class="card empty-state shadow-soft">No active tuition posts matched your search.</div></div><?php endif; ?>
        <?php foreach ($rows as $p): ?>
            <div class="col-md-6">
                <div class="card shadow-soft h-100 p-4">
                    <div class="d-flex justify-content-between"><span class="badge text-bg-info"><?= e($p['subject']) ?></span><small class="text-muted"><?= date('d M Y', strtotime($p['created_at'])) ?></small></div>
                    <h4 class="mt-3"><?= e($p['title']) ?></h4>
                    <p class="text-muted"><?= e($p['class_level']) ?> • <?= e($p['location']) ?> • <?= e($p['teaching_mode']) ?></p>
                    <div class="row small"><div class="col-6"><strong>Salary</strong><br>৳<?= number_format((float)$p['salary_min']) ?>–৳<?= number_format((float)$p['salary_max']) ?></div><div class="col-6"><strong>Schedule</strong><br><?= e((string)$p['days_per_week']) ?> days/week, <?= e($p['preferred_time']) ?></div></div>
                    <a class="btn btn-outline-primary mt-4" href="<?= url('tuition_view.php?id=' . $p['id']) ?>">View and Apply</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
