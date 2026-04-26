<?php
require_once '../../config/database.php';
require_once '../../model/Auth.php';

if (!isLoggedIn() || !isAdmin()) {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Utilisateurs - Administration</title>
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
                <h1>Utilisateurs</h1>
                <p>Gérez les utilisateurs de la plateforme.</p>
            </div>
            <div class="page-actions">
                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" placeholder="Rechercher un utilisateur...">
                </div>
                <button class="btn-filter"><i class="fa-solid fa-filter"></i> Filtres</button>
                <button class="btn-primary"><i class="fa-solid fa-plus"></i> Ajouter utilisateur</button>
            </div>
        </div>

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Utilisateur</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th>Statut</th>
                        <th>Inscription</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td>
                                <div class="client-cell">
                                    <div class="client-avatar"><img src="<?= !empty($u['avatar']) ? htmlspecialchars($u['avatar']) : 'https://ui-avatars.com/api/?name='.urlencode($u['name']).'&background=random' ?>" alt="Avatar"></div>
                                    <span style="font-weight: 500;"><?= htmlspecialchars($u['name']) ?></span>
                                </div>
                            </td>
                            <td style="color: var(--text-muted);"><?= htmlspecialchars($u['email']) ?></td>
                            <td><?= ucfirst(htmlspecialchars($u['role'])) ?></td>
                            <td>
                                <?php if ($u['status'] == 'active'): ?>
                                    <span class="status-badge actif">Actif</span>
                                <?php else: ?>
                                    <span class="status-badge inactif">Inactif</span>
                                <?php endif; ?>
                            </td>
                            <td style="color: var(--text-muted);"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
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
            
            <div class="pagination">
                <button class="page-btn active">1</button>
                <button class="page-btn">2</button>
                <button class="page-btn">3</button>
                <button class="page-btn">4</button>
                <span class="page-dots">...</span>
                <button class="page-btn">10</button>
                <button class="page-btn next"><i class="fa-solid fa-chevron-right"></i></button>
            </div>
        </div>
    </main>
</div>
</body>
</html>
