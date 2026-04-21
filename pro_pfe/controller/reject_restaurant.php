<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../model/Auth.php';

requireAdmin();

$id = $_GET['id'] ?? null;
$reason = $_GET['reason'] ?? 'Non spécifié';

if ($id) {
    try {
        $stmt = $pdo->prepare("UPDATE restaurants SET status = 'rejected', rejection_reason = ? WHERE id = ?");
        $stmt->execute([$reason, $id]);
        header("Location: ../view/admin/restaurants.php?status=pending&msg=rejected");
    } catch (PDOException $e) {
        die("Erreur base de données : " . $e->getMessage());
    }
} else {
    header("Location: ../view/admin/restaurants.php");
}
exit();
