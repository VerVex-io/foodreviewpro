<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../model/Auth.php';

requireAdmin();

$id = $_GET['id'] ?? null;

if ($id) {
    try {
        $stmt = $pdo->prepare("UPDATE restaurants SET status = 'active', rejection_reason = NULL WHERE id = ?");
        $stmt->execute([$id]);
        
        if (isset($_GET['ajax'])) {
            echo json_encode(['success' => true]);
            exit();
        }
        header("Location: ../view/index.php?msg=approved");
    } catch (PDOException $e) {
        if (isset($_GET['ajax'])) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit();
        }
        die("Erreur base de données : " . $e->getMessage());
    }
} else {
    header("Location: ../view/index.php");
}
exit();
