<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../model/Auth.php';

requireAdmin();

$id = $_GET['id'] ?? null;

if ($id) {
    try {
        // Supprimer les dépendances d'abord (menus et avis)
        $pdo->prepare("DELETE FROM menu_items WHERE restaurant_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM reviews WHERE restaurant_id = ?")->execute([$id]);
        
        $stmt = $pdo->prepare("DELETE FROM restaurants WHERE id = ?");
        $stmt->execute([$id]);
        
        if (isset($_GET['ajax'])) {
            echo json_encode(['success' => true]);
            exit();
        }
        header("Location: ../view/index.php?msg=deleted");
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
