<?php
require_once '../config/database.php';
require_once '../model/Auth.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($password !== $confirm_password) {
        $error = "Les mots de passe ne correspondent pas.";
    } else {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = "Cette adresse e-mail est déjà utilisée.";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, 'user', 'active')");
            try {
                $stmt->execute([$name, $email, $hashed_password]);
                $success = "Compte créé avec succès ! Vous pouvez maintenant vous connecter.";
            } catch (PDOException $e) {
                $error = "Erreur lors de la création du compte.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Ala Dhawkek</title>
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
            <h2>Créer un compte</h2>
            <p style="color: var(--text-secondary);">Rejoignez la plus grande communauté de gourmets en Tunisie.</p>
        </div>

        <?php if ($error): ?>
            <div style="background: rgba(255, 68, 68, 0.1); color: #ff4444; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.9rem; border: 1px solid rgba(255, 68, 68, 0.2);">
                <i class="fa-solid fa-circle-exclamation"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div style="background: rgba(46, 213, 115, 0.1); color: #2ed573; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.9rem; border: 1px solid rgba(46, 213, 115, 0.2);">
                <i class="fa-solid fa-circle-check"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="signup.php">
            <div class="form-group" style="text-align: left;">
                <label>Nom complet</label>
                <input type="text" name="name" required placeholder="Ex: Ahmed Ben Salah">
            </div>
            <div class="form-group" style="text-align: left;">
                <label>Adresse E-mail</label>
                <input type="email" name="email" required placeholder="votre@email.com">
            </div>
            <div class="form-group" style="text-align: left;">
                <label>Mot de passe</label>
                <input type="password" name="password" required placeholder="••••••••">
            </div>
            <div class="form-group" style="text-align: left;">
                <label>Confirmer le mot de passe</label>
                <input type="password" name="confirm_password" required placeholder="••••••••">
            </div>
            
            <button type="submit" class="btn-primary btn-large" style="width: 100%; justify-content: center; margin-top: 1rem;">
                S'inscrire <i class="fa-solid fa-user-plus"></i>
            </button>
        </form>

        <div style="margin-top: 2rem; font-size: 0.9rem; color: var(--text-secondary);">
            Déjà un compte ? <a href="login.php" style="color: var(--accent-primary); font-weight: 600;">Se connecter</a>
        </div>
        
        <div style="margin-top: 1rem; font-size: 0.9rem; color: var(--text-secondary);">
            <a href="index.php" style="color: var(--text-secondary);"><i class="fa-solid fa-arrow-left"></i> Retour à l'accueil</a>
        </div>
    </div>

</body>
</html>
