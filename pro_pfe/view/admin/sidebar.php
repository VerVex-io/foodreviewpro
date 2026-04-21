<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="admin-sidebar" style="height: 100vh; position: sticky; top: 0;">
    <div class="admin-sidebar-header">
        <h3 onclick="window.location.href='index.php'" style="cursor:pointer">
            <i class="fa-solid fa-utensils"></i> <bdi>على <span>ذوقك</span></bdi>
        </h3>
    </div>
    <div class="admin-nav">
        <a href="dashboard.php" class="admin-nav-item <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-chart-line"></i> Vue d'ensemble
        </a>
        <a href="restaurants.php" class="admin-nav-item <?php echo $current_page == 'restaurants.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-utensils"></i> Restaurants
        </a>
        <a href="reviews.php" class="admin-nav-item <?php echo $current_page == 'reviews.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-star"></i> Avis & Modération
        </a>
        <a href="users.php" class="admin-nav-item <?php echo $current_page == 'users.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-users"></i> Utilisateurs
        </a>
        <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.05);">
            <a href="../index.php" class="admin-nav-item">
                <i class="fa-solid fa-house"></i> Retour au site
            </a>
        </div>
    </div>
    <div class="admin-sidebar-footer" style="padding: 2rem; margin-top: auto; border-top: 1px solid var(--border-color);">
        <a href="../login.php?action=logout" style="color: #ff4444; font-weight: 600; font-size: 0.9rem;">
            <i class="fa-solid fa-right-from-bracket"></i> Déconnexion
        </a>
        
        <div style="margin-top: 1.5rem; display: flex; align-items: center; justify-content: center;">
             <button onclick="toggleAdminTheme()" class="theme-toggle" title="Changer le thème" style="background: rgba(255,255,255,0.05); border: 1px solid var(--border-color); color: var(--accent-primary); width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.3s ease;">
                <i class="fa-solid fa-sun"></i>
                <i class="fa-solid fa-moon"></i>
            </button>
        </div>
    </div>
</div>
