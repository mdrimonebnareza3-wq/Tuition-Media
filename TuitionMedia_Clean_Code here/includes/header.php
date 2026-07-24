<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
$flash = get_flash();
if (is_logged_in()) {
    refresh_session_user($pdo);
    if ((current_user()['status'] ?? 'blocked') !== 'active') {
        session_unset();
        set_flash('danger', 'Your account has been blocked by the administrator.');
        redirect('login.php');
    }
}
$pageTitle = $pageTitle ?? APP_NAME;
$unread = is_logged_in() ? unread_notification_count($pdo, (int)current_user()['id']) : 0;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark sticky-top tm-navbar shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= url() ?>"><span class="brand-mark">TM</span> Tuition Media</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="<?= url() ?>">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= url('tutors.php') ?>">Find Tutors</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= url('tuitions.php') ?>">Find Tuitions</a></li>
            </ul>
            <ul class="navbar-nav align-items-lg-center gap-lg-2">
                <?php if (is_logged_in()): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= url('notifications.php') ?>">Notifications<?php if ($unread): ?><span class="badge rounded-pill text-bg-danger ms-1"><?= $unread ?></span><?php endif; ?></a></li>
                    <li class="nav-item"><a class="btn btn-light btn-sm px-3" href="<?= url('dashboard.php') ?>">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= url('logout.php') ?>">Logout</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="<?= url('login.php') ?>">Login</a></li>
                    <li class="nav-item"><a class="btn btn-warning btn-sm px-3" href="<?= url('register.php') ?>">Create Account</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<?php if ($flash): ?>
<div class="container mt-3"><div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show shadow-sm" role="alert"><?= e($flash['message']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div></div>
<?php endif; ?>
