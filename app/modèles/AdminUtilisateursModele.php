<?php
require_once __DIR__ . '/../../config/database.php';

class AdminUtilisateursModele
{
    private PDO $baseDeDonnees;

    public function __construct()
    {
        $this->baseDeDonnees = getDatabase();
    }

    public function tousLesElements(): array
    {
        $sql = "SELECT id, nom, email, role, photo, created_at FROM users WHERE deleted_at IS NULL ORDER BY created_at DESC";
        return $this->baseDeDonnees->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function trouver(int $id): ?array
    {
        $sql = "SELECT id, nom, email, role, photo, created_at FROM users WHERE id = :id AND deleted_at IS NULL";
        $requete = $this->baseDeDonnees->prepare($sql);
        $requete->execute([':id' => $id]);
        $result = $requete->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function creer(array $data): int
    {
        $hash = !empty($data['password']) ? password_hash($data['password'], PASSWORD_BCRYPT) : null;
        $sql = "INSERT INTO users (nom, email, password_hash, role, created_at)
                VALUES (:nom, :email, :password_hash, :role, NOW())";
        $requete = $this->baseDeDonnees->prepare($sql);
        $requete->execute([
            ':nom'           => $data['nom'],
            ':email'         => $data['email'],
            ':password_hash' => $hash,
            ':role'          => $data['role'] ?? 'Utilisateur',
        ]);
        return (int)$this->baseDeDonnees->lastInsertId();
    }

    public function mettreAJour(int $id, array $data): bool
    {
        if (!empty($data['password'])) {
            $sql = "UPDATE users
                    SET nom=:nom, email=:email, role=:role, password_hash=:password_hash
                    WHERE id=:id";
            $requete = $this->baseDeDonnees->prepare($sql);
            return $requete->execute([
                ':nom'          => $data['nom'],
                ':email'        => $data['email'],
                ':role'         => $data['role'] ?? 'Utilisateur',
                ':password_hash' => password_hash($data['password'], PASSWORD_BCRYPT),
                ':id'           => $id
            ]);
        } else {
            $sql = "UPDATE users
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

    public function supprimer(int $id): bool
    {
        $requete = $this->baseDeDonnees->prepare("UPDATE users SET deleted_at = NOW() WHERE id = :id");
        return $requete->execute([':id' => $id]);
    }

    
    public function supprimerDefinitivement(int $id): bool
    {
        $requete = $this->baseDeDonnees->prepare("DELETE FROM users WHERE id = :id AND deleted_at IS NOT NULL");
        return $requete->execute([':id' => $id]);
    }

    
    public function restaurer(int $id): bool
    {
        $requete = $this->baseDeDonnees->prepare("UPDATE users SET deleted_at = NULL WHERE id = :id AND deleted_at IS NOT NULL");
        return $requete->execute([':id' => $id]);
    }

    
    public function elementsSupprimes(): array
    {
        $sql = "SELECT id, nom, email, role, created_at, deleted_at
                FROM users WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC";
        return $this->baseDeDonnees->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}
