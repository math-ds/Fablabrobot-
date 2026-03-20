<?php
require_once __DIR__ . '/../../config/database.php';

class AdminArticlesModele
{
    private PDO $baseDeDonnees;

    public function __construct()
    {
        $this->baseDeDonnees = getDatabase();
    }

    public function tousLesElements(): array
    {
        $sql = "SELECT a.id, a.titre, a.contenu,
                       a.categorie, a.image_url, a.created_at, a.updated_at,
                       c.id AS auteur_id, c.nom AS auteur_nom, c.photo AS auteur_photo
                FROM articles a
                LEFT JOIN users c ON c.id = a.auteur_id AND c.deleted_at IS NULL
                WHERE a.deleted_at IS NULL ORDER BY a.created_at DESC";
        return $this->baseDeDonnees->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function trouver(int $id): ?array
    {
        $sql = "SELECT a.id, a.titre, a.contenu,
                       a.categorie, a.image_url, a.created_at, a.updated_at,
                       c.id AS auteur_id, c.nom AS auteur_nom, c.photo AS auteur_photo
                FROM articles a
                LEFT JOIN users c ON c.id = a.auteur_id AND c.deleted_at IS NULL
                WHERE a.id = :id AND a.deleted_at IS NULL";
        $requete = $this->baseDeDonnees->prepare($sql);
        $requete->execute([':id' => $id]);
        $result = $requete->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function creer(array $data): int
    {
        $sql = "INSERT INTO articles (titre, contenu, auteur_id, categorie, image_url, created_at, updated_at)
                VALUES (:titre, :contenu, :auteur_id, :categorie, :image_url, NOW(), NOW())";
        $requete = $this->baseDeDonnees->prepare($sql);
        $requete->execute([
            ':titre'     => $data['titre'],
            ':contenu'   => $data['contenu'],
            ':auteur_id' => $data['auteur_id'] ?? null,
            ':categorie' => $data['categorie'] ?? null,
            ':image_url' => $data['image_url'] ?? null,
        ]);
        return (int)$this->baseDeDonnees->lastInsertId();
    }

    public function mettreAJour(int $id, array $data): bool
    {
        $sql = "UPDATE articles
                SET titre=:titre, contenu=:contenu,
                    categorie=:categorie, image_url=:image_url, updated_at=NOW()
                WHERE id=:id";
        $requete = $this->baseDeDonnees->prepare($sql);
        return $requete->execute([
            ':titre'     => $data['titre'],
            ':contenu'   => $data['contenu'],
            ':categorie' => $data['categorie'] ?? null,
            ':image_url' => $data['image_url'] ?? null,
            ':id'        => $id
        ]);
    }

    public function trouverIdParNom(string $nom): ?int
    {
        $req = $this->baseDeDonnees->prepare(
            "SELECT id FROM users WHERE nom = :nom AND deleted_at IS NULL LIMIT 1"
        );
        $req->execute([':nom' => $nom]);
        $result = $req->fetchColumn();
        return $result !== false ? (int)$result : null;
    }

    public function supprimer(int $id): bool
    {
        $requete = $this->baseDeDonnees->prepare("UPDATE articles SET deleted_at = NOW() WHERE id = :id");
        return $requete->execute([':id' => $id]);
    }

    
    public function supprimerDefinitivement(int $id): bool
    {
        $requete = $this->baseDeDonnees->prepare("DELETE FROM articles WHERE id = :id AND deleted_at IS NOT NULL");
        return $requete->execute([':id' => $id]);
    }

    
    public function restaurer(int $id): bool
    {
        $requete = $this->baseDeDonnees->prepare("UPDATE articles SET deleted_at = NULL WHERE id = :id AND deleted_at IS NOT NULL");
        return $requete->execute([':id' => $id]);
    }

    
    public function elementsSupprimes(): array
    {
        $sql = "SELECT a.id, a.titre, a.contenu,
                       a.image_url, a.created_at, a.deleted_at,
                       c.id AS auteur_id, c.nom AS auteur_nom
                FROM articles a
                LEFT JOIN users c ON c.id = a.auteur_id
                WHERE a.deleted_at IS NOT NULL ORDER BY a.deleted_at DESC";
        return $this->baseDeDonnees->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}
