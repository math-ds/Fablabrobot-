<?php
require_once __DIR__ . '/../../config/database.php';

class CommentairesVideoModele
{
    private PDO $baseDeDonnees;

    public function __construct()
    {
        $this->baseDeDonnees = getDatabase();
    }

    public function listerPourVideo(int $videoId): array
    {
        $sql = "SELECT
                    c.id, c.texte, c.created_at, c.user_id, c.video_id, c.parent_id,
                    u.nom AS auteur,
                    u.photo AS user_photo
                FROM commentaires c
                INNER JOIN users u ON u.id = c.user_id
                WHERE c.video_id = :video_id
                  AND c.deleted_at IS NULL
                ORDER BY
                    COALESCE(c.parent_id, c.id) DESC,
                    CASE WHEN c.parent_id IS NULL THEN 0 ELSE 1 END ASC,
                    c.created_at ASC";

        $requete = $this->baseDeDonnees->prepare($sql);
        $requete->execute([':video_id' => $videoId]);
        return $requete->fetchAll(PDO::FETCH_ASSOC);
    }

    public function trouverPourVideo(int $commentId, int $videoId): ?array
    {
        $sql = "SELECT
                    c.id, c.texte, c.created_at, c.user_id, c.video_id, c.parent_id,
                    u.nom AS auteur,
                    u.photo AS user_photo
                FROM commentaires c
                INNER JOIN users u ON u.id = c.user_id
                WHERE c.id = :id
                  AND c.video_id = :video_id
                  AND c.deleted_at IS NULL
                LIMIT 1";

        $requete = $this->baseDeDonnees->prepare($sql);
        $requete->execute([
            ':id' => $commentId,
            ':video_id' => $videoId,
        ]);

        $resultat = $requete->fetch(PDO::FETCH_ASSOC);
        return $resultat ?: null;
    }

    public function creer(int $videoId, int $userId, string $texte, ?int $parentId = null): bool
    {
        $parentId = ($parentId !== null && $parentId > 0) ? $parentId : null;

        $sql = "INSERT INTO commentaires (video_id, user_id, parent_id, texte, created_at)
                VALUES (:video_id, :user_id, :parent_id, :texte, NOW())";

        $params = [
            ':video_id' => $videoId,
            ':user_id' => $userId,
            ':texte' => $texte,
            ':parent_id' => $parentId,
        ];
        $requete = $this->baseDeDonnees->prepare($sql);
        return $requete->execute($params);
    }

    public function dernierIdInsere(): int
    {
        return (int)$this->baseDeDonnees->lastInsertId();
    }

    public function compterPourVideo(int $videoId): int
    {
        $requete = $this->baseDeDonnees->prepare(
            "SELECT COUNT(*) FROM commentaires WHERE video_id = :video_id AND deleted_at IS NULL"
        );
        $requete->execute([':video_id' => $videoId]);
        return (int)$requete->fetchColumn();
    }

    public function supprimer(int $id): bool
    {
        $requeteEnfants = $this->baseDeDonnees->prepare("DELETE FROM commentaires WHERE parent_id = :id");
        $requeteEnfants->execute([':id' => $id]);

        $requete = $this->baseDeDonnees->prepare("DELETE FROM commentaires WHERE id = :id");
        return $requete->execute([':id' => $id]);
    }
}
