<?php

/**
 * Pilote de Cache basé sur les Fichiers
 * Stocke les données en cache dans des fichiers sur le disque
 */
class CacheFichier {
    
    private $cheminCache;
    private $prefixe;
    private $extension = '.cache';
    
    /**
     * Constructeur
     * 
     * @param array $configuration Configuration du cache
     */
    public function __construct($configuration) {
        $this->cheminCache = $configuration['chemin_cache'] ?? sys_get_temp_dir() . '/fablab_cache';
        $this->prefixe = $configuration['prefixe'] ?? 'cache_';
        
        // Créer le dossier de cache s'il n'existe pas
        $this->creerDossierCache();
    }
    
    /**
     * Crée le dossier de cache s'il n'existe pas
     */
    private function creerDossierCache() {
        if (!file_exists($this->cheminCache)) {
            @mkdir($this->cheminCache, 0755, true);
        }
        
        // Créer un fichier .htaccess pour protéger le dossier
        $htaccessPath = $this->cheminCache . '/.htaccess';
        if (!file_exists($htaccessPath)) {
            file_put_contents($htaccessPath, "Deny from all\n");
        }
        
        // Créer un index.php vide pour plus de sécurité
        $indexPath = $this->cheminCache . '/index.php';
        if (!file_exists($indexPath)) {
            file_put_contents($indexPath, "<?php\n// Accès interdit\nhttp_response_code(403);\nexit;");
        }
    }
    
    /**
     * Génère le chemin complet du fichier de cache
     * 
     * @param string $cle Clé du cache
     * @return string Chemin du fichier
     */
    private function obtenirCheminFichier($cle) {
        // Nettoyer la clé pour éviter les problèmes de système de fichiers
        $cleSecurisee = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $cle);
        return $this->cheminCache . '/' . $cleSecurisee . $this->extension;
    }
    
    /**
     * Stocke une valeur dans un fichier cache
     * 
     * @param string $cle Clé du cache
     * @param mixed $valeur Valeur à stocker
     * @param int $dureeVie Durée de vie en secondes
     * @return bool Succès de l'opération
     */
    public function stocker($cle, $valeur, $dureeVie) {
        $cheminFichier = $this->obtenirCheminFichier($cle);
        
        $donnees = [
            'cle' => $cle,
            'valeur' => $valeur,
            'creation' => time(),
            'expiration' => time() + $dureeVie,
            'duree_vie' => $dureeVie
        ];
        
        $contenu = serialize($donnees);
        
        // Écrire dans un fichier temporaire puis renommer (opération atomique)
        $fichierTemp = $cheminFichier . '.tmp';
        
        if (file_put_contents($fichierTemp, $contenu, LOCK_EX) === false) {
            return false;
        }
        
        return rename($fichierTemp, $cheminFichier);
    }
    
    /**
     * Récupère une valeur du cache
     * 
     * @param string $cle Clé du cache
     * @return mixed|false Valeur ou false si non trouvée/expirée
     */
    public function recuperer($cle) {
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
            // Fichier corrompu, le supprimer
            @unlink($cheminFichier);
            return false;
        }
        
        // Vérifier si le cache est expiré
        if (time() > $donnees['expiration']) {
            $this->supprimer($cle);
            return false;
        }
        
        return $donnees['valeur'];
    }
    
    /**
     * Vérifie si une clé existe et n'est pas expirée
     * 
     * @param string $cle Clé à vérifier
     * @return bool
     */
    public function existe($cle) {
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
        
        // Vérifier si expiré
        return time() <= $donnees['expiration'];
    }
    
    /**
     * Supprime une entrée du cache
     * 
     * @param string $cle Clé à supprimer
     * @return bool Succès de la suppression
     */
    public function supprimer($cle) {
        $cheminFichier = $this->obtenirCheminFichier($cle);
        
        if (!file_exists($cheminFichier)) {
            return true; // Déjà supprimé
        }
        
        return @unlink($cheminFichier);
    }
    
    /**
     * Vide complètement le cache
     * 
     * @return bool Succès de l'opération
     */
    public function vider() {
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
    
    /**
     * Nettoie les entrées expirées du cache
     * 
     * @return bool Succès du nettoyage
     */
    public function nettoyerExpire() {
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
    
    /**
     * Supprime toutes les entrées correspondant à un préfixe
     * 
     * @param string $prefixe Préfixe des clés à supprimer
     * @return int Nombre d'entrées supprimées
     */
    public function supprimerParPrefixe($prefixe) {
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
    
    /**
     * Obtient des statistiques sur le cache
     * 
     * @return array Statistiques
     */
    public function obtenirStatistiques() {
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
        
        // Formater la taille en Ko ou Mo
        if ($stats['taille_totale'] > 1024 * 1024) {
            $stats['taille_formatee'] = round($stats['taille_totale'] / (1024 * 1024), 2) . ' Mo';
        } else {
            $stats['taille_formatee'] = round($stats['taille_totale'] / 1024, 2) . ' Ko';
        }
        
        return $stats;
    }
}