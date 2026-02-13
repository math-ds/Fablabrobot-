<?php
require_once __DIR__ . '/../../config/database.php';

class VideoModele
{
    private PDO $baseDeDonnees;

    public function __construct()
    {
        $this->baseDeDonnees = getDatabase();
    }


    public function tousLesVideos(?string $q = null, ?string $cat = null): array
    {
        $where = ['deleted_at IS NULL'];
        $params = [];

        if ($q && trim($q) !== '') {
            $where[] = "(titre LIKE :q OR description LIKE :q OR auteur LIKE :q)";
            $params[':q'] = "%" . trim($q) . "%";
        }

        if ($cat && trim($cat) !== '') {
            $where[] = "categorie = :cat";
            $params[':cat'] = trim($cat);
        }

        $sql = "SELECT id, titre, description, categorie, type, fichier, youtube_url,
                       vignette, vues, duree, auteur, likes, created_at
                FROM videos";

        $sql .= " WHERE " . implode(" AND ", $where);

        $sql .= " ORDER BY created_at DESC";

        $requete = $this->baseDeDonnees->prepare($sql);
        $requete->execute($params);
        return $requete->fetchAll(PDO::FETCH_ASSOC);
    }


    public function obtenirCategories(): array
    {
        $sql = "SELECT DISTINCT categorie
                FROM videos
                WHERE deleted_at IS NULL AND categorie IS NOT NULL AND categorie != ''
                ORDER BY categorie ASC";
        return $this->baseDeDonnees->query($sql)->fetchAll(PDO::FETCH_COLUMN);
    }


    public function trouverParId(int $id): ?array
    {
        $requete = $this->baseDeDonnees->prepare("SELECT * FROM videos WHERE id = :id AND deleted_at IS NULL");
        $requete->execute([':id' => $id]);
        $video = $requete->fetch(PDO::FETCH_ASSOC);
        return $video ?: null;
    }


    public function trouverParUrlYoutube(string $youtubeUrl): ?array
    {
        $requete = $this->baseDeDonnees->prepare("SELECT * FROM videos WHERE youtube_url = :y AND deleted_at IS NULL LIMIT 1");
        $requete->execute([':y' => $youtubeUrl]);
        $video = $requete->fetch(PDO::FETCH_ASSOC);
        return $video ?: null;
    }


    public function incrementerVues(int $id): void
    {
        $requete = $this->baseDeDonnees->prepare("UPDATE videos SET vues = COALESCE(vues, 0) + 1 WHERE id = :id AND deleted_at IS NULL");
        $requete->execute([':id' => $id]);
    }


    public function compter(): int
    {
        return (int) $this->baseDeDonnees->query("SELECT COUNT(*) FROM videos WHERE deleted_at IS NULL")->fetchColumn();
    }
}
