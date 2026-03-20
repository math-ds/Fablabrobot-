<?php
require_once __DIR__ . '/../modèles/ProjetModele.php';
require_once __DIR__ . '/../modèles/FavorisModele.php';
require_once __DIR__ . '/../helpers/GestionnaireCache.php';

class ProjetControleur
{

    public function detail($id)
    {
        $modele = new ProjetModele();
        $cache = GestionnaireCache::obtenirInstance();

        
        $projet = $cache->memoriser(
            'projet_' . $id,
            function () use ($modele, $id) {
                return $modele->obtenirProjetParId($id);
            },
            3600
        );


        if ($projet) {
            foreach ($projet as $key => $value) {
                if (is_string($value)) {
                    $projet[$key] = trim($value);
                }
            }
        }

        if ($projet) {
            $utilisateurId = (int)($_SESSION['utilisateur_id'] ?? 0);
            if ($utilisateurId > 0 && !empty($projet['id'])) {
                $modeleFavoris = new FavorisModele();
                $projet['is_favori'] = $modeleFavoris->estFavori($utilisateurId, 'projet', (int)$projet['id']);
            } else {
                $projet['is_favori'] = false;
            }
        }

        require __DIR__ . '/../vues/projets/detail.php';
    }
}
