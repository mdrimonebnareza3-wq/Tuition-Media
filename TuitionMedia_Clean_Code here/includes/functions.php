<?php
function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string {
    return BASE_URL . ($path ? '/' . ltrim($path, '/') : '');
}

function redirect(string $path): never {
    header('Location: ' . url($path));
    exit;
}

function set_flash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array {
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        exit('Invalid request token. Please go back and try again.');
    }
}

function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool {
    return !empty($_SESSION['user']);
}

function require_login(): void {
    if (!is_logged_in()) {
        set_flash('warning', 'Please log in to continue.');
        redirect('login.php');
    }
}

function require_role(array|string $roles): void {
    require_login();
    $roles = (array)$roles;
    if (!in_array(current_user()['role'], $roles, true)) {
        http_response_code(403);
        exit('Access denied.');
    }
}

function refresh_session_user(PDO $pdo): void {
    if (!is_logged_in()) return;
    $stmt = $pdo->prepare('SELECT id, name, email, phone, role, status FROM users WHERE id = ?');
    $stmt->execute([current_user()['id']]);
    $user = $stmt->fetch();
    if ($user) $_SESSION['user'] = $user;
}

function notify(
    PDO $pdo,
    int $userId,
    string $message,
    ?string $link = null,
    ?string $entityType = null,
    ?int $entityId = null
): void {
    $stmt = $pdo->prepare(
        'INSERT INTO notifications (user_id, message, link, entity_type, entity_id)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([$userId, $message, $link, $entityType, $entityId]);
}

function notify_admins(
    PDO $pdo,
    string $message,
    ?string $link = null,
    ?string $entityType = null,
    ?int $entityId = null
): void {
    $admins = $pdo->query(
        "SELECT id FROM users WHERE role='admin' AND status='active'"
    )->fetchAll(PDO::FETCH_COLUMN);

    if (!$admins) return;

    $stmt = $pdo->prepare(
        'INSERT INTO notifications (user_id, message, link, entity_type, entity_id)
         VALUES (?, ?, ?, ?, ?)'
    );

    foreach ($admins as $adminId) {
        $stmt->execute([
            (int)$adminId,
            $message,
            $link,
            $entityType,
            $entityId,
        ]);
    }
}

function unread_notification_count(PDO $pdo, int $userId): int {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

function role_label(string $role): string {
    return match ($role) {
        'student' => 'Student',
        'guardian' => 'Guardian',
        'tutor' => 'Tutor',
        'admin' => 'Admin',
        default => ucfirst($role),
    };
}

function status_badge(string $status): string {
    $class = match ($status) {
        'approved', 'accepted', 'active', 'closed', 'completed' => 'success',
        'pending' => 'warning',
        'rejected', 'blocked', 'withdrawn', 'cancelled' => 'danger',
        default => 'secondary',
    };
    return '<span class="badge text-bg-' . $class . '">' . e(ucfirst($status)) . '</span>';
}

function post_value(string $key, string $default = ''): string {
    return e($_POST[$key] ?? $default);
}

function validate_required(array $fields): array {
    $errors = [];
    foreach ($fields as $field => $label) {
        if (trim($_POST[$field] ?? '') === '') {
            $errors[] = $label . ' is required.';
        }
    }
    return $errors;
}

function normalized_money(string $value): ?float {
    if ($value === '') return null;
    if (!is_numeric($value)) return null;
    return max(0, (float)$value);
}
