<?php
require_once __DIR__ . '/../../config/database.php';

class AdminModele
{
    private $baseDeDonnees;

    public function __construct()
    {
        $database = new Database();
        $this->baseDeDonnees = $database->getConnection();
    }

    public function obtenirTousLesProjets()
    {
        $requete = $this->baseDeDonnees->query("
            SELECT p.id, p.title, p.description, p.description_detailed,
                   p.technologies, p.image_url, p.features, p.challenges,
                   p.categorie, p.created_at, p.updated_at, p.deleted_at, p.auteur_id
            FROM projects p
            ORDER BY p.created_at DESC
        ");
        return $requete->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getStatistiques()
    {
        $stats = [];
        $stats['total_projets'] = $this->baseDeDonnees->query("SELECT COUNT(*) FROM projects")->fetchColumn();
        $stats['total_articles'] = $this->baseDeDonnees->query("SELECT COUNT(*) FROM articles")->fetchColumn();
        $stats['total_utilisateurs'] = $this->baseDeDonnees->query("SELECT COUNT(*) FROM users")->fetchColumn();
        return $stats;
    }
}
