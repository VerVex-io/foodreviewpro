<?php
require_once 'header.php';
require_once 'sidebar.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$action = $id > 0 ? 'edit' : 'add';
$restaurant = null;

if ($action == 'edit') {
    $stmt = $pdo->prepare("SELECT * FROM restaurants WHERE id = ?");
    $stmt->execute([$id]);
    $restaurant = $stmt->fetch();
    if (!$restaurant) {
        redirect('restaurants.php');
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'] ?? '';
    $category = $_POST['category'] ?? '';
    $image = $_POST['image'] ?? '';
    $description = $_POST['description'] ?? '';
    $location_lat = $_POST['location_lat'] ?? 0;
    $location_lng = $_POST['location_lng'] ?? 0;
    $tags = $_POST['tags'] ?? '';

    if ($action == 'add') {
        $stmt = $pdo->prepare("INSERT INTO restaurants (name, category, image, description, location_lat, location_lng, tags, rating, reviews_count) VALUES (?, ?, ?, ?, ?, ?, ?, 0.0, 0)");
        try {
            $stmt->execute([$name, $category, $image, $description, $location_lat, $location_lng, $tags]);
            redirect('restaurants.php?msg=added');
        } catch (PDOException $e) {
            $error = "Erreur lors de l'ajout : " . $e->getMessage();
        }
    } else {
        $stmt = $pdo->prepare("UPDATE restaurants SET name = ?, category = ?, image = ?, description = ?, location_lat = ?, location_lng = ?, tags = ? WHERE id = ?");
        try {
            $stmt->execute([$name, $category, $image, $description, $location_lat, $location_lng, $tags, $id]);
            redirect('restaurants.php?msg=updated');
        } catch (PDOException $e) {
            $error = "Erreur lors de la mise à jour : " . $e->getMessage();
        }
    }
}
?>

<div class="admin-content">
    <div class="admin-page-header">
        <div>
            <h2><?php echo $action == 'edit' ? 'Modifier le restaurant' : 'Ajouter un restaurant'; ?></h2>
            <p><?php echo $action == 'edit' ? 'Mise à jour des informations de ' . htmlspecialchars($restaurant['name']) : 'Remplissez les détails pour le nouveau restaurant.'; ?></p>
        </div>
        <a href="restaurants.php" class="btn-icon" title="Retour"><i class="fa-solid fa-arrow-left"></i></a>
    </div>

    <?php if ($error): ?>
        <div class="custom-notif" style="position: static; margin-bottom: 2rem; display: flex; align-items: center; gap: 10px; background: rgba(255, 68, 68, 0.1); color: #ff4444; border: 1px solid rgba(255, 68, 68, 0.2); padding: 1rem; border-radius: 12px;">
            <i class="fa-solid fa-circle-exclamation"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div class="form-card" style="max-width: 800px; margin: 0 auto; padding: 2.5rem;">
        <form method="POST">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                <div class="form-group">
                    <label>Nom du Restaurant</label>
                    <input type="text" name="name" required value="<?php echo $restaurant ? htmlspecialchars($restaurant['name']) : ''; ?>" placeholder="Ex: Dar El Jeld">
                </div>
                <div class="form-group">
                    <label>Ville / Quartier</label>
                    <input type="text" name="category" required value="<?php echo $restaurant ? htmlspecialchars($restaurant['category']) : ''; ?>" placeholder="Ex: Tunis, La Marsa...">
                </div>
            </div>

            <div class="form-group">
                <label>URL de l'image (Unsplash ou autre)</label>
                <input type="url" name="image" required value="<?php echo $restaurant ? htmlspecialchars($restaurant['image']) : ''; ?>" placeholder="https://images.unsplash.com/...">
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="4" required placeholder="Décrivez l'expérience culinaire..."><?php echo $restaurant ? htmlspecialchars($restaurant['description']) : ''; ?></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                <div class="form-group">
                    <label>Latitude</label>
                    <input type="number" step="any" name="location_lat" required value="<?php echo $restaurant ? $restaurant['location_lat'] : ''; ?>" placeholder="36.8001">
                </div>
                <div class="form-group">
                    <label>Longitude</label>
                    <input type="number" step="any" name="location_lng" required value="<?php echo $restaurant ? $restaurant['location_lng'] : ''; ?>" placeholder="10.1685">
                </div>
            </div>

            <div class="form-group">
                <label>Tags (Séparés par des virgules)</label>
                <input type="text" name="tags" value="<?php echo $restaurant ? htmlspecialchars($restaurant['tags']) : ''; ?>" placeholder="Ex: Gastronomie, Luxe, Vue Mer">
            </div>

            <div style="margin-top: 2rem; display: flex; justify-content: flex-end; gap: 1rem;">
                <button type="button" onclick="window.location.href='restaurants.php'" class="btn-icon" style="background: rgba(255,255,255,0.05); color: var(--text-secondary); width: auto; padding: 0 1.5rem;">Annuler</button>
                <button type="submit" class="btn-primary" style="padding: 0 2rem;">
                    <?php echo $action == 'edit' ? 'Sauvegarder les modifications' : 'Ajouter le restaurant'; ?>
                </button>
            </div>
        </form>
    </div>
</div>


