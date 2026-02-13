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
                    c.id, c.texte, c.created_at, c.user_id, c.video_id,
                    u.nom AS auteur,
                    u.photo AS user_photo
                FROM commentaires c
                INNER JOIN connexion u ON u.id = c.user_id
                WHERE c.video_id = :video_id
                ORDER BY c.created_at DESC";

        $requete = $this->baseDeDonnees->prepare($sql);
        $requete->execute([':video_id' => $videoId]);
        return $requete->fetchAll(PDO::FETCH_ASSOC);
    }

    
    public function creer(int $videoId, int $userId, string $texte): bool
    {
        $sql = "INSERT INTO commentaires (video_id, user_id, texte, created_at)
                VALUES (:video_id, :user_id, :texte, NOW())";

        $requete = $this->baseDeDonnees->prepare($sql);
        return $requete->execute([
            ':video_id' => $videoId,
            ':user_id' => $userId,
            ':texte' => $texte
        ]);
    }

  
    public function supprimer(int $id): bool
    {
        $requete = $this->baseDeDonnees->prepare("DELETE FROM commentaires WHERE id = :id");
        return $requete->execute([':id' => $id]);
    }
}
