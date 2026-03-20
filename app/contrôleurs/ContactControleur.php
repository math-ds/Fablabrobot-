<?php
require_once __DIR__ . '/../modèles/ContactModele.php';
require_once __DIR__ . '/../helpers/CsrfHelper.php';

class ContactControleur
{
    private function estRequeteAjax(): bool
    {
        $requestedWith = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
        if ($requestedWith === 'xmlhttprequest') {
            return true;
        }

        return isset($_GET['ajax']) && (string)$_GET['ajax'] === '1';
    }

    private function repondreJson(array $payload, int $codeHttp = 200): void
    {
        http_response_code($codeHttp);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function index(): void
    {
        $modele = new ContactModele();
        $message_sent = false;
        $error_message = '';
        $estAjax = $this->estRequeteAjax();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!CsrfHelper::validerJeton($_POST['csrf_token'] ?? '')) {
                $error_message = 'Token CSRF invalide. Veuillez réessayer.';
            } else {
                $name = trim((string)($_POST['name'] ?? ''));
                $email = trim((string)($_POST['email'] ?? ''));
                $subject = trim((string)($_POST['subject'] ?? ''));
                $message = trim((string)($_POST['message'] ?? ''));

                if ($name === '' || $email === '' || $subject === '' || $message === '') {
                    $error_message = 'Tous les champs sont requis.';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error_message = 'Adresse email invalide.';
                } elseif (
                    strlen($name) > 100
                    || strlen($email) > 100
                    || strlen($subject) > 100
                    || strlen($message) > 1000
                ) {
                    $error_message = 'Un ou plusieurs champs depassent la longueur maximale autorisee.';
                } else {
                    try {
                        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
                        $message_sent = $modele->creerMessage($name, $email, $subject, $message, $ip_address);
                    } catch (Throwable $e) {
                        $error_message = 'Erreur lors de l\'enregistrement du message. Veuillez réessayer.';
                    }
                }
            }

            if ($estAjax) {
                if ($message_sent) {
                    $this->repondreJson([
                        'success' => true,
                        'message' => 'Votre message a ete envoye avec succes. Merci pour votre retour.',
                    ]);
                }

                $this->repondreJson([
                    'success' => false,
                    'message' => $error_message !== '' ? $error_message : 'Impossible d envoyer le message.',
                ], 422);
            }

            if ($message_sent) {
                $_SESSION['message'] = 'Votre message a ete envoye avec succes.';
                $_SESSION['message_type'] = 'success';
                header('Location: ?page=accueil');
                exit;
            }
        }

        CsrfHelper::init();

        require __DIR__ . '/../vues/contact/contact.php';
    }
}
