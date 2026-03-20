<?php


class CacheMemoire
{
    private $prefixe;
    
    
    public function __construct($configuration)
    {
        $this->prefixe = $configuration['prefixe'] ?? 'cache_';
        
        
        if (!extension_loaded('apcu') || !ini_get('apc.enabled')) {
            throw new Exception('L\'extension APCu n\'est pas disponible. Utilisez le pilote "fichier" à la place.');
        }
    }
    
    
    private function obtenirCleComplete($cle)
    {
        return $this->prefixe . $cle;
    }
    
    
    public function stocker($cle, $valeur, $dureeVie)
    {
        $cleComplete = $this->obtenirCleComplete($cle);
        
        
        return apcu_store($cleComplete, $valeur, $dureeVie);
    }
    
    
    public function recuperer($cle)
    {
        $cleComplete = $this->obtenirCleComplete($cle);
        
        $succes = false;
        $valeur = apcu_fetch($cleComplete, $succes);
        
        return $succes ? $valeur : false;
    }
    
    
    public function existe($cle)
    {
        $cleComplete = $this->obtenirCleComplete($cle);
        return apcu_exists($cleComplete);
    }
    
    
    public function supprimer($cle)
    {
        $cleComplete = $this->obtenirCleComplete($cle);
        return apcu_delete($cleComplete);
    }
    
    
    public function vider()
    {
        
        
        
        $iterator = new APCUIterator('/^' . preg_quote($this->prefixe, '/') . '/');
        
        foreach ($iterator as $entree) {
            apcu_delete($entree['key']);
        }
        
        return true;
    }
    
    
    public function nettoyerExpire()
    {
        
        return true;
    }
    
    
    public function supprimerParPrefixe($prefixe)
    {
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
    
    
    public function obtenirStatistiques()
    {
        
        $infosApcu = apcu_cache_info(true);
        $smaInfo = apcu_sma_info(true);
        
        
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
    
    
    private function calculerTauxReussite($hits, $misses)
    {
        $total = $hits + $misses;
        
        if ($total === 0) {
            return '0%';
        }
        
        $taux = ($hits / $total) * 100;
        return round($taux, 2) . '%';
    }
}
