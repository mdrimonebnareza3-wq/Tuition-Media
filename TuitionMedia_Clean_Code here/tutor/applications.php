<?php
$pageTitle = 'My Applications';
require __DIR__ . '/../includes/header.php';
require_role('tutor');

$stmt = $pdo->prepare('SELECT id FROM tutor_profiles WHERE user_id = ?');
$stmt->execute([current_user()['id']]);
$tutorId = (int) $stmt->fetchColumn();
$rows = [];

if ($tutorId) {
    $stmt = $pdo->prepare(
        'SELECT a.*, t.title, t.subject, t.location, t.salary_min, t.salary_max
         FROM applications a
         JOIN tuition_posts t ON t.id = a.tuition_id
         WHERE a.tutor_id = ?
         ORDER BY a.created_at DESC'
    );
    $stmt->execute([$tutorId]);
    $rows = $stmt->fetchAll();
}
?>

<div class="container py-5">
    <h1 class="section-title">My Applications</h1>

    <div class="card shadow-soft table-card mt-4">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Tuition</th>
                        <th>Location</th>
                        <th>Salary</th>
                        <th>Status</th>
                        <th>Timeline</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (!$rows): ?>
                        <tr>
                            <td colspan="5" class="empty-state">
                                You have not applied to any tuition yet.
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td>
                                <a href="<?= url('tuition_view.php?id=' . $row['tuition_id']) ?>">
                                    <strong><?= e($row['title']) ?></strong>
                                </a>
                                <br>
                                <small><?= e($row['subject']) ?></small>
                            </td>

                            <td><?= e($row['location']) ?></td>

                            <td>
                                ৳<?= number_format((float) $row['salary_min']) ?>–
                                ৳<?= number_format((float) $row['salary_max']) ?>
                            </td>

                            <td>
                                <?= status_badge($row['status']) ?>

                                <?php if ($row['status'] === 'cancelled'): ?>
                                    <div class="small text-danger mt-2">
                                        <strong>Reason:</strong><br>
                                        <?= e($row['cancellation_reason'] ?? '') ?>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td>
                                <small>
                                    Applied:
                                    <?= date('d M Y', strtotime($row['created_at'])) ?>

                                    <?php if ($row['accepted_at']): ?>
                                        <br>
                                        Accepted:
                                        <?= date('d M Y', strtotime($row['accepted_at'])) ?>
                                    <?php endif; ?>

                                    <?php if ($row['completed_at']): ?>
                                        <br>
                                        Completed:
                                        <?= date('d M Y', strtotime($row['completed_at'])) ?>
                                    <?php endif; ?>

                                    <?php if ($row['cancelled_at']): ?>
                                        <br>
                                        Cancelled:
                                        <?= date('d M Y', strtotime($row['cancelled_at'])) ?>
                                    <?php endif; ?>
                                </small>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
