<?php
require_once __DIR__ . '/../../config/database.php';

class ArticleModele
{
    private $baseDeDonnees;

    public function __construct()
    {
        $this->baseDeDonnees = (new Database())->getConnection();
    }

    public function obtenirTousLesArticles()
    {
        $requete = $this->baseDeDonnees->query("
            SELECT a.*,
                   c.id AS auteur_id, c.nom AS auteur_nom, c.photo AS auteur_photo
            FROM articles a
            LEFT JOIN users c ON c.id = a.auteur_id AND c.deleted_at IS NULL
            WHERE a.deleted_at IS NULL
            ORDER BY a.created_at DESC
        ");
        return $requete->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenirArticleParId($id)
    {
        $requete = $this->baseDeDonnees->prepare("
            SELECT a.*,
                   c.id AS auteur_id, c.nom AS auteur_nom, c.photo AS auteur_photo
            FROM articles a
            LEFT JOIN users c ON c.id = a.auteur_id AND c.deleted_at IS NULL
            WHERE a.id = ? AND a.deleted_at IS NULL
        ");
        $requete->execute([$id]);
        return $requete->fetch(PDO::FETCH_ASSOC);
    }

    public function creer(array $data): int
    {
        $sql = "INSERT INTO articles (titre, contenu, auteur_id, categorie, image_url, created_at, updated_at)
                VALUES (:titre, :contenu, :auteur_id, :categorie, :image_url, NOW(), NOW())";
        $requete = $this->baseDeDonnees->prepare($sql);
        $requete->execute([
            ':titre'     => $data['titre'],
            ':contenu'   => $data['contenu'],
            ':auteur_id' => $data['auteur_id'] ?? null,
            ':categorie' => $data['categorie'] ?? null,
            ':image_url' => $data['image_url'] ?? null,
        ]);

        return (int)$this->baseDeDonnees->lastInsertId();
    }

    
    private function variantesCategorie(string $categorie): array
    {
        $value = trim($categorie);
        if ($value === '' || $value === 'all') {
            return [];
        }

        $key = function_exists('mb_strtolower')
            ? mb_strtolower($value, 'UTF-8')
            : strtolower($value);

        $map = [
            'robotique' => ['Robotique'],
            'électronique' => ['Électronique', 'Electronique'],
            'electronique' => ['Électronique', 'Electronique'],
            'programmation' => ['Programmation'],
            'impression 3d' => ['Impression 3D'],
            'mécanique' => ['Mécanique', 'Mecanique'],
            'mecanique' => ['Mécanique', 'Mecanique'],
            'conception' => ['Conception'],
            'intelligence artificielle' => ['Intelligence Artificielle'],
            'autre' => ['Autre'],
        ];

        if (isset($map[$key])) {
            return $map[$key];
        }

        return [$value];
    }

    
    public function compterArticles(string $q = '', string $categorie = ''): int
    {
        $where = ['a.deleted_at IS NULL'];
        $params = [];

        if ($q !== '') {
            $where[] = "(a.titre LIKE :q OR a.contenu LIKE :q OR a.categorie LIKE :q OR c.nom LIKE :q)";
            $params[':q'] = '%' . $q . '%';
        }

        $categories = $this->variantesCategorie($categorie);
        if (!empty($categories)) {
            $placeholders = [];
            foreach ($categories as $index => $categoryValue) {
                $placeholder = ':categorie' . $index;
                $placeholders[] = $placeholder;
                $params[$placeholder] = $categoryValue;
            }
            $where[] = 'a.categorie IN (' . implode(', ', $placeholders) . ')';
        }

        $sql = "SELECT COUNT(*) FROM articles a LEFT JOIN users c ON c.id = a.auteur_id AND c.deleted_at IS NULL WHERE " . implode(' AND ', $where);
        $requete = $this->baseDeDonnees->prepare($sql);
        $requete->execute($params);
        return (int) $requete->fetchColumn();
    }

    
    public function obtenirArticlesPagines(int $limit, int $offset, string $q = '', string $categorie = ''): array
    {
        $where = ['a.deleted_at IS NULL'];
        $params = [];

        if ($q !== '') {
            $where[] = "(a.titre LIKE :q OR a.contenu LIKE :q OR a.categorie LIKE :q OR c.nom LIKE :q)";
            $params[':q'] = '%' . $q . '%';
        }

        $categories = $this->variantesCategorie($categorie);
        if (!empty($categories)) {
            $placeholders = [];
            foreach ($categories as $index => $categoryValue) {
                $placeholder = ':categorie' . $index;
                $placeholders[] = $placeholder;
                $params[$placeholder] = $categoryValue;
            }
            $where[] = 'a.categorie IN (' . implode(', ', $placeholders) . ')';
        }

        $sql = "SELECT a.*,
                       c.id AS auteur_id, c.nom AS auteur_nom, c.photo AS auteur_photo
                FROM articles a
                LEFT JOIN users c ON c.id = a.auteur_id AND c.deleted_at IS NULL
                WHERE " . implode(' AND ', $where)
             . " ORDER BY a.created_at DESC LIMIT :limit OFFSET :offset";

        $requete = $this->baseDeDonnees->prepare($sql);
        foreach ($params as $key => $value) {
            $requete->bindValue($key, $value);
        }
        $requete->bindValue(':limit', $limit, PDO::PARAM_INT);
        $requete->bindValue(':offset', $offset, PDO::PARAM_INT);
        $requete->execute();
        return $requete->fetchAll(PDO::FETCH_ASSOC);
    }
}
