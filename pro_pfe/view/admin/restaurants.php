<?php
require_once 'header.php';
require_once 'sidebar.php';

// Handle Deletion
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM restaurants WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: restaurants.php?msg=deleted");
    exit();
}

// Fetch Categories for filter (optional, keeping current flow)
$status_filter = $_GET['status'] ?? 'active';

// Fetch Restaurants based on status
$stmt = $pdo->prepare("SELECT * FROM restaurants WHERE status = ? ORDER BY created_at DESC");
$stmt->execute([$status_filter]);
$restaurants = $stmt->fetchAll();
?>

<div class="admin-content">
    <div class="admin-page-header">
        <div>
            <h2>Restaurants</h2>
            <p>Gestion du catalogue culinaire.</p>
        </div>
        <div style="display: flex; gap: 1rem;">
            <a href="?status=active" class="btn-secondary <?php echo $status_filter == 'active' ? 'active' : ''; ?>" style="<?php echo $status_filter == 'active' ? 'border-color: var(--accent-primary);' : ''; ?>">Actifs</a>
            <a href="?status=pending" class="btn-secondary <?php echo $status_filter == 'pending' ? 'active' : ''; ?>" style="<?php echo $status_filter == 'pending' ? 'border-color: var(--accent-primary);' : ''; ?>">En attente</a>
            <a href="?status=rejected" class="btn-secondary <?php echo $status_filter == 'rejected' ? 'active' : ''; ?>" style="<?php echo $status_filter == 'rejected' ? 'border-color: var(--accent-primary);' : ''; ?>">Refusés</a>
        </div>
        <a href="manage_restaurant.php" class="btn-primary"><i class="fa-solid fa-plus"></i> Ajouter un restaurant</a>
    </div>



    <div class="admin-table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Nom</th>
                    <th>Ville</th>
                    <th>Note</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($restaurants as $res): ?>
                <tr>
                    <td><img src="<?php echo htmlspecialchars($res['image']); ?>" class="table-avatar" style="border-radius: 8px; width: 60px; height: 40px; object-fit: cover;" alt=""></td>
                    <td><strong><?php echo htmlspecialchars($res['name']); ?></strong></td>
                    <td><span class="tag-chip"><?php echo htmlspecialchars($res['category']); ?></span></td>
                    <td><i class="fa-solid fa-star" style="color:#ffb03a"></i> <?php echo $res['rating']; ?></td>
                    <td>
                        <div class="review-actions-btns">
                            <?php if ($status_filter == 'pending'): ?>
                                <a href="../../controller/approve_restaurant.php?id=<?php echo $res['id']; ?>" class="btn-icon" title="Approuver" style="color: #00ff88;">
                                    <i class="fa-solid fa-check"></i>
                                </a>
                                <a href="#" onclick="openRejectModal(<?php echo $res['id']; ?>)" class="btn-icon" title="Refuser" style="color: #ff4444;">
                                    <i class="fa-solid fa-xmark"></i>
                                </a>
                            <?php endif; ?>
                            <a href="menus.php?restaurant_id=<?php echo $res['id']; ?>" class="btn-icon" title="Gérer le menu">
                                <i class="fa-solid fa-utensils"></i>
                            </a>
                            <a href="manage_restaurant.php?id=<?php echo $res['id']; ?>" class="btn-icon" title="Modifier">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <a href="?delete=<?php echo $res['id']; ?>" class="btn-icon delete" title="Supprimer" onclick="return askDeleteConfirmation(this.href, 'le restaurant <?php echo addslashes($res['name']); ?>')">
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

<!-- Modal de Rejet -->
<div id="reject-modal" class="modal-overlay">
    <div class="modal-content-premium">
        <div class="modal-header-premium">
            <h3>Refuser la proposition</h3>
            <p>Veuillez indiquer la raison de ce refus pour en informer l'utilisateur.</p>
        </div>
        <div class="form-group">
            <label>Commentaire / Réclamation</label>
            <textarea id="reject-reason" rows="4" placeholder="Ex: Image de mauvaise qualité, description incomplète..."></textarea>
        </div>
        <div class="modal-footer-premium">
            <button class="btn-secondary" onclick="closeRejectModal()">Annuler</button>
            <button class="btn-primary" id="confirm-reject-btn">Confirmer le refus</button>
        </div>
    </div>
</div>

<script>
let currentRejectId = null;

function openRejectModal(id) {
    currentRejectId = id;
    document.getElementById('reject-modal').style.display = 'flex';
    document.getElementById('reject-reason').value = '';
    document.getElementById('reject-reason').focus();
}

function closeRejectModal() {
    document.getElementById('reject-modal').style.display = 'none';
}

document.getElementById('confirm-reject-btn').addEventListener('click', function() {
    const reason = document.getElementById('reject-reason').value.trim();
    if (!reason) {
        let errDiv = document.getElementById('reject-error');
        if(!errDiv) {
            errDiv = document.createElement('div');
            errDiv.id = 'reject-error';
            errDiv.style.cssText = 'color: #ff4444; background: rgba(255, 68, 68, 0.1); padding: 10px; border-radius: 8px; margin-bottom: 15px; font-size: 0.9rem;';
            const formGroup = document.querySelector('#reject-modal .form-group');
            formGroup.parentNode.insertBefore(errDiv, formGroup);
        }
        errDiv.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i> Veuillez saisir une raison.';
        return;
    }
    
    window.location.href = '../../controller/reject_restaurant.php?id=' + currentRejectId + '&reason=' + encodeURIComponent(reason);
});

// Fermer au clic extérieur
window.onclick = function(event) {
    const modal = document.getElementById('reject-modal');
    if (event.target == modal) {
        closeRejectModal();
    }
}
</script>

<?php require_once 'footer.php'; ?>
