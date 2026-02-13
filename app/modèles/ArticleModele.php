<?php
require_once __DIR__ . '/../../config/database.php';

class ArticleModele {
    private $baseDeDonnees;

    public function __construct() {
        $this->baseDeDonnees = (new Database())->getConnection();
    }

    public function obtenirTousLesArticles() {
        $requete = $this->baseDeDonnees->query("SELECT * FROM articles WHERE deleted_at IS NULL ORDER BY created_at DESC");
        return $requete->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenirArticleParId($id) {
        $requete = $this->baseDeDonnees->prepare("SELECT * FROM articles WHERE id = ? AND deleted_at IS NULL");
        $requete->execute([$id]);
        return $requete->fetch(PDO::FETCH_ASSOC);
    }
}
