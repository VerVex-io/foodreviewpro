<?php
/**
 * Controller : Ajouter un restaurant (Admin)
 * POST /controller/add_restaurant.php
 * Reçoit un formulaire multipart/form-data depuis view/admin/add_restaurant.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../model/Auth.php';

// Seuls les admins peuvent accéder à cette action
if (!isLoggedIn() || !isAdmin()) {
    header("Location: ../view/login.php?error=unauthorized");
    exit;
}

// Accepter uniquement les requêtes POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../view/admin/add_restaurant.php?error=method");
    exit;
}

// ── Récupération et Sanitisation des données ──────────────────────────────────
$name        = trim($_POST['name']        ?? '');
$category    = trim($_POST['category']    ?? '');
$town        = trim($_POST['town']        ?? '');
$address     = trim($_POST['address']     ?? '');
$phone       = trim($_POST['phone']       ?? '');
$email       = trim($_POST['email']       ?? '');
$description = trim($_POST['description'] ?? '');
$tags        = trim($_POST['tags']        ?? '');
$lat         = floatval($_POST['lat']     ?? 0);
$lng         = floatval($_POST['lng']     ?? 0);
$status      = in_array($_POST['status'] ?? '', ['active', 'pending']) ? $_POST['status'] : 'pending';
$menu_json   = $_POST['menu']             ?? '[]';

// ── Validation des champs obligatoires ───────────────────────────────────────
$errors = [];
if (!$name)        $errors[] = "Le nom du restaurant est requis.";
if (!$category)    $errors[] = "La catégorie est requise.";
if (!$description) $errors[] = "La description est requise.";

if (!empty($errors)) {
    $msg = urlencode(implode(' | ', $errors));
    header("Location: ../view/admin/add_restaurant.php?error=" . $msg);
    exit;
}

// ── Vérification de proximité (éviter doublons GPS) ──────────────────────────
if ($lat && $lng) {
    $stmt_check = $pdo->prepare("SELECT id FROM restaurants WHERE ABS(location_lat - ?) < 0.0001 AND ABS(location_lng - ?) < 0.0001 LIMIT 1");
    $stmt_check->execute([$lat, $lng]);
    if ($stmt_check->fetch()) {
        $msg = urlencode("Un établissement est déjà enregistré à cet emplacement GPS.");
        header("Location: ../view/admin/add_restaurant.php?error=" . $msg);
        exit;
    }
}

// ── Gestion de l'image ────────────────────────────────────────────────────────
$image_path = '';
if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
    $allowed_ext = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $file_ext    = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));

    if (!in_array($file_ext, $allowed_ext)) {
        $msg = urlencode("Format d'image non autorisé. Utilisez JPG, PNG ou WebP.");
        header("Location: ../view/admin/add_restaurant.php?error=" . $msg);
        exit;
    }

    $upload_dir = __DIR__ . '/../uploads/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

    $filename    = uniqid('rest_', true) . '.' . $file_ext;
    $target_path = $upload_dir . $filename;

    if (move_uploaded_file($_FILES['image_file']['tmp_name'], $target_path)) {
        $image_path = 'uploads/' . $filename;
    } else {
        $msg = urlencode("Erreur lors du téléchargement de l'image.");
        header("Location: ../view/admin/add_restaurant.php?error=" . $msg);
        exit;
    }
} elseif (!empty($_POST['image_url'])) {
    $image_path = trim($_POST['image_url']);
} else {
    // Image par défaut si aucune image fournie
    $image_path = 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=600';
}

// ── Calcul automatique des statistiques de base ───────────────────────────────
$base_score    = 4.0;
$stat_food     = round($base_score + (rand(-5, 8)  / 10), 1);
$stat_service  = round($base_score + (rand(-7, 7)  / 10), 1);
$stat_price    = round($base_score + (rand(-10, 5) / 10), 1);
$stat_ambiance = round($base_score + (rand(-5, 10) / 10), 1);
$stat_location = round($base_score + (rand(-2, 10) / 10), 1);
$avg_rating    = round(($stat_food + $stat_service + $stat_price + $stat_ambiance + $stat_location) / 5, 2);

// ── Insertion en base de données ──────────────────────────────────────────────
try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO restaurants
            (name, category, town, address, phone, email, rating, reviews_count,
             image, description, location_lat, location_lng, tags, status, user_id,
             stat_food, stat_service, stat_price, stat_ambiance, stat_location)
        VALUES
            (?, ?, ?, ?, ?, ?, ?, 0,
             ?, ?, ?, ?, ?, ?, ?,
             ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $name, $category, $town, $address, $phone, $email, $avg_rating,
        $image_path, $description, $lat ?: null, $lng ?: null, $tags, $status, $_SESSION['user_id'],
        $stat_food, $stat_service, $stat_price, $stat_ambiance, $stat_location
    ]);

    $newId = $pdo->lastInsertId();

    // ── Insertion des items du menu ────────────────────────────────────────────
    $menu_items = json_decode($menu_json, true);
    if (is_array($menu_items)) {
        $stmt_menu = $pdo->prepare("INSERT INTO menu_items (restaurant_id, name, price) VALUES (?, ?, ?)");
        foreach ($menu_items as $item) {
            if (!empty($item['name']) && isset($item['price'])) {
                $stmt_menu->execute([$newId, trim($item['name']), floatval($item['price'])]);
            }
        }
    }

    $pdo->commit();

    $msg = urlencode("Le restaurant \"$name\" a été ajouté avec succès !");
    header("Location: ../view/admin/restaurants.php?success=" . $msg);
    exit;

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $msg = urlencode("Erreur technique : " . $e->getMessage());
    header("Location: ../view/admin/add_restaurant.php?error=" . $msg);
    exit;
}
?>
