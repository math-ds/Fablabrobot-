<?php
require_once __DIR__ . '/../modèles/ProfilModele.php';
require_once __DIR__ . '/../helpers/CsrfHelper.php';
require_once __DIR__ . '/../helpers/GestionnaireCache.php';
require_once __DIR__ . '/../helpers/AvatarHelper.php';
require_once __DIR__ . '/../helpers/RoleHelper.php';
require_once __DIR__ . '/../helpers/ValidationHelper.php';

class ProfilControleur
{
    private ProfilModele $modele;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->modele = new ProfilModele();
    }

    private function invaliderCachesCommentairesVideo(): void
    {
        try {
            $cache = GestionnaireCache::obtenirInstance();
            $cache->supprimerParPrefixe('video_comments_');
            $cache->supprimerParPrefixe('liste_videos_');
            $cache->supprimer('video_categories');
            $cache->supprimerParPrefixe('article_');
            $cache->supprimerParPrefixe('liste_articles_');
            $cache->supprimer('liste_articles');
            $cache->supprimerParPrefixe('projet_');
            $cache->supprimer('liste_projets');
        } catch (Throwable $e) {
            
        }
    }

    private function obtenirUtilisateurParId(int $id): ?array
    {
        return $this->modele->obtenirUtilisateurParId($id);
    }

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

    private function terminerAvecMessage(bool $success, string $message, int $codeHttp = 200, array $extra = []): void
    {
        if ($this->estRequeteAjax()) {
            $this->repondreJson(array_merge([
                'success' => $success,
                'message' => $message,
            ], $extra), $codeHttp);
        }

        $_SESSION['message'] = $message;
        $_SESSION['message_type'] = $success ? 'success' : 'danger';
        header('Location: ?page=profil');
        exit;
    }

    private function construireAvatar(string $nom, ?string $photo): array
    {
        $baseUrl = $GLOBALS['baseUrl'] ?? '/Fablabrobot/public/';
        return AvatarHelper::construireDonnees($nom, $photo, $baseUrl);
    }

    public function index(): void
    {
        $id = (int)($_SESSION['utilisateur_id'] ?? 0);
        if ($id <= 0) {
            header('Location: ?page=login');
            exit;
        }

        $user = $this->obtenirUtilisateurParId($id);

        if (!$user) {
            header('Location: ?page=logout');
            exit;
        }

        $_SESSION['utilisateur_nom'] = $user['nom'];
        $_SESSION['utilisateur_email'] = $user['email'];
        $_SESSION['utilisateur_role'] = RoleHelper::normaliser($user['role'] ?? '');
        $_SESSION['utilisateur_photo'] = $user['photo'] ?? null;

        include __DIR__ . '/../vues/profil/profil.php';
    }

    public function mettreAJourPhoto(): void
    {
        $id = (int)($_SESSION['utilisateur_id'] ?? 0);
        if ($id <= 0) {
            $this->terminerAvecMessage(false, 'Session utilisateur invalide.', 401);
        }

        $user = $this->obtenirUtilisateurParId($id);

        if (!$user) {
            $this->terminerAvecMessage(false, 'Utilisateur introuvable.', 404);
        }

        if (!CsrfHelper::validerJeton($_POST['csrf_token'] ?? '')) {
            $this->terminerAvecMessage(false, 'Token de sécurité invalide. Veuillez réessayer.', 419);
        }

        if (isset($_POST['action']) && $_POST['action'] === 'update-info') {
            $nom = trim((string)($_POST['nom'] ?? ''));
            $email = trim((string)($_POST['email'] ?? ''));

            if ($nom === '' || $email === '') {
                $this->terminerAvecMessage(false, 'Tous les champs sont requis.', 422);
            }

            if ($this->modele->emailExistePourAutreUtilisateur($email, $id)) {
                $this->terminerAvecMessage(false, 'Cet email est deja utilise par un autre compte.', 422);
            }

            $this->modele->mettreAJourInfos($id, $nom, $email);

            $_SESSION['utilisateur_nom'] = $nom;
            $_SESSION['utilisateur_email'] = $email;
            $this->invaliderCachesCommentairesVideo();

            $avatar = $this->construireAvatar($nom, $_SESSION['utilisateur_photo'] ?? ($user['photo'] ?? null));
            $this->terminerAvecMessage(true, 'Informations mises a jour avec succes.', 200, [
                'user' => [
                    'nom' => $nom,
                    'email' => $email,
                ],
                'avatar' => $avatar,
            ]);
        }

        if (isset($_POST['action']) && $_POST['action'] === 'update-password') {
            $old = (string)($_POST['old_password'] ?? '');
            $new = (string)($_POST['new_password'] ?? '');
            $confirm = (string)($_POST['confirm_password'] ?? '');

            if ($old === '' || $new === '' || $confirm === '') {
                $this->terminerAvecMessage(false, 'Tous les champs sont requis.', 422);
            }

            if ($new !== $confirm) {
                $this->terminerAvecMessage(false, 'Les mots de passe ne correspondent pas.', 422);
            }

            if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $new)) {
                $this->terminerAvecMessage(false, 'Le mot de passe doit contenir au moins 8 caracteres, une majuscule, une minuscule, un chiffre et un caractere special.', 422);
            }

            if (!password_verify($old, (string)($user['password_hash'] ?? ''))) {
                $this->terminerAvecMessage(false, 'Ancien mot de passe incorrect.', 422);
            }

            $hash = password_hash($new, PASSWORD_DEFAULT);
            $this->modele->mettreAJourMotDePasse($id, $hash);

            $this->terminerAvecMessage(true, 'Mot de passe mis a jour avec succes.');
        }

        if (isset($_POST['action']) && $_POST['action'] === 'delete') {
            if (!empty($user['photo'])) {
                $currentPath = __DIR__ . '/../../public/uploads/profils/' . $user['photo'];
                if (is_file($currentPath)) {
                    @unlink($currentPath);
                }
            }

            foreach (glob(__DIR__ . '/../../public/uploads/profils/user_' . $id . '.*') as $old) {
                @unlink($old);
            }

            $this->modele->supprimerPhoto($id);

            $_SESSION['utilisateur_photo'] = null;
            $this->invaliderCachesCommentairesVideo();

            $avatar = $this->construireAvatar((string)($_SESSION['utilisateur_nom'] ?? $user['nom']), null);
            $this->terminerAvecMessage(true, 'Photo supprimée avec succès.', 200, [
                'avatar' => $avatar,
            ]);
        }

        if (!isset($_FILES['photo'])) {
            $this->terminerAvecMessage(false, 'Aucun fichier image recu.', 422);
        }

        $file = $_FILES['photo'];
        $validation = ValidationHelper::validerFichierImage($file, 2048);
        if (!$validation['valid']) {
            $this->terminerAvecMessage(false, (string)$validation['error'], 422);
        }

        foreach (glob(__DIR__ . '/../../public/uploads/profils/user_' . $id . '.*') as $old) {
            @unlink($old);
        }

        $extension = (string)($validation['extension'] ?? 'jpg');
        $fileName = 'user_' . $id . '_' . time() . '.' . $extension;
        $dest = __DIR__ . '/../../public/uploads/profils/' . $fileName;

        if (!is_dir(dirname($dest))) {
            mkdir(dirname($dest), 0755, true);
        }

        if (!move_uploaded_file((string)$file['tmp_name'], $dest)) {
            $this->terminerAvecMessage(false, 'Echec lors de la sauvegarde de la photo.', 500);
        }

        $this->modele->mettreAJourPhoto($id, $fileName);

        $_SESSION['utilisateur_photo'] = $fileName;
        $this->invaliderCachesCommentairesVideo();

        $avatar = $this->construireAvatar((string)($_SESSION['utilisateur_nom'] ?? $user['nom']), $fileName);
        $this->terminerAvecMessage(true, 'Photo mise a jour avec succes.', 200, [
            'avatar' => $avatar,
        ]);
    }
}
