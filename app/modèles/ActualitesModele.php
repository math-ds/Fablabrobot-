<?php

class ActualitesModele
{
    private $connexion;

    public function __construct($db)
    {
        $this->connexion = $db;
    }

    
    public function obtenirToutesLesActualites(int $limite = 20, int $offset = 0): array
    {
        $sql = "SELECT * FROM actualites
                WHERE deleted_at IS NULL
                ORDER BY published_at DESC
                LIMIT :limite OFFSET :offset";

        $stmt = $this->connexion->prepare($sql);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    
    public function compterActualites(): int
    {
        $sql = "SELECT COUNT(*) as total FROM actualites WHERE deleted_at IS NULL";
        $stmt = $this->connexion->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['total'] ?? 0);
    }

    
    public function obtenirActualiteParId(int $id): ?array
    {
        $sql = "SELECT * FROM actualites WHERE id = :id AND deleted_at IS NULL";
        $stmt = $this->connexion->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    
    public function rechercherActualites(string $query, int $limite = 20): array
    {
        $sql = "SELECT * FROM actualites
                WHERE deleted_at IS NULL
                AND (titre LIKE :query OR description LIKE :query OR source LIKE :query)
                ORDER BY published_at DESC
                LIMIT :limite";

        $stmt = $this->connexion->prepare($sql);
        $searchTerm = '%' . $query . '%';
        $stmt->bindValue(':query', $searchTerm, PDO::PARAM_STR);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    
    public function compterActualitesRecherche(string $query): int
    {
        $sql = "SELECT COUNT(*) as total
                FROM actualites
                WHERE deleted_at IS NULL
                AND (titre LIKE :query OR description LIKE :query OR source LIKE :query)";

        $stmt = $this->connexion->prepare($sql);
        $searchTerm = '%' . $query . '%';
        $stmt->bindValue(':query', $searchTerm, PDO::PARAM_STR);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['total'] ?? 0);
    }

    
    public function obtenirActualitesRecherche(string $query, int $limite = 20, int $offset = 0): array
    {
        $sql = "SELECT * FROM actualites
                WHERE deleted_at IS NULL
                AND (titre LIKE :query OR description LIKE :query OR source LIKE :query)
                ORDER BY published_at DESC
                LIMIT :limite OFFSET :offset";

        $stmt = $this->connexion->prepare($sql);
        $searchTerm = '%' . $query . '%';
        $stmt->bindValue(':query', $searchTerm, PDO::PARAM_STR);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    
    public function compterActualitesFiltrees(string $query = '', string $source = ''): int
    {
        $conditions = ['deleted_at IS NULL'];
        $params = [];

        $queryNormalisee = trim($query);
        if ($queryNormalisee !== '') {
            $conditions[] = '(titre LIKE :query OR description LIKE :query OR source LIKE :query)';
            $params[':query'] = '%' . $queryNormalisee . '%';
        }

        $sourceNormalisee = trim($source);
        if ($sourceNormalisee !== '') {
            $conditions[] = 'source = :source';
            $params[':source'] = $sourceNormalisee;
        }

        $sql = 'SELECT COUNT(*) as total FROM actualites WHERE ' . implode(' AND ', $conditions);
        $stmt = $this->connexion->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['total'] ?? 0);
    }

    
    public function obtenirActualitesFiltrees(string $query = '', string $source = '', int $limite = 20, int $offset = 0): array
    {
        $conditions = ['deleted_at IS NULL'];
        $params = [];

        $queryNormalisee = trim($query);
        if ($queryNormalisee !== '') {
            $conditions[] = '(titre LIKE :query OR description LIKE :query OR source LIKE :query)';
            $params[':query'] = '%' . $queryNormalisee . '%';
        }

        $sourceNormalisee = trim($source);
        if ($sourceNormalisee !== '') {
            $conditions[] = 'source = :source';
            $params[':source'] = $sourceNormalisee;
        }

        $sql = 'SELECT * FROM actualites WHERE ' . implode(' AND ', $conditions)
             . ' ORDER BY published_at DESC LIMIT :limite OFFSET :offset';

        $stmt = $this->connexion->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    
    public function creerActualite(array $data): bool
    {
        $sql = "INSERT INTO actualites (titre, description, contenu, source, url_source, image_url, auteur, published_at)
                VALUES (:titre, :description, :contenu, :source, :url_source, :image_url, :auteur, :published_at)";

        $stmt = $this->connexion->prepare($sql);
        $stmt->bindValue(':titre', $data['titre'] ?? '', PDO::PARAM_STR);
        $stmt->bindValue(':description', $data['description'] ?? '', PDO::PARAM_STR);
        $stmt->bindValue(':contenu', $data['contenu'] ?? '', PDO::PARAM_STR);
        $stmt->bindValue(':source', $data['source'] ?? '', PDO::PARAM_STR);
        $stmt->bindValue(':url_source', $data['url_source'] ?? '', PDO::PARAM_STR);
        $stmt->bindValue(':image_url', $data['image_url'] ?? '', PDO::PARAM_STR);
        $stmt->bindValue(':auteur', $data['auteur'] ?? '', PDO::PARAM_STR);
        $stmt->bindValue(':published_at', $data['published_at'] ?? date('Y-m-d H:i:s'), PDO::PARAM_STR);

        return $stmt->execute();
    }

    
    public function actualiteExiste(string $urlSource): bool
    {
        $sql = "SELECT COUNT(*) as count FROM actualites WHERE url_source = :url_source";
        $stmt = $this->connexion->prepare($sql);
        $stmt->bindValue(':url_source', $urlSource, PDO::PARAM_STR);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return ($result['count'] ?? 0) > 0;
    }

    
    public function supprimerActualite(int $id): bool
    {
        $sql = "UPDATE actualites SET deleted_at = NOW() WHERE id = :id AND deleted_at IS NULL";
        $stmt = $this->connexion->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    
    public function supprimerDefinitivement(int $id): bool
    {
        $sql = "DELETE FROM actualites WHERE id = :id AND deleted_at IS NOT NULL";
        $stmt = $this->connexion->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    
    public function restaurerActualite(int $id): bool
    {
        $sql = "UPDATE actualites SET deleted_at = NULL WHERE id = :id AND deleted_at IS NOT NULL";
        $stmt = $this->connexion->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    
    public function obtenirActualitesSupprimees(): array
    {
        $sql = "SELECT * FROM actualites WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC";
        $stmt = $this->connexion->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    
    public function nettoyerAnciennesActualites(int $joursConservation = 30): int
    {
        $sql = "UPDATE actualites
                SET deleted_at = NOW()
                WHERE deleted_at IS NULL
                AND published_at < DATE_SUB(NOW(), INTERVAL :jours DAY)";

        $stmt = $this->connexion->prepare($sql);
        $stmt->bindValue(':jours', $joursConservation, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount();
    }

    
    public function obtenirSources(): array
    {
        $sql = "SELECT DISTINCT source FROM actualites WHERE deleted_at IS NULL AND source IS NOT NULL ORDER BY source";
        $stmt = $this->connexion->query($sql);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
