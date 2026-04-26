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
        // Try to find login.php relatively
        if (file_exists('login.php')) header("Location: login.php");
        elseif (file_exists('../login.php')) header("Location: ../login.php");
        else header("Location: /pro_pfe/view/login.php"); // Fallback
        exit();
    }
}

function requireAdmin() {
    if (!isLoggedIn()) {
        if (file_exists('login.php')) header("Location: login.php");
        elseif (file_exists('../login.php')) header("Location: ../login.php");
        else header("Location: /pro_pfe/view/login.php"); // Fallback
        exit();
    }
    if (!isAdmin()) {
        if (file_exists('index.php')) header("Location: index.php");
        elseif (file_exists('../index.php')) header("Location: ../index.php");
        else header("Location: /pro_pfe/view/index.php"); // Fallback
        exit();
    }
}

function logout() {
    session_destroy();
    if (file_exists('index.php')) header("Location: index.php");
    elseif (file_exists('../index.php')) header("Location: ../index.php");
    else header("Location: /pro_pfe/view/index.php"); // Fallback
    exit();
}
?>
