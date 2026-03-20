<?php
require_once __DIR__ . '/../../config/database.php';

class ContactModele
{
    private PDO $baseDeDonnees;

    public function __construct()
    {
        $this->baseDeDonnees = getDatabase();
    }

    public function creerMessage(string $nom, string $email, string $sujet, string $message, ?string $ipAddress): bool
    {
        $sql = 'INSERT INTO contact_messages (nom, email, sujet, message, ip_address)
                VALUES (:nom, :email, :sujet, :message, :ip)';
        $requete = $this->baseDeDonnees->prepare($sql);

        return $requete->execute([
            ':nom' => $nom,
            ':email' => $email,
            ':sujet' => $sujet,
            ':message' => $message,
            ':ip' => $ipAddress,
        ]);
    }
}
