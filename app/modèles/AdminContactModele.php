<?php
require_once __DIR__ . '/../../config/database.php';

class AdminContactModele
{
    private PDO $baseDeDonnees;

    public function __construct()
    {
        $this->baseDeDonnees = getDatabase();
    }

    public function tousLesElements(): array
    {
        $sql = "SELECT * FROM contact_messages WHERE deleted_at IS NULL ORDER BY date_envoi DESC";
        return $this->baseDeDonnees->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function mettreAJourStatut(int $id, string $statut): bool
    {
        $requete = $this->baseDeDonnees->prepare("UPDATE contact_messages SET statut = :statut, date_lecture = NOW() WHERE id = :id");
        return $requete->execute([':id' => $id, ':statut' => $statut]);
    }

    public function supprimer(int $id): bool
    {
        $requete = $this->baseDeDonnees->prepare("UPDATE contact_messages SET deleted_at = NOW() WHERE id = :id");
        return $requete->execute([':id' => $id]);
    }

    
    public function supprimerDefinitivement(int $id): bool
    {
        $requete = $this->baseDeDonnees->prepare("DELETE FROM contact_messages WHERE id = :id");
        return $requete->execute([':id' => $id]);
    }

    
    public function restaurer(int $id): bool
    {
        $requete = $this->baseDeDonnees->prepare("UPDATE contact_messages SET deleted_at = NULL WHERE id = :id");
        return $requete->execute([':id' => $id]);
    }

    
    public function elementsSupprimes(): array
    {
        $sql = "SELECT id, nom, email, sujet, message, date_envoi, deleted_at
                FROM contact_messages WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC";
        return $this->baseDeDonnees->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}
