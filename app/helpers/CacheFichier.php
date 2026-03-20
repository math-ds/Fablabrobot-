<?php


class CacheFichier
{
    private $cheminCache;
    private $prefixe;
    private $extension = '.cache';
    
    
    public function __construct($configuration)
    {
        $this->cheminCache = $configuration['chemin_cache'] ?? sys_get_temp_dir() . '/fablab_cache';
        $this->prefixe = $configuration['prefixe'] ?? 'cache_';
        
        
        $this->creerDossierCache();
    }
    
    
    private function creerDossierCache()
    {
        if (!file_exists($this->cheminCache)) {
            @mkdir($this->cheminCache, 0755, true);
        }
        
        
        $htaccessPath = $this->cheminCache . '/.htaccess';
        if (!file_exists($htaccessPath)) {
            file_put_contents($htaccessPath, "Deny from all\n");
        }
        
        
        $indexPath = $this->cheminCache . '/index.php';
        if (!file_exists($indexPath)) {
            file_put_contents($indexPath, "<?php\n// Accès interdit\nhttp_response_code(403);\nexit;");
        }
    }
    
    
    private function obtenirCheminFichier($cle)
    {
        
        $cleSecurisee = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $cle);
        return $this->cheminCache . '/' . $cleSecurisee . $this->extension;
    }
    
    
    public function stocker($cle, $valeur, $dureeVie)
    {
        $cheminFichier = $this->obtenirCheminFichier($cle);
        
        $donnees = [
            'cle' => $cle,
            'valeur' => $valeur,
            'creation' => time(),
            'expiration' => time() + $dureeVie,
            'duree_vie' => $dureeVie
        ];
        
        $contenu = serialize($donnees);
        
        
        $fichierTemp = $cheminFichier . '.tmp';
        
        if (file_put_contents($fichierTemp, $contenu, LOCK_EX) === false) {
            return false;
        }
        
        return rename($fichierTemp, $cheminFichier);
    }
    
    
    public function recuperer($cle)
    {
        $cheminFichier = $this->obtenirCheminFichier($cle);
        
        if (!file_exists($cheminFichier)) {
            return false;
        }
        
        $contenu = @file_get_contents($cheminFichier);
        
        if ($contenu === false) {
            return false;
        }
        
        $donnees = @unserialize($contenu);
        
        if ($donnees === false) {
            
            @unlink($cheminFichier);
            return false;
        }
        
        
        if (time() > $donnees['expiration']) {
            $this->supprimer($cle);
            return false;
        }
        
        return $donnees['valeur'];
    }
    
    
    public function existe($cle)
    {
        $cheminFichier = $this->obtenirCheminFichier($cle);
        
        if (!file_exists($cheminFichier)) {
            return false;
        }
        
        $contenu = @file_get_contents($cheminFichier);
        
        if ($contenu === false) {
            return false;
        }
        
        $donnees = @unserialize($contenu);
        
        if ($donnees === false) {
            return false;
        }
        
        
        return time() <= $donnees['expiration'];
    }
    
    
    public function supprimer($cle)
    {
        $cheminFichier = $this->obtenirCheminFichier($cle);
        
        if (!file_exists($cheminFichier)) {
            return true; 
        }
        
        return @unlink($cheminFichier);
    }
    
    
    public function vider()
    {
        $fichiers = glob($this->cheminCache . '/*' . $this->extension);
        
        if ($fichiers === false) {
            return false;
        }
        
        $succes = true;
        foreach ($fichiers as $fichier) {
            if (is_file($fichier)) {
                $succes = @unlink($fichier) && $succes;
            }
        }
        
        return $succes;
    }
    
    
    public function nettoyerExpire()
    {
        $fichiers = glob($this->cheminCache . '/*' . $this->extension);
        
        if ($fichiers === false) {
            return false;
        }
        
        $maintenant = time();
        $nettoyes = 0;
        
        foreach ($fichiers as $fichier) {
            if (!is_file($fichier)) {
                continue;
            }
            
            $contenu = @file_get_contents($fichier);
            
            if ($contenu === false) {
                continue;
            }
            
            $donnees = @unserialize($contenu);
            
            if ($donnees === false || $maintenant > $donnees['expiration']) {
                if (@unlink($fichier)) {
                    $nettoyes++;
                }
            }
        }
        
        return true;
    }
    
    
    public function supprimerParPrefixe($prefixe)
    {
        $fichiers = glob($this->cheminCache . '/*' . $this->extension);
        
        if ($fichiers === false) {
            return 0;
        }
        
        $supprimes = 0;
        
        foreach ($fichiers as $fichier) {
            if (!is_file($fichier)) {
                continue;
            }
            
            $contenu = @file_get_contents($fichier);
            
            if ($contenu === false) {
                continue;
            }
            
            $donnees = @unserialize($contenu);
            
            if ($donnees !== false && strpos($donnees['cle'], $prefixe) === 0) {
                if (@unlink($fichier)) {
                    $supprimes++;
                }
            }
        }
        
        return $supprimes;
    }
    
    
    public function obtenirStatistiques()
    {
        $fichiers = glob($this->cheminCache . '/*' . $this->extension);
        
        if ($fichiers === false) {
            return [
                'total_entrees' => 0,
                'entrees_valides' => 0,
                'entrees_expirees' => 0,
                'taille_totale' => 0,
                'chemin' => $this->cheminCache
            ];
        }
        
        $maintenant = time();
        $stats = [
            'total_entrees' => count($fichiers),
            'entrees_valides' => 0,
            'entrees_expirees' => 0,
            'taille_totale' => 0,
            'chemin' => $this->cheminCache
        ];
        
        foreach ($fichiers as $fichier) {
            if (!is_file($fichier)) {
                continue;
            }
            
            $stats['taille_totale'] += filesize($fichier);
            
            $contenu = @file_get_contents($fichier);
            if ($contenu === false) {
                continue;
            }
            
            $donnees = @unserialize($contenu);
            if ($donnees === false) {
                continue;
            }
            
            if ($maintenant <= $donnees['expiration']) {
                $stats['entrees_valides']++;
            } else {
                $stats['entrees_expirees']++;
            }
        }
        
        
        if ($stats['taille_totale'] > 1024 * 1024) {
            $stats['taille_formatee'] = round($stats['taille_totale'] / (1024 * 1024), 2) . ' Mo';
        } else {
            $stats['taille_formatee'] = round($stats['taille_totale'] / 1024, 2) . ' Ko';
        }
        
        return $stats;
    }
}
