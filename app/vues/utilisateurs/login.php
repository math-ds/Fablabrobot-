<?php
$baseUrl = $GLOBALS['baseUrl'] ?? '/Fablabrobot/public/';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - FABLAB</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= $baseUrl ?>css/utilisateurs.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>css/footer.css">
</head>
<body>

<div class="particles" id="particles"></div>

<?php include __DIR__ . '/../parties/header-auth.php'; ?>

<main class="main-container">
    <div class="registration-card">
        <h1 class="card-title">Se connecter</h1>

        <?php if (!empty($error)): ?>
            <div class="message error">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['success'])): ?>
            <div class="message success">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['success']) ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <form id="loginForm" method="POST" action="?page=login">
            <div class="form-group">
                <label for="email" class="form-label">Adresse email</label>
                <div class="input-group">
                    <input type="email" class="form-input" id="email" name="email"
                           placeholder="votre.email@exemple.com"
                           required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    <i class="fas fa-envelope input-icon"></i>
                </div>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Mot de passe</label>
                <div class="input-group">
                    <input type="password" class="form-input" id="password" name="password"
                           placeholder="Votre mot de passe" required>
                    <i class="fas fa-lock input-icon"></i>
                </div>
            </div>

            <button type="submit" class="submit-btn">
                <i class="fas fa-sign-in-alt"></i> Se connecter
            </button>
        </form>

        <div class="login-link">
            <p><a href="?page=mdp-oublie">Mot de passe oublié ?</a></p>
        </div>

        <div class="login-link">
            <p>Pas encore inscrit ?</p>
            <a href="?page=inscription">Créer un compte</a>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../parties/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const particles = document.getElementById('particles');
    for (let i = 0; i < 50; i++) {
        const p = document.createElement('div');
        p.className = 'particle';
        p.style.left = Math.random() * 100 + '%';
        p.style.top = Math.random() * 100 + '%';
        p.style.animationDelay = Math.random() * 6 + 's';
        particles.appendChild(p);
    }
});
</script>

</body>
</html>