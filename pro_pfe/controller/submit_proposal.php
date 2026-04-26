<?php
/**
 * Controller : Soumettre une proposition de restaurant (Utilisateur)
 * POST /controller/submit_proposal.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../model/Auth.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    header("Location: ../view/login.php?msg=" . urlencode("Veuillez vous connecter pour proposer un restaurant"));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../view/propose_restaurant.php");
    exit;
}

// ── Récupération et Sanitisation ──────────────────────────────────────────────
$name         = trim($_POST['name'] ?? '');
$category     = trim($_POST['category'] ?? '');
$cuisine_type = trim($_POST['cuisine_type'] ?? '');
$price_range  = trim($_POST['price_range'] ?? '');
$description  = trim($_POST['description'] ?? '');

$town         = trim($_POST['town'] ?? '');
$address      = trim($_POST['address'] ?? '');
$lat          = floatval($_POST['lat'] ?? 0);
$lng          = floatval($_POST['lng'] ?? 0);

$phone        = trim($_POST['phone'] ?? '');
$email        = trim($_POST['email'] ?? '');
$website      = trim($_POST['website'] ?? '');

$capacity     = trim($_POST['capacity'] ?? '');
$amenities    = trim($_POST['amenities'] ?? '');
$hours        = trim($_POST['hours'] ?? '');

// ── Construction des champs additionnels ────────────────────────────────────
// Tags : on combine la cuisine, la fourchette de prix, et les équipements
$tags_array = [];
if ($cuisine_type) $tags_array[] = $cuisine_type;
if ($price_range)  $tags_array[] = explode(' ', $price_range)[0]; // Just take $, $$, etc.
if ($amenities)    $tags_array[] = $amenities;
$tags = implode(', ', $tags_array);

// Description enrichie : on ajoute les infos supplémentaires si fournies
$full_description = $description;
$extras = [];
if ($website)  $extras[] = "Site web: " . $website;
if ($capacity) $extras[] = "Capacité: " . $capacity;
if ($hours)    $extras[] = "Horaires: " . $hours;

if (!empty($extras)) {
    $full_description .= "\n\n" . implode("\n", $extras);
}

// ── Gestion de l'image (Upload) ──────────────────────────────────────────────
$image_path = '';
if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
    $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];
    $file_ext    = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));

    if (!in_array($file_ext, $allowed_ext)) {
        header("Location: ../view/propose_restaurant.php?error=" . urlencode("Format d'image non autorisé. Utilisez JPG, PNG ou WebP."));
        exit;
    }

    $upload_dir = __DIR__ . '/../uploads/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

    $filename    = uniqid('rest_', true) . '.' . $file_ext;
    $target_path = $upload_dir . $filename;

    if (move_uploaded_file($_FILES['image_file']['tmp_name'], $target_path)) {
        $image_path = 'uploads/' . $filename;
    } else {
        header("Location: ../view/propose_restaurant.php?error=" . urlencode("Erreur lors de l'upload de l'image."));
        exit;
    }
} else {
    // Si pas d'image, on utilise un placeholder stylé
    $image_path = 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=600';
}

// ── Stats par défaut (simulées pour l'IA) ───────────────────────────────────
$base_score    = 3.5;
$stat_food     = round($base_score + (rand(0, 10) / 10), 1);
$stat_service  = round($base_score + (rand(0, 10) / 10), 1);
$stat_price    = round($base_score + (rand(0, 10) / 10), 1);
$stat_ambiance = round($base_score + (rand(0, 10) / 10), 1);
$stat_location = round($base_score + (rand(0, 10) / 10), 1);
$avg_rating    = round(($stat_food + $stat_service + $stat_price + $stat_ambiance + $stat_location) / 5, 2);

// ── Insertion en base ────────────────────────────────────────────────────────
try {
    $stmt = $pdo->prepare("
        INSERT INTO restaurants 
        (name, category, town, address, phone, email, rating, reviews_count, image, description, 
         location_lat, location_lng, tags, status, user_id, 
         stat_food, stat_service, stat_price, stat_ambiance, stat_location)
        VALUES 
        (?, ?, ?, ?, ?, ?, ?, 0, ?, ?, 
         ?, ?, ?, 'pending', ?, 
         ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $name, $category, $town, $address, $phone, $email, $avg_rating, $image_path, $full_description,
        $lat ?: null, $lng ?: null, $tags, $_SESSION['user_id'],
        $stat_food, $stat_service, $stat_price, $stat_ambiance, $stat_location
    ]);

    header("Location: ../view/propose_restaurant.php?success=1");
    exit;

} catch (PDOException $e) {
    header("Location: ../view/propose_restaurant.php?error=" . urlencode("Erreur base de données : " . $e->getMessage()));
    exit;
}
?>
