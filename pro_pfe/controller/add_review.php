<?php
require_once '../config/database.php';
require_once '../model/Auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté pour laisser un avis.']);
    exit();
}

// Récupérer les données JSON
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['restaurant_id']) || !isset($data['text']) || !isset($data['rating'])) {
    echo json_encode(['success' => false, 'message' => 'Données invalides.']);
    exit();
}

$restaurant_id = intval($data['restaurant_id']);
$text          = trim($data['text']);
$rating        = intval($data['rating']);
$user_id       = $_SESSION['user_id'];
$user_name     = $_SESSION['user_name'] ?? 'Anonyme';

if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'La note doit être entre 1 et 5 étoiles.']);
    exit();
}

if (empty($text)) {
    echo json_encode(['success' => false, 'message' => 'Le commentaire ne peut pas être vide.']);
    exit();
}

try {
    // Insérer l'avis
    $stmt = $pdo->prepare("INSERT INTO reviews (restaurant_id, user_id, user_name, text, rating, status) VALUES (?, ?, ?, ?, ?, 'approved')");
    $stmt->execute([$restaurant_id, $user_id, $user_name, $text, $rating]);

    // Recalculer la moyenne et le compte pour le restaurant
    $stmt_avg = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM reviews WHERE restaurant_id = ? AND status = 'approved'");
    $stmt_avg->execute([$restaurant_id]);
    $result = $stmt_avg->fetch();
    
    $new_rating = round($result['avg_rating'], 1);
    $new_count  = $result['total_reviews'];

    $stmt_update = $pdo->prepare("UPDATE restaurants SET rating = ?, reviews_count = ? WHERE id = ?");
    $stmt_update->execute([$new_rating, $new_count, $restaurant_id]);

    echo json_encode(['success' => true, 'message' => 'Votre avis a été ajouté avec succès !']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur base de données : ' . $e->getMessage()]);
}
?>
