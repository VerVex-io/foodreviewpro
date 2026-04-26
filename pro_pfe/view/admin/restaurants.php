<?php
require_once '../../config/database.php';
require_once '../../model/Auth.php';

if (!isLoggedIn() || !isAdmin()) {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

// Fetch all restaurants
$stmt = $pdo->query("SELECT r.*, 
    (SELECT COUNT(*) FROM reviews rev WHERE rev.restaurant_id = r.id) as review_count
    FROM restaurants r ORDER BY r.id DESC");
$restaurants = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurants - Administration</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin_style.css?v=<?= time() ?>">
</head>
<body>

<div class="admin-layout">
    <?php include 'includes/sidebar.php'; ?>

    <main class="admin-main">
        <?php include 'includes/header.php'; ?>

        <div class="page-header">
            <div>
                <h1>Restaurants</h1>
                <p>Gérez la liste de tous les restaurants.</p>
            </div>
            <div class="page-actions">
                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" placeholder="Rechercher un restaurant...">
                </div>
                <button class="btn-filter"><i class="fa-solid fa-filter"></i> Filtres</button>
                <a href="add_restaurant.php" class="btn-primary"><i class="fa-solid fa-plus"></i> Ajouter</a>
            </div>
        </div>

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Restaurant</th>
                        <th>Catégorie</th>
                        <th>Ville</th>
                        <th>Note</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($restaurants) > 0): ?>
                        <?php foreach ($restaurants as $r): ?>
                            <tr>
                                <td>
                                    <div class="td-item">
                                        <img src="<?= !empty($r['image']) ? '../'.htmlspecialchars($r['image']) : 'https://images.unsplash.com/photo-1550966871-3ed3cdb5ed0c?w=100' ?>" alt="thumb">
                                        <span><?= htmlspecialchars($r['name']) ?></span>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($r['category']) ?></td>
                                <td><?= htmlspecialchars($r['tags'] ?? 'Tunis') ?></td>
                                <td>
                                    <div class="td-rating">
                                        <i class="fa-solid fa-star"></i> 
                                        <?= number_format($r['rating'], 1) ?> 
                                        <span class="rev-count">(<?= $r['review_count'] ?>)</span>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($r['status'] == 'approved' || $r['status'] == 'Publié'): ?>
                                        <span class="status-badge published">Publié</span>
                                    <?php elseif ($r['status'] == 'pending'): ?>
                                        <span class="status-badge pending">En attente</span>
                                    <?php else: ?>
                                        <span class="status-badge draft">Brouillon</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="td-actions">
                                        <button class="action-btn edit"><i class="fa-solid fa-pen"></i></button>
                                        <button class="action-btn delete"><i class="fa-solid fa-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- Fallback fake data if empty -->
                        <tr>
                            <td>
                                <div class="td-item">
                                    <img src="https://images.unsplash.com/photo-1550966871-3ed3cdb5ed0c?w=100" alt="thumb">
                                    <span>Le Golfe</span>
                                </div>
                            </td>
                            <td>Fruits de mer</td>
                            <td>La Marsa</td>
                            <td><div class="td-rating"><i class="fa-solid fa-star"></i> 4.8 <span class="rev-count">(256)</span></div></td>
                            <td><span class="status-badge published">Publié</span></td>
                            <td>
                                <div class="td-actions">
                                    <button class="action-btn edit"><i class="fa-solid fa-pen"></i></button>
                                    <button class="action-btn delete"><i class="fa-solid fa-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <div class="pagination">
                <button class="page-btn active">1</button>
                <button class="page-btn">2</button>
                <button class="page-btn">3</button>
                <button class="page-btn">4</button>
                <span class="page-dots">...</span>
                <button class="page-btn">36</button>
                <button class="page-btn next"><i class="fa-solid fa-chevron-right"></i></button>
            </div>
        </div>
    </main>
</div>
</body>
</html>
