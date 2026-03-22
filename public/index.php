<?php
/**
 * public/index.php
 * Central router — handles all POST actions and GET redirects.
 */

session_start();

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../src/Controllers/AuthController.php';
require_once __DIR__ . '/../src/Controllers/RestaurantController.php';
require_once __DIR__ . '/../src/Controllers/ReviewController.php';
require_once __DIR__ . '/../src/Controllers/UserController.php';

$action = $_GET['action'] ?? null;

// ── Helper: redirect back with flash message ─────────────────
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function back(string $fallback = '/foodreviewpro/view/admin/dashboard.php'): void {
    $ref = $_SERVER['HTTP_REFERER'] ?? $fallback;
    redirect($ref);
}

// ── POST actions ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ── AUTH ─────────────────────────────────────────────────
    if ($action === 'signup') {
        $auth   = new AuthController($pdo);
        $result = $auth->signup(
            trim($_POST['user_name'] ?? ''),
            trim($_POST['email']     ?? ''),
            $_POST['password']       ?? ''
        );
        if ($result['success']) {
            redirect('/foodreviewpro/view/auth/signup_login.php?msg=account_created');
        } else {
            redirect('/foodreviewpro/view/auth/signup_login.php?error=' . urlencode($result['error']));
        }
    }

    if ($action === 'login') {
        $auth   = new AuthController($pdo);
        $result = $auth->login(
            trim($_POST['email'] ?? ''),
            $_POST['pswd']       ?? ''
        );
        if ($result['success']) {
            redirect($_SESSION['role'] === 'admin'
                ? '/foodreviewpro/view/admin/dashboard.php'
                : '/foodreviewpro/view/auth/signup_login.php');
        } else {
            redirect('/foodreviewpro/view/auth/signup_login.php?error=' . urlencode($result['error']));
        }
    }

    // ── RESTAURANT: add ──────────────────────────────────────
    if ($action === 'add_restaurant') {
        if (empty($_SESSION['user_id'])) {
            redirect('/foodreviewpro/view/auth/signup_login.php');
        }

        $name        = trim($_POST['name']        ?? '');
        $category    = trim($_POST['category']    ?? '');
        $city        = trim($_POST['city']        ?? '');
        $address     = trim($_POST['address']     ?? '');
        $phone       = trim($_POST['phone']       ?? '');
        $description = trim($_POST['description'] ?? '');
        $priceRange  = $_POST['price_range']      ?? '€€';
        $status      = $_POST['status']           ?? 'pending';
        $latitude    = !empty($_POST['latitude'])  ? (float)$_POST['latitude']  : null;
        $longitude   = !empty($_POST['longitude']) ? (float)$_POST['longitude'] : null;

        if (empty($name) || empty($category) || empty($city)) {
            $_SESSION['flash_err'] = 'Nom, catégorie et ville sont obligatoires.';
            back('/foodreviewpro/view/admin/restaurants.php');
        }

        // Insert directly — RestaurantController::insert doesn't exist yet so we use PDO directly
        $stmt = $pdo->prepare(
            'INSERT INTO restaurants
             (owner_id, name, description, category, city, address, phone, price_range, status, latitude, longitude)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            (int)$_SESSION['user_id'],
            $name, $description, $category, $city,
            $address, $phone, $priceRange, $status,
            $latitude, $longitude
        ]);

        // Log the action
        $newId = (int)$pdo->lastInsertId();
        $logStmt = $pdo->prepare(
            'INSERT INTO activity_logs (admin_id, action, target_type, target_id, details)
             VALUES (?, ?, ?, ?, ?)'
        );
        $logStmt->execute([(int)$_SESSION['user_id'], 'CREATE', 'restaurant', $newId, $name]);

        $_SESSION['flash'] = "Restaurant \"$name\" ajouté avec succès ✓";
        redirect('/foodreviewpro/view/admin/restaurants.php');
    }

    // ── EXPORT ───────────────────────────────────────────────
    if ($action === 'export') {
        if (empty($_SESSION['user_id'])) redirect('/foodreviewpro/view/auth/signup_login.php');

        $type = $_GET['type'] ?? 'restaurants';
        $allowed = ['restaurants', 'reviews', 'users'];
        if (!in_array($type, $allowed)) exit('Invalid type');

        $data = $pdo->query("SELECT * FROM $type")->fetchAll();
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $type . '_export_' . date('Y-m-d') . '.json"');
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// ── Logout works as GET or POST ─────────────────────────────
if ($action === 'logout') {
    session_destroy();
    redirect('/foodreviewpro/view/auth/signup_login.php');
}

// ── GET — redirect based on session ─────────────────────────
if (isset($_SESSION['user_id'])) {
    redirect($_SESSION['role'] === 'admin'
        ? '/foodreviewpro/view/admin/dashboard.php'
        : '/foodreviewpro/view/auth/signup_login.php');
} else {
    redirect('/foodreviewpro/view/auth/signup_login.php');
}