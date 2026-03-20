<?php
require_once __DIR__ . '/../../config/database.php';

class FavorisModele
{
    private PDO $baseDeDonnees;

    private const TYPES_AUTORISES = ['article', 'projet', 'video'];

    public function __construct()
    {
        $this->baseDeDonnees = (new Database())->getConnection();
    }

    public function normaliserType(string $type): ?string
    {
        $typeNormalise = strtolower(trim($type));
        return in_array($typeNormalise, self::TYPES_AUTORISES, true) ? $typeNormalise : null;
    }

    public function utilisateurActifExiste(int $utilisateurId): bool
    {
        if ($utilisateurId <= 0) {
            return false;
        }

        $requete = $this->baseDeDonnees->prepare(
            'SELECT id
             FROM users
             WHERE id = :id AND deleted_at IS NULL
             LIMIT 1'
        );
        $requete->execute([':id' => $utilisateurId]);
        return (bool)$requete->fetchColumn();
    }

    public function obtenirUtilisateurActifParEmail(string $email): ?array
    {
        $emailNormalise = trim($email);
        if ($emailNormalise === '') {
            return null;
        }

        $requete = $this->baseDeDonnees->prepare(
            'SELECT id, nom, email, role, photo
             FROM users
             WHERE email = :email AND deleted_at IS NULL
             LIMIT 1'
        );
        $requete->execute([':email' => $emailNormalise]);
        $utilisateur = $requete->fetch(PDO::FETCH_ASSOC);

        return is_array($utilisateur) ? $utilisateur : null;
    }

    public function contenuExiste(string $type, int $contenuId): bool
    {
        $typeNormalise = $this->normaliserType($type);
        if ($typeNormalise === null || $contenuId <= 0) {
            return false;
        }

        switch ($typeNormalise) {
            case 'article':
                $sql = 'SELECT id FROM articles WHERE id = :id AND deleted_at IS NULL LIMIT 1';
                break;
            case 'projet':
                $sql = 'SELECT id FROM projects WHERE id = :id AND deleted_at IS NULL LIMIT 1';
                break;
            case 'video':
                $sql = 'SELECT id FROM videos WHERE id = :id AND deleted_at IS NULL LIMIT 1';
                break;
            default:
                return false;
        }

        $requete = $this->baseDeDonnees->prepare($sql);
        $requete->execute([':id' => $contenuId]);

        return (bool)$requete->fetchColumn();
    }

    public function estFavori(int $utilisateurId, string $type, int $contenuId): bool
    {
        $typeNormalise = $this->normaliserType($type);
        if ($typeNormalise === null || $utilisateurId <= 0 || $contenuId <= 0) {
            return false;
        }

        $colonne = $this->colonnePourType($typeNormalise);
        if ($colonne === null) {
            return false;
        }

        $sql = "SELECT id
                FROM favoris
                WHERE user_id = :user_id AND {$colonne} = :contenu_id
                LIMIT 1";
        $requete = $this->baseDeDonnees->prepare($sql);
        $requete->execute([
            ':user_id' => $utilisateurId,
            ':contenu_id' => $contenuId,
        ]);

        return (bool)$requete->fetchColumn();
    }

    public function toggleFavori(int $utilisateurId, string $type, int $contenuId): bool
    {
        $typeNormalise = $this->normaliserType($type);
        if ($typeNormalise === null || $utilisateurId <= 0 || $contenuId <= 0) {
            throw new InvalidArgumentException('Parametres favoris invalides.');
        }
        if (!$this->utilisateurActifExiste($utilisateurId)) {
            throw new RuntimeException('Session utilisateur invalide. Veuillez vous reconnecter.');
        }

        $this->baseDeDonnees->beginTransaction();

        try {
            $colonne = $this->colonnePourType($typeNormalise);
            if ($colonne === null) {
                throw new InvalidArgumentException('Type de favori invalide.');
            }

            $requete = $this->baseDeDonnees->prepare(
                "SELECT id
                 FROM favoris
                 WHERE user_id = :user_id AND {$colonne} = :contenu_id
                 LIMIT 1"
            );
            $requete->execute([
                ':user_id' => $utilisateurId,
                ':contenu_id' => $contenuId,
            ]);
            $idFavori = $requete->fetchColumn();

            if ($idFavori !== false) {
                $suppression = $this->baseDeDonnees->prepare('DELETE FROM favoris WHERE id = :id');
                $suppression->execute([':id' => (int)$idFavori]);
                $this->baseDeDonnees->commit();
                return false;
            }

            $colonnesDediees = $this->valeursColonnesDediees($typeNormalise, $contenuId);
            $insertion = $this->baseDeDonnees->prepare(
                'INSERT INTO favoris (user_id, article_id, projet_id, video_id, created_at)
                 VALUES (:user_id, :article_id, :projet_id, :video_id, NOW())'
            );
            $insertion->execute([
                ':user_id' => $utilisateurId,
                ':article_id' => $colonnesDediees['article_id'],
                ':projet_id' => $colonnesDediees['projet_id'],
                ':video_id' => $colonnesDediees['video_id'],
            ]);

            $this->baseDeDonnees->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->baseDeDonnees->inTransaction()) {
                $this->baseDeDonnees->rollBack();
            }
            throw $e;
        }
    }

    public function obtenirIdsFavorisUtilisateurEtType(int $utilisateurId, string $type): array
    {
        $typeNormalise = $this->normaliserType($type);
        if ($typeNormalise === null || $utilisateurId <= 0) {
            return [];
        }

        $colonne = $this->colonnePourType($typeNormalise);
        if ($colonne === null) {
            return [];
        }

        $requete = $this->baseDeDonnees->prepare(
            "SELECT {$colonne}
             FROM favoris
             WHERE user_id = :user_id AND {$colonne} IS NOT NULL"
        );
        $requete->execute([':user_id' => $utilisateurId]);

        return array_map('intval', $requete->fetchAll(PDO::FETCH_COLUMN));
    }

    public function compterFavorisValidesUtilisateur(int $utilisateurId): array
    {
        $compteurs = [
            'article' => 0,
            'projet' => 0,
            'video' => 0,
            'all' => 0,
        ];

        if ($utilisateurId <= 0) {
            return $compteurs;
        }

        $compteurs['article'] = $this->compterFavorisTypeValides($utilisateurId, 'article');
        $compteurs['projet'] = $this->compterFavorisTypeValides($utilisateurId, 'projet');
        $compteurs['video'] = $this->compterFavorisTypeValides($utilisateurId, 'video');
        $compteurs['all'] = $compteurs['article'] + $compteurs['projet'] + $compteurs['video'];
        return $compteurs;
    }

    public function supprimerTousFavorisUtilisateur(int $utilisateurId): int
    {
        if ($utilisateurId <= 0) {
            return 0;
        }

        $requete = $this->baseDeDonnees->prepare('DELETE FROM favoris WHERE user_id = :user_id');
        $requete->execute([':user_id' => $utilisateurId]);
        return $requete->rowCount();
    }

    public function obtenirFavorisUtilisateur(int $utilisateurId, string $filtreType = 'all'): array
    {
        if ($utilisateurId <= 0) {
            return [];
        }

        $filtre = strtolower(trim($filtreType));
        $types = $filtre === 'all'
            ? self::TYPES_AUTORISES
            : [$filtre];

        $favoris = [];
        foreach ($types as $type) {
            if ($type === 'article') {
                $favoris = array_merge($favoris, $this->obtenirFavorisArticles($utilisateurId));
            } elseif ($type === 'projet') {
                $favoris = array_merge($favoris, $this->obtenirFavorisProjets($utilisateurId));
            } elseif ($type === 'video') {
                $favoris = array_merge($favoris, $this->obtenirFavorisVideos($utilisateurId));
            }
        }

        usort($favoris, static function (array $a, array $b): int {
            return strcmp((string)($b['favori_created_at'] ?? ''), (string)($a['favori_created_at'] ?? ''));
        });

        return $favoris;
    }

    private function obtenirFavorisArticles(int $utilisateurId): array
    {
        $requete = $this->baseDeDonnees->prepare(
            'SELECT f.article_id AS contenu_id,
                    f.created_at AS favori_created_at,
                    a.titre,
                    a.contenu,
                    a.image_url,
                    a.created_at,
                    c.nom AS auteur_nom
             FROM favoris f
             INNER JOIN articles a ON a.id = f.article_id AND a.deleted_at IS NULL
             LEFT JOIN users c ON c.id = a.auteur_id AND c.deleted_at IS NULL
             WHERE f.user_id = :user_id AND f.article_id IS NOT NULL
             ORDER BY f.created_at DESC'
        );

        $requete->execute([':user_id' => $utilisateurId]);
        $rows = $requete->fetchAll(PDO::FETCH_ASSOC);

        return array_map(static function (array $row): array {
            return [
                'type_contenu' => 'article',
                'id' => (int)($row['contenu_id'] ?? 0),
                'titre' => (string)($row['titre'] ?? ''),
                'description' => (string)($row['contenu'] ?? ''),
                'image_url' => (string)($row['image_url'] ?? ''),
                'auteur_nom' => (string)($row['auteur_nom'] ?? ''),
                'created_at' => (string)($row['created_at'] ?? ''),
                'favori_created_at' => (string)($row['favori_created_at'] ?? ''),
                'url' => '?page=article-detail&id=' . (int)($row['contenu_id'] ?? 0),
            ];
        }, $rows);
    }

    private function obtenirFavorisProjets(int $utilisateurId): array
    {
        $requete = $this->baseDeDonnees->prepare(
            'SELECT f.projet_id AS contenu_id,
                    f.created_at AS favori_created_at,
                    p.title AS title,
                    p.description,
                    p.image_url,
                    p.created_at,
                    c.nom AS auteur_nom
             FROM favoris f
             INNER JOIN projects p ON p.id = f.projet_id AND p.deleted_at IS NULL
             LEFT JOIN users c ON c.id = p.auteur_id AND c.deleted_at IS NULL
             WHERE f.user_id = :user_id AND f.projet_id IS NOT NULL
             ORDER BY f.created_at DESC'
        );

        $requete->execute([':user_id' => $utilisateurId]);
        $rows = $requete->fetchAll(PDO::FETCH_ASSOC);

        return array_map(static function (array $row): array {
            return [
                'type_contenu' => 'projet',
                'id' => (int)($row['contenu_id'] ?? 0),
                'titre' => (string)($row['title'] ?? ''),
                'description' => (string)($row['description'] ?? ''),
                'image_url' => (string)($row['image_url'] ?? ''),
                'auteur_nom' => (string)($row['auteur_nom'] ?? ''),
                'created_at' => (string)($row['created_at'] ?? ''),
                'favori_created_at' => (string)($row['favori_created_at'] ?? ''),
                'url' => '?page=projet&id=' . (int)($row['contenu_id'] ?? 0),
            ];
        }, $rows);
    }

    private function obtenirFavorisVideos(int $utilisateurId): array
    {
        $requete = $this->baseDeDonnees->prepare(
            'SELECT f.video_id AS contenu_id,
                    f.created_at AS favori_created_at,
                    v.titre,
                    v.description,
                    v.vignette,
                    v.youtube_url,
                    v.created_at,
                    c.nom AS auteur_nom
             FROM favoris f
             INNER JOIN videos v ON v.id = f.video_id AND v.deleted_at IS NULL
             LEFT JOIN users c ON c.id = v.auteur_id AND c.deleted_at IS NULL
             WHERE f.user_id = :user_id AND f.video_id IS NOT NULL
             ORDER BY f.created_at DESC'
        );

        $requete->execute([':user_id' => $utilisateurId]);
        $rows = $requete->fetchAll(PDO::FETCH_ASSOC);

        return array_map(static function (array $row): array {
            return [
                'type_contenu' => 'video',
                'id' => (int)($row['contenu_id'] ?? 0),
                'titre' => (string)($row['titre'] ?? ''),
                'description' => (string)($row['description'] ?? ''),
                'image_url' => (string)($row['vignette'] ?? ''),
                'youtube_url' => (string)($row['youtube_url'] ?? ''),
                'auteur_nom' => (string)($row['auteur_nom'] ?? ''),
                'created_at' => (string)($row['created_at'] ?? ''),
                'favori_created_at' => (string)($row['favori_created_at'] ?? ''),
                'url' => '?page=webtv&video=' . (int)($row['contenu_id'] ?? 0),
            ];
        }, $rows);
    }

    private function compterFavorisTypeValides(int $utilisateurId, string $type): int
    {
        $colonne = $this->colonnePourType($type);
        if ($colonne === null) {
            return 0;
        }

        switch ($type) {
            case 'article':
                $table = 'articles';
                break;
            case 'projet':
                $table = 'projects';
                break;
            case 'video':
                $table = 'videos';
                break;
            default:
                return 0;
        }

        $sql = "SELECT COUNT(*)
                FROM favoris f
                INNER JOIN {$table} t ON t.id = f.{$colonne} AND t.deleted_at IS NULL
                WHERE f.user_id = :user_id AND f.{$colonne} IS NOT NULL";

        $requete = $this->baseDeDonnees->prepare($sql);
        $requete->execute([':user_id' => $utilisateurId]);
        return (int)$requete->fetchColumn();
    }

    private function colonnePourType(string $type): ?string
    {
        switch ($type) {
            case 'article':
                return 'article_id';
            case 'projet':
                return 'projet_id';
            case 'video':
                return 'video_id';
            default:
                return null;
        }
    }

    private function valeursColonnesDediees(string $type, int $contenuId): array
    {
        return [
            'article_id' => $type === 'article' ? $contenuId : null,
            'projet_id' => $type === 'projet' ? $contenuId : null,
            'video_id' => $type === 'video' ? $contenuId : null,
        ];
    }
}
