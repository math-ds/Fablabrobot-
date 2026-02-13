<?php

class AuthentificationModele {
    private $baseDeDonnees;

    public function __construct($baseDeDonnees) {
        $this->baseDeDonnees = $baseDeDonnees;
    }


    public function obtenirUtilisateurParEmail($email) {
        $requete = $this->baseDeDonnees->prepare("SELECT * FROM connexion WHERE email = ?");
        $requete->execute([$email]);
        return $requete->fetch(PDO::FETCH_ASSOC);
    }


    public function creerUtilisateur($nom, $email, $mot_de_passe, $role = 'Utilisateur') {
        $requete = $this->baseDeDonnees->prepare("
            INSERT INTO connexion (nom, email, mot_de_passe, role)
            VALUES (?, ?, ?, ?)
        ");
        return $requete->execute([$nom, $email, $mot_de_passe, $role]);
    }


    public function verifierConnexion($email, $mot_de_passe) {
        $requete = $this->baseDeDonnees->prepare("SELECT * FROM connexion WHERE email = ?");
        $requete->execute([$email]);
        $utilisateur = $requete->fetch(PDO::FETCH_ASSOC);

        if ($utilisateur && password_verify($mot_de_passe, $utilisateur['mot_de_passe'])) {
            return $utilisateur;
        }
        return false;
    }


    public function emailExiste($email) {
        $requete = $this->baseDeDonnees->prepare("SELECT id FROM connexion WHERE email = ?");
        $requete->execute([$email]);
        return $requete->fetch(PDO::FETCH_ASSOC) !== false;
    }
}
