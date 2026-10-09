<?php
// Database connection + session.
// Loaded by includes/header.php and action scripts.

declare(strict_types=1);

const DB_HOST = '127.0.0.1';
const DB_USER = 'root';
const DB_PASS = ''; // Default XAMPP password
const DB_NAME = 'movie_watchlist';

// Start the session before using session variables.
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS'])
            && $_SERVER['HTTPS'] !== 'off',
        'path'     => '/',
    ]);

    session_start();
}

// Enable MySQLi exceptions for database errors.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli(
        DB_HOST,
        DB_USER,
        DB_PASS,
        DB_NAME
    );

    $conn->set_charset('utf8mb4');

} catch (mysqli_sql_exception $e) {
    error_log('CineTrack database connection error: ' . $e->getMessage());

    http_response_code(500);

    exit(
        'Could not connect to the database. '
        . 'Check that MySQL is running in XAMPP and that '
        . 'sql/schema.sql was imported correctly.'
    );
}
