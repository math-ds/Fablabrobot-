<?php
$baseUrl = $GLOBALS['baseUrl'] ?? '/Fablabrobot/public/';
$authTitle = 'Connexion - FABLAB';
$authCss = ['utilisateurs.css', 'footer.css', 'header-auth.css'];
include __DIR__ . '/../parties/public-auth-layout-start.php';
?>

<div class="particles" id="particles"></div>

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
            <?php require_once __DIR__ . '/../../helpers/CsrfHelper.php'; echo CsrfHelper::obtenirChampJeton(); ?>
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

<?php $publicScripts = ['js/auth-particles.js']; ?>
<?php include __DIR__ . '/../parties/public-auth-layout-end.php'; ?>
