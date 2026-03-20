<?php
require_once __DIR__ . '/../../config/database.php';

class ProjetModele
{
    private $connexion;

    private function normaliserLigneDonnees(array $ligne): array
    {
        foreach ($ligne as $key => $value) {
            if (is_string($value)) {
                $ligne[$key] = trim($value);
            }
        }

        return $ligne;
    }

    public function __construct()
    {
        $baseDeDonnees = new Database();
        $this->connexion = $baseDeDonnees->getConnection();
    }

    public function obtenirTousLesProjets()
    {
        $requete = $this->connexion->query("
            SELECT p.id, p.title, p.description, p.description_detailed,
                   p.technologies, p.image_url, p.features, p.challenges,
                   p.categorie, p.created_at, p.updated_at, p.deleted_at, p.auteur_id,
                   c.id AS auteur_id, c.nom AS auteur_nom, c.photo AS auteur_photo
            FROM projects p
            LEFT JOIN users c ON c.id = p.auteur_id AND c.deleted_at IS NULL
            WHERE p.deleted_at IS NULL ORDER BY p.created_at DESC
        ");
        $projects = $requete->fetchAll(PDO::FETCH_ASSOC);

        foreach ($projects as &$projet) {
            $projet = $this->normaliserLigneDonnees($projet);
        }
        unset($projet);

        return $projects;
    }

    public function obtenirProjetParId($id)
    {
        $requete = $this->connexion->prepare("
            SELECT p.id, p.title, p.description, p.description_detailed,
                   p.technologies, p.image_url, p.features, p.challenges,
                   p.categorie, p.created_at, p.updated_at, p.deleted_at, p.auteur_id,
                   c.id AS auteur_id, c.nom AS auteur_nom, c.photo AS auteur_photo
            FROM projects p
            LEFT JOIN users c ON c.id = p.auteur_id AND c.deleted_at IS NULL
            WHERE p.id = ? AND p.deleted_at IS NULL
        ");
        $requete->execute([$id]);
        $projet = $requete->fetch(PDO::FETCH_ASSOC);

        if ($projet) {
            $projet = $this->normaliserLigneDonnees($projet);
        }

        return $projet ?: [];
    }

    
    public function compterProjets(string $q = ''): int
    {
        $where = ['p.deleted_at IS NULL'];
        $params = [];

        if ($q !== '') {
            $where[] = "(p.title LIKE :q OR p.description LIKE :q OR p.technologies LIKE :q OR c.nom LIKE :q)";
            $params[':q'] = '%' . $q . '%';
        }

        $sql = "SELECT COUNT(*) FROM projects p LEFT JOIN users c ON c.id = p.auteur_id AND c.deleted_at IS NULL WHERE " . implode(' AND ', $where);
        $requete = $this->connexion->prepare($sql);
        $requete->execute($params);
        return (int) $requete->fetchColumn();
    }

    
    public function obtenirProjetsPagines(int $limit, int $offset, string $q = ''): array
    {
        $where = ['p.deleted_at IS NULL'];
        $params = [];

        if ($q !== '') {
            $where[] = "(p.title LIKE :q OR p.description LIKE :q OR p.technologies LIKE :q OR c.nom LIKE :q)";
            $params[':q'] = '%' . $q . '%';
        }

        $sql = "SELECT p.id, p.title, p.description, p.description_detailed,
                       p.technologies, p.image_url, p.features, p.challenges,
                       p.categorie, p.created_at, p.updated_at, p.deleted_at, p.auteur_id,
                       c.id AS auteur_id, c.nom AS auteur_nom, c.photo AS auteur_photo
                FROM projects p
                LEFT JOIN users c ON c.id = p.auteur_id AND c.deleted_at IS NULL
                WHERE " . implode(' AND ', $where)
             . " ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset";

        $requete = $this->connexion->prepare($sql);
        foreach ($params as $key => $value) {
            $requete->bindValue($key, $value);
        }
        $requete->bindValue(':limit', $limit, PDO::PARAM_INT);
        $requete->bindValue(':offset', $offset, PDO::PARAM_INT);
        $requete->execute();

        $projects = $requete->fetchAll(PDO::FETCH_ASSOC);
        foreach ($projects as &$projet) {
            $projet = $this->normaliserLigneDonnees($projet);
        }
        unset($projet);
        return $projects;
    }

    public function creerProjet(array $data): int
    {
        $sql = 'INSERT INTO projects (title, auteur_id, description, description_detailed, technologies, categorie, image_url, features, challenges)
                VALUES (:title, :auteur_id, :description, :description_detailed, :technologies, :categorie, :image_url, :features, :challenges)';

        $requete = $this->connexion->prepare($sql);
        $requete->execute([
            ':title' => $data['title'],
            ':auteur_id' => $data['auteur_id'],
            ':description' => $data['description'],
            ':description_detailed' => $data['description_detailed'],
            ':technologies' => $data['technologies'],
            ':categorie' => $data['categorie'],
            ':image_url' => $data['image_url'],
            ':features' => $data['features'],
            ':challenges' => $data['challenges'],
        ]);

        return (int)$this->connexion->lastInsertId();
    }
}
