<?php

/**
 * Pilote de Cache en Mémoire (APCu)
 * Utilise APCu (Alternative PHP Cache) pour un cache ultra-rapide en mémoire
 * Nécessite l'extension APCu installée et activée
 */
class CacheMemoire {
    
    private $prefixe;
    
    /**
     * Constructeur
     * 
     * @param array $configuration Configuration du cache
     */
    public function __construct($configuration) {
        $this->prefixe = $configuration['prefixe'] ?? 'cache_';
        
        // Vérifier que APCu est disponible
        if (!extension_loaded('apcu') || !ini_get('apc.enabled')) {
            throw new Exception('L\'extension APCu n\'est pas disponible. Utilisez le pilote "fichier" à la place.');
        }
    }
    
    /**
     * Génère une clé complète avec préfixe
     * 
     * @param string $cle Clé de base
     * @return string Clé avec préfixe
     */
    private function obtenirCleComplete($cle) {
        return $this->prefixe . $cle;
    }
    
    /**
     * Stocke une valeur dans le cache APCu
     * 
     * @param string $cle Clé du cache
     * @param mixed $valeur Valeur à stocker
     * @param int $dureeVie Durée de vie en secondes
     * @return bool Succès de l'opération
     */
    public function stocker($cle, $valeur, $dureeVie) {
        $cleComplete = $this->obtenirCleComplete($cle);
        
        // APCu stocke directement avec TTL
        return apcu_store($cleComplete, $valeur, $dureeVie);
    }
    
    /**
     * Récupère une valeur du cache
     * 
     * @param string $cle Clé du cache
     * @return mixed|false Valeur ou false si non trouvée
     */
    public function recuperer($cle) {
        $cleComplete = $this->obtenirCleComplete($cle);
        
        $succes = false;
        $valeur = apcu_fetch($cleComplete, $succes);
        
        return $succes ? $valeur : false;
    }
    
    /**
     * Vérifie si une clé existe dans le cache
     * 
     * @param string $cle Clé à vérifier
     * @return bool
     */
    public function existe($cle) {
        $cleComplete = $this->obtenirCleComplete($cle);
        return apcu_exists($cleComplete);
    }
    
    /**
     * Supprime une entrée du cache
     * 
     * @param string $cle Clé à supprimer
     * @return bool Succès de la suppression
     */
    public function supprimer($cle) {
        $cleComplete = $this->obtenirCleComplete($cle);
        return apcu_delete($cleComplete);
    }
    
    /**
     * Vide complètement le cache
     * 
     * @return bool Succès de l'opération
     */
    public function vider() {
        // APCu ne permet pas de vider uniquement notre préfixe
        // On doit itérer sur toutes les clés
        
        $iterator = new APCUIterator('/^' . preg_quote($this->prefixe, '/') . '/');
        
        foreach ($iterator as $entree) {
            apcu_delete($entree['key']);
        }
        
        return true;
    }
    
    /**
     * Nettoie les entrées expirées du cache
     * APCu gère automatiquement l'expiration, donc cette méthode ne fait rien
     * 
     * @return bool Toujours true
     */
    public function nettoyerExpire() {
        // APCu gère automatiquement l'expiration
        return true;
    }
    
    /**
     * Supprime toutes les entrées correspondant à un préfixe
     * 
     * @param string $prefixe Préfixe des clés à supprimer
     * @return int Nombre d'entrées supprimées
     */
    public function supprimerParPrefixe($prefixe) {
        $prefixeComplet = $this->obtenirCleComplete($prefixe);
        $iterator = new APCUIterator('/^' . preg_quote($prefixeComplet, '/') . '/');
        
        $supprimes = 0;
        foreach ($iterator as $entree) {
            if (apcu_delete($entree['key'])) {
                $supprimes++;
            }
        }
        
        return $supprimes;
    }
    
    /**
     * Obtient des statistiques sur le cache APCu
     * 
     * @return array Statistiques
     */
    public function obtenirStatistiques() {
        // Statistiques globales APCu
        $infosApcu = apcu_cache_info(true);
        $smaInfo = apcu_sma_info(true);
        
        // Compter nos entrées spécifiques
        $iterator = new APCUIterator('/^' . preg_quote($this->prefixe, '/') . '/');
        $nosEntrees = 0;
        
        foreach ($iterator as $entree) {
            $nosEntrees++;
        }
        
        return [
            'type' => 'APCu (Mémoire)',
            'entrees_fablab' => $nosEntrees,
            'total_entrees_apcu' => $infosApcu['num_entries'] ?? 0,
            'taille_cache' => isset($smaInfo['num_seg']) ? $smaInfo['num_seg'] . ' segments' : 'N/A',
            'memoire_utilisee' => isset($smaInfo['seg_size']) ? round($smaInfo['seg_size'] / (1024 * 1024), 2) . ' Mo' : 'N/A',
            'hits' => $infosApcu['num_hits'] ?? 0,
            'misses' => $infosApcu['num_misses'] ?? 0,
            'taux_reussite' => $this->calculerTauxReussite($infosApcu['num_hits'] ?? 0, $infosApcu['num_misses'] ?? 0)
        ];
    }
    
    /**
     * Calcule le taux de réussite du cache
     * 
     * @param int $hits Nombre de hits
     * @param int $misses Nombre de misses
     * @return string Pourcentage formaté
     */
    private function calculerTauxReussite($hits, $misses) {
        $total = $hits + $misses;
        
        if ($total === 0) {
            return '0%';
        }
        
        $taux = ($hits / $total) * 100;
        return round($taux, 2) . '%';
    }
}