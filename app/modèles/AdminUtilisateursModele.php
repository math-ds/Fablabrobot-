<?php
require_once __DIR__ . '/../../config/database.php';

class AdminUtilisateursModele {
    private PDO $baseDeDonnees;

    public function __construct() { $this->baseDeDonnees = getDatabase(); }

    public function tousLesElements(): array {
        $sql = "SELECT id, nom, email, role, date_creation FROM connexion WHERE deleted_at IS NULL ORDER BY date_creation DESC";
        return $this->baseDeDonnees->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function trouver(int $id): ?array {
        $sql = "SELECT id, nom, email, role, date_creation FROM connexion WHERE id = :id AND deleted_at IS NULL";
        $requete = $this->baseDeDonnees->prepare($sql);
        $requete->execute([':id' => $id]);
        $result = $requete->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function creer(array $data): int {
        $hash = !empty($data['mot_de_passe']) ? password_hash($data['mot_de_passe'], PASSWORD_BCRYPT) : null;
        $sql = "INSERT INTO connexion (nom, email, mot_de_passe, role, date_creation)
                VALUES (:nom, :email, :mot_de_passe, :role, NOW())";
        $requete = $this->baseDeDonnees->prepare($sql);
        $requete->execute([
            ':nom'           => $data['nom'],
            ':email'         => $data['email'],
            ':mot_de_passe'  => $hash,
            ':role'          => $data['role'] ?? 'Utilisateur',
        ]);
        return (int)$this->baseDeDonnees->lastInsertId();
    }

    public function mettreAJour(int $id, array $data): bool {
        if (!empty($data['mot_de_passe'])) {
            $sql = "UPDATE connexion
                    SET nom=:nom, email=:email, role=:role, mot_de_passe=:mot_de_passe
                    WHERE id=:id";
            $requete = $this->baseDeDonnees->prepare($sql);
            return $requete->execute([
                ':nom'          => $data['nom'],
                ':email'        => $data['email'],
                ':role'         => $data['role'] ?? 'Utilisateur',
                ':mot_de_passe' => password_hash($data['mot_de_passe'], PASSWORD_BCRYPT),
                ':id'           => $id
            ]);
        } else {
            $sql = "UPDATE connexion
                    SET nom=:nom, email=:email, role=:role
                    WHERE id=:id";
            $requete = $this->baseDeDonnees->prepare($sql);
            return $requete->execute([
                ':nom'   => $data['nom'],
                ':email' => $data['email'],
                ':role'  => $data['role'] ?? 'Utilisateur',
                ':id'    => $id
            ]);
        }
    }

    public function supprimer(int $id): bool {
        $requete = $this->baseDeDonnees->prepare("UPDATE connexion SET deleted_at = NOW() WHERE id = :id");
        return $requete->execute([':id' => $id]);
    }

    /**
     * Suppression définitive d'un utilisateur (hard delete)
     * À utiliser avec précaution
     */
    public function supprimerDefinitivement(int $id): bool {
        $requete = $this->baseDeDonnees->prepare("DELETE FROM connexion WHERE id = :id AND deleted_at IS NOT NULL");
        return $requete->execute([':id' => $id]);
    }

    /**
     * Restaurer un utilisateur supprimé
     */
    public function restaurer(int $id): bool {
        $requete = $this->baseDeDonnees->prepare("UPDATE connexion SET deleted_at = NULL WHERE id = :id AND deleted_at IS NOT NULL");
        return $requete->execute([':id' => $id]);
    }

    /**
     * Récupérer tous les utilisateurs supprimés (dans la corbeille)
     */
    public function elementsSupprimes(): array {
        $sql = "SELECT id, nom, email, role, date_creation, deleted_at
                FROM connexion WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC";
        return $this->baseDeDonnees->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}
