<?php
require_once __DIR__ . '/../../config/database.php';

class AdminModele {
    private $baseDeDonnees;

    public function __construct() {
        $database = new Database();
        $this->baseDeDonnees = $database->getConnection();
    }
public function obtenirTousLesProjets() {
    $requete = $this->baseDeDonnees->query("SELECT * FROM projects ORDER BY created_at DESC");
    return $requete->fetchAll(PDO::FETCH_ASSOC);
}

    public function getStatistiques() {
    
        $stats = [];
        $stats['total_projets'] = $this->baseDeDonnees->query("SELECT COUNT(*) FROM projects")->fetchColumn();
        $stats['total_articles'] = $this->baseDeDonnees->query("SELECT COUNT(*) FROM articles")->fetchColumn();
        $stats['total_utilisateurs'] = $this->baseDeDonnees->query("SELECT COUNT(*) FROM connexion")->fetchColumn();
        return $stats;
    }
}
