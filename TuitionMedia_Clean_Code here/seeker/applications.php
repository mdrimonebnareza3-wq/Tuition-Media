<?php
$pageTitle = 'Review Applicants';
require __DIR__ . '/../includes/header.php';
require_role(['student', 'guardian']);

$uid = (int) current_user()['id'];
$stmt = $pdo->prepare(
    "SELECT a.*, t.title, t.id AS tuition_id, t.status AS tuition_status,
            tp.id AS tutor_profile_id, tp.subjects, tp.experience_years,
            u.name AS tutor_name, r.id AS review_id
     FROM applications a
     JOIN tuition_posts t ON t.id = a.tuition_id
     JOIN tutor_profiles tp ON tp.id = a.tutor_id
     JOIN users u ON u.id = tp.user_id
     LEFT JOIN reviews r
        ON r.tuition_id = a.tuition_id
       AND r.reviewer_id = t.user_id
       AND r.tutor_id = a.tutor_id
     WHERE t.user_id = ?
     ORDER BY a.created_at DESC"
);
$stmt->execute([$uid]);
$rows = $stmt->fetchAll();
?>

<div class="container py-5">
    <h1 class="section-title">Review Applicants</h1>
    <p class="text-muted">
        Accept or reject applications, complete a tuition, or cancel an accepted tuition when it will not continue.
    </p>

    <div class="row g-4 mt-1">
        <?php if (!$rows): ?>
            <div class="col-12">
                <div class="card empty-state shadow-soft">
                    No applications received yet.
                </div>
            </div>
        <?php endif; ?>

        <?php foreach ($rows as $row): ?>
            <div class="col-lg-6">
                <div class="card shadow-soft p-4 h-100">
                    <div class="d-flex justify-content-between gap-3">
                        <div>
                            <h5><?= e($row['tutor_name']) ?></h5>
                            <p class="text-muted mb-1">
                                Applied for: <?= e($row['title']) ?>
                            </p>
                        </div>
                        <?= status_badge($row['status']) ?>
                    </div>

                    <p>
                        <strong>Subjects:</strong> <?= e($row['subjects']) ?><br>
                        <strong>Experience:</strong>
                        <?= e((string) $row['experience_years']) ?> years
                    </p>

                    <div class="bg-light rounded p-3 mb-3">
                        <?= nl2br(e($row['message'])) ?>
                    </div>

                    <?php if ($row['accepted_at']): ?>
                        <small class="text-muted d-block mb-2">
                            Accepted:
                            <?= date('d M Y, g:i A', strtotime($row['accepted_at'])) ?>
                        </small>
                    <?php endif; ?>

                    <?php if ($row['completed_at']): ?>
                        <small class="text-muted d-block mb-2">
                            Completed:
                            <?= date('d M Y, g:i A', strtotime($row['completed_at'])) ?>
                        </small>
                    <?php endif; ?>

                    <?php if ($row['cancelled_at']): ?>
                        <div class="alert alert-danger py-2">
                            <strong>Cancelled:</strong>
                            <?= date('d M Y, g:i A', strtotime($row['cancelled_at'])) ?>
                            <br>
                            <strong>Reason:</strong>
                            <?= e($row['cancellation_reason'] ?? '') ?>
                        </div>
                    <?php endif; ?>

                    <div class="d-flex gap-2 flex-wrap">
                        <a
                            class="btn btn-outline-primary btn-sm"
                            href="<?= url('tutor_view.php?id=' . $row['tutor_profile_id']) ?>"
                        >
                            View Tutor
                        </a>

                        <?php if ($row['status'] === 'pending'): ?>
                            <form
                                method="post"
                                action="<?= url('actions/application_action.php') ?>"
                            >
                                <?= csrf_field() ?>
                                <input
                                    type="hidden"
                                    name="application_id"
                                    value="<?= (int) $row['id'] ?>"
                                >
                                <input type="hidden" name="action" value="accept">
                                <button
                                    class="btn btn-success btn-sm"
                                    data-confirm="Accept this tutor? The tuition post will be closed to new applications."
                                >
                                    Accept
                                </button>
                            </form>

                            <form
                                method="post"
                                action="<?= url('actions/application_action.php') ?>"
                            >
                                <?= csrf_field() ?>
                                <input
                                    type="hidden"
                                    name="application_id"
                                    value="<?= (int) $row['id'] ?>"
                                >
                                <input type="hidden" name="action" value="reject">
                                <button class="btn btn-outline-danger btn-sm">
                                    Reject
                                </button>
                            </form>
                        <?php elseif ($row['status'] === 'accepted'): ?>
                            <form
                                method="post"
                                action="<?= url('actions/application_action.php') ?>"
                            >
                                <?= csrf_field() ?>
                                <input
                                    type="hidden"
                                    name="application_id"
                                    value="<?= (int) $row['id'] ?>"
                                >
                                <input type="hidden" name="action" value="complete">
                                <button
                                    class="btn btn-warning btn-sm"
                                    data-confirm="Confirm that this tuition session or agreement has been completed?"
                                >
                                    Mark Tuition Completed
                                </button>
                            </form>

                            <button
                                class="btn btn-outline-danger btn-sm"
                                data-bs-toggle="modal"
                                data-bs-target="#cancelTuition<?= (int) $row['id'] ?>"
                            >
                                Cancel Tuition
                            </button>
                        <?php elseif ($row['status'] === 'completed' && !$row['review_id']): ?>
                            <button
                                class="btn btn-warning btn-sm"
                                data-bs-toggle="modal"
                                data-bs-target="#review<?= (int) $row['id'] ?>"
                            >
                                Add Review
                            </button>
                        <?php elseif ($row['review_id']): ?>
                            <span class="badge text-bg-success align-self-center">
                                Reviewed
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if ($row['status'] === 'accepted'): ?>
                <div
                    class="modal fade"
                    id="cancelTuition<?= (int) $row['id'] ?>"
                    tabindex="-1"
                >
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form
                                method="post"
                                action="<?= url('actions/application_action.php') ?>"
                            >
                                <div class="modal-header">
                                    <h5 class="modal-title">Cancel Tuition</h5>
                                    <button
                                        type="button"
                                        class="btn-close"
                                        data-bs-dismiss="modal"
                                    ></button>
                                </div>

                                <div class="modal-body">
                                    <?= csrf_field() ?>
                                    <input
                                        type="hidden"
                                        name="application_id"
                                        value="<?= (int) $row['id'] ?>"
                                    >
                                    <input type="hidden" name="action" value="cancel">

                                    <p class="text-muted">
                                        You are cancelling the tuition with
                                        <strong><?= e($row['tutor_name']) ?></strong>.
                                        The tutor will receive a notification.
                                    </p>

                                    <label class="form-label">
                                        Cancellation Reason
                                    </label>
                                    <textarea
                                        class="form-control"
                                        name="cancellation_reason"
                                        rows="4"
                                        minlength="5"
                                        maxlength="500"
                                        required
                                        placeholder="Example: The tuition is no longer required or the schedule has changed."
                                    ></textarea>
                                </div>

                                <div class="modal-footer">
                                    <button
                                        type="button"
                                        class="btn btn-outline-secondary"
                                        data-bs-dismiss="modal"
                                    >
                                        Keep Tuition
                                    </button>
                                    <button
                                        class="btn btn-danger"
                                        data-confirm="Are you sure you want to cancel this tuition?"
                                    >
                                        Confirm Cancellation
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($row['status'] === 'completed' && !$row['review_id']): ?>
                <div
                    class="modal fade"
                    id="review<?= (int) $row['id'] ?>"
                    tabindex="-1"
                >
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="post" action="<?= url('actions/review.php') ?>">
                                <div class="modal-header">
                                    <h5 class="modal-title">
                                        Review <?= e($row['tutor_name']) ?>
                                    </h5>
                                    <button
                                        type="button"
                                        class="btn-close"
                                        data-bs-dismiss="modal"
                                    ></button>
                                </div>

                                <div class="modal-body">
                                    <?= csrf_field() ?>
                                    <input
                                        type="hidden"
                                        name="application_id"
                                        value="<?= (int) $row['id'] ?>"
                                    >

                                    <label class="form-label">Rating</label>
                                    <select class="form-select mb-3" name="rating">
                                        <option value="5">5 - Excellent</option>
                                        <option value="4">4 - Very good</option>
                                        <option value="3">3 - Good</option>
                                        <option value="2">2 - Fair</option>
                                        <option value="1">1 - Poor</option>
                                    </select>

                                    <label class="form-label">Comment</label>
                                    <textarea
                                        class="form-control"
                                        name="comment"
                                        rows="4"
                                        required
                                    ></textarea>
                                </div>

                                <div class="modal-footer">
                                    <button class="btn btn-primary">
                                        Submit Review
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
