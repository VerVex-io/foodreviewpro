<?php
/**
 * API Endpoint : Ajouter un restaurant
 * POST /api/add_restaurant.php
 * Reçoit un JSON avec : name, category, image, lat, lng, description, tags
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../model/Auth.php';

// Accepter uniquement les requêtes POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
    exit;
}

// Lire le corps JSON
$body = file_get_contents('php://input');
$data = json_decode($body, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Données JSON invalides.']);
    exit;
}

// Validation des champs requis
$required = ['name', 'category', 'image', 'lat', 'lng', 'description'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Le champ '$field' est requis."]);
        exit;
    }
}

// Sanitisation
$name        = trim($_POST['name'] ?? '');
$category    = trim($_POST['category'] ?? '');
$lat         = floatval($_POST['lat'] ?? 0);
$lng         = floatval($_POST['lng'] ?? 0);
$description = trim($_POST['description'] ?? '');
$tags        = trim($_POST['tags'] ?? '');
$menu_json   = $_POST['menu'] ?? '[]';

if (!$name || !$description || !$lat || !$lng) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Des informations essentielles sont manquantes.']);
    exit;
}

// 1. Vérification de proximité (Éviter les doublons au même endroit)
// Un rayon de 0.0001 degré est environ 10-15 mètres
$stmt_check = $pdo->prepare("SELECT id FROM restaurants WHERE ABS(location_lat - ?) < 0.0001 AND ABS(location_lng - ?) < 0.0001 LIMIT 1");
$stmt_check->execute([$lat, $lng]);
if ($stmt_check->fetch()) {
    http_response_code(409); // Conflict
    echo json_encode(['success' => false, 'message' => 'Un autre établissement est déjà enregistré exactement à cet emplacement.']);
    exit;
}

// 2. Gestion de l'image (Upload ou URL)
$image_path = "";
if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = '../uploads/';
    $file_ext = pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION);
    $filename = uniqid('rest_', true) . '.' . $file_ext;
    $target_path = $upload_dir . $filename;
    
    if (move_uploaded_file($_FILES['image_file']['tmp_name'], $target_path)) {
        $image_path = 'uploads/' . $filename; // Chemin relatif pour le frontend
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erreur lors du téléchargement de l\'image.']);
        exit;
    }
} elseif (!empty($_POST['image_url'])) {
    $image_path = trim($_POST['image_url']);
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Une image est requise.']);
    exit;
}

// 3. Calcul automatique des statistiques (Basé sur un standard de qualité)
$base_score = 4.0;
$stat_food = round($base_score + (rand(-5, 8) / 10), 1); // 3.5 - 4.8
$stat_service = round($base_score + (rand(-7, 7) / 10), 1);
$stat_price = round($base_score + (rand(-10, 5) / 10), 1);
$stat_ambiance = round($base_score + (rand(-5, 10) / 10), 1);
$stat_location = round($base_score + (rand(-2, 10) / 10), 1);

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO restaurants (name, category, rating, reviews_count, image, description, location_lat, location_lng, tags, status, user_id, stat_food, stat_service, stat_price, stat_ambiance, stat_location)
        VALUES (?, ?, ?, 0, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?)
    ");
    
    // La note globale initiale est la moyenne des stats calculées
    $avg_rating = round(($stat_food + $stat_service + $stat_price + $stat_ambiance + $stat_location) / 5, 2);

    $stmt->execute([
        $name, $category, $avg_rating, $image_path, $description, $lat, $lng, $tags, $_SESSION['user_id'],
        $stat_food, $stat_service, $stat_price, $stat_ambiance, $stat_location
    ]);

    $newId = $pdo->lastInsertId();

    // Insertion des items du menu
    $menu_items = json_decode($menu_json, true);
    if (is_array($menu_items)) {
        $stmt_menu = $pdo->prepare("INSERT INTO menu_items (restaurant_id, name, price) VALUES (?, ?, ?)");
        foreach ($menu_items as $item) {
            if (!empty($item['name']) && !empty($item['price'])) {
                $stmt_menu->execute([$newId, trim($item['name']), floatval($item['price'])]);
            }
        }
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Félicitations ! Votre proposition pour ' . $name . ' a été envoyée. Elle sera validée par nos experts prochainement.',
        'id'      => $newId
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur technique : ' . $e->getMessage()]);
}
?>
