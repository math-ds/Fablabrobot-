<?php


require_once __DIR__ . '/CacheFichier.php';
require_once __DIR__ . '/CacheMemoire.php';


class GestionnaireCache
{
    private static $instance = null;
    private $pilote;
    private $active = true;
    private $configuration = [];
    
    
    public static function obtenirInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    
    private function __construct()
    {
        $this->chargerConfiguration();
        $this->initialiserPilote();
    }
    
    
    private function chargerConfiguration()
    {
        $fichierConfig = __DIR__ . '/../../config/cache.php';

        if (file_exists($fichierConfig)) {
            $this->configuration = require $fichierConfig;
        } else {
            $this->configuration = [
                'active' => true,
                'pilote' => 'fichier',
                'duree_vie_defaut' => 3600, 
                'chemin_cache' => __DIR__ . '/../../storage/cache',
                'prefixe' => 'fablab_',
                'nettoyer_expire' => true
            ];
        }
        
        $fichierEtat = $this->obtenirCheminFichierEtat();
        if (file_exists($fichierEtat)) {
            $etat = file_get_contents($fichierEtat);
            $this->active = ($etat === '1');
        } else {
            $this->active = $this->configuration['active'] ?? true;
        }
    }

    
    private function obtenirCheminFichierEtat()
    {
        $cheminCache = $this->configuration['chemin_cache'] ?? __DIR__ . '/../../stockage/cache';
        return $cheminCache . '/.cache_active';
    }

    
    private function sauvegarderEtat()
    {
        $fichierEtat = $this->obtenirCheminFichierEtat();
        $dossier = dirname($fichierEtat);

        
        if (!file_exists($dossier)) {
            mkdir($dossier, 0755, true);
        }

        
        file_put_contents($fichierEtat, $this->active ? '1' : '0');
    }
    
    
    private function initialiserPilote()
    {
        $typePilote = $this->configuration['pilote'] ?? 'fichier';
        
        switch ($typePilote) {
            case 'fichier':
                $this->pilote = new CacheFichier($this->configuration);
                break;
            
            case 'memoire':
                
                if (extension_loaded('apcu') && ini_get('apc.enabled')) {
                    $this->pilote = new CacheMemoire($this->configuration);
                } else {
                    
                    $this->pilote = new CacheFichier($this->configuration);
                }
                break;
            
            default:
                $this->pilote = new CacheFichier($this->configuration);
        }
    }
    
    
    public function stocker($cle, $valeur, $dureeVie = null)
    {
        if (!$this->active) {
            return false;
        }
        
        $dureeVie = $dureeVie ?? $this->configuration['duree_vie_defaut'];
        $cleComplete = $this->configuration['prefixe'] . $cle;
        
        return $this->pilote->stocker($cleComplete, $valeur, $dureeVie);
    }
    
    
    public function recuperer($cle, $defaut = null)
    {
        if (!$this->active) {
            return $defaut;
        }
        
        $cleComplete = $this->configuration['prefixe'] . $cle;
        $valeur = $this->pilote->recuperer($cleComplete);
        
        return $valeur !== false ? $valeur : $defaut;
    }
    
    
    public function existe($cle)
    {
        if (!$this->active) {
            return false;
        }
        
        $cleComplete = $this->configuration['prefixe'] . $cle;
        return $this->pilote->existe($cleComplete);
    }
    
    
    public function supprimer($cle)
    {
        $cleComplete = $this->configuration['prefixe'] . $cle;
        return $this->pilote->supprimer($cleComplete);
    }
    
    
    public function vider()
    {
        return $this->pilote->vider();
    }
    
    
    public function nettoyerExpire()
    {
        return $this->pilote->nettoyerExpire();
    }
    
    
    public function memoriser($cle, callable $callback, $dureeVie = null)
    {
        
        $valeur = $this->recuperer($cle);
        
        if ($valeur !== null) {
            return $valeur;
        }
        
        
        $valeur = $callback();
        
        
        if ($valeur !== null) {
            $this->stocker($cle, $valeur, $dureeVie);
        }
        
        return $valeur;
    }
    
    
    public function supprimerParPrefixe($prefixe)
    {
        $cleComplete = $this->configuration['prefixe'] . $prefixe;
        return $this->pilote->supprimerParPrefixe($cleComplete);
    }
    
    
    public function activer()
    {
        $this->active = true;
        $this->sauvegarderEtat();
    }

    
    public function desactiver()
    {
        $this->active = false;
        $this->sauvegarderEtat();
    }
    
    
    public function estActif()
    {
        return $this->active;
    }
    
    
    public function obtenirStatistiques()
    {
        if (!$this->active) {
            return [
                'active' => false,
                'message' => 'Le cache est désactivé'
            ];
        }
        
        return $this->pilote->obtenirStatistiques();
    }
}
