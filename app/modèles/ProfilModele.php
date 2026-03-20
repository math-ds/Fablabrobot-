<?php
require_once __DIR__ . '/../../config/database.php';

class ProfilModele
{
    private PDO $baseDeDonnees;

    public function __construct()
    {
        $this->baseDeDonnees = getDatabase();
    }

    public function obtenirUtilisateurParId(int $id): ?array
    {
        $requete = $this->baseDeDonnees->prepare('SELECT id, nom, email, role, photo, created_at, password_hash FROM users WHERE id = :id LIMIT 1');
        $requete->execute([':id' => $id]);
        $utilisateur = $requete->fetch(PDO::FETCH_ASSOC);
        return $utilisateur ?: null;
    }

    public function emailExistePourAutreUtilisateur(string $email, int $utilisateurId): bool
    {
        $requete = $this->baseDeDonnees->prepare('SELECT id FROM users WHERE email = :email AND id != :id LIMIT 1');
        $requete->execute([
            ':email' => $email,
            ':id' => $utilisateurId,
        ]);
        return (bool)$requete->fetch(PDO::FETCH_ASSOC);
    }

    public function mettreAJourInfos(int $utilisateurId, string $nom, string $email): bool
    {
        $requete = $this->baseDeDonnees->prepare('UPDATE users SET nom = :nom, email = :email WHERE id = :id');
        return $requete->execute([
            ':nom' => $nom,
            ':email' => $email,
            ':id' => $utilisateurId,
        ]);
    }

    public function mettreAJourMotDePasse(int $utilisateurId, string $passwordHash): bool
    {
        $requete = $this->baseDeDonnees->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id');
        return $requete->execute([
            ':password_hash' => $passwordHash,
            ':id' => $utilisateurId,
        ]);
    }

    public function supprimerPhoto(int $utilisateurId): bool
    {
        $requete = $this->baseDeDonnees->prepare('UPDATE users SET photo = NULL WHERE id = :id');
        return $requete->execute([':id' => $utilisateurId]);
    }

    public function mettreAJourPhoto(int $utilisateurId, string $photo): bool
    {
        $requete = $this->baseDeDonnees->prepare('UPDATE users SET photo = :photo WHERE id = :id');
        return $requete->execute([
            ':photo' => $photo,
            ':id' => $utilisateurId,
        ]);
    }
}
