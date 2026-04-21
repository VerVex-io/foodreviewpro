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
                redirect('index.php'); // Redirige d'abord vers le site public
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
<body style="display: flex; align-items: center; justify-content: center; min-height: 100vh; background: var(--bg-dark);">
    
    <div class="form-card" style="width: 100%; max-width: 450px; padding: 3rem 2.5rem; text-align: center; animation: fadeIn 0.6s ease;">
        <div class="login-header" style="margin-bottom: 2.5rem;">
            <div class="logo" style="justify-content: center; margin-bottom: 1rem; font-size: 2.5rem;">
                <i class="fa-solid fa-utensils"></i> <bdi>على <span>ذوقك</span></bdi>
            </div>
            <h2>Bon retour !</h2>
            <p style="color: var(--text-secondary);">Connectez-vous pour explorer le meilleur de la Tunisie.</p>
        </div>

        <?php if ($error): ?>
            <div style="background: rgba(255, 68, 68, 0.1); color: #ff4444; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.9rem; border: 1px solid rgba(255, 68, 68, 0.2);">
                <i class="fa-solid fa-circle-exclamation"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error']) && $_GET['error'] == 'unauthorized'): ?>
            <div style="background: rgba(255, 176, 58, 0.1); color: #ffb03a; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.9rem; border: 1px solid rgba(255, 176, 58, 0.2);">
                <i class="fa-solid fa-shield-halved"></i> Accès réservé aux administrateurs.
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group" style="text-align: left;">
                <label>Adresse E-mail</label>
                <input type="email" name="email" required placeholder="votre@email.com">
            </div>
            <div class="form-group" style="text-align: left;">
                <label>Mot de passe</label>
                <input type="password" name="password" required placeholder="Votre mot de passe">
            </div>
            
            <button type="submit" class="btn-primary btn-large" style="width: 100%; justify-content: center; margin-top: 1rem;">
                Se connecter <i class="fa-solid fa-right-to-bracket"></i>
            </button>
        </form>

        <div style="margin-top: 2rem; font-size: 0.9rem; color: var(--text-secondary);">
            Pas encore de compte ? <a href="signup.php" style="color: var(--accent-primary); font-weight: 600;">S'inscrire</a>
        </div>

        <div style="margin-top: 1rem; font-size: 0.9rem; color: var(--text-secondary);">
            <a href="index.php" style="color: var(--text-secondary);"><i class="fa-solid fa-arrow-left"></i> Retour à l'accueil</a>
        </div>
    </div>

</body>
</html>
