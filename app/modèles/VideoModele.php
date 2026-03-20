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
        $where = ['v.deleted_at IS NULL'];
        $params = [];

        if ($q && trim($q) !== '') {
            $where[] = "(v.titre LIKE :q OR v.description LIKE :q OR c.nom LIKE :q)";
            $params[':q'] = "%" . trim($q) . "%";
        }

        if ($cat && trim($cat) !== '') {
            $where[] = "v.categorie = :cat";
            $params[':cat'] = trim($cat);
        }

                $sql = "SELECT v.id, v.titre, v.description, v.categorie, v.type, v.fichier, v.youtube_url,
                       v.vignette, v.vues, v.duree, v.likes, v.created_at,
                       c.id AS auteur_id, c.nom AS auteur_nom, c.photo AS auteur_photo
                FROM videos v
                LEFT JOIN users c ON c.id = v.auteur_id AND c.deleted_at IS NULL";

        $sql .= " WHERE " . implode(" AND ", $where);

        $sql .= " ORDER BY v.created_at DESC";

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
        $requete = $this->baseDeDonnees->prepare("
            SELECT v.*,
                   c.id AS auteur_id, c.nom AS auteur_nom, c.photo AS auteur_photo
            FROM videos v
            LEFT JOIN users c ON c.id = v.auteur_id AND c.deleted_at IS NULL
            WHERE v.id = :id AND v.deleted_at IS NULL
        ");
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

    
    public function compterVideos(?string $q = null, ?string $cat = null): int
    {
        $where = ['v.deleted_at IS NULL'];
        $params = [];

        if ($q && trim($q) !== '') {
            $where[] = "(v.titre LIKE :q OR v.description LIKE :q OR c.nom LIKE :q)";
            $params[':q'] = "%" . trim($q) . "%";
        }

        if ($cat && trim($cat) !== '') {
            $where[] = "v.categorie = :cat";
            $params[':cat'] = trim($cat);
        }

        $sql = "SELECT COUNT(*) FROM videos v LEFT JOIN users c ON c.id = v.auteur_id AND c.deleted_at IS NULL WHERE " . implode(" AND ", $where);
        $requete = $this->baseDeDonnees->prepare($sql);
        $requete->execute($params);
        return (int) $requete->fetchColumn();
    }

    
    public function tousLesVideosPagines(int $limit, int $offset, ?string $q = null, ?string $cat = null): array
    {
        $where = ['v.deleted_at IS NULL'];
        $params = [];

        if ($q && trim($q) !== '') {
            $where[] = "(v.titre LIKE :q OR v.description LIKE :q OR c.nom LIKE :q)";
            $params[':q'] = "%" . trim($q) . "%";
        }

        if ($cat && trim($cat) !== '') {
            $where[] = "v.categorie = :cat";
            $params[':cat'] = trim($cat);
        }

                $sql = "SELECT v.id, v.titre, v.description, v.categorie, v.type, v.fichier, v.youtube_url,
                       v.vignette, v.vues, v.duree, v.likes, v.created_at,
                       c.id AS auteur_id, c.nom AS auteur_nom, c.photo AS auteur_photo
                FROM videos v
                LEFT JOIN users c ON c.id = v.auteur_id AND c.deleted_at IS NULL
                WHERE " . implode(" AND ", $where) . "
                ORDER BY v.created_at DESC
                LIMIT :limit OFFSET :offset";

        $requete = $this->baseDeDonnees->prepare($sql);
        foreach ($params as $key => $value) {
            $requete->bindValue($key, $value);
        }
        $requete->bindValue(':limit', $limit, PDO::PARAM_INT);
        $requete->bindValue(':offset', $offset, PDO::PARAM_INT);
        $requete->execute();
        return $requete->fetchAll(PDO::FETCH_ASSOC);
    }

    public function creer(array $data): int
    {
        $sql = "INSERT INTO videos (
                    titre,
                    description,
                    categorie,
                    type,
                    fichier,
                    youtube_url,
                    vignette,
                    auteur_id,
                    created_at
                ) VALUES (
                    :titre,
                    :description,
                    :categorie,
                    :type,
                    :fichier,
                    :youtube_url,
                    :vignette,
                    :auteur_id,
                    NOW()
                )";

        $requete = $this->baseDeDonnees->prepare($sql);
        $requete->execute([
            ':titre' => $data['titre'] ?? '',
            ':description' => $data['description'] ?? null,
            ':categorie' => $data['categorie'] ?? null,
            ':type' => $data['type'] ?? 'youtube',
            ':fichier' => $data['fichier'] ?? '',
            ':youtube_url' => $data['youtube_url'] ?? null,
            ':vignette' => $data['vignette'] ?? null,
            ':auteur_id' => $data['auteur_id'] ?? null,
        ]);

        return (int) $this->baseDeDonnees->lastInsertId();
    }
}
