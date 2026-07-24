<?php
$pageTitle = 'All Applications';
require __DIR__ . '/../includes/header.php';
require_role('admin');

$rows = $pdo->query(
    'SELECT a.*, t.title, owner.name AS owner_name, tutor.name AS tutor_name
     FROM applications a
     JOIN tuition_posts t ON t.id = a.tuition_id
     JOIN users owner ON owner.id = t.user_id
     JOIN tutor_profiles tp ON tp.id = a.tutor_id
     JOIN users tutor ON tutor.id = tp.user_id
     ORDER BY a.created_at DESC'
)->fetchAll();
?>

<div class="container py-5">
    <h1 class="section-title">All Applications</h1>
    <p class="text-muted">
        Monitor pending, accepted, rejected, completed and cancelled tuition matching activity.
    </p>

    <div class="card shadow-soft table-card mt-4">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Tutor</th>
                        <th>Tuition / Owner</th>
                        <th>Status</th>
                        <th>Timeline</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (!$rows): ?>
                        <tr>
                            <td colspan="4" class="empty-state">
                                No applications found.
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?= e($row['tutor_name']) ?></td>

                            <td>
                                <strong><?= e($row['title']) ?></strong><br>
                                <small><?= e($row['owner_name']) ?></small>
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
