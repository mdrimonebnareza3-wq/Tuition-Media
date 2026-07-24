<?php

$pageTitle = 'Tuition Details';

require __DIR__ . '/includes/header.php';

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT t.*, u.name, u.phone, u.email
     FROM tuition_posts t
     JOIN users u ON u.id = t.user_id
     WHERE t.id = ?'
);

$stmt->execute([$id]);

$p = $stmt->fetch();

if (!$p) {
    http_response_code(404);
    exit('Tuition post not found.');
}

$already = false;

if (
    is_logged_in() &&
    current_user()['role'] === 'tutor'
) {
    $stmt = $pdo->prepare(
        'SELECT id
         FROM tutor_profiles
         WHERE user_id = ?'
    );

    $stmt->execute([
        current_user()['id']
    ]);

    $tutorProfileId = $stmt->fetchColumn();

    if ($tutorProfileId) {
        $stmt = $pdo->prepare(
            'SELECT id, status
             FROM applications
             WHERE tuition_id = ?
             AND tutor_id = ?'
        );

        $stmt->execute([
            $id,
            $tutorProfileId
        ]);

        $already = $stmt->fetch();
    }
}

?>

<div class="container py-5">

    <div class="row g-4">

        <div class="col-lg-8">

            <div class="card shadow-soft p-4 p-md-5">

                <div class="d-flex justify-content-between align-items-start">

                    <span class="badge text-bg-info">
                        <?= e($p['subject']) ?>
                    </span>

                    <?= status_badge($p['status']) ?>

                </div>

                <h1 class="h2 mt-3">
                    <?= e($p['title']) ?>
                </h1>

                <p class="text-muted">
                    Posted by <?= e($p['name']) ?>
                    on
                    <?= date('d M Y', strtotime($p['created_at'])) ?>
                </p>

                <hr>

                <div class="row g-4">

                    <div class="col-md-6">
                        <strong>Subject</strong>
                        <p><?= e($p['subject']) ?></p>
                    </div>

                    <div class="col-md-6">
                        <strong>Class Level</strong>
                        <p><?= e($p['class_level']) ?></p>
                    </div>

                    <div class="col-md-6">
                        <strong>Location</strong>
                        <p><?= e($p['location']) ?></p>
                    </div>

                    <div class="col-md-6">
                        <strong>Salary Range</strong>

                        <p>
                            ৳<?= number_format((float) $p['salary_min']) ?>
                            –
                            ৳<?= number_format((float) $p['salary_max']) ?>
                        </p>
                    </div>

                    <div class="col-md-6">
                        <strong>Preferred Gender</strong>
                        <p><?= e($p['preferred_gender']) ?></p>
                    </div>

                    <div class="col-md-6">
                        <strong>Teaching Mode</strong>
                        <p><?= e($p['teaching_mode']) ?></p>
                    </div>

                    <div class="col-md-6">
                        <strong>Schedule</strong>

                        <p>
                            <?= e((string) $p['days_per_week']) ?>
                            days/week,
                            <?= e($p['preferred_time']) ?>
                        </p>
                    </div>

                </div>

                <strong>Description</strong>

                <p>
                    <?= nl2br(e($p['description'])) ?>
                </p>

            </div>

        </div>

        <div class="col-lg-4">

            <div class="card shadow-soft p-4">

                <h4 class="section-title">
                    Apply for this tuition
                </h4>

                <?php if (!is_logged_in()): ?>

                    <p>
                        Log in as a tutor to submit an application.
                    </p>

                    <a
                        class="btn btn-primary"
                        href="<?= url('login.php') ?>"
                    >
                        Login
                    </a>

                <?php elseif (current_user()['role'] !== 'tutor'): ?>

                    <p class="text-muted">
                        Only tutor accounts can apply.
                    </p>

                <?php elseif ($p['status'] !== 'approved'): ?>

                    <p class="text-muted">
                        This post is not accepting applications.
                    </p>

                <?php elseif ($already): ?>

                    <p>
                        Your application status:
                        <?= status_badge($already['status']) ?>
                    </p>

                    <a href="<?= url('tutor/applications.php') ?>">
                        View applications
                    </a>

                <?php else: ?>

                    <form
                        method="post"
                        action="<?= url('actions/apply.php') ?>"
                        class="vstack gap-3"
                    >

                        <?= csrf_field() ?>

                        <input
                            type="hidden"
                            name="tuition_id"
                            value="<?= (int) $p['id'] ?>"
                        >

                        <div>

                            <label class="form-label">
                                Application Message
                            </label>

                            <textarea
                                class="form-control"
                                rows="5"
                                name="message"
                                required
                                placeholder="Introduce yourself and explain why you are suitable."
                            ></textarea>

                        </div>

                        <button class="btn btn-primary">
                            Submit Application
                        </button>

                    </form>

                <?php endif; ?>

            </div>

        </div>

    </div>

</div>

<?php require __DIR__ . '/includes/footer.php'; ?>