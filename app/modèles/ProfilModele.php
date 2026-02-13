<?php
class ProfilModele
{
    private PDO $baseDeDonnees;

    public function __construct()
    {
        $this->baseDeDonnees = new PDO('mysql:host=127.0.0.1:3306;dbname=fablab;charset=utf8mb4', 'root', '');
        $this->baseDeDonnees->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function obtenirUtilisateurParId(int $id): ?array
    {
        $requete = $this->baseDeDonnees->prepare("SELECT * FROM utilisateurs WHERE id = ?");
        $requete->execute([$id]);
        return $requete->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function mettreAJourPhoto(int $id, string $photo): void
    {
        $requete = $this->baseDeDonnees->prepare("UPDATE utilisateurs SET photo = ? WHERE id = ?");
        $requete->execute([$photo, $id]);
    }
}
