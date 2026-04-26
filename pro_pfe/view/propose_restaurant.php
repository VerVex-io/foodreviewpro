<?php
require_once '../config/database.php';
require_once '../model/Auth.php';

// On vérifie si l'utilisateur est connecté pour l'affichage conditionnel
$is_logged_in = isLoggedIn();

$success = isset($_GET['success']) ? $_GET['success'] : false;
$error = isset($_GET['error']) ? urldecode($_GET['error']) : false;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter votre restaurant - Ala Dhawkek</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #ff6b00;
            --bg-dark: #0a0a0b;
            --card-bg: #141415; /* Dark solid color matching screenshot */
            --border-color: #2a2a2c;
            --input-bg: #1a1a1c;
            --input-border: #333335;
            --text-main: #ffffff;
            --text-muted: #888888;
            --red-asterisk: #ff4444;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-dark);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        /* Background Image with heavy overlay */
        .page-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('https://images.unsplash.com/photo-1414235077428-338989a2e8c0?q=80&w=2000&auto=format&fit=crop') center/cover no-repeat;
            z-index: -1;
        }
        .page-bg::after {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(12, 12, 14, 0.90); /* Very dark overlay */
        }

        /* Navbar (Identique à index.php) */
        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            padding: 1rem 2rem;
            z-index: 1000;
            background: rgba(10, 10, 11, 0.8);
            backdrop-filter: blur(15px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.3s ease;
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }

        .logo {
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .search-bar {
            flex: 1;
            max-width: 500px;
            margin: 0 2rem;
            position: relative;
            display: flex;
            align-items: center;
        }

        .search-bar i {
            position: absolute;
            left: 1.2rem;
            color: var(--text-muted);
            font-size: 1rem;
        }

        .search-bar input {
            width: 100%;
            padding: 0.9rem 1.2rem 0.9rem 3rem;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 50px;
            color: white;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            font-family: 'Inter', sans-serif;
            outline: none;
        }

        .search-bar input:focus {
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(255, 107, 0, 0.4);
            box-shadow: 0 0 15px rgba(255, 107, 0, 0.1);
        }

        .search-shortcut {
            position: absolute;
            right: 1.2rem;
            background: rgba(255, 255, 255, 0.1);
            padding: 0.2rem 0.6rem;
            border-radius: 4px;
            font-size: 0.7rem;
            color: var(--text-muted);
            font-weight: 600;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .nav-links a {
            color: var(--text-main);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            padding: 0.5rem 1rem;
            border-radius: 50px;
        }

        .nav-links a:hover {
            color: var(--primary);
            background: rgba(255, 107, 0, 0.1);
        }

        #nav-add {
            background: var(--primary);
            color: white;
            padding: 0.6rem 1.2rem;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(255, 107, 0, 0.3);
        }

        #nav-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 107, 0, 0.4);
            color: white;
        }
        
        .admin-link {
            color: #ffd700 !important;
            background: rgba(255, 215, 0, 0.1);
        }

        .nav-btn-logout:hover {
            color: #ff4757 !important;
            background: rgba(255, 71, 87, 0.1) !important;
        }

        body { padding-top: 80px; }

        /* Main Content */
        .container {
            max-width: 1200px; /* Wider to match the screenshot */
            margin: 0 auto;
            padding: 2.5rem 2rem 5rem;
            width: 100%;
            position: relative;
            z-index: 10;
        }

        .page-header {
            text-align: center;
            margin-bottom: 3.5rem;
        }
        .page-header h1 {
            font-family: 'Inter', sans-serif;
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 8px;
            color: white;
        }
        .page-header h1 span { color: var(--primary); }
        .page-header p {
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        /* Form Grid */
        .form-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            align-items: stretch; /* Cards stretch to same height in a row */
        }
        
        /* Form Cards */
        .form-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 30px;
            display: flex;
            flex-direction: column;
        }

        .card-full { grid-column: 1 / -1; }

        .card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
        }
        .step-number {
            background: var(--primary);
            color: white;
            width: 26px; height: 26px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 0.85rem;
        }
        .card-title {
            font-family: 'Inter', sans-serif;
            font-size: 1.05rem;
            font-weight: 600;
            display: flex; align-items: center; gap: 8px;
            color: #fff;
        }
        .card-title i { color: #888; font-size: 0.95rem; }

        /* Form Controls */
        .form-group { margin-bottom: 20px; position: relative; width: 100%; }
        .form-row { display: flex; gap: 20px; width: 100%; } /* Flex for exact column splitting */
        
        label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.8rem;
            font-weight: 600;
            color: #fff;
        }
        label span { color: var(--red-asterisk); }

        input[type="text"], input[type="email"], input[type="number"], select, textarea {
            width: 100%;
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: 8px;
            padding: 12px 16px;
            color: white;
            font-family: 'Inter', sans-serif;
            font-size: 0.85rem;
            transition: border-color 0.2s;
        }
        input::placeholder, textarea::placeholder { color: #555; }
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #666;
        }
        textarea { resize: none; flex-grow: 1; min-height: 100px; } /* Flex grow to fill remaining space */
        
        select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23666666'%3E%3Cpath d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 16px;
            padding-right: 36px;
        }
        select option { background: var(--input-bg); color: white; }

        .char-count { 
            position: absolute;
            bottom: 12px; right: 16px;
            font-size: 0.7rem; color: #555; 
            pointer-events: none;
        }

        /* Dropzone */
        .dropzone-container {
            border: 1px dashed #444;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            background: transparent;
            cursor: pointer;
            transition: all 0.2s;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        .dropzone-container:hover { border-color: #666; }
        .dropzone-container i { font-size: 1.8rem; color: #fff; }
        .dropzone-content { text-align: left; }
        .dropzone-content p { font-size: 0.85rem; margin-bottom: 2px; color: #fff; font-weight: 500; }
        .dropzone-content span { font-size: 0.75rem; color: #666; }

        .gallery-preview {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
        }
        .gallery-item {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            aspect-ratio: 4/3;
            border: 1px solid var(--border-color);
        }
        .gallery-item img { width: 100%; height: 100%; object-fit: cover; }
        .remove-btn {
            position: absolute; top: 6px; right: 6px;
            background: #fff; color: #000;
            border: none; border-radius: 50%;
            width: 20px; height: 20px;
            cursor: pointer; display: flex; align-items: center; justify-content: center;
            font-size: 0.7rem; font-weight:bold;
        }
        .add-more-btn {
            border: 1px dashed #444;
            border-radius: 8px;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            gap: 8px; cursor: pointer; color: #888; font-size: 0.75rem; text-align:center;
            background: transparent; aspect-ratio: 4/3;
        }
        .add-more-btn i { font-size: 1.2rem; color: #888; }

        /* Social buttons */
        .social-row {
            display: flex; gap: 10px; height: 42px; /* Fixed height to match inputs */
        }
        .social-btn {
            flex: 1;
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            color: #888;
            cursor: pointer;
            transition: 0.2s;
        }
        .social-btn:hover { border-color: #555; color: #fff; }

        /* Footer / Submit */
        .form-footer {
            margin-top: 40px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }
        
        /* Custom Checkbox */
        .terms-container {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.85rem;
            color: #ddd;
        }
        .custom-checkbox {
            appearance: none;
            width: 18px; height: 18px;
            background: var(--primary); /* Checked by default styling */
            border: 1px solid var(--primary);
            border-radius: 4px;
            cursor: pointer;
            position: relative;
        }
        .custom-checkbox::after {
            content: '\f00c';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            color: white;
            font-size: 10px;
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
        }
        .terms-container a { color: var(--primary); text-decoration: underline; }

        .btn-submit {
            background: var(--primary);
            color: white;
            border: none;
            padding: 16px 0;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: 10px;
            width: 380px; /* Wide button */
        }
        .btn-submit:hover { background: var(--primary-hover); }

        /* Alerts */
        .alert {
            padding: 14px; border-radius: 8px; margin-bottom: 24px;
            display: flex; align-items: center; gap: 12px; font-size: 0.9rem; font-weight: 500;
        }
        .alert-success { background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3); color: #4ade80; }
        .alert-error { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: #f87171; }
    </style>
</head>
<body>

    <div class="page-bg"></div>

    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo">
                <i class="fa-solid fa-utensils" style="color: var(--primary); font-size: 2.2rem;"></i>
                <div style="display: flex; flex-direction: column; line-height: 1.1;">
                    <span style="font-family: 'Playfair Display', serif; font-size: 1.6rem; font-weight: 900; color: #fff; letter-spacing: 1px;">3la dhaw9ek</span>
                    <span style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 3px; color: var(--primary); font-weight: 700;">Guide Gourmand</span>
                </div>
            </a>
            
            <div class="search-bar">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" placeholder="Rechercher l'excellence...">
                <span class="search-shortcut">K</span>
            </div>

            <div class="nav-links">
                <a href="index.php?view=explore" class="nav-btn-explorer">
                    <i class="fa-solid fa-compass"></i> Explorer
                </a>
                
                <a href="propose_restaurant.php" id="nav-add">
                    <i class="fa-solid fa-plus-circle"></i> Ajouter
                </a>

                <?php if (isLoggedIn()): ?>
                    <?php if (isAdmin()): ?>
                        <a href="admin/dashboard.php" class="admin-link">
                            <i class="fa-solid fa-crown"></i> Admin Hub
                        </a>
                    <?php endif; ?>
                    <a href="index.php" id="nav-profile">
                        <i class="fa-solid fa-circle-user"></i> Profil
                    </a>
                    <a href="login.php?action=logout" class="nav-btn-logout">
                        <i class="fa-solid fa-right-from-bracket"></i> Déconnexion
                    </a>
                <?php else: ?>
                    <a href="login.php" class="nav-btn-login">
                        <i class="fa-solid fa-user"></i> Connexion
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <?php if ($is_logged_in): ?>
    <div class="container">
        <div class="page-header">
            <h1>Ajouter <span>votre restaurant</span></h1>
            <p>Rejoignez notre guide et faites découvrir votre établissement</p>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fa-solid fa-circle-check"></i>
                <div>Votre restaurant a été soumis avec succès ! Il sera examiné par notre équipe.</div>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <div><?= htmlspecialchars($error) ?></div>
            </div>
        <?php endif; ?>

        <form action="../controller/submit_proposal.php" method="POST" enctype="multipart/form-data">
            <div class="form-layout">
                
                <!-- Card 1: Infos Generales -->
                <div class="form-card">
                    <div class="card-header">
                        <div class="step-number">1</div>
                        <div class="card-title"><i class="fa-solid fa-building-user"></i> Informations générales</div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Nom du restaurant <span>*</span></label>
                            <input type="text" name="name" placeholder="Ex: Le Golfe" required>
                        </div>
                        <div class="form-group">
                            <label>Catégorie <span>*</span></label>
                            <select name="category" required>
                                <option value="" disabled selected>Sélectionner une catégorie</option>
                                <option value="Restaurant">Restaurant</option>
                                <option value="Café">Café</option>
                                <option value="Salon de thé">Salon de thé</option>
                                <option value="Fast Food">Fast Food</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Type de cuisine <span>*</span></label>
                            <select name="cuisine_type" required>
                                <option value="" disabled selected>Sélectionner un type de cuisine</option>
                                <option value="Tunisienne">Tunisienne</option>
                                <option value="Italienne">Italienne</option>
                                <option value="Française">Française</option>
                                <option value="Internationale">Internationale</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Fourchette de prix <span>*</span></label>
                            <select name="price_range" required>
                                <option value="" disabled selected>Sélectionner une fourchette de prix</option>
                                <option value="$ - Économique">$ - Économique</option>
                                <option value="$$ - Moyen">$$ - Moyen</option>
                                <option value="$$$ - Luxe">$$$ - Luxe</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom:0; display:flex; flex-direction:column; flex-grow:1;">
                        <label>Description <span>*</span></label>
                        <textarea name="description" placeholder="Décrivez votre restaurant, votre ambiance, vos spécialités..." required maxlength="500"></textarea>
                        <div class="char-count">0/500</div>
                    </div>
                </div>

                <!-- Card 2: Localisation -->
                <div class="form-card">
                    <div class="card-header">
                        <div class="step-number">2</div>
                        <div class="card-title"><i class="fa-solid fa-location-dot"></i> Localisation</div>
                    </div>

                    <div class="form-group">
                        <label>Ville <span>*</span></label>
                        <select name="town" required>
                            <option value="" disabled selected>Sélectionner votre ville</option>
                            <option value="Tunis">Tunis</option>
                            <option value="Sousse">Sousse</option>
                            <option value="Sfax">Sfax</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Adresse complète <span>*</span></label>
                        <input type="text" name="address" placeholder="Ex: Rue de la Liberté, Sousse, Tunisie" required>
                    </div>

                    <div class="form-row" style="margin-bottom:0;">
                        <div class="form-group">
                            <label>Latitude (optionnel)</label>
                            <input type="text" name="lat" placeholder="Ex: 35.8256">
                        </div>
                        <div class="form-group">
                            <label>Longitude (optionnel)</label>
                            <input type="text" name="lng" placeholder="Ex: 10.6364">
                        </div>
                    </div>
                </div>

                <!-- Card 3: Contact -->
                <div class="form-card">
                    <div class="card-header">
                        <div class="step-number">3</div>
                        <div class="card-title"><i class="fa-solid fa-phone"></i> Contact</div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Téléphone <span>*</span></label>
                            <input type="text" name="phone" placeholder="+216 XX XXX XXX" required>
                        </div>
                        <div class="form-group">
                            <label>Email <span>*</span></label>
                            <input type="email" name="email" placeholder="contact@exemple.com" required>
                        </div>
                    </div>

                    <div class="form-row" style="margin-bottom:0;">
                        <div class="form-group">
                            <label>Site web (optionnel)</label>
                            <input type="text" name="website" placeholder="https://www.exemple.com">
                        </div>
                        <div class="form-group">
                            <label>Réseaux sociaux (optionnel)</label>
                            <div class="social-row">
                                <div class="social-btn"><i class="fa-brands fa-facebook-f"></i></div>
                                <div class="social-btn"><i class="fa-brands fa-instagram"></i></div>
                                <div class="social-btn"><i class="fa-brands fa-youtube"></i></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 4: Images -->
                <div class="form-card">
                    <div class="card-header">
                        <div class="step-number">4</div>
                        <div class="card-title"><i class="fa-regular fa-images"></i> Images du restaurant</div>
                    </div>

                    <div class="dropzone-container" onclick="document.getElementById('image_file').click()">
                        <input type="file" id="image_file" name="image_file" accept="image/*" style="display:none;" required>
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                        <div class="dropzone-content">
                            <p>Cliquez pour télécharger ou glissez-déposez vos images ici</p>
                            <span>PNG, JPG ou WEBP (Max. 5MB par image)</span>
                        </div>
                    </div>

                    <div class="gallery-preview" id="gallery-preview">
                        <!-- Simulated pre-filled images exactly as in the screenshot -->
                        <div class="gallery-item">
                            <img src="https://images.unsplash.com/photo-1514933651103-005eec06c04b?w=200" alt="img1">
                            <button type="button" class="remove-btn"><i class="fa-solid fa-xmark"></i></button>
                        </div>
                        <div class="gallery-item">
                            <img src="https://images.unsplash.com/photo-1544148103-0773bf10d330?w=200" alt="img2">
                            <button type="button" class="remove-btn"><i class="fa-solid fa-xmark"></i></button>
                        </div>
                        <div class="gallery-item">
                            <img src="https://images.unsplash.com/photo-1550966871-3ed3cdb5ed0c?w=200" alt="img3">
                            <button type="button" class="remove-btn"><i class="fa-solid fa-xmark"></i></button>
                        </div>
                        <div class="add-more-btn" onclick="document.getElementById('image_file').click()">
                            <i class="fa-solid fa-plus"></i>
                            <span>Ajouter<br>plus d'images</span>
                        </div>
                    </div>
                </div>

                <!-- Card 5: Infos supp -->
                <div class="form-card card-full">
                    <div class="card-header">
                        <div class="step-number">5</div>
                        <div class="card-title"><i class="fa-solid fa-file-lines"></i> Informations supplémentaires</div>
                    </div>

                    <div class="form-row" style="margin-bottom:0;">
                        <div class="form-group" style="flex:1;">
                            <label>Capacité d'accueil</label>
                            <input type="text" name="capacity" placeholder="Ex: 80 personnes">
                        </div>
                        <div class="form-group" style="flex:1;">
                            <label>Équipements & Services (optionnel)</label>
                            <input type="text" name="amenities" placeholder="Ex: Wifi, Parking, Terrasse, Musique live...">
                        </div>
                        <div class="form-group" style="flex:1;">
                            <label>Heures d'ouverture</label>
                            <input type="text" name="hours" placeholder="Ex: 09:00 - 23:00">
                        </div>
                    </div>
                </div>

            </div>

            <div class="form-footer">
                <div class="terms-container">
                    <input type="checkbox" required id="terms" class="custom-checkbox" checked>
                    <label for="terms" style="margin:0; font-weight:400;">J'accepte les <a href="#">conditions générales</a> et la <a href="#">politique de confidentialité</a></label>
                </div>
                
                <button type="submit" class="btn-submit">
                    <i class="fa-regular fa-paper-plane"></i> Soumettre mon restaurant
                </button>
            </div>
        </form>

    </div>
    <?php else: ?>
    <div class="container" style="display: flex; justify-content: center; align-items: center; min-height: 70vh;">
        <div style="background: rgba(20, 20, 21, 0.7); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 20px; padding: 50px 40px; max-width: 480px; text-align: center; box-shadow: 0 15px 40px rgba(0,0,0,0.4);">
            <div style="width: 80px; height: 80px; background: rgba(255,107,0,0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; box-shadow: 0 0 30px rgba(255,107,0,0.15);">
                <i class="fa-solid fa-lock" style="font-size: 2rem; color: var(--primary);"></i>
            </div>
            <h2 style="font-family: 'Inter', sans-serif; font-size: 1.8rem; margin-bottom: 12px; color: #fff; font-weight: 700; letter-spacing: -0.5px;">Accès Réservé</h2>
            <p style="color: rgba(255, 255, 255, 0.6); font-size: 0.95rem; line-height: 1.6; margin-bottom: 35px;">
                Pour garantir la qualité de notre guide gourmand, la proposition de nouveaux établissements est réservée aux membres de notre communauté.
            </p>
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <a href="login.php" style="background: linear-gradient(135deg, #ffb03a 0%, #ff6b00 100%); color: white; padding: 14px 20px; border-radius: 12px; text-decoration: none; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 10px; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(255, 107, 0, 0.3);">
                    <i class="fa-regular fa-user"></i> Se connecter / S'inscrire
                </a>
                <a href="index.php" style="background: transparent; color: rgba(255, 255, 255, 0.6); padding: 14px 20px; border-radius: 12px; text-decoration: none; font-weight: 500; display: flex; align-items: center; justify-content: center; gap: 10px; transition: all 0.3s ease;" onmouseover="this.style.color='#fff'; this.style.background='rgba(255,255,255,0.05)';" onmouseout="this.style.color='rgba(255, 255, 255, 0.6)'; this.style.background='transparent';">
                    <i class="fa-solid fa-arrow-left"></i> Ignorer et retourner à l'exploration
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script>
        // File input handling to show new images in gallery
        document.getElementById('image_file').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('gallery-preview');
                    const addBtn = preview.querySelector('.add-more-btn');
                    const imgDiv = document.createElement('div');
                    imgDiv.className = 'gallery-item';
                    imgDiv.innerHTML = `
                        <img src="${e.target.result}" alt="preview">
                        <button type="button" class="remove-btn" onclick="this.parentElement.remove()"><i class="fa-solid fa-xmark"></i></button>
                    `;
                    preview.insertBefore(imgDiv, addBtn);
                }
                reader.readAsDataURL(file);
            }
        });

        // Textarea char count
        const textarea = document.querySelector('textarea[name="description"]');
        const charCount = document.querySelector('.char-count');
        textarea.addEventListener('input', function() {
            charCount.textContent = `${this.value.length}/500`;
        });
        
        // Setup initial remove buttons
        document.querySelectorAll('.remove-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                this.parentElement.remove();
            });
        });
    </script>
</body>
</html>
