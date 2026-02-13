<?php
// app/modeles/AdminProjetsModele.php
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
            SELECT
                id,
                title,
                description,
                auteur,
                description_detailed,
                technologies,
                image_url,
                features,
                challenges,
                created_at,
                updated_at
            FROM projects
            WHERE deleted_at IS NULL
            ORDER BY created_at DESC
        ";

        return $this->baseDeDonnees->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function trouver(int $id): ?array
    {

        $sql = "
            SELECT
                id,
                title,
                description,
                auteur,
                description_detailed,
                technologies,
                image_url,
                features,
                challenges,
                created_at,
                updated_at
            FROM projects
            WHERE id = :id AND deleted_at IS NULL
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
            (title, description, auteur, description_detailed, technologies, image_url, features, challenges, created_at, updated_at)
            VALUES 
            (:title, :description, :auteur, :description_detailed, :technologies, :image_url, :features, :challenges, NOW(), NOW())
        ";


        $auteur = empty($data['auteur']) ? 'Fablabteam' : $data['auteur'];

        $requete = $this->baseDeDonnees->prepare($sql);
        $requete->execute([
            ':title'                => $data['title'],
            ':description'          => $data['description'],
            ':auteur'               => $auteur,
            ':description_detailed' => $data['description_detailed'] ?? null,
            ':technologies'         => $data['technologies'] ?? null,
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
                auteur = :auteur,
                description_detailed = :description_detailed,
                technologies = :technologies,
                image_url = :image_url,
                features = :features,
                challenges = :challenges,
                updated_at = NOW()
            WHERE id = :id
        ";


        $auteur = empty($data['auteur']) ? 'Fablabteam' : $data['auteur'];

        $requete = $this->baseDeDonnees->prepare($sql);
        return $requete->execute([
            ':title'                => $data['title'],
            ':description'          => $data['description'],
            ':auteur'               => $auteur,
            ':description_detailed' => $data['description_detailed'] ?? null,
            ':technologies'         => $data['technologies'] ?? null,
            ':image_url'            => $data['image_url'] ?? null,
            ':features'             => $data['features'] ?? null,
            ':challenges'           => $data['challenges'] ?? null,
            ':id'                   => $id
        ]);
    }

    public function supprimer(int $id): bool
    {
        $requete = $this->baseDeDonnees->prepare("UPDATE projects SET deleted_at = NOW() WHERE id = :id");
        return $requete->execute([':id' => $id]);
    }

    /**
     * Suppression définitive d'un projet (hard delete)
     * À utiliser avec précaution
     */
    public function supprimerDefinitivement(int $id): bool
    {
        $requete = $this->baseDeDonnees->prepare("DELETE FROM projects WHERE id = :id AND deleted_at IS NOT NULL");
        return $requete->execute([':id' => $id]);
    }

    /**
     * Restaurer un projet supprimé
     */
    public function restaurer(int $id): bool
    {
        $requete = $this->baseDeDonnees->prepare("UPDATE projects SET deleted_at = NULL WHERE id = :id AND deleted_at IS NOT NULL");
        return $requete->execute([':id' => $id]);
    }

    /**
     * Récupérer tous les projets supprimés (dans la corbeille)
     */
    public function elementsSupprimes(): array
    {
        $sql = "
            SELECT
                id,
                title,
                description,
                auteur,
                image_url,
                created_at,
                deleted_at
            FROM projects
            WHERE deleted_at IS NOT NULL
            ORDER BY deleted_at DESC
        ";

        return $this->baseDeDonnees->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}
