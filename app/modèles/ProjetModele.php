<?php
require_once __DIR__ . '/../../config/database.php';

class ProjetModele {
    private $connexion;

    public function __construct() {
        $baseDeDonnees = new Database();
        $this->connexion = $baseDeDonnees->getConnection();
    }

   
    public function obtenirTousLesProjets() {
        $requete = $this->connexion->query("SELECT * FROM projects WHERE deleted_at IS NULL ORDER BY created_at DESC");
        $projects = $requete->fetchAll(PDO::FETCH_ASSOC);


        foreach ($projects as &$projet) {
            foreach ($projet as $key => $value) {

                $projet[$key] = htmlspecialchars_decode(trim($value ?? ''), ENT_QUOTES);
            }
        }

        return $projects;
    }


    public function obtenirProjetParId($id) {
        $requete = $this->connexion->prepare("SELECT * FROM projects WHERE id = ? AND deleted_at IS NULL");
        $requete->execute([$id]);
        $projet = $requete->fetch(PDO::FETCH_ASSOC);

        if ($projet) {
            foreach ($projet as $key => $value) {
                $projet[$key] = htmlspecialchars_decode(trim($value ?? ''), ENT_QUOTES);
            }
        }

        return $projet ?: [];
    }
}
