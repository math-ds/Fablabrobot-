<?php
require_once __DIR__ . '/../modèles/ProjetModele.php';
require_once __DIR__ . '/../helpers/GestionnaireCache.php';

class ProjetControleur {

    public function detail($id) {
        $modele = new ProjetModele();
        $cache = GestionnaireCache::obtenirInstance();

        // Cache : Projet individuel (1 heure)
        $projet = $cache->memoriser('projet_' . $id, function() use ($modele, $id) {
            return $modele->obtenirProjetParId($id);
        }, 3600);


        if ($projet) {
            foreach ($projet as $key => $value) {
                if (is_string($value)) {
                    $projet[$key] = trim($value);
                }
            }
        }

        require __DIR__ . '/../vues/projets/detail.php';
    }
}
