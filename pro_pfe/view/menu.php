<?php
require_once '../config/database.php';
require_once '../model/Auth.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$stmt = $pdo->prepare("SELECT * FROM restaurants WHERE id = ?");
$stmt->execute([$id]);
$restaurant = $stmt->fetch();

if (!$restaurant) {
    die("Restaurant introuvable.");
}

$stmtMenu = $pdo->prepare("SELECT * FROM menu_items WHERE restaurant_id = ? ORDER BY price ASC");
$stmtMenu->execute([$id]);
$menu = $stmtMenu->fetchAll();

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu - <?php echo htmlspecialchars($restaurant['name']); ?></title>
    <script>
        (function() {
            const savedTheme = localStorage.getItem('atng_theme') || 'dark';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .menu-page-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 4rem 2rem;
            color: var(--text-primary);
        }
        .menu-header {
            text-align: center;
            margin-bottom: 4rem;
            animation: fadeInDown 0.6s ease;
        }
        .menu-header h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 3.5rem;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, var(--text-primary) 0%, var(--text-secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .menu-header p {
            color: var(--text-secondary);
            font-size: 1.2rem;
        }
        .menu-list-container {
            background: var(--bg-card);
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            border: 1px solid rgba(255, 107, 0, 0.1);
            animation: fadeInUp 0.6s ease 0.2s both;
        }
        .back-btn-container {
            margin-bottom: 2rem;
        }
        .back-btn-container a {
            color: var(--text-secondary);
            text-decoration: none;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 1.1rem;
            padding: 10px 20px;
            border-radius: 10px;
            background: rgba(255,255,255,0.05);
        }
        .back-btn-container a:hover {
            color: var(--text-primary);
            background: rgba(255,255,255,0.1);
            transform: translateX(-5px);
        }
        
        .empty-menu {
            text-align: center;
            padding: 3rem;
            color: var(--text-secondary);
            font-style: italic;
        }
    </style>
</head>
<body style="background: var(--bg-dark); min-height: 100vh;">

    <!-- Navbar simple -->
    <nav class="navbar" style="position: static; padding: 1.5rem 5%;">
        <div class="logo" style="cursor: pointer;" onclick="window.location.href='index.php'">
            <i class="fa-solid fa-utensils"></i> <bdi>على <span>ذوقك</span></bdi>
        </div>
        <div class="nav-links">
            <a href="index.php"><i class="fa-solid fa-house"></i> Accueil</a>
        </div>
    </nav>

    <div class="menu-page-container">
        <div class="back-btn-container">
            <a href="#" onclick="history.back(); return false;"><i class="fa-solid fa-arrow-left"></i> Retour</a>
        </div>
        
        <div class="menu-header">
            <h1><?php echo htmlspecialchars($restaurant['name']); ?></h1>
            <p>Découvrez notre carte détaillée</p>
        </div>

        <div class="menu-list-container">
            <?php if (count($menu) > 0): ?>
                <?php foreach ($menu as $item): ?>
                    <div class="menu-item-premium" style="font-size: 1.2rem; padding: 1rem 0; border-bottom: 1px solid rgba(255,255,255,0.05); align-items: center;">
                        <span class="menu-dish-name" style="font-weight: 500; font-size: 1.4rem; padding-right: 15px;"><?php echo htmlspecialchars($item['name']); ?></span>
                        <span class="menu-dots" style="flex:1; border-bottom: 2px dotted rgba(255,255,255,0.1); margin: 0 10px;"></span>
                        <span class="menu-dish-price" style="color: var(--accent-primary); font-weight: 700; font-size: 1.5rem; padding-left: 15px; white-space: nowrap;"><?php echo floatval($item['price']); ?> DT</span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-menu">
                    <i class="fa-solid fa-file-signature" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                    <p>Le menu de ce restaurant n'est pas encore disponible.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>
