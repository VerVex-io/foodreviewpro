<?php
/**
 * src/Middleware/Auth.php
 * Include this at the top of every protected page.
 *
 * Usage:
 *   require_once __DIR__ . '/../../src/Middleware/Auth.php';
 *   Auth::requireAdmin();   // admin dashboard pages
 *   Auth::requireLogin();   // any logged-in user
 */

class Auth
{
    /**
     * Require any authenticated user.
     * Redirects to login if no session.
     */
    public static function requireLogin(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['user_id'])) {
            header('Location: /view/auth/signup_login.php');
            exit;
        }
    }

    /**
     * Require admin role.
     * Redirects to login if not authenticated, or 403 if wrong role.
     */
    public static function requireAdmin(): void
    {
        self::requireLogin();
        if (($_SESSION['role'] ?? '') !== 'admin') {
            http_response_code(403);
            echo '<h1>403 — Accès refusé</h1><p>Vous n\'avez pas les droits nécessaires.</p>';
            exit;
        }
    }

    /**
     * Return current user ID or null.
     */
    public static function userId(): ?int
    {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    /**
     * Return current user role or null.
     */
    public static function role(): ?string
    {
        return $_SESSION['role'] ?? null;
    }
}
