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
        
        if (isset($_GET['ajax'])) {
            echo json_encode(['success' => true]);
            exit();
        }
        header("Location: ../view/index.php?msg=rejected");
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
