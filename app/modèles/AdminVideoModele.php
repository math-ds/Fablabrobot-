<?php
require_once __DIR__ . '/../../config/database.php';

class AdminVideoModele
{
    private PDO $baseDeDonnees;

    public function __construct()
    {
        $this->baseDeDonnees = getDatabase();
    }

    public function tousLesElements(): array
    {
        $sql = "SELECT v.id, v.titre, v.description, v.categorie, v.type, v.fichier,
                       v.youtube_url, v.vignette, v.created_at,
                       c.id AS auteur_id, c.nom AS auteur_nom, c.photo AS auteur_photo
                FROM videos v
                LEFT JOIN users c ON c.id = v.auteur_id AND c.deleted_at IS NULL
                WHERE v.deleted_at IS NULL ORDER BY v.created_at DESC";
        return $this->baseDeDonnees->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function trouver(int $id): ?array
    {
        $sql = "SELECT v.id, v.titre, v.description, v.categorie, v.type, v.fichier,
                       v.youtube_url, v.vignette, v.created_at,
                       c.id AS auteur_id, c.nom AS auteur_nom, c.photo AS auteur_photo
                FROM videos v
                LEFT JOIN users c ON c.id = v.auteur_id AND c.deleted_at IS NULL
                WHERE v.id = :id AND v.deleted_at IS NULL";
        $requete = $this->baseDeDonnees->prepare($sql);
        $requete->execute([':id' => $id]);
        $result = $requete->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function creer(array $data): int
    {
        $sql = "INSERT INTO videos (titre, description, categorie, type, fichier, youtube_url, vignette, auteur_id, created_at)
                VALUES (:titre, :description, :categorie, :type, :fichier, :youtube_url, :vignette, :auteur_id, NOW())";
        $requete = $this->baseDeDonnees->prepare($sql);
        $requete->execute([
            ':titre'       => $data['titre'],
            ':description' => $data['description'] ?? null,
            ':categorie'   => $data['categorie'] ?? null,
            ':type'        => $data['type'] ?? 'local',
            ':fichier'     => $data['fichier'] ?? null,
            ':youtube_url' => $data['youtube_url'] ?? null,
            ':vignette'    => $data['vignette'] ?? null,
            ':auteur_id'   => $data['auteur_id'] ?? null
        ]);
        return (int)$this->baseDeDonnees->lastInsertId();
    }

    public function mettreAJour(int $id, array $data): bool
    {
        $sql = "UPDATE videos
                SET titre=:titre, description=:description, categorie=:categorie, type=:type,
                    fichier=:fichier, youtube_url=:youtube_url, vignette=:vignette
                WHERE id=:id";
        $requete = $this->baseDeDonnees->prepare($sql);
        return $requete->execute([
            ':titre'       => $data['titre'],
            ':description' => $data['description'] ?? null,
            ':categorie'   => $data['categorie'] ?? null,
            ':type'        => $data['type'] ?? 'local',
            ':fichier'     => $data['fichier'] ?? null,
            ':youtube_url' => $data['youtube_url'] ?? null,
            ':vignette'    => $data['vignette'] ?? null,
            ':id'          => $id
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
        $requete = $this->baseDeDonnees->prepare("UPDATE videos SET deleted_at = NOW() WHERE id = :id");
        return $requete->execute([':id' => $id]);
    }

    
    public function supprimerDefinitivement(int $id): bool
    {
        $requete = $this->baseDeDonnees->prepare("DELETE FROM videos WHERE id = :id AND deleted_at IS NOT NULL");
        return $requete->execute([':id' => $id]);
    }

    
    public function restaurer(int $id): bool
    {
        $requete = $this->baseDeDonnees->prepare("UPDATE videos SET deleted_at = NULL WHERE id = :id AND deleted_at IS NOT NULL");
        return $requete->execute([':id' => $id]);
    }

    
    public function elementsSupprimes(): array
    {
        $sql = "SELECT v.id, v.titre, v.description, v.categorie, v.type, v.vignette,
                       v.created_at, v.deleted_at,
                       c.id AS auteur_id, c.nom AS auteur_nom
                FROM videos v
                LEFT JOIN users c ON c.id = v.auteur_id
                WHERE v.deleted_at IS NOT NULL ORDER BY v.deleted_at DESC";
        return $this->baseDeDonnees->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}
