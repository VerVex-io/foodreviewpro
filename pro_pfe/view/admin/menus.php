<?php
require_once 'header.php';
require_once 'sidebar.php';

$restaurant_id = isset($_GET['restaurant_id']) ? intval($_GET['restaurant_id']) : 0;
if ($restaurant_id <= 0) {
    redirect('restaurants.php');
}

// Fetch Restaurant Info
$stmt = $pdo->prepare("SELECT name FROM restaurants WHERE id = ?");
$stmt->execute([$restaurant_id]);
$restaurant = $stmt->fetch();
if (!$restaurant) {
    redirect('restaurants.php');
}

$error = '';
$success = '';

// Handle Deletion
if (isset($_GET['delete_item'])) {
    $item_id = intval($_GET['delete_item']);
    $stmt = $pdo->prepare("DELETE FROM menu_items WHERE id = ? AND restaurant_id = ?");
    $stmt->execute([$item_id, $restaurant_id]);
    header("Location: menus.php?restaurant_id=$restaurant_id&msg=deleted");
    exit();
}

// Handle Add/Edit Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
    $name = $_POST['name'] ?? '';
    $price = $_POST['price'] ?? 0;
    $description = $_POST['description'] ?? '';
    $category = $_POST['category'] ?? 'Plat';

    if ($item_id > 0) {
        // Update
        $stmt = $pdo->prepare("UPDATE menu_items SET name = ?, price = ?, description = ?, category = ? WHERE id = ? AND restaurant_id = ?");
        $stmt->execute([$name, $price, $description, $category, $item_id, $restaurant_id]);
        $success = "Plat mis à jour !";
    } else {
        // Add
        $stmt = $pdo->prepare("INSERT INTO menu_items (restaurant_id, name, price, description, category) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$restaurant_id, $name, $price, $description, $category]);
        $success = "Nouveau plat ajouté au menu !";
    }
}

// Fetch Menu Items
$stmt = $pdo->prepare("SELECT * FROM menu_items WHERE restaurant_id = ? ORDER BY category, name ASC");
$stmt->execute([$restaurant_id]);
$menu_items = $stmt->fetchAll();

// Item to edit (if any)
$edit_item = null;
if (isset($_GET['edit_item'])) {
    $eid = intval($_GET['edit_item']);
    foreach ($menu_items as $item) {
        if ($item['id'] == $eid) {
            $edit_item = $item;
            break;
        }
    }
}
?>

<div class="admin-content">
    <div class="admin-page-header">
        <div>
            <h2>Menu : <?php echo htmlspecialchars($restaurant['name']); ?></h2>
            <p>Gérez les plats et les prix de cet établissement.</p>
        </div>
        <a href="restaurants.php" class="btn-icon" title="Retour aux restaurants"><i class="fa-solid fa-arrow-left"></i></a>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
        <div class="custom-notif" style="position: static; margin-bottom: 2rem; display: flex; align-items: center; gap: 10px; background: rgba(255, 68, 68, 0.1); color: #ff4444; border: 1px solid rgba(255, 68, 68, 0.2); padding: 1rem; border-radius: 12px;">
            <i class="fa-solid fa-circle-check"></i> Plat supprimé avec succès.
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="custom-notif" style="position: static; margin-bottom: 2rem; display: flex; align-items: center; gap: 10px; background: rgba(46, 213, 115, 0.1); color: #2ed573; border: 1px solid rgba(46, 213, 115, 0.2); padding: 1rem; border-radius: 12px;">
            <i class="fa-solid fa-circle-check"></i> <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 350px 1fr; gap: 3rem; align-items: start;">
        <!-- Form Section -->
        <div class="form-card" style="padding: 2rem;">
            <h3><?php echo $edit_item ? 'Modifier le plat' : 'Ajouter un plat'; ?></h3>
            <form method="POST" action="menus.php?restaurant_id=<?php echo $restaurant_id; ?>">
                <input type="hidden" name="item_id" value="<?php echo $edit_item ? $edit_item['id'] : 0; ?>">
                
                <div class="form-group">
                    <label>Nom du plat</label>
                    <input type="text" name="name" required value="<?php echo $edit_item ? htmlspecialchars($edit_item['name']) : ''; ?>" placeholder="Ex: Couscous au mérou">
                </div>
                
                <div class="form-group">
                    <label>Prix (TND)</label>
                    <input type="number" step="0.01" name="price" required value="<?php echo $edit_item ? $edit_item['price'] : ''; ?>" placeholder="0.00">
                </div>
                
                <div class="form-group">
                    <label>Catégorie</label>
                    <select name="category" class="form-control" style="width: 100%; background: var(--bg-card); color: white; border: 1px solid var(--border-color); padding: 0.8rem; border-radius: 8px;">
                        <option value="Entrée" <?php echo ($edit_item && $edit_item['category'] == 'Entrée') ? 'selected' : ''; ?>>Entrée</option>
                        <option value="Plat" <?php echo ($edit_item && $edit_item['category'] == 'Plat') ? 'selected' : ''; ?>>Plat</option>
                        <option value="Dessert" <?php echo ($edit_item && $edit_item['category'] == 'Dessert') ? 'selected' : ''; ?>>Dessert</option>
                        <option value="Boisson" <?php echo ($edit_item && $edit_item['category'] == 'Boisson') ? 'selected' : ''; ?>>Boisson</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Description (Optionnel)</label>
                    <textarea name="description" rows="3" placeholder="Ingrédients, portion..."><?php echo $edit_item ? htmlspecialchars($edit_item['description']) : ''; ?></textarea>
                </div>

                <div style="margin-top: 1.5rem; display: flex; gap: 10px;">
                    <button type="submit" class="btn-primary" style="flex: 1;"><?php echo $edit_item ? 'Mettre à jour' : 'Ajouter'; ?></button>
                    <?php if ($edit_item): ?>
                        <a href="menus.php?restaurant_id=<?php echo $restaurant_id; ?>" class="btn-icon" style="background: rgba(255,255,255,0.1); width: auto; padding: 0 1rem;">Annuler</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Table Section -->
        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Catégorie</th>
                        <th>Nom</th>
                        <th>Prix</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($menu_items)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 3rem; color: var(--text-secondary);">Le menu est vide. Commencez par ajouter un plat.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($menu_items as $item): ?>
                    <tr>
                        <td><span class="tag-chip" style="background: rgba(255,255,255,0.05)"><?php echo htmlspecialchars($item['category']); ?></span></td>
                        <td><strong><?php echo htmlspecialchars($item['name']); ?></strong><br><small style="color: var(--text-secondary);"><?php echo htmlspecialchars($item['description']); ?></small></td>
                        <td><?php echo number_format($item['price'], 2); ?> TND</td>
                        <td>
                            <div class="review-actions-btns">
                                <a href="menus.php?restaurant_id=<?php echo $restaurant_id; ?>&edit_item=<?php echo $item['id']; ?>" class="btn-icon" title="Modifier">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <a href="menus.php?restaurant_id=<?php echo $restaurant_id; ?>&delete_item=<?php echo $item['id']; ?>" class="btn-icon delete" title="Supprimer" onclick="return askDeleteConfirmation(this.href, 'le plat <?php echo addslashes($item['name']); ?>')">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
