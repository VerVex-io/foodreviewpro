<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FoodReviewPro — Connexion</title>
  <link href="https://fonts.googleapis.com/css2?family=Jost:wght@500&display=swap" rel="stylesheet">
  <!-- Fixed: path now points to public/css/ -->
  <link rel="stylesheet" href="/foodreviewpro/public/css/auth.css">
</head>
<body>
  <div class="main">
    <input type="checkbox" id="chk" aria-hidden="true">

    <!-- SIGN UP -->
    <div class="signup">
      <form action="/foodreviewpro/public/index.php?action=signup" method="post">
        <label for="chk" aria-hidden="true">Sign up</label>
        <input type="text"     name="user_name" placeholder="Nom d'utilisateur" required>
        <input type="email"    name="email"     placeholder="Email"              required>
        <input type="password" name="password"  placeholder="Mot de passe"       required>
        <button type="submit">S'inscrire</button>
      </form>
    </div>

    <!-- LOGIN -->
    <div class="login">
      <form action="/foodreviewpro/public/index.php?action=login" method="post">
        <label for="chk" aria-hidden="true">Login</label>
        <?php if (!empty($_GET['error'])): ?>
          <p class="error-msg"><?= htmlspecialchars($_GET['error']) ?></p>
        <?php endif; ?>
        <input type="email"    name="email"    placeholder="Email"          required>
        <input type="password" name="pswd"     placeholder="Mot de passe"   required>
        <button type="submit">Se connecter</button>
        <a href="/view/auth/forgot-password.php" class="forgot-link">Mot de passe oublié ?</a>
      </form>
    </div>

  </div>
</body>
</html>
