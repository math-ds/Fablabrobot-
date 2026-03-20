<?php

class AuthentificationModele
{
    private $baseDeDonnees;

    public function __construct($baseDeDonnees)
    {
        $this->baseDeDonnees = $baseDeDonnees;
    }

    public function obtenirUtilisateurParEmail($email)
    {
        $requete = $this->baseDeDonnees->prepare("SELECT * FROM users WHERE email = ? AND deleted_at IS NULL");
        $requete->execute([$email]);
        return $requete->fetch(PDO::FETCH_ASSOC);
    }

    public function creerUtilisateur($nom, $email, $passwordHash, $role = 'Utilisateur')
    {
        $requete = $this->baseDeDonnees->prepare("
            INSERT INTO users (nom, email, password_hash, role)
            VALUES (?, ?, ?, ?)
        ");
        return $requete->execute([$nom, $email, $passwordHash, $role]);
    }

    public function verifierConnexion($email, $password)
    {
        $requete = $this->baseDeDonnees->prepare("SELECT * FROM users WHERE email = ? AND deleted_at IS NULL");
        $requete->execute([$email]);
        $utilisateur = $requete->fetch(PDO::FETCH_ASSOC);

        if ($utilisateur && password_verify($password, $utilisateur['password_hash'])) {
            return $utilisateur;
        }
        return false;
    }

    public function emailExiste($email)
    {
        $requete = $this->baseDeDonnees->prepare("SELECT id FROM users WHERE email = ? AND deleted_at IS NULL");
        $requete->execute([$email]);
        return $requete->fetch(PDO::FETCH_ASSOC) !== false;
    }
}
