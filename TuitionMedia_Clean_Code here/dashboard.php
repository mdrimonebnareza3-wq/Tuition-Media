<?php
$pageTitle = 'Dashboard';
require __DIR__ . '/includes/header.php';
require_login();
$u = current_user();
?>
<div class="container py-5">
    <div class="profile-banner p-4 p-md-5 mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div><p class="mb-1 text-white-50"><?= e(role_label($u['role'])) ?> Account</p><h1 class="h2 fw-bold mb-0">Welcome, <?= e($u['name']) ?></h1></div>
            <a class="btn btn-light" href="<?= url('profile.php') ?>">Edit Basic Profile</a>
        </div>
    </div>

    <?php if ($u['role'] === 'admin'): ?>
        <?php
        $s = [
            'users' => $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
            'tutors' => $pdo->query("SELECT COUNT(*) FROM tutor_profiles WHERE approval_status='pending'")->fetchColumn(),
            'posts' => $pdo->query("SELECT COUNT(*) FROM tuition_posts WHERE status='pending'")->fetchColumn(),
            'apps' => $pdo->query('SELECT COUNT(*) FROM applications')->fetchColumn(),
        ];
        ?>
        <div class="row g-4">
            <div class="col-md-3"><div class="card shadow-soft p-4"><h3><?= (int)$s['users'] ?></h3><p class="text-muted mb-0">Total Users</p></div></div>
            <div class="col-md-3"><div class="card shadow-soft p-4"><h3><?= (int)$s['tutors'] ?></h3><p class="text-muted mb-0">Pending Tutors</p></div></div>
            <div class="col-md-3"><div class="card shadow-soft p-4"><h3><?= (int)$s['posts'] ?></h3><p class="text-muted mb-0">Pending Posts</p></div></div>
            <div class="col-md-3"><div class="card shadow-soft p-4"><h3><?= (int)$s['apps'] ?></h3><p class="text-muted mb-0">Applications</p></div></div>
        </div>
        <div class="row g-4 mt-1">
            <div class="col-md-3"><a class="card shadow-soft p-4 text-decoration-none" href="<?= url('admin/users.php') ?>"><h5>Manage Users</h5><span>Block, activate and review accounts</span></a></div>
            <div class="col-md-3"><a class="card shadow-soft p-4 text-decoration-none" href="<?= url('admin/tutors.php') ?>"><h5>Approve Tutors</h5><span>Review tutor profiles</span></a></div>
            <div class="col-md-3"><a class="card shadow-soft p-4 text-decoration-none" href="<?= url('admin/tuitions.php') ?>"><h5>Moderate Posts</h5><span>Approve or reject tuition posts</span></a></div>
            <div class="col-md-3"><a class="card shadow-soft p-4 text-decoration-none" href="<?= url('admin/applications.php') ?>"><h5>Applications</h5><span>Monitor matching activity</span></a></div>
        </div>

    <?php elseif ($u['role'] === 'tutor'): ?>
        <?php
        $stmt = $pdo->prepare('SELECT * FROM tutor_profiles WHERE user_id = ?');
        $stmt->execute([$u['id']]);
        $tp = $stmt->fetch();
        $appCount = 0;
        if ($tp) {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM applications WHERE tutor_id = ?');
            $stmt->execute([$tp['id']]);
            $appCount = (int)$stmt->fetchColumn();
        }
        ?>
        <div class="row g-4">
            <div class="col-md-4"><div class="card shadow-soft p-4"><h4>Profile Status</h4><p><?= $tp ? status_badge($tp['approval_status']) : '<span class="badge text-bg-secondary">Not created</span>' ?></p><a href="<?= url('tutor/profile.php') ?>" class="btn btn-primary"><?= $tp ? 'Update Tutor Profile' : 'Create Tutor Profile' ?></a></div></div>
            <div class="col-md-4"><div class="card shadow-soft p-4"><h4><?= $appCount ?></h4><p class="text-muted">Applications Submitted</p><a href="<?= url('tutor/applications.php') ?>">View applications</a></div></div>
            <div class="col-md-4"><div class="card shadow-soft p-4"><h4>Availability</h4><p class="text-muted">Set preferred days and time slots.</p><?php if ($tp): ?><a href="<?= url('tutor/availability.php') ?>">Manage availability</a><?php else: ?><span class="text-muted">Create your tutor profile first.</span><?php endif; ?></div></div>
        </div>

    <?php else: ?>
        <?php
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM tuition_posts WHERE user_id = ?');
        $stmt->execute([$u['id']]);
        $postCount = (int)$stmt->fetchColumn();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM applications a JOIN tuition_posts t ON t.id=a.tuition_id WHERE t.user_id=? AND a.status='pending'");
        $stmt->execute([$u['id']]);
        $pendingApps = (int)$stmt->fetchColumn();
        ?>
        <div class="row g-4">
            <div class="col-md-4"><div class="card shadow-soft p-4"><h4><?= $postCount ?></h4><p class="text-muted">Tuition Posts</p><a href="<?= url('seeker/my_posts.php') ?>">Manage posts</a></div></div>
            <div class="col-md-4"><div class="card shadow-soft p-4"><h4><?= $pendingApps ?></h4><p class="text-muted">Pending Applications</p><a href="<?= url('seeker/applications.php') ?>">Review applicants</a></div></div>
            <div class="col-md-4"><div class="card shadow-soft p-4"><h4>Create New Post</h4><p class="text-muted">Describe your tutor requirement.</p><a class="btn btn-primary" href="<?= url('seeker/post_form.php') ?>">Post Tuition</a></div></div>
        </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
