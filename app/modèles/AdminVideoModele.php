<?php
require_once __DIR__ . '/../../config/database.php';

class AdminVideoModele
{
    private PDO $baseDeDonnees;
    public function __construct() { $this->baseDeDonnees = getDatabase(); }

    public function tousLesElements(): array
    {
        $sql = "SELECT id, titre, description, categorie, type, fichier, youtube_url, vignette, created_at
                FROM videos WHERE deleted_at IS NULL ORDER BY created_at DESC";
        return $this->baseDeDonnees->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function trouver(int $id): ?array
    {
        $sql = "SELECT id, titre, description, categorie, type, fichier, youtube_url, vignette, created_at
                FROM videos WHERE id = :id AND deleted_at IS NULL";
        $requete = $this->baseDeDonnees->prepare($sql);
        $requete->execute([':id' => $id]);
        $result = $requete->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function creer(array $data): int
    {
        $sql = "INSERT INTO videos (titre, description, categorie, type, fichier, youtube_url, vignette, created_at)
                VALUES (:titre, :description, :categorie, :type, :fichier, :youtube_url, :vignette, NOW())";
        $requete = $this->baseDeDonnees->prepare($sql);
        $requete->execute([
            ':titre'       => $data['titre'],
            ':description' => $data['description'] ?? null,
            ':categorie'   => $data['categorie'] ?? null,
            ':type'        => $data['type'] ?? 'local',
            ':fichier'     => $data['fichier'] ?? null,
            ':youtube_url' => $data['youtube_url'] ?? null,
            ':vignette'    => $data['vignette'] ?? null
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

    public function supprimer(int $id): bool
    {
        $requete = $this->baseDeDonnees->prepare("UPDATE videos SET deleted_at = NOW() WHERE id = :id");
        return $requete->execute([':id' => $id]);
    }

    /**
     * Suppression définitive d'une vidéo (hard delete)
     * À utiliser avec précaution
     */
    public function supprimerDefinitivement(int $id): bool
    {
        $requete = $this->baseDeDonnees->prepare("DELETE FROM videos WHERE id = :id AND deleted_at IS NOT NULL");
        return $requete->execute([':id' => $id]);
    }

    /**
     * Restaurer une vidéo supprimée
     */
    public function restaurer(int $id): bool
    {
        $requete = $this->baseDeDonnees->prepare("UPDATE videos SET deleted_at = NULL WHERE id = :id AND deleted_at IS NOT NULL");
        return $requete->execute([':id' => $id]);
    }

    /**
     * Récupérer toutes les vidéos supprimées (dans la corbeille)
     */
    public function elementsSupprimes(): array
    {
        $sql = "SELECT id, titre, description, categorie, type, vignette, created_at, deleted_at
                FROM videos WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC";
        return $this->baseDeDonnees->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}
