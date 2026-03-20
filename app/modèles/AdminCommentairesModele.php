<?php
require_once __DIR__ . '/../../config/database.php';

class AdminCommentairesModele
{
    private PDO $baseDeDonnees;

    public function __construct()
    {
        $this->baseDeDonnees = getDatabase();
    }

    public function tousLesElements(?string $q = null, string $type = 'all'): array
    {
        $params = [];
        $sql = "SELECT
                    c.id, c.video_id, c.user_id, c.parent_id,
                    c.texte, c.created_at,
                    v.titre AS video_titre, v.youtube_url,
                    u.nom AS auteur, u.email AS user_email,
                    p.id AS parent_comment_id,
                    p.texte AS parent_texte,
                    up.nom AS parent_auteur
                FROM commentaires c
                LEFT JOIN videos v ON v.id = c.video_id
                LEFT JOIN users u ON u.id = c.user_id
                LEFT JOIN commentaires p ON p.id = c.parent_id
                LEFT JOIN users up ON up.id = p.user_id
                WHERE c.deleted_at IS NULL";

        if ($type === 'parent') {
            $sql .= " AND (c.parent_id IS NULL OR c.parent_id = 0)";
        } elseif ($type === 'reponse') {
            $sql .= " AND (c.parent_id IS NOT NULL AND c.parent_id <> 0)";
        }

        if ($q && trim($q) !== '') {
            $sql .= " AND (
                        c.texte LIKE :q
                        OR v.titre LIKE :q
                        OR u.nom LIKE :q
                        OR u.email LIKE :q
                        OR p.texte LIKE :q
                        OR up.nom LIKE :q
                    )";
            $params[':q'] = '%' . trim($q) . '%';
        }

        $sql .= " ORDER BY
                    COALESCE(NULLIF(c.parent_id, 0), c.id) DESC,
                    CASE WHEN c.parent_id IS NULL OR c.parent_id = 0 THEN 0 ELSE 1 END ASC,
                    c.created_at ASC";

        $requete = $this->baseDeDonnees->prepare($sql);
        $requete->execute($params);
        return $requete->fetchAll(PDO::FETCH_ASSOC);
    }

    public function trouver(int $id): ?array
    {
        $requete = $this->baseDeDonnees->prepare("SELECT id, video_id, parent_id FROM commentaires WHERE id = :id LIMIT 1");
        $requete->execute([':id' => $id]);
        $commentaire = $requete->fetch(PDO::FETCH_ASSOC);

        return $commentaire ?: null;
    }

    public function supprimer(int $id): bool
    {
        return $this->supprimerAvecDescendants($id) > 0;
    }

    public function supprimerAvecDescendants(int $id): int
    {
        $commentaire = $this->trouver($id);
        if (!$commentaire) {
            return 0;
        }

        $estParent = empty($commentaire['parent_id']);
        $sql = $estParent
            ? "UPDATE commentaires
               SET deleted_at = NOW()
               WHERE deleted_at IS NULL AND (id = :id OR parent_id = :id)"
            : "UPDATE commentaires
               SET deleted_at = NOW()
               WHERE deleted_at IS NULL AND id = :id";

        $requete = $this->baseDeDonnees->prepare($sql);
        $requete->execute([':id' => $id]);

        return $requete->rowCount();
    }

    
    public function supprimerDefinitivement(int $id): bool
    {
        $requete = $this->baseDeDonnees->prepare("DELETE FROM commentaires WHERE id = :id");
        return $requete->execute([':id' => $id]);
    }

    
    public function restaurer(int $id): bool
    {
        $requete = $this->baseDeDonnees->prepare("UPDATE commentaires SET deleted_at = NULL WHERE id = :id");
        return $requete->execute([':id' => $id]);
    }

    public function obtenirStatistiques(): array
    {
        $total = (int)$this->baseDeDonnees->query("SELECT COUNT(*) FROM commentaires WHERE deleted_at IS NULL")->fetchColumn();

        $requete = $this->baseDeDonnees->prepare("SELECT COUNT(*) FROM commentaires WHERE deleted_at IS NULL AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
        $requete->execute();
        $recent = (int)$requete->fetchColumn();

        $parents = (int)$this->baseDeDonnees->query(
            "SELECT COUNT(*) FROM commentaires WHERE deleted_at IS NULL AND (parent_id IS NULL OR parent_id = 0)"
        )->fetchColumn();

        $reponses = (int)$this->baseDeDonnees->query(
            "SELECT COUNT(*) FROM commentaires WHERE deleted_at IS NULL AND (parent_id IS NOT NULL AND parent_id <> 0)"
        )->fetchColumn();

        return [
            'total' => $total,
            'recent' => $recent,
            'parents' => $parents,
            'reponses' => $reponses,
        ];
    }
}
