<?php
require_once __DIR__ . '/../../config/database.php';

class AdminCommentairesModele
{
    private PDO $baseDeDonnees;

    public function __construct()
    {
        $this->baseDeDonnees = getDatabase();
    }

    
    public function tousLesElements(?string $q = null): array
    {
        $params = [];
        $sql = "SELECT
                    c.id, c.video_id, c.user_id,
                    c.texte, c.created_at,
                    v.titre AS video_titre, v.youtube_url,
                    u.nom AS auteur, u.email AS user_email
                FROM commentaires c
                LEFT JOIN videos v ON v.id = c.video_id
                LEFT JOIN connexion u ON u.id = c.user_id
                WHERE c.deleted_at IS NULL";

        if ($q && trim($q) !== '') {
            $sql .= " AND (c.texte LIKE :q OR v.titre LIKE :q OR u.nom LIKE :q OR u.email LIKE :q)";
            $params[':q'] = '%' . trim($q) . '%';
        }

        $sql .= " ORDER BY c.created_at DESC";

        $requete = $this->baseDeDonnees->prepare($sql);
        $requete->execute($params);
        return $requete->fetchAll(PDO::FETCH_ASSOC);
    }

    public function supprimer(int $id): bool
    {
        $requete = $this->baseDeDonnees->prepare("UPDATE commentaires SET deleted_at = NOW() WHERE id = :id");
        return $requete->execute([':id' => $id]);
    }

    /**
     * Suppression définitive d'un commentaire (hard delete)
     * À utiliser avec précaution
     */
    public function supprimerDefinitivement(int $id): bool
    {
        $requete = $this->baseDeDonnees->prepare("DELETE FROM commentaires WHERE id = :id");
        return $requete->execute([':id' => $id]);
    }

    /**
     * Restaurer un commentaire supprimé
     */
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

        return [
            'total' => $total,
            'recent' => $recent
        ];
    }
}
