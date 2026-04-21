<?php 
require_once '../config/database.php';
require_once '../model/Auth.php';

// Charger TOUS les restaurants ACTIFS depuis la BD avec leurs menus et avis
$stmt = $pdo->query("SELECT * FROM restaurants WHERE status = 'active' ORDER BY rating DESC");
$all_restaurants = $stmt->fetchAll();
foreach ($all_restaurants as &$r) {
    $m = $pdo->prepare("SELECT name, price, is_best_seller FROM menu_items WHERE restaurant_id = ? ORDER BY is_best_seller DESC, id ASC");
    $m->execute([$r['id']]);
    $r['menu'] = $m->fetchAll();

    $rev = $pdo->prepare("SELECT user_name, text, rating, created_at FROM reviews WHERE restaurant_id = ? AND status = 'approved' ORDER BY created_at DESC LIMIT 5");
    $rev->execute([$r['id']]);
    $r['reviews'] = $rev->fetchAll();
}
unset($r);

// Charger les propositions de l'utilisateur actuel s'il est connecté
$my_submissions = [];
$admin_stats = [];
if (isLoggedIn()) {
    $stmt_my = $pdo->prepare("SELECT * FROM restaurants WHERE user_id = ? ORDER BY created_at DESC");
    $stmt_my->execute([$_SESSION['user_id']]);
    $my_submissions = $stmt_my->fetchAll();

    // Si admin, charger les stats globales pour le tableau de bord intégré
    if (isAdmin()) {
        $admin_stats = [
            'restaurants' => $pdo->query("SELECT COUNT(*) FROM restaurants")->fetchColumn(),
            'reviews'     => $pdo->query("SELECT COUNT(*) FROM reviews")->fetchColumn(),
            'users'       => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
            'avg_rating'  => round($pdo->query("SELECT AVG(rating) FROM restaurants")->fetchColumn() ?? 0, 1)
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ala Dhawkek | على ذوقك - Votre Guide Gastronomique Intuitif</title>

    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Outfit:wght@400;600;700;800&display=swap" rel="stylesheet">
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Leaflet CSS & JS (Interactive Map) -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <!-- Swiper CSS (3D Photo Galleries) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />

    <!-- CSS Personnalisé -->
    <link rel="stylesheet" href="style.css">


    <!-- Chart.js (Statistics) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- App Logic -->
    <script src="../script/app.js" defer></script>
    <!-- Moteur IA (TF-IDF, BFS/DFS, MinMax) -->
    <script src="../script/ai_client.js" defer></script>

    <!-- Données restaurants injectées depuis PHP/MySQL -->
    <script>
    window.APP_RESTAURANTS = <?= json_encode($all_restaurants, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
    window.APP_STATS = <?= json_encode($admin_stats) ?>;
    window.IS_LOGGED_IN = <?= json_encode(isLoggedIn()) ?>;
    </script>
</head>
<body>


    <nav class="navbar">
        <div class="nav-container">
            <div class="logo" onclick="app.showHome()">
                <i class="fa-solid fa-utensils"></i> <bdi>على <span>ذوقك</span></bdi>
            </div>
            <div class="search-bar">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="global-search" onkeyup="app.handleSearch(event)" placeholder="Rechercher l'excellence...">
                <span class="search-shortcut">K</span>
            </div>

            <div class="nav-links">
                <a href="#" onclick="app.showHome()" class="active" id="nav-home">Accueil</a>
                <a href="#" onclick="app.showExplore()" id="nav-explore">Explorer</a>
                <a href="#" onclick="app.showAdd()" id="nav-add">Ajouter <?php if(!isLoggedIn()) echo '<i class="fa-solid fa-lock" style="font-size:0.7rem; opacity:0.6;"></i>'; ?></a>
                <?php if (isAdmin()): ?>
                    <a href="/foodreviewpro/view/admin/dashboard.php" id="nav-admin" class="admin-link"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
                <?php endif; ?>
                
                <?php if (isLoggedIn()): ?>
                    <a href="#" onclick="app.showProfile()" id="nav-profile"><i class="fa-regular fa-user-circle"></i> Profil</a>
                    <a href="login.php?action=logout" class="btn-secondary"><i class="fa-solid fa-right-from-bracket"></i> Déconnexion</a>
                <?php else: ?>
                    <a href="login.php" class="btn-primary" id="nav-login"><i class="fa-regular fa-user"></i> Connexion</a>
                <?php endif; ?>

            </div>
            <div class="mobile-menu-btn">
                <i class="fa-solid fa-bars"></i>
            </div>
        </div>
    </nav>

    <!-- SECTION 1: HOME -->
    <section id="home-view" class="view">
        <div class="hero">
            <div class="hero-content reveal">
                <h1>L'art culinaire<br><span class="text-gradient">En Tunisie.</span></h1>
                <p>Découvrez les meilleures tables du pays, de Tunis à Djerba, et réservez des expériences gastronomiques inoubliables 100% locales.</p>
                <div class="hero-buttons">
                    <button class="btn-primary btn-large" onclick="app.showExplore()">Explorer la carte</button>
                </div>
            </div>
            <div class="hero-overlay"></div>
        </div>



        <div class="container main-content">
            <div class="section-header">
                <h2>Top Restaurants de la semaine</h2>
                <a href="#" onclick="app.showExplore()">Voir tout <i class="fa-solid fa-arrow-right"></i></a>
            </div>
            
            <div class="filter-section">
                <div class="custom-dropdown" id="town-dropdown">
                    <div class="dropdown-trigger" onclick="app.toggleDropdown('town-dropdown')">
                        <div class="trigger-info">
                            <i class="fa-solid fa-location-dot"></i>
                            <span id="selected-town">Savourer dans toute la Tunisie</span>
                        </div>
                        <i class="fa-solid fa-chevron-down arrow"></i>
                    </div>
                    <div class="dropdown-menu">
                        <div class="dropdown-item" onclick="app.selectTown('all', 'Savourer dans toute la Tunisie')">
                            <i class="fa-solid fa-globe"></i> Toute la Tunisie
                        </div>
                        <div class="dropdown-item" onclick="app.selectTown('tunis', 'Tunis')">
                            <i class="fa-solid fa-city"></i> Tunis
                        </div>
                        <div class="dropdown-item" onclick="app.selectTown('sidi bou said', 'Sidi Bou Saïd')">
                            <i class="fa-solid fa-umbrella"></i> Sidi Bou Saïd
                        </div>
                        <div class="dropdown-item" onclick="app.selectTown('sousse', 'Sousse')">
                            <i class="fa-solid fa-sun"></i> Sousse
                        </div>
                        <div class="dropdown-item" onclick="app.selectTown('hammamet', 'Hammamet')">
                            <i class="fa-solid fa-water"></i> Hammamet
                        </div>
                        <div class="dropdown-item" onclick="app.selectTown('djerba', 'Djerba')">
                            <i class="fa-solid fa-palm-tree"></i> Djerba
                        </div>
                        <div class="dropdown-item" onclick="app.selectTown('sfax', 'Sfax')">
                            <i class="fa-solid fa-industry"></i> Sfax
                        </div>
                    </div>
                </div>
            </div>

            <div class="swiper trending-swiper">
                <div class="swiper-wrapper" id="trending-restaurants">
                    <!-- Généré dynamiquement -->
                </div>
                <!-- Pagination -->
                <div class="swiper-pagination"></div>
            </div>
        </div>
    </section>

    <!-- SECTION 2: EXPLORE (Map + List) -->
    <section id="explore-view" class="view hidden">
        <div class="explore-container">
            <div class="explore-sidebar">
                <div class="sidebar-header">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <h2>Explorer</h2>
                        <div class="filters">
                            <select id="filter-sort" onchange="app.renderExploreList()">
                                <option value="rating">Les mieux notés</option>
                                <option value="reviews">Plus commentés</option>
                                <option value="distance">À proximité</option>
                            </select>
                        </div>
                    </div>
                    <div class="tags-filter-container" id="tags-filter-container">
                        <!-- Généré dynamiquement -->
                    </div>
                    <div class="budget-filters" style="margin-top: 15px; display: flex; gap: 10px;">
                        <button class="budget-btn active" onclick="app.setBudget('all')">Tous</button>
                        <button class="budget-btn" onclick="app.setBudget('$')">$ Éco</button>
                        <button class="budget-btn" onclick="app.setBudget('$$')">$$ Moyen</button>
                        <button class="budget-btn" onclick="app.setBudget('$$$')">$$$ Luxe</button>
                    </div>
                </div>
                <div class="restaurant-list" id="explore-restaurants">
                    <!-- Généré dynamiquement -->
                </div>
            </div>
            <div class="map-container">
                <div id="map"></div>
            </div>
        </div>
    </section>

    <!-- SECTION 3: ADD RESTAURANT -->
    <section id="add-view" class="view hidden">
        <div class="container">
            <div class="form-card">
                <div class="add-header">
                    <h2>Proposer un nouveau restaurant</h2>
                    <p>Faites découvrir les meilleures adresses de Tunisie à la communauté.</p>
                </div>
                
                <form id="add-restaurant-form">
                    <!-- SECTION 1: IDENTITÉ -->
                    <div class="add-section">
                        <div class="section-title"><i class="fa-solid fa-id-card"></i> Identité de l'établissement</div>
                        <div class="form-group">
                            <label>Nom du restaurant</label>
                            <input type="text" id="add-name" required placeholder="Ex: Le Petit Tunis">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Gouvernorat / Ville</label>
                                <div class="select-wrapper">
                                    <select id="add-category" required>
                                        <option value="" disabled selected>Choisir une ville...</option>
                                        <option value="Tunis">Tunis</option>
                                        <option value="Ariana">Ariana</option>
                                        <option value="Ben Arous">Ben Arous</option>
                                        <option value="Manouba">Manouba</option>
                                        <option value="Nabeul">Nabeul</option>
                                        <option value="Sousse">Sousse</option>
                                        <option value="Monastir">Monastir</option>
                                        <option value="Mahdia">Mahdia</option>
                                        <option value="Sfax">Sfax</option>
                                        <option value="Bizerte">Bizerte</option>
                                        <option value="Béja">Béja</option>
                                        <option value="Jendouba">Jendouba</option>
                                        <option value="Le Kef">Le Kef</option>
                                        <option value="Siliana">Siliana</option>
                                        <option value="Kairouan">Kairouan</option>
                                        <option value="Kasserine">Kasserine</option>
                                        <option value="Sidi Bouzid">Sidi Bouzid</option>
                                        <option value="Gabès">Gabès</option>
                                        <option value="Medenine">Medenine</option>
                                        <option value="Tataouine">Tataouine</option>
                                        <option value="Gafsa">Gafsa</option>
                                        <option value="Tozeur">Tozeur</option>
                                        <option value="Kebili">Kebili</option>
                                        <option value="Zaghouan">Zaghouan</option>
                                    </select>
                                    <i class="fa-solid fa-chevron-down"></i>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Tags (ambiance, spécialité...)</label>
                                <input type="text" id="add-tags" placeholder="Ex: Traditionnel, Vue Mer, Cosy">
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 2: MÉDIAS -->
                    <div class="add-section">
                        <div class="section-title"><i class="fa-solid fa-image"></i> Photographie de couverture</div>
                        <div class="form-group">
                            <label>Fichier local (image JPG, PNG...)</label>
                            <input type="file" id="add-image-file" accept="image/*" class="custom-file-input">
                        </div>
                        <div style="text-align: center; margin: 10px 0; color: var(--text-secondary); font-size: 0.8rem; font-weight: 600;">OU BIEN</div>
                        <div class="form-group">
                            <label>Lien URL direct</label>
                            <input type="url" id="add-image-url" placeholder="https://images.unsplash.com/...">
                        </div>
                    </div>

                    <!-- SECTION 3: PRÉSENTATION -->
                    <div class="add-section">
                        <div class="section-title"><i class="fa-solid fa-pen-nib"></i> Présentation</div>
                        <div class="form-group">
                            <label>Description de l'expérience</label>
                            <textarea id="add-description" rows="4" required placeholder="Parlez-nous de l'ambiance, de la cuisine, de ce qui rend ce lieu spécial..."></textarea>
                        </div>
                    </div>

                    <!-- SECTION 4: LOCALISATION -->
                    <div class="add-section">
                        <div class="section-title"><i class="fa-solid fa-location-dot"></i> Géolocalisation précise</div>
                        <div id="add-map-picker" style="height: 350px; border-radius: 16px; margin-bottom: 15px; border: 1px solid var(--border-color);"></div>
                        <div id="latlng-display" style="background: rgba(255,107,0,0.1); color: var(--accent-primary); padding: 12px; border-radius: 10px; font-size: 0.85rem; font-weight: 700; text-align: center; border: 1px solid rgba(255,107,0,0.2);">
                            <i class="fa-solid fa-map-pin"></i> Cliquez sur la carte pour définir la position
                        </div>
                        <input type="hidden" id="add-lat" value="36.8065">
                        <input type="hidden" id="add-lng" value="10.1815">
                    </div>

                    <!-- SECTION 5: MENU -->
                    <div class="add-section" style="border-right: 4px solid var(--accent-primary); background: rgba(255,107,0,0.02);">
                        <div class="section-title" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="background: var(--accent-primary); color: #fff; width: 30px; height: 30px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 0.8rem;">
                                    <i class="fa-solid fa-list-ul"></i>
                                </div>
                                <span style="font-size: 1.1rem; letter-spacing: 2px;">MENU SIGNATURE</span>
                            </div>
                            <button type="button" onclick="app.addMenuRow()" class="btn-secondary" style="border-radius: 50px; padding: 10px 20px; font-weight: 700; font-size: 0.8rem; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.05); display: flex; align-items: center; gap: 8px;">
                                <i class="fa-solid fa-plus" style="color: var(--accent-primary);"></i> Ajouter un plat
                            </button>
                        </div>
                        <div id="menu-rows-container" style="display: flex; flex-direction: column; gap: 5px;">
                            <!-- Les lignes de menu sont injectées ici via app.addMenuRow() -->
                        </div>
                    </div>

                    <?php if (isLoggedIn()): ?>
                        <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid var(--border-color);">
                            <button type="button" onclick="app.submitRestaurantForm()" class="btn-primary" style="width: 100%; height: 60px; font-size: 1.1rem; border-radius: 50px; box-shadow: 0 10px 30px rgba(255,107,0,0.3);">
                                <i class="fa-solid fa-paper-plane"></i> Envoyer la proposition
                            </button>
                            <p style="text-align: center; font-size: 0.75rem; color: var(--text-secondary); margin-top: 15px;">
                                En envoyant ce formulaire, vous certifiez l'exactitude des informations fournies.
                            </p>
                        </div>
                    <?php else: ?>
                        <div style="background: rgba(255, 176, 58, 0.1); color: #ffb03a; padding: 1.5rem; border-radius: 15px; text-align: center; margin-top: 2.5rem; border: 1px dashed rgba(255,176,58,0.4);">
                            <i class="fa-solid fa-lock" style="font-size: 1.5rem; margin-bottom: 10px; display: block;"></i> 
                            Vous devez être <a href="login.php" style="font-weight: 800; text-decoration: underline;">connecté</a> pour contribuer à la plateforme.
                        </div>
                    <?php endif; ?>
                </form>

            </div>

            <!-- New: My Submissions List -->
            <?php if (isLoggedIn() && count($my_submissions) > 0): ?>
                <div class="form-card" style="margin-top: 3rem; padding: 2.5rem;">
                    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 2rem;">
                        <h2 style="margin: 0;"><i class="fa-solid fa-clock-rotate-left" style="color: var(--accent-primary);"></i> Mes Propositions</h2>
                        <span style="background: rgba(255,255,255,0.05); padding: 4px 12px; border-radius: 20px; font-size: 0.8rem;"><?php echo count($my_submissions); ?> soumissions</span>
                    </div>

                    <div class="submissions-container">
                        <?php foreach ($my_submissions as $sub): ?>
                            <div class="submission-item">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 20px;">
                                    <div style="flex: 1;">
                                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 5px;">
                                            <h4 style="margin: 0; font-size: 1.2rem;"><?php echo htmlspecialchars($sub['name']); ?></h4>
                                            <span class="status-badge <?php echo $sub['status']; ?>">
                                                <?php 
                                                    if($sub['status'] == 'pending') echo "En attente";
                                                    elseif($sub['status'] == 'active') echo "Accepté";
                                                    else echo "Refusé";
                                                ?>
                                            </span>
                                        </div>
                                        <p style="margin: 0; font-size: 0.9rem; color: var(--text-secondary);">
                                            <i class="fa-solid fa-map-pin" style="font-size: 0.8rem;"></i> <?php echo htmlspecialchars($sub['category']); ?> 
                                            <span style="margin: 0 10px; opacity: 0.3;">|</span>
                                            <i class="fa-solid fa-calendar" style="font-size: 0.8rem;"></i> Posté le <?php echo date('d M Y', strtotime($sub['created_at'])); ?>
                                        </p>
                                        
                                        <?php if ($sub['status'] == 'rejected' && $sub['rejection_reason']): ?>
                                            <div class="rejection-box">
                                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 5px; color: #ff4444; font-weight: 700; font-size: 0.85rem;">
                                                    <i class="fa-solid fa-circle-exclamation"></i> RÉCLAMATION DE L'ADMIN
                                                </div>
                                                <p style="margin: 0; font-size: 0.95rem; color: var(--text-primary); line-height: 1.5; font-style: italic;">
                                                    "<?php echo htmlspecialchars($sub['rejection_reason']); ?>"
                                                </p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if ($sub['status'] == 'active'): ?>
                                        <button class="btn-secondary" style="padding: 8px 15px; font-size: 0.85rem;" onclick="app.showExplore()">Voir sur la carte</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- SECTION 4: PROFILE -->
    <section id="profile-view" class="view hidden">
        <div class="container main-content" style="max-width: 1000px;">
            <!-- Header Profil -->
            <div class="profile-header-premium" style="margin-bottom: 3rem;">
                <div class="profile-main-info" style="display: flex; align-items: center; gap: 30px;">
                    <div class="profile-avatar-big">
                        <?= isLoggedIn() ? strtoupper(substr($_SESSION['user_name'], 0, 1)) : 'U' ?>
                    </div>
                    <div>
                        <h2 style="font-size: 2.5rem; margin: 0; font-family: 'Outfit', sans-serif;">Bonjour, <?= isLoggedIn() ? htmlspecialchars($_SESSION['user_name']) : 'Utilisateur' ?></h2>
                        <p style="color: var(--accent-primary); font-weight: 700; margin: 5px 0 0; letter-spacing: 1px;">MEMBRE GOURMET</p>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div id="profile-stats-grid" class="profile-stats-container" style="margin-bottom: 3rem;">
                <!-- Injecté par JS -->
            </div>

            <!-- Badges Section -->
            <div class="profile-section-wrapper" style="margin-bottom: 4rem;">
                <h3 class="section-title-premium"><i class="fa-solid fa-award"></i> MES BADGES</h3>
                <div id="profile-badges-list" class="badges-grid">
                    <!-- Injecté par JS -->
                </div>
            </div>
            
            <!-- Favoris Section -->
            <div class="profile-section-wrapper" style="margin-bottom: 4rem;">
                <h3 class="section-title-premium"><i class="fa-solid fa-heart"></i> MES FAVORIS</h3>
                <div id="profile-favorites-list" class="restaurant-grid" style="grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));">
                    <!-- Injecté par JS -->
                </div>
            </div>
            
            <!-- Historique Section -->
            <div class="profile-section-wrapper">
                <h3 class="section-title-premium"><i class="fa-solid fa-clock-rotate-left"></i> RÉCEMMENT CONSULTÉS</h3>
                <div id="profile-history-list" class="history-grid">
                    <!-- Injecté par JS -->
                </div>
            </div>
        </div>
    </section>

    <style>
        .profile-header-premium {
            background: linear-gradient(135deg, rgba(255,107,0,0.1) 0%, rgba(20,20,20,0) 100%);
            padding: 40px;
            border-radius: 24px;
            border: 1px solid rgba(255,107,0,0.05);
        }
        .profile-avatar-big {
            width: 100px; height: 100px;
            background: var(--accent-primary);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 3rem; font-weight: 800; color: #fff;
            box-shadow: 0 10px 30px rgba(255,107,0,0.3);
            border: 4px solid rgba(255,255,255,0.1);
        }
        .section-title-premium {
            font-family: 'Outfit', sans-serif;
            font-size: 1rem; font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 25px;
            display: flex; align-items: center; gap: 12px;
            letter-spacing: 2px;
        }
        .section-title-premium i { color: var(--accent-primary); }

        /* Stats items */
        .profile-stats-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }
        .profile-stat-item {
            background: rgba(255,255,255,0.03);
            padding: 25px;
            border-radius: 20px;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.05);
            transition: transform 0.3s ease;
        }
        .profile-stat-item:hover { transform: translateY(-5px); border-color: rgba(255,107,0,0.2); }
        .profile-stat-item .val { font-size: 1.8rem; font-weight: 800; color: #fff; font-family: 'Outfit', sans-serif; margin-bottom: 5px; }
        .profile-stat-item .lab { font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px; font-weight: 700; }

        /* Badges styles */
        .badges-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 15px;
        }
        .badge-item {
            background: rgba(255,255,255,0.02);
            padding: 20px;
            border-radius: 16px;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.05);
            filter: grayscale(1);
            opacity: 0.5;
            transition: all 0.4s ease;
        }
        .badge-item.unlocked {
            filter: grayscale(0);
            opacity: 1;
            background: rgba(255,107,0,0.03);
            border-color: rgba(255,107,0,0.2);
            box-shadow: 0 5px 15px rgba(255,107,0,0.05);
        }
        .badge-icon { font-size: 1.8rem; margin-bottom: 12px; color: var(--accent-primary); }
        .badge-name { font-weight: 800; font-size: 0.9rem; margin-bottom: 4px; }
        .badge-desc { font-size: 0.7rem; color: var(--text-secondary); }

        @media (max-width: 768px) {
            .profile-stats-container { grid-template-columns: repeat(2, 1fr); }
            .profile-header-premium { text-align: center; }
            .profile-main-info { flex-direction: column; }
        }
    </style>

    <!-- SECTION 5: LOGIN / REGISTER -->
    <section id="login-view" class="view hidden">
        <div class="container" style="display: flex; justify-content: center; align-items: center; min-height: 80vh;">
            <div class="form-card" style="width: 100%; max-width: 450px; padding: 3rem 2.5rem; text-align: center;">
                <div class="login-header" style="margin-bottom: 2.5rem;">
                    <h2>Bienvenue</h2>
                    <p style="margin-bottom: 0;">Connectez-vous pour continuer vers l'excellence.</p>
                </div>
                <form id="login-form">
                    <div class="form-group" style="text-align: left;">
                        <label>Adresse E-mail</label>
                        <input type="email" id="login-email" required placeholder="vous@exemple.com">
                    </div>
                    <div class="form-group" style="text-align: left;">
                        <label>Mot de passe</label>
                        <input type="password" id="login-password" required placeholder="Votre mot de passe">
                    </div>
                    <div class="login-options" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; font-size: 0.9rem;">
                        <label class="remember-me" style="display: flex; align-items: center; gap: 8px; cursor: pointer; color: var(--text-secondary);">
                            <input type="checkbox" style="width: auto; accent-color: var(--accent-primary);"> Se souvenir de moi
                        </label>
                        <a href="#" class="forgot-password" style="color: var(--accent-primary); font-weight: 600;">Mot de passe oublié ?</a>
                    </div>
                    <button type="button" onclick="app.login()" class="btn-primary btn-large" style="width: 100%; justify-content: center; font-size: 1.1rem; padding: 1.2rem;">Se connecter</button>
                    <button type="button" class="btn-secondary btn-large" style="width: 100%; justify-content: center; font-size: 1.1rem; padding: 1.2rem; margin-top: 1rem; display: flex; align-items: center; gap: 10px;">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/c/c1/Google_%22G%22_logo.svg" alt="Google" style="width: 20px; height: 20px;"> Continuer avec Google
                    </button>
                </form>
                <div class="login-footer" style="margin-top: 2.5rem; font-size: 0.95rem;">
                    <p style="margin-bottom: 0;">Nouveau sur Ala Dhawkek ? <a href="signup.php" class="text-gradient" style="font-weight: 700;">Créer un compte</a></p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- SECTION 5: ADMIN DASHBOARD -->
    <section id="admin-view" class="view hidden">
        <div class="admin-container">
            <div class="admin-sidebar">
                <div class="admin-sidebar-header">
                    <h3><i class="fa-solid fa-gauge-high"></i> Dashboard</h3>
                </div>
                <div class="admin-nav">
                    <div class="admin-nav-item active" onclick="app.switchAdminTab('overview', event)">
                        <i class="fa-solid fa-chart-line"></i> Vue d'ensemble
                    </div>
                    <div class="admin-nav-item" onclick="app.switchAdminTab('restaurants', event)">
                        <i class="fa-solid fa-utensils"></i> Restaurants
                    </div>
                    <div class="admin-nav-item" onclick="app.switchAdminTab('reviews', event)">
                        <i class="fa-solid fa-star"></i> Avis & Modération
                    </div>
                    <div class="admin-nav-item" onclick="app.switchAdminTab('users', event)">
                        <i class="fa-solid fa-users"></i> Utilisateurs
                    </div>
                </div>
            </div>
            
            <div class="admin-content">
                <!-- Overview Tab -->
                <div id="admin-tab-overview" class="admin-tab-content active">
                    <div class="admin-page-header">
                        <h2>Vue d'ensemble</h2>
                        <p>Statistiques et performances de la plateforme.</p>
                    </div>
                    <div class="admin-stats-grid" id="admin-stats-summary">
                        <!-- Dynamique -->
                    </div>
                    <div class="admin-charts-row">
                        <div class="admin-chart-card">
                            <h3>Croissance des avis</h3>
                            <canvas id="reviews-chart"></canvas>
                        </div>
                        <div class="admin-chart-card">
                            <h3>Popularité par ville</h3>
                            <canvas id="city-chart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Restaurants Tab -->
                <div id="admin-tab-restaurants" class="admin-tab-content">
                    <div class="admin-page-header">
                        <h2>Gestion des Restaurants</h2>
                        <button class="btn-primary" onclick="app.showAdd()"><i class="fa-solid fa-plus"></i> Nouveau</button>
                    </div>
                    <div class="admin-table-container">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Catégorie</th>
                                    <th>Note</th>
                                    <th>Avis</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="admin-restaurants-list">
                                <!-- Dynamique -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Reviews Tab -->
                <div id="admin-tab-reviews" class="admin-tab-content">
                    <div class="admin-page-header">
                        <h2>Modération des Avis</h2>
                    </div>
                    <div class="admin-reviews-list" id="admin-reviews-list">
                        <!-- Dynamique -->
                    </div>
                </div>

                <!-- Users Tab -->
                <div id="admin-tab-users" class="admin-tab-content">
                    <div class="admin-page-header">
                        <h2>Utilisateurs</h2>
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
                            <tbody id="admin-users-list">
                                <!-- Dynamique -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- MODAL : Restaurant Details -->
    <div id="restaurant-modal" class="modal">
        <div class="modal-content">
            <div class="modal-close" onclick="app.closeModal()"><i class="fa-solid fa-xmark"></i></div>
            <div id="modal-body">
                <!-- Dynamique -->
            </div>
        </div>
    </div>






    <!-- Scripts Externes -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/vanilla-tilt/1.8.1/vanilla-tilt.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

    <!-- App Logic -->
    <!-- (Supprimé ici car déplacé dans le head avec defer pour une meilleure structure) -->
</body>
</html>

<style>
    /* Styles spécifiques pour le formulaire d'ajout premium */
    #add-view .form-card {
        background: var(--bg-card);
        padding: 4rem;
        border-radius: 32px;
        border: 1px solid var(--border-color);
        box-shadow: var(--shadow-premium);
    }

    .add-header { text-align: center; margin-bottom: 50px; }
    .add-header h2 { font-size: 2.8rem; margin-bottom: 12px; color: #fff; font-family: 'Outfit', sans-serif; letter-spacing: -1px; }
    .add-header p { font-size: 1.1rem; color: var(--text-secondary); max-width: 500px; margin: 0 auto; }
    
    .add-section {
        background: rgba(255,255,255,0.02);
        padding: 30px;
        border-radius: 24px;
        border: 1px solid rgba(255,255,255,0.06);
        margin-bottom: 35px;
        transition: transform 0.3s ease, border-color 0.3s ease;
    }
    
    .add-section:hover {
        border-color: rgba(255, 107, 0, 0.2);
    }
    
    .section-title {
        font-family: 'Outfit', sans-serif;
        font-size: 0.9rem;
        font-weight: 800;
        color: var(--accent-primary);
        margin-bottom: 25px;
        text-transform: uppercase;
        letter-spacing: 2px;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .section-title i { font-size: 1.2rem; }
    
    /* Harmonisation des champs de saisie (Dark Mode) */
    .form-group label {
        display: block;
        margin-bottom: 10px;
        font-weight: 600;
        font-size: 0.9rem;
        color: var(--text-secondary);
        padding-left: 5px;
    }

    .form-group input, 
    .form-group select, 
    .form-group textarea {
        width: 100%;
        background: rgba(255, 255, 255, 0.05) !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        border-radius: 12px;
        padding: 15px 20px !important;
        color: #fff !important;
        font-family: 'Inter', sans-serif;
        font-size: 0.95rem;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        outline: none;
    }

    /* Supprimer le style blanc par défaut des navigateurs */
    .form-group input::placeholder, 
    .form-group textarea::placeholder {
        color: rgba(255,255,255,0.3);
    }

    .form-group input:focus, 
    .form-group select:focus, 
    .form-group textarea:focus {
        background: rgba(255, 255, 255, 0.08) !important;
        border-color: var(--accent-primary) !important;
        box-shadow: 0 0 0 4px rgba(255, 107, 0, 0.15) !important;
        transform: translateY(-2px);
    }

    /* Style spécifique pour le sélecteur de ville */
    .select-wrapper { position: relative; }
    .select-wrapper i {
        position: absolute;
        right: 20px;
        top: 50%;
        transform: translateY(-50%);
        pointer-events: none;
        color: var(--accent-primary);
        font-size: 0.9rem;
    }
    
    #add-category {
        -webkit-appearance: none;
        appearance: none;
        cursor: pointer;
        padding-right: 50px !important;
    }

    /* Options du select pour le mode sombre */
    #add-category option {
        background: #141414;
        color: #fff;
        padding: 10px;
    }

    .custom-file-input {
        padding: 20px !important;
        background: rgba(255,255,255,0.03) !important;
        border: 2px dashed rgba(255,255,255,0.1) !important;
        border-radius: 16px;
        cursor: pointer;
        text-align: center;
    }

    .custom-file-input:hover { 
        border-color: var(--accent-primary) !important; 
        background: rgba(255,255,255,0.06) !important;
    }

    #latlng-display {
        background: rgba(255,107,0,0.08);
        color: var(--accent-primary);
        padding: 15px;
        border-radius: 12px;
        font-size: 0.9rem;
        font-weight: 700;
        text-align: center;
        border: 1px solid rgba(255,107,0,0.15);
        margin-top: 15px;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 25px;
    }

    @media (max-width: 768px) {
        .form-row { grid-template-columns: 1fr; }
        #add-view .form-card { padding: 2rem; }
    }
</style>


