<?php
require_once '../config/database.php';
require_once '../model/Auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Support both hashed passwords and plaintext (for legacy dummy data)
        $authenticated = false;
        if (password_verify($password, $user['password'])) {
            $authenticated = true;
        } elseif ($password === $user['password']) {
            $authenticated = true;
        }

        if ($authenticated) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            
            if ($user['role'] === 'admin') {
                redirect('admin/dashboard.php'); // Redirige vers le nouveau tableau de bord standalone
            } else {
                redirect('index.php');
            }
        } else {
            $error = "Mot de passe incorrect.";
        }
    } else {
        $error = "Identifiants invalides ou compte inactif.";
    }
}

// Gestion de la déconnexion
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    logout();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Ala Dhawkek</title>
    <script>
        (function() {
            const savedTheme = localStorage.getItem('atng_theme') || 'dark';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body style="margin: 0; padding: 0; overflow-x: hidden;">
    
    <style>
        .login-layout {
            display: flex;
            width: 100vw;
            min-height: 100vh;
            background: url('https://images.unsplash.com/photo-1414235077428-338989a2e8c0?q=80&w=2000&auto=format&fit=crop') center/cover no-repeat;
            position: relative;
            align-items: center;
            padding: 2rem 5%;
            box-sizing: border-box;
        }
        
        .login-layout::before {
            content: '';
            position: absolute;
            inset: 0;
            /* Gradient global plus sombre pour faire ressortir la carte */
            background: linear-gradient(135deg, rgba(10,10,11,0.85) 0%, rgba(10,10,11,0.7) 100%);
            z-index: 1;
        }

        .login-left {
            flex: 1.2;
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            justify-content: center;
            color: white;
            padding-right: 4rem;
        }

        .left-content {
            position: relative;
            z-index: 2;
            max-width: 650px;
        }

        .login-logo {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 3rem;
            cursor: pointer;
        }
        .login-logo i { font-size: 2rem; color: #ff6b00; }
        .login-logo span { font-size: 1.8rem; font-family: 'Outfit', sans-serif; font-weight: 800; letter-spacing: -0.5px; }
        
        .login-title {
            font-family: 'Outfit', sans-serif;
            font-size: 4rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 1.5rem;
        }
        .login-title span { color: #ff6b00; }
        
        .login-subtitle {
            font-size: 1.2rem;
            color: rgba(255,255,255,0.7);
            margin-bottom: 4rem;
            line-height: 1.6;
            max-width: 500px;
        }

        .features-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
        }
        
        .feature-item {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .feature-item i { font-size: 1.8rem; color: #ffb03a; background: rgba(255,176,58,0.1); width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; border-radius: 12px; }
        .feature-item h4 { margin: 0; font-size: 1rem; font-weight: 700; color: #fff; }
        .feature-item p { margin: 0; font-size: 0.85rem; color: rgba(255,255,255,0.5); line-height: 1.5; }

        .login-right {
            flex: 1;
            max-width: 480px;
            margin-left: auto;
            position: relative;
            z-index: 2;
            /* Carte flottante sombre premium */
            background: rgba(15, 15, 18, 0.85);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem;
            box-shadow: 0 25px 50px rgba(0,0,0,0.6);
        }

        .login-form-container {
            width: 100%;
            max-width: 420px;
            animation: fadeIn 0.8s ease;
        }

        .right-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        .right-header h2 { font-family: 'Outfit', sans-serif; font-size: 2.5rem; font-weight: 800; margin-bottom: 10px; color: #fff; }
        .right-header p { color: rgba(255,255,255,0.6); font-size: 1rem; }

        .form-group {
            margin-bottom: 1.8rem;
            position: relative;
        }
        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            color: rgba(255,255,255,0.8);
            letter-spacing: 0.5px;
        }
        
        .input-icon-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .input-icon-wrapper i.left-icon {
            position: absolute;
            left: 20px;
            color: rgba(255,255,255,0.4);
            font-size: 1.2rem;
            transition: color 0.3s ease;
        }
        
        .input-icon-wrapper input {
            width: 100%;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            padding: 18px 20px 18px 55px;
            color: #fff;
            font-family: 'Inter', sans-serif;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .input-icon-wrapper input:focus {
            background: rgba(255,255,255,0.06);
            border-color: #ff6b00;
            outline: none;
            box-shadow: 0 0 0 4px rgba(255,107,0,0.1);
        }
        .input-icon-wrapper input:focus + i.left-icon { color: #ff6b00; }

        .forgot-link {
            position: absolute;
            right: 0;
            top: 0;
            font-size: 0.85rem;
            color: rgba(255,255,255,0.5);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }
        .forgot-link:hover { color: #ff6b00; }

        .btn-submit {
            width: 100%;
            background: #ff6b00;
            color: #fff;
            border: none;
            padding: 18px;
            border-radius: 16px;
            font-family: 'Outfit', sans-serif;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            transition: all 0.3s ease;
            box-shadow: 0 10px 25px rgba(255,107,0,0.3);
            margin-top: 1rem;
        }
        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(255,107,0,0.4);
            background: #e66000;
        }

        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 2.5rem 0;
            color: rgba(255,255,255,0.3);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-weight: 600;
        }
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .divider:not(:empty)::before { margin-right: 1em; }
        .divider:not(:empty)::after { margin-left: 1em; }

        .btn-google {
            width: 100%;
            background: transparent;
            color: #fff;
            border: 1px solid rgba(255,255,255,0.2);
            padding: 16px;
            border-radius: 16px;
            font-family: 'Inter', sans-serif;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            transition: all 0.3s ease;
        }
        .btn-google:hover {
            background: rgba(255,255,255,0.05);
            border-color: rgba(255,255,255,0.4);
        }
        .btn-google i { font-size: 1.2rem; }

        .signup-prompt {
            text-align: center;
            margin-top: 3rem;
            font-size: 0.95rem;
            color: rgba(255,255,255,0.6);
        }
        .signup-prompt a {
            color: #fff;
            text-decoration: none;
            font-weight: 700;
            border-bottom: 1px solid rgba(255,255,255,0.3);
            padding-bottom: 2px;
            transition: all 0.3s;
            margin-left: 5px;
        }
        .signup-prompt a:hover { color: #ff6b00; border-color: #ff6b00; }

        @media (max-width: 1024px) {
            .login-left { padding: 3rem; flex: 1; }
            .login-title { font-size: 3rem; }
        }
        
        @media (max-width: 768px) {
            .login-layout { flex-direction: column; }
            .login-left { display: none; }
            .login-right { padding: 2rem; align-items: flex-start; padding-top: 4rem; }
        }
        
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    </style>

    <div class="login-layout">
        <!-- LEFT PANEL -->
        <div class="login-left">
            <div class="left-content">
                <div class="login-logo" onclick="window.location.href='index.php'">
                    <i class="fa-solid fa-utensils"></i>
                    <span>Ala dhawkek</span>
                </div>
                
                <h1 class="login-title">Savourez<br>le meilleur de <span>la Tunisie</span></h1>
                <p class="login-subtitle">Découvrez, réservez et partagez vos meilleures expériences culinaires à travers tout le pays.</p>
                
                <div class="features-row">
                    <div class="feature-item">
                        <i class="fa-solid fa-compass"></i>
                        <h4>Découverte</h4>
                        <p>Des adresses uniques testées par notre communauté.</p>
                    </div>
                    <div class="feature-item">
                        <i class="fa-solid fa-star"></i>
                        <h4>Avis Fiables</h4>
                        <p>Consultez de vrais avis pour faire le bon choix.</p>
                    </div>
                    <div class="feature-item">
                        <i class="fa-solid fa-calendar-check"></i>
                        <h4>Réservation</h4>
                        <p>Réservez votre table en quelques clics rapides.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT PANEL -->
        <div class="login-right">
            <div class="login-form-container">
                <div class="right-header">
                    <h2>Bon retour !</h2>
                    <p>Connectez-vous pour continuer vers l'excellence.</p>
                </div>

                <?php if ($error): ?>
                    <div style="background: rgba(255, 68, 68, 0.1); color: #ff4444; padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; font-size: 0.9rem; border: 1px solid rgba(255, 68, 68, 0.2); display: flex; align-items: center; gap: 10px;">
                        <i class="fa-solid fa-circle-exclamation"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['error']) && $_GET['error'] == 'unauthorized'): ?>
                    <div style="background: rgba(255, 176, 58, 0.1); color: #ffb03a; padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; font-size: 0.9rem; border: 1px solid rgba(255, 176, 58, 0.2); display: flex; align-items: center; gap: 10px;">
                        <i class="fa-solid fa-shield-halved"></i> Accès réservé aux administrateurs.
                    </div>
                <?php endif; ?>

                <form method="POST" action="login.php">
                    <div class="form-group">
                        <label>Adresse e-mail</label>
                        <div class="input-icon-wrapper">
                            <input type="email" name="email" required placeholder="votre@email.com">
                            <i class="fa-regular fa-envelope left-icon"></i>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Mot de passe</label>
                        <a href="#" class="forgot-link">Mot de passe oublié ?</a>
                        <div class="input-icon-wrapper">
                            <input type="password" name="password" required placeholder="Votre mot de passe">
                            <i class="fa-solid fa-lock left-icon"></i>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-submit">
                        Se connecter <i class="fa-solid fa-arrow-right-to-bracket"></i>
                    </button>
                </form>

                <div class="divider">ou</div>

                <button type="button" class="btn-google" onclick="alert('Bientôt disponible')">
                    <i class="fa-brands fa-google" style="color: #ea4335;"></i> Continuer avec Google
                </button>

                <div class="signup-prompt">
                    Pas encore de compte ? <a href="signup.php">S'inscrire</a>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
