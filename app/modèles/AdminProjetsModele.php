<?php

require_once __DIR__ . '/../../config/database.php';

class AdminProjetsModele
{
    private PDO $baseDeDonnees;

    public function __construct()
    {
        $this->baseDeDonnees = getDatabase();
    }

    public function tousLesElements(): array
    {
        $sql = "
            SELECT p.id, p.title, p.description,
                   p.description_detailed, p.technologies, p.categorie,
                   p.image_url, p.features, p.challenges, p.created_at, p.updated_at,
                   c.id AS auteur_id, c.nom AS auteur_nom, c.photo AS auteur_photo
            FROM projects p
            LEFT JOIN users c ON c.id = p.auteur_id AND c.deleted_at IS NULL
            WHERE p.deleted_at IS NULL
            ORDER BY p.created_at DESC
        ";

        return $this->baseDeDonnees->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function trouver(int $id): ?array
    {
        $sql = "
            SELECT p.id, p.title, p.description,
                   p.description_detailed, p.technologies, p.categorie,
                   p.image_url, p.features, p.challenges, p.created_at, p.updated_at,
                   c.id AS auteur_id, c.nom AS auteur_nom, c.photo AS auteur_photo
            FROM projects p
            LEFT JOIN users c ON c.id = p.auteur_id AND c.deleted_at IS NULL
            WHERE p.id = :id AND p.deleted_at IS NULL
            LIMIT 1
        ";

        $requete = $this->baseDeDonnees->prepare($sql);
        $requete->execute([':id' => $id]);

        $result = $requete->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function creer(array $data): int
    {
        $sql = "
            INSERT INTO projects
            (title, description, auteur_id, description_detailed, technologies, categorie, image_url, features, challenges, created_at, updated_at)
            VALUES
            (:title, :description, :auteur_id, :description_detailed, :technologies, :categorie, :image_url, :features, :challenges, NOW(), NOW())
        ";

        $requete = $this->baseDeDonnees->prepare($sql);
        $requete->execute([
            ':title'                => $data['title'],
            ':description'          => $data['description'],
            ':auteur_id'            => $data['auteur_id'] ?? null,
            ':description_detailed' => $data['description_detailed'] ?? null,
            ':technologies'         => $data['technologies'] ?? null,
            ':categorie'            => $data['categorie'] ?? null,
            ':image_url'            => $data['image_url'] ?? null,
            ':features'             => $data['features'] ?? null,
            ':challenges'           => $data['challenges'] ?? null
        ]);

        return (int)$this->baseDeDonnees->lastInsertId();
    }

    public function mettreAJour(int $id, array $data): bool
    {
        $sql = "
            UPDATE projects
            SET
                title = :title,
                description = :description,
                description_detailed = :description_detailed,
                technologies = :technologies,
                categorie = :categorie,
                image_url = :image_url,
                features = :features,
                challenges = :challenges,
                updated_at = NOW()
            WHERE id = :id
        ";

        $requete = $this->baseDeDonnees->prepare($sql);
        return $requete->execute([
            ':title'                => $data['title'],
            ':description'          => $data['description'],
            ':description_detailed' => $data['description_detailed'] ?? null,
            ':technologies'         => $data['technologies'] ?? null,
            ':categorie'            => $data['categorie'] ?? null,
            ':image_url'            => $data['image_url'] ?? null,
            ':features'             => $data['features'] ?? null,
            ':challenges'           => $data['challenges'] ?? null,
            ':id'                   => $id
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
        $requete = $this->baseDeDonnees->prepare("UPDATE projects SET deleted_at = NOW() WHERE id = :id");
        return $requete->execute([':id' => $id]);
    }

    
    public function supprimerDefinitivement(int $id): bool
    {
        $requete = $this->baseDeDonnees->prepare("DELETE FROM projects WHERE id = :id AND deleted_at IS NOT NULL");
        return $requete->execute([':id' => $id]);
    }

    
    public function restaurer(int $id): bool
    {
        $requete = $this->baseDeDonnees->prepare("UPDATE projects SET deleted_at = NULL WHERE id = :id AND deleted_at IS NOT NULL");
        return $requete->execute([':id' => $id]);
    }

    
    public function elementsSupprimes(): array
    {
        $sql = "
            SELECT p.id, p.title, p.description,
                   p.image_url, p.created_at, p.deleted_at,
                   c.id AS auteur_id, c.nom AS auteur_nom
            FROM projects p
            LEFT JOIN users c ON c.id = p.auteur_id
            WHERE p.deleted_at IS NOT NULL
            ORDER BY p.deleted_at DESC
        ";

        return $this->baseDeDonnees->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}
