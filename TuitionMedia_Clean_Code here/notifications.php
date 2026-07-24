<?php
$pageTitle = 'Notifications';
require __DIR__ . '/includes/header.php';
require_login();

$user = current_user();
$userId = (int)$user['id'];
$isAdmin = $user['role'] === 'admin';

$pdo->prepare(
    'UPDATE notifications SET is_read = 1 WHERE user_id = ?'
)->execute([$userId]);

$stmt = $pdo->prepare(
    'SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC'
);
$stmt->execute([$userId]);
$rows = $stmt->fetchAll();

$tuitionPosts = [];
$tuitionList = [];

if ($isAdmin) {
    $tuitionList = $pdo->query(
        'SELECT id, title, status FROM tuition_posts ORDER BY created_at DESC'
    )->fetchAll();

    foreach ($tuitionList as $post) {
        $tuitionPosts[(int)$post['id']] = $post;
    }
}
?>

<div class="container py-5">
    <h1 class="section-title">Notifications</h1>

    <div class="card shadow-soft mt-4">
        <?php if (!$rows): ?>
            <div class="empty-state">You have no notifications.</div>
        <?php endif; ?>

        <?php foreach ($rows as $notification): ?>
            <?php
            $relatedPost = null;

            if ($isAdmin) {
                $entityId = (int)($notification['entity_id'] ?? 0);

                if (
                    ($notification['entity_type'] ?? '') === 'tuition'
                    && $entityId > 0
                    && isset($tuitionPosts[$entityId])
                ) {
                    $relatedPost = $tuitionPosts[$entityId];
                } elseif (($notification['link'] ?? '') === 'admin/tuitions.php') {
                    foreach ($tuitionList as $post) {
                        if (
                            $post['title'] !== ''
                            && stripos($notification['message'], $post['title']) !== false
                        ) {
                            $relatedPost = $post;
                            break;
                        }
                    }
                }
            }
            ?>

            <div class="p-4 border-bottom">
                <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
                    <div class="flex-grow-1">
                        <p class="mb-2"><?= e($notification['message']) ?></p>

                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <?php if ($relatedPost): ?>
                                <a
                                    class="btn btn-sm btn-outline-primary"
                                    href="<?= url('tuition_view.php?id=' . $relatedPost['id']) ?>"
                                >
                                    Open Details
                                </a>

                                <?= status_badge($relatedPost['status']) ?>

                                <?php if ($relatedPost['status'] === 'pending'): ?>
                                    <form
                                        method="post"
                                        action="<?= url('actions/admin_action.php') ?>"
                                        class="d-inline"
                                    >
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="type" value="tuition">
                                        <input type="hidden" name="id" value="<?= (int)$relatedPost['id'] ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="return_to" value="notifications.php">
                                        <input type="hidden" name="notification_id" value="<?= (int)$notification['id'] ?>">
                                        <button class="btn btn-sm btn-success">Approve</button>
                                    </form>

                                    <form
                                        method="post"
                                        action="<?= url('actions/admin_action.php') ?>"
                                        class="d-inline"
                                    >
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="type" value="tuition">
                                        <input type="hidden" name="id" value="<?= (int)$relatedPost['id'] ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <input type="hidden" name="return_to" value="notifications.php">
                                        <input type="hidden" name="notification_id" value="<?= (int)$notification['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger">Reject</button>
                                    </form>
                                <?php endif; ?>
                            <?php elseif (!empty($notification['link'])): ?>
                                <a href="<?= url($notification['link']) ?>">Open details</a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <small class="text-muted text-nowrap">
                        <?= date('d M, g:i A', strtotime($notification['created_at'])) ?>
                    </small>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
