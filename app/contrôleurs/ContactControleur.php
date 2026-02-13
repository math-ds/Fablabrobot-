<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';

class ContactControleur
{
    public function index()
    {
        $pdo = getDatabase();
        $message_sent = false;
        $error_message = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Vérification CSRF
            $csrf_token = $_POST['csrf_token'] ?? '';
            if (!verifyCsrfToken($csrf_token)) {
                $error_message = 'Token CSRF invalide. Veuillez réessayer.';
            } else {
                $name = htmlspecialchars(trim($_POST['name'] ?? ''));
                $email = htmlspecialchars(trim($_POST['email'] ?? ''));
                $subject = htmlspecialchars(trim($_POST['subject'] ?? ''));
                $message = htmlspecialchars(trim($_POST['message'] ?? ''));

                if (empty($name) || empty($email) || empty($subject) || empty($message)) {
                    $error_message = 'Tous les champs sont requis.';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error_message = 'Adresse email invalide.';
                } elseif (strlen($name) > 100 || strlen($email) > 100 || strlen($subject) > 100 || strlen($message) > 1000) {
                    $error_message = 'Un ou plusieurs champs dépassent la longueur maximale autorisée.';
                } else {
                    try {
                        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;

                        $stmt = $pdo->prepare("
                            INSERT INTO contact_messages (nom, email, sujet, message, ip_address)
                            VALUES (:nom, :email, :sujet, :message, :ip)
                        ");
                        $stmt->execute([
                            ':nom' => $name,
                            ':email' => $email,
                            ':sujet' => $subject,
                            ':message' => $message,
                            ':ip' => $ip_address
                        ]);

                        $message_sent = true;
                    } catch (PDOException $e) {
                        $error_message = 'Erreur lors de l\'enregistrement du message. Veuillez réessayer.';
                    }
                }
            }
        }

        // Générer un nouveau token CSRF pour le formulaire
        $csrf_token = generateCsrfToken();

        require __DIR__ . '/../vues/contact/contact.php';
    }
}
