<?php
require_once 'header.php';
require_once 'sidebar.php';

// Handle Action
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    if ($_GET['action'] == 'delete') {
        $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
        $stmt->execute([$id]);
    } elseif ($_GET['action'] == 'approve') {
        $stmt = $pdo->prepare("UPDATE reviews SET status = 'approved' WHERE id = ?");
        $stmt->execute([$id]);
    }
    header("Location: reviews.php");
    exit();
}

// Fetch Reviews
$stmt = $pdo->query("SELECT r.*, res.name as restaurant_name FROM reviews r JOIN restaurants res ON r.restaurant_id = res.id ORDER BY r.created_at DESC");
$reviews = $stmt->fetchAll();
?>

<div class="admin-content">
    <div class="admin-page-header">
        <div>
            <h2>Avis & Modération</h2>
            <p>Assurez la qualité des retours de la communauté.</p>
        </div>
    </div>

    <div class="admin-reviews-list">
        <?php foreach ($reviews as $rev): ?>
        <div class="admin-review-card">
            <div class="reviewer-info" style="display:flex; gap:15px; align-items:center">
                <div class="reviewer-avatar"><?php echo strtoupper($rev['user_name'][0]); ?></div>
                <div>
                    <div class="reviewer-name"><?php echo htmlspecialchars($rev['user_name']); ?></div>
                    <div class="reviewer-date"><?php echo date('d/m/Y', strtotime($rev['created_at'])); ?></div>
                </div>
            </div>
            <div class="review-info-main">
                <div>Sur <span class="review-target-name"><?php echo htmlspecialchars($rev['restaurant_name']); ?></span> • <span style="color:#ffb03a"><?php echo str_repeat('★', $rev['rating']); ?></span></div>
                <p style="color:var(--text-secondary); font-size:0.9rem; margin-top:5px;">"<?php echo htmlspecialchars($rev['text']); ?>"</p>
                <?php if ($rev['status'] == 'pending'): ?>
                    <span class="status-indicator closed" style="margin-top:5px; display:inline-block">En attente</span>
                <?php endif; ?>
            </div>
            <div class="review-actions-btns">
                <?php if ($rev['status'] == 'pending'): ?>
                    <a href="?action=approve&id=<?php echo $rev['id']; ?>" class="btn-icon approve"><i class="fa-solid fa-check"></i></a>
                <?php endif; ?>
                <a href="?action=delete&id=<?php echo $rev['id']; ?>" class="btn-icon delete" onclick="return askDeleteConfirmation(this.href, 'cet avis')">
                    <i class="fa-solid fa-trash"></i>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once 'footer.php'; ?>
