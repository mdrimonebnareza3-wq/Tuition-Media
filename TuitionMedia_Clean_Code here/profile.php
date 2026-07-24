<?php
$pageTitle = 'My Profile';
require __DIR__ . '/includes/header.php';
require_login();

$user = current_user();
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));

    if ($name === '') $errors[] = 'Name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';

    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id <> ?');
    $stmt->execute([$email, $user['id']]);
    if ($stmt->fetch()) $errors[] = 'This email is already used by another account.';

    if (!$errors) {
        $stmt = $pdo->prepare('UPDATE users SET name = ?, phone = ?, email = ? WHERE id = ?');
        $stmt->execute([$name, $phone, $email, $user['id']]);
        refresh_session_user($pdo);
        notify($pdo, (int)$user['id'], 'Your basic profile information was updated.', 'profile.php');
        set_flash('success', 'Profile updated successfully. Use the new email address for your next login.');
        redirect('profile.php');
    }
}
?>
<div class="container py-5"><div class="row justify-content-center"><div class="col-lg-7"><div class="card shadow-soft p-4 p-md-5"><h2 class="section-title">Basic Profile</h2><?php if($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?><form method="post" class="row g-3"><?= csrf_field() ?><div class="col-md-6"><label class="form-label">Full Name</label><input class="form-control" name="name" value="<?= e($_POST['name'] ?? current_user()['name']) ?>" required></div><div class="col-md-6"><label class="form-label">Phone</label><input class="form-control" name="phone" value="<?= e($_POST['phone'] ?? current_user()['phone']) ?>"></div><div class="col-12"><label class="form-label">Email</label><input type="email" class="form-control" name="email" value="<?= e($_POST['email'] ?? current_user()['email']) ?>" required><div class="form-text">Changing this email also changes the email used for login.</div></div><div class="col-12"><button class="btn btn-primary">Save Changes</button></div></form></div></div></div></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
