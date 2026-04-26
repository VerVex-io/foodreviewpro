<?php
/**
 * Configuration de la Base de Données
 */
define('DB_HOST', 'localhost');
define('DB_NAME', 'pfa');
define('DB_USER', 'root');
define('DB_PASS', ''); // Par défaut sur XAMPP

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("ERREUR : Impossible de se connecter à la base de données. " . $e->getMessage());
}

/**
 * Fonctions Utilitaires
 */
if (!function_exists('redirect')) {
    function redirect($url) {
        header("Location: $url");
        exit();
    }
}

// ── Constantes d'Administration ───────────────────────────────
if (!defined('STATUS_PENDING')) define('STATUS_PENDING',  'pending');
if (!defined('STATUS_ACTIVE'))  define('STATUS_ACTIVE',   'active');
if (!defined('STATUS_REJECTED')) define('STATUS_REJECTED', 'rejected');
if (!defined('STATUS_INACTIVE')) define('STATUS_INACTIVE', 'inactive');
if (!defined('PER_PAGE'))        define('PER_PAGE', 10);
