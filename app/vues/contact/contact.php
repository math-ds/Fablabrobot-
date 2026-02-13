<?php
$baseUrl = $GLOBALS['baseUrl'] ?? '/Fablabrobot/public/';
include(__DIR__ . '/../parties/header.php');
?>

<link rel="stylesheet" href="<?= $baseUrl ?>css/contact.css">

<main class="main-container">
  <section class="hero-section">
    <div class="hero-content">
      <h1 class="hero-title">Contactez-nous</h1>
      <p class="hero-subtitle">
        Vous avez une question, une suggestion ou souhaitez signaler un problème ?
        N'hésitez pas à nous contacter !
      </p>
    </div>
  </section>

  <div class="contact-card">
    <?php if ($message_sent): ?>
      <div class="alert alert-success">
        ✓ Votre message a été envoyé avec succès ! Nous vous répondrons dans les plus brefs délais.
      </div>
    <?php endif; ?>

    <?php if (!empty($error_message)): ?>
      <div class="alert alert-danger">
        ✕ <?= $error_message ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="" id="contactForm" class="contact-form">
      <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? '' ?>">

      <div class="form-group contact-name-group">
        <label for="name" class="form-label contact-label">Nom complet *</label>
        <input type="text" id="name" name="name" class="form-input contact-input" placeholder="Entrez votre nom"
               value="<?= $_POST['name'] ?? '' ?>" required maxlength="100">
      </div>

      <div class="form-group contact-email-group">
        <label for="email" class="form-label contact-label">Email *</label>
        <input type="email" id="email" name="email" class="form-input contact-input"
               placeholder="votre@email.com" value="<?= $_POST['email'] ?? '' ?>" required maxlength="100">
      </div>

      <div class="form-group contact-subject-group">
        <label for="subject" class="form-label contact-label">Sujet *</label>
        <select id="subject" name="subject" class="form-select contact-select" required>
          <option value="">Sélectionnez un sujet</option>
          <option value="suggestion" <?= (($_POST['subject'] ?? '') === 'suggestion') ? 'selected' : '' ?>>Suggestion d'amélioration</option>
          <option value="bug" <?= (($_POST['subject'] ?? '') === 'bug') ? 'selected' : '' ?>>Signaler un bug</option>
          <option value="question" <?= (($_POST['subject'] ?? '') === 'question') ? 'selected' : '' ?>>Question générale</option>
          <option value="feedback" <?= (($_POST['subject'] ?? '') === 'feedback') ? 'selected' : '' ?>>Feedback</option>
          <option value="other" <?= (($_POST['subject'] ?? '') === 'other') ? 'selected' : '' ?>>Autre</option>
        </select>
      </div>

      <div class="form-group contact-message-group">
        <label for="message" class="form-label contact-label">Message *</label>
        <textarea id="message" name="message" class="form-textarea contact-textarea"
                  placeholder="Décrivez votre message en détail..." required maxlength="1000"><?= $_POST['message'] ?? '' ?></textarea>
      </div>

      <button type="submit" class="btn btn-primary contact-submit-btn">Envoyer le message</button>
    </form>
  </div>

  <!-- Informations de contact -->
  <div class="contact-info">
    <h2 class="contact-info-title">Informations de contact</h2>
    <div class="contact-info-grid">
      <div class="contact-info-item">
        <div class="contact-info-label">Email</div>
        <div class="contact-info-value">contact@fablab-robotique.fr</div>
      </div>
      <div class="contact-info-item">
        <div class="contact-info-label">Téléphone</div>
        <div class="contact-info-value">01 23 45 67 89</div>
      </div>
      <div class="contact-info-item">
        <div class="contact-info-label">Adresse</div>
        <div class="contact-info-value">123 Rue de la Robotique<br>75001 Paris, France</div>
      </div>
    </div>
  </div>
</main>

<?php if ($message_sent): ?>
<script>
setTimeout(() => {
  window.location.href = '<?= $baseUrl ?>';
}, 5000);
</script>
<?php endif; ?>

<script>
const inputs = document.querySelectorAll('input, textarea, select');
inputs.forEach(input => {
  input.addEventListener('blur', () => {
    input.style.borderColor = input.value.trim() ? 'var(--primary-color)' : 'rgba(255, 255, 255, 0.1)';
  });
});
const messages = document.querySelectorAll('.message');
messages.forEach(m => setTimeout(() => m.style.display = 'none', 5000));
</script>

<?php include(__DIR__ . '/../parties/footer.php'); ?>
