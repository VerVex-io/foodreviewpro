<header class="admin-header">
    <div class="header-search">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" placeholder="Rechercher...">
    </div>

    <div class="header-actions">
        <div class="notifications">
            <i class="fa-regular fa-bell"></i>
            <span class="badge">3</span>
        </div>
        <div class="admin-profile">
            <img src="https://ui-avatars.com/api/?name=Admin&background=random" alt="Admin">
            <div class="admin-profile-info">
                <span class="name"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?></span>
                <span class="role">Administrateur <i class="fa-solid fa-chevron-down" style="font-size:0.6rem;"></i></span>
            </div>
        </div>
    </div>
</header>
