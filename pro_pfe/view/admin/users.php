<?php
require_once 'header.php';
require_once 'sidebar.php';

// Fetch Users
$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();
?>

<div class="admin-content">
    <div class="admin-page-header">
        <div>
            <h2>Utilisateurs</h2>
            <p>Gestion des membres et des permissions.</p>
        </div>
        <button class="btn-primary"><i class="fa-solid fa-user-plus"></i> Inviter</button>
    </div>

    <div class="admin-table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Avatar</th>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Rôle</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><img src="<?php echo htmlspecialchars($user['avatar'] ?: 'https://ui-avatars.com/api/?name='.urlencode($user['name'])); ?>" class="table-avatar" alt=""></td>
                    <td><strong><?php echo htmlspecialchars($user['name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td>
                        <span class="role-badge role-<?php echo $user['role']; ?>">
                            <?php echo $user['role']; ?>
                        </span>
                    </td>
                    <td>
                        <div class="status-badge status-<?php echo $user['status']; ?>">
                            <div class="status-dot"></div>
                            <?php echo $user['status'] == 'active' ? 'Actif' : 'Inactif'; ?>
                        </div>
                    </td>
                    <td>
                        <div class="review-actions-btns">
                            <button class="btn-icon"><i class="fa-solid fa-pen"></i></button>
                            <button class="btn-icon delete"><i class="fa-solid fa-ban"></i></button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'footer.php'; ?>
