<?php
/**
 * Gestion des Sessions et Permissions
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

function requireAdmin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
    if (!isAdmin()) {
        header("Location: index.php");
        exit();
    }
}

function logout() {
    session_destroy();
    header("Location: index.php");
    exit();
}
?>
