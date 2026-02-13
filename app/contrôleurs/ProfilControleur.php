<?php
require_once __DIR__ . '/../../config/database.php';

class ProfilControleur
{
    private PDO $db;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->db = (new Database())->getConnection();
    }

    
    private function obtenirUtilisateurParId(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT id, nom, email, role, photo, date_creation, mot_de_passe FROM connexion WHERE id = ?");
        $stmt->execute([$id]);
        $u = $stmt->fetch(PDO::FETCH_ASSOC);
        return $u ?: null;
    }

   
    public function index(): void
    {
        if (empty($_SESSION['utilisateur_id'])) {
            header('Location: ?page=login');
            exit;
        }

        $id = (int) $_SESSION['utilisateur_id'];
        $user = $this->obtenirUtilisateurParId($id);

        if (!$user) {
            echo "<h2>Utilisateur introuvable.</h2>";
            return;
        }

       
        $_SESSION['utilisateur_nom']   = $user['nom'];
        $_SESSION['utilisateur_email'] = $user['email'];
        $_SESSION['utilisateur_role']  = $user['role'];
        $_SESSION['utilisateur_photo'] = $user['photo'] ?? null;

        include __DIR__ . '/../vues/profil/profil.php';
    }

  
    public function mettreAJourPhoto(): void
    {
        if (empty($_SESSION['utilisateur_id'])) {
            header('Location: ?page=login');
            exit;
        }

        $id = (int) $_SESSION['utilisateur_id'];
        $user = $this->obtenirUtilisateurParId($id);

        if (!$user) {
            $_SESSION['message'] = "Utilisateur introuvable.";
            header('Location: ?page=profil');
            exit;
        }

       
        if (isset($_POST['action']) && $_POST['action'] === 'update-info') {
            $nom   = trim($_POST['nom'] ?? '');
            $email = trim($_POST['email'] ?? '');

            if ($nom === '' || $email === '') {
                $_SESSION['message'] = "⚠️ Tous les champs sont requis.";
                header('Location: ?page=profil');
                exit;
            }

           
            $stmt = $this->db->prepare("SELECT id FROM connexion WHERE email = ? AND id != ?");
            $stmt->execute([$email, $id]);
            if ($stmt->fetch()) {
                $_SESSION['message'] = "⚠️ Cet email est déjà utilisé par un autre compte.";
                header('Location: ?page=profil');
                exit;
            }

            $stmt = $this->db->prepare("UPDATE connexion SET nom = ?, email = ? WHERE id = ?");
            $stmt->execute([$nom, $email, $id]);

            $_SESSION['utilisateur_nom']   = $nom;
            $_SESSION['utilisateur_email'] = $email;
            $_SESSION['message'] = "✅ Informations mises à jour avec succès.";
            header('Location: ?page=profil');
            exit;
        }

        
        if (isset($_POST['action']) && $_POST['action'] === 'update-password') {
            $old = $_POST['old_password'] ?? '';
            $new = $_POST['new_password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';

            if (empty($old) || empty($new) || empty($confirm)) {
                $_SESSION['message'] = "⚠️ Tous les champs sont requis.";
                header('Location: ?page=profil');
                exit;
            }

            if ($new !== $confirm) {
                $_SESSION['message'] = "⚠️ Les mots de passe ne correspondent pas.";
                header('Location: ?page=profil');
                exit;
            }

            
            if (!password_verify($old, $user['mot_de_passe'])) {
                $_SESSION['message'] = "❌ Ancien mot de passe incorrect.";
                header('Location: ?page=profil');
                exit;
            }

            
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("UPDATE connexion SET mot_de_passe = ? WHERE id = ?");
            $stmt->execute([$hash, $id]);

            $_SESSION['message'] = "✅ Mot de passe mis à jour avec succès.";
            header('Location: ?page=profil');
            exit;
        }

        
        if (isset($_POST['action']) && $_POST['action'] === 'delete') {
            if (!empty($user['photo'])) {
                $currentPath = __DIR__ . '/../../public/uploads/profils/' . $user['photo'];
                if (is_file($currentPath)) unlink($currentPath);
            }

            foreach (glob(__DIR__ . '/../../public/uploads/profils/user_' . $id . '.*') as $old) {
                @unlink($old);
            }

            $stmt = $this->db->prepare("UPDATE connexion SET photo = NULL WHERE id = ?");
            $stmt->execute([$id]);

            $_SESSION['utilisateur_photo'] = null;
            $_SESSION['message'] = "🗑️ Photo supprimée avec succès.";
            header('Location: ?page=profil');
            exit;
        }

        
        if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['message'] = "⚠️ Erreur lors du téléversement.";
            header('Location: ?page=profil');
            exit;
        }

        $file = $_FILES['photo'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($ext, $allowed)) {
            $_SESSION['message'] = "⚠️ Format d'image non supporté.";
            header('Location: ?page=profil');
            exit;
        }

        
        foreach (glob(__DIR__ . '/../../public/uploads/profils/user_' . $id . '.*') as $old) {
            @unlink($old);
        }

        
        $fileName = 'user_' . $id . '_' . time() . '.' . $ext;
        $dest = __DIR__ . '/../../public/uploads/profils/' . $fileName;

        if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0777, true);

        move_uploaded_file($file['tmp_name'], $dest);

     
        $stmt = $this->db->prepare("UPDATE connexion SET photo = ? WHERE id = ?");
        $stmt->execute([$fileName, $id]);

        $_SESSION['utilisateur_photo'] = $fileName;
        $_SESSION['message'] = "✅ Photo mise à jour avec succès !";
        header('Location: ?page=profil');
        exit;
    }
}
