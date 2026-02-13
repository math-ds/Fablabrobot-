<?php
require_once __DIR__ . '/../../config/database.php';

class AdminArticlesModele
{
    private PDO $baseDeDonnees;
    public function __construct() { $this->baseDeDonnees = getDatabase(); }

    public function tousLesElements(): array
    {
        $sql = "SELECT id, titre, contenu, auteur, image_url, created_at, updated_at
                FROM articles WHERE deleted_at IS NULL ORDER BY created_at DESC";
        return $this->baseDeDonnees->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function trouver(int $id): ?array
    {
        $sql = "SELECT id, titre, contenu, auteur, image_url, created_at, updated_at
                FROM articles WHERE id = :id AND deleted_at IS NULL";
        $requete = $this->baseDeDonnees->prepare($sql);
        $requete->execute([':id' => $id]);
        $result = $requete->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function creer(array $data): int
    {
        $sql = "INSERT INTO articles (titre, contenu, auteur, image_url, created_at, updated_at)
                VALUES (:titre, :contenu, :auteur, :image_url, NOW(), NOW())";
        $requete = $this->baseDeDonnees->prepare($sql);
        $requete->execute([
            ':titre'     => $data['titre'],
            ':contenu'   => $data['contenu'],
            ':auteur'    => $data['auteur'],
            ':image_url' => $data['image_url'] ?? null,
        ]);
        return (int)$this->baseDeDonnees->lastInsertId();
    }

    public function mettreAJour(int $id, array $data): bool
    {
        $sql = "UPDATE articles
                SET titre=:titre, contenu=:contenu, auteur=:auteur, image_url=:image_url, updated_at=NOW()
                WHERE id=:id";
        $requete = $this->baseDeDonnees->prepare($sql);
        return $requete->execute([
            ':titre'     => $data['titre'],
            ':contenu'   => $data['contenu'],
            ':auteur'    => $data['auteur'],
            ':image_url' => $data['image_url'] ?? null,
            ':id'        => $id
        ]);
    }

    public function supprimer(int $id): bool
    {
        $requete = $this->baseDeDonnees->prepare("UPDATE articles SET deleted_at = NOW() WHERE id = :id");
        return $requete->execute([':id' => $id]);
    }

    /**
     * Suppression définitive d'un article (hard delete)
     * À utiliser avec précaution
     */
    public function supprimerDefinitivement(int $id): bool
    {
        $requete = $this->baseDeDonnees->prepare("DELETE FROM articles WHERE id = :id AND deleted_at IS NOT NULL");
        return $requete->execute([':id' => $id]);
    }

    /**
     * Restaurer un article supprimé
     */
    public function restaurer(int $id): bool
    {
        $requete = $this->baseDeDonnees->prepare("UPDATE articles SET deleted_at = NULL WHERE id = :id AND deleted_at IS NOT NULL");
        return $requete->execute([':id' => $id]);
    }

    /**
     * Récupérer tous les articles supprimés (dans la corbeille)
     */
    public function elementsSupprimes(): array
    {
        $sql = "SELECT id, titre, contenu, auteur, image_url, created_at, deleted_at
                FROM articles WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC";
        return $this->baseDeDonnees->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}
