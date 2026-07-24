<?php
$success = false;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = new PDO('mysql:host=localhost;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $sql = file_get_contents(__DIR__ . '/database.sql');
        $statements = array_filter(array_map('trim', preg_split('/;\s*(?:\r?\n|$)/', $sql)));
        foreach ($statements as $statement) {
            if ($statement !== '') $pdo->exec($statement);
        }
        $success = true;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Tuition Media Installer</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="bg-light"><div class="container py-5"><div class="card border-0 shadow mx-auto" style="max-width:760px"><div class="card-body p-5"><h1 class="h3">Tuition Media Installer</h1><p class="text-muted">This creates the <code>tuition_media</code> database, tables, sample data and demo accounts.</p><?php if($success): ?><div class="alert alert-success"><strong>Installation completed.</strong> The project is ready to run.</div><a class="btn btn-primary" href="/TuitionMedia/">Open Website</a><hr><h5>Demo accounts</h5><ul><li>Admin: admin@tuitionmedia.local / Admin@123</li><li>Tutor: tutor@tuitionmedia.local / Tutor@123</li><li>Guardian: guardian@tuitionmedia.local / Guardian@123</li><li>Student: student@tuitionmedia.local / Student@123</li></ul><p class="small text-danger">For security, delete or rename setup.php after installation.</p><?php else: ?><?php if($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?><ol><li>Start Apache and MySQL from XAMPP.</li><li>Keep the project folder name as <strong>TuitionMedia</strong>.</li><li>Click Install Database.</li></ol><form method="post"><button class="btn btn-success btn-lg">Install Database</button></form><?php endif; ?></div></div></div></body></html>
