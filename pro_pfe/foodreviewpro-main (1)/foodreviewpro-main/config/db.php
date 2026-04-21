<?php
/**
 * config/db.php
 * Hardcoded XAMPP credentials — no .env needed.
 * Change these values if your XAMPP setup is different.
 */

// ── Database credentials ─────────────────────────────────────
// Using 127.0.0.1 instead of localhost forces TCP (not socket)
// which is required on Windows XAMPP
define('DB_HOST',    '127.0.0.1');
define('DB_PORT',    '3307');
define('DB_NAME',    'foodreviewpro');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

$dsn = 'mysql:host=' . DB_HOST
     . ';port='      . DB_PORT
     . ';dbname='    . DB_NAME
     . ';charset='   . DB_CHARSET;

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    http_response_code(500);
    error_log('[FoodReviewPro] DB error: ' . $e->getMessage());

    // Show a helpful message in development
    $msg = 'Database connection failed.<br><br>'
         . '<strong>Check:</strong><br>'
         . '1. MySQL is running in XAMPP Control Panel<br>'
         . '2. Database <code>foodreviewpro</code> exists in phpMyAdmin<br>'
         . '3. Username/password in config/db.php<br><br>'
         . '<em>Error: ' . htmlspecialchars($e->getMessage()) . '</em>';

    die('<!DOCTYPE html><html><head><meta charset="UTF-8">
         <title>DB Error</title>
         <style>body{font-family:sans-serif;padding:40px;max-width:600px;margin:auto;color:#333}
         code{background:#f1f5f9;padding:2px 6px;border-radius:4px}
         strong{color:#dc2626}</style></head>
         <body><h2>⚠️ Connexion base de données échouée</h2><p>' . $msg . '</p></body></html>');
}
