<?php
require_once '../../config/database.php';
require_once '../../model/Auth.php';

if (!isLoggedIn() || !isAdmin()) {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

$stmt = $pdo->query("SELECT * FROM categories ORDER BY id ASC");
$categories = $stmt->fetchAll();

// Mock count of restaurants per category for the UI
$mockCounts = [
    'Fruits de mer' => 45,
    'Tunisien' => 38,
    'Méditerranéen' => 32,
    'International' => 28,
    'Italien' => 25,
    'Café & Restaurant' => 19,
    'Fast Food' => 15
];

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catégories - Administration</title>
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
                <h1>Catégories</h1>
                <p>Gérez les catégories de restaurants.</p>
            </div>
            <div class="page-actions">
                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" placeholder="Rechercher une catégorie...">
                </div>
                <button class="btn-primary"><i class="fa-solid fa-plus"></i> Ajouter catégorie</button>
            </div>
        </div>

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Catégorie</th>
                        <th>Description</th>
                        <th style="text-align: center;">Nombre de restaurants</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td>
                                <div class="td-item">
                                    <i class="<?= htmlspecialchars($cat['icon']) ?>"></i>
                                    <span><?= htmlspecialchars($cat['name']) ?></span>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($cat['description']) ?></td>
                            <td style="text-align: center; color: var(--text-muted);"><?= $mockCounts[$cat['name']] ?? rand(5, 20) ?></td>
                            <td>
                                <?php if ($cat['status'] == 'Publié'): ?>
                                    <span class="status-badge published">Publié</span>
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
                </tbody>
            </table>
        </div>
    </main>
</div>
</body>
</html>
