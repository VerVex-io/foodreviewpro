<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mot de passe oublié — FoodReviewPro</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/foodreviewpro/public/css/auth.css">
  <style>
    .forgot-box {
      width: 350px;
      background: #fff;
      border-radius: 12px;
      padding: 36px 32px;
      box-shadow: 0 8px 32px rgba(0,0,0,0.18);
    }
    .forgot-box h2 { font-size: 1.4em; font-weight: 600; color: #1e293b; margin-bottom: 6px; }
    .forgot-box p  { font-size: 0.82em; color: #64748b; margin-bottom: 24px; }
    .forgot-box input[type="email"] {
      width: 100%; padding: 10px 14px; border: 1px solid #e2e8f0;
      border-radius: 8px; font-size: 0.9em; outline: none; margin-bottom: 14px;
    }
    .forgot-box input:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
    .forgot-box button {
      width: 100%; padding: 10px; background: #2563eb; color: #fff;
      border: none; border-radius: 8px; font-size: 0.9em; font-weight: 500; cursor: pointer;
    }
    .forgot-box button:hover { background: #1d4ed8; }
    .back-link { display: block; text-align: center; margin-top: 16px; font-size: 0.8em; color: #64748b; text-decoration: none; }
    .back-link:hover { color: #2563eb; }
    .success-msg { background: #d1fae5; color: #065f46; border-radius: 8px; padding: 10px 14px; font-size: 0.82em; margin-bottom: 14px; }
  </style>
</head>
<body>
  <div class="forgot-box">
    <h2>Mot de passe oublié</h2>
    <p>Entrez votre email et nous vous enverrons un lien de réinitialisation.</p>

    <?php if (!empty($_GET['sent'])): ?>
      <div class="success-msg">
        Si cet email existe dans notre base, vous recevrez un lien sous peu.
      </div>
    <?php endif; ?>

    <form action="/foodreviewpro/public/index.php?action=forgot_password" method="POST">
      <input type="email" name="email" placeholder="votre@email.fr" required>
      <button type="submit">Envoyer le lien</button>
    </form>
    <a href="/foodreviewpro/view/auth/signup_login.php" class="back-link">← Retour à la connexion</a>
  </div>
</body>
</html>
