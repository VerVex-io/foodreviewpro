<?php
// Déterminer la page courante pour la classe 'active'
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="admin-sidebar">
    <div class="sidebar-brand">
        <i class="fa-solid fa-utensils"></i>
        <div>
            3la dhawhek
            <span class="sidebar-brand-sub">Guide Gourmand</span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <a href="dashboard.php" class="nav-item <?= $current_page == 'dashboard.php' ? 'active' : '' ?>"><i class="fa-solid fa-house"></i> Tableau de bord</a>
        <a href="restaurants.php" class="nav-item <?= $current_page == 'restaurants.php' || $current_page == 'add_restaurant.php' ? 'active' : '' ?>"><i class="fa-solid fa-utensils"></i> Restaurants</a>
        <a href="categories.php" class="nav-item <?= $current_page == 'categories.php' ? 'active' : '' ?>"><i class="fa-solid fa-layer-group"></i> Catégories</a>
        <a href="users.php" class="nav-item <?= $current_page == 'users.php' ? 'active' : '' ?>"><i class="fa-solid fa-user-group"></i> Utilisateurs</a>
    </nav>

    <div class="sidebar-footer">
        <a href="../index.php" class="site-preview-card">
            <img src="https://images.unsplash.com/photo-1514933651103-005eec06c04b?q=80&w=100&auto=format&fit=crop" alt="Preview">
            <div class="site-preview-text">
                <span class="title">Voir le site</span>
                <span class="subtitle">3la dhawhek.tn</span>
            </div>
        </a>
    </div>
</aside>
