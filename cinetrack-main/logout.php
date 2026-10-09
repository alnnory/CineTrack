<?php
declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

// Ensure an active session is started first so CSRF validation and helpers can read $_SESSION
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Allow logout via POST request (preferred for CSRF security)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token if helper function exists
    if (function_exists('csrf_valid') && !csrf_valid()) {
        if (function_exists('flash')) {
            flash('Your session expired or token was invalid. Please try again.', 'error');
        }
        redirect('index.php');
        exit;
    }
}

// Perform application user logout (clears session variables/cookies)
if (function_exists('logout_user')) {
    logout_user();
} else {
    // Fallback standard session destruction if logout_user() isn't defined
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    session_destroy();
}

// Start a fresh session to carry the flash message over to the login page
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set success message and redirect
if (function_exists('flash')) {
    flash('You have been signed out successfully.', 'info');
}

redirect('login.php');
exit;