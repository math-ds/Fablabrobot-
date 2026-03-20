<?php
$baseUrl = $GLOBALS['baseUrl'] ?? '/Fablabrobot/public/';
$authTitle = 'Inscription - FABLAB';
$authCss = ['utilisateurs.css', 'footer.css', 'header-auth.css'];
include __DIR__ . '/../parties/public-auth-layout-start.php';
?>

<div class="particles" id="particles"></div>

<main class="main-container">
    <div class="registration-card">
        <h1 class="card-title">Créer un compte</h1>

        <?php if (!empty($error)): ?>
            <div class="message error">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="?page=inscription">
            <?php require_once __DIR__ . '/../../helpers/CsrfHelper.php'; echo CsrfHelper::obtenirChampJeton(); ?>
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
                <small class="aide-mot-de-passe">
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

<?php $publicScripts = ['js/auth-particles.js', 'js/inscription-page.js']; ?>
<?php include __DIR__ . '/../parties/public-auth-layout-end.php'; ?>
