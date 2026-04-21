<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../model/Auth.php';

requireAdmin();

$id = $_GET['id'] ?? null;

if ($id) {
    try {
        $stmt = $pdo->prepare("UPDATE restaurants SET status = 'active', rejection_reason = NULL WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: ../view/admin/restaurants.php?status=pending&msg=approved");
    } catch (PDOException $e) {
        die("Erreur base de données : " . $e->getMessage());
    }
} else {
    header("Location: ../view/admin/restaurants.php");
}
exit();
