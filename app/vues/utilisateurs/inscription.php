<?php
$baseUrl = $GLOBALS['baseUrl'] ?? '/Fablabrobot/public/';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - FABLAB</title>
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
        <h1 class="card-title">Créer un compte</h1>

        <?php if (!empty($error)): ?>
            <div class="message error">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="?page=inscription">
            <div class="form-group">
                <label for="name" class="form-label">Nom complet</label>
                <div class="input-group">
                    <input type="text" class="form-input" id="name" name="name"
                           placeholder="Votre nom complet"
                           required
                           minlength="2"
                           pattern="[A-Za-zÀ-ÿ\s\-']+"
                           title="Le nom doit contenir au moins 2 caractères (lettres uniquement)"
                           value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                    <i class="fas fa-user input-icon"></i>
                </div>
            </div>

            <div class="form-group">
                <label for="email" class="form-label">Adresse email</label>
                <div class="input-group">
                    <input type="email" class="form-input" id="email" name="email"
                           placeholder="votre.email@exemple.com"
                           required
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    <i class="fas fa-envelope input-icon"></i>
                </div>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Mot de passe</label>
                <div class="input-group">
                    <input type="password" class="form-input" id="password" name="password"
                           placeholder="Votre mot de passe"
                           required
                           minlength="8"
                           title="Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial">
                    <i class="fas fa-lock input-icon"></i>
                </div>
                <small style="color: #8b92b0; font-size: 0.85rem; margin-top: 0.25rem; display: block;">
                    Minimum 8 caractères, 1 majuscule, 1 minuscule, 1 chiffre, 1 caractère spécial
                </small>
            </div>

            <div class="form-group">
                <label for="confirm-password" class="form-label">Confirmer le mot de passe</label>
                <div class="input-group">
                    <input type="password" class="form-input" id="confirm-password"
                           name="confirm-password"
                           placeholder="Confirmez votre mot de passe"
                           required
                           minlength="8">
                    <i class="fas fa-lock input-icon"></i>
                </div>
            </div>

            <button type="submit" class="submit-btn">
                <i class="fas fa-user-plus"></i> S'inscrire
            </button>
        </form>

        <div class="login-link">
            <p>Déjà inscrit ?</p>
            <a href="?page=login">Se connecter</a>
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

    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm-password');

    confirmPassword.addEventListener('input', () => {
        if (password.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity('Les mots de passe ne correspondent pas');
        } else {
            confirmPassword.setCustomValidity('');
        }
    });

    password.addEventListener('input', () => {
        if (confirmPassword.value) {
            confirmPassword.dispatchEvent(new Event('input'));
        }
    });
});
</script>

</body>
</html>