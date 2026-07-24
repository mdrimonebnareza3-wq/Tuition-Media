<?php
// Tuition Media - Database and application configuration
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Dhaka');

const DB_HOST = 'localhost';
const DB_NAME = 'tuition_media';
const DB_USER = 'root';
const DB_PASS = '';
const APP_NAME = 'Tuition Media';
const BASE_URL = '/TuitionMedia';
// Required only when registering a new administrator account.
const ADMIN_REGISTRATION_CODE = 'TM-ADMIN-2026';

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    $setupLink = BASE_URL . '/setup.php';
    exit('<div style="font-family:Arial;max-width:760px;margin:60px auto;padding:30px;border:1px solid #ddd;border-radius:12px">'
        . '<h2>Database connection failed</h2>'
        . '<p>Please start Apache and MySQL in XAMPP, then run the installer.</p>'
        . '<p><a href="' . htmlspecialchars($setupLink) . '">Open Tuition Media Installer</a></p>'
        . '<small>' . htmlspecialchars($e->getMessage()) . '</small></div>');
}
