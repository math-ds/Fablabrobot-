<?php

// Charger les pilotes de cache
require_once __DIR__ . '/CacheFichier.php';
require_once __DIR__ . '/CacheMemoire.php';

/**
 * Gestionnaire de Cache
 * Système centralisé de gestion du cache pour améliorer les performances
 */
class GestionnaireCache {
    
    private static $instance = null;
    private $pilote;
    private $active = true;
    private $configuration = [];
    
    /**
     * Pattern Singleton - Instance unique du gestionnaire
     */
    public static function obtenirInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructeur privé (Singleton)
     */
    private function __construct() {
        $this->chargerConfiguration();
        $this->initialiserPilote();
    }
    
    /**
     * Charge la configuration du cache depuis le fichier de config
     */
    private function chargerConfiguration() {
        $fichierConfig = __DIR__ . '/../../config/cache.php';

        if (file_exists($fichierConfig)) {
            $this->configuration = require $fichierConfig;
        } else {
            // Configuration par défaut
            $this->configuration = [
                'active' => true,
                'pilote' => 'fichier',
                'duree_vie_defaut' => 3600, // 1 heure
                'chemin_cache' => __DIR__ . '/../../storage/cache',
                'prefixe' => 'fablab_',
                'nettoyer_expire' => true
            ];
        }

        // Lire l'état depuis le fichier d'état si il existe
        $fichierEtat = $this->obtenirCheminFichierEtat();
        if (file_exists($fichierEtat)) {
            $etat = file_get_contents($fichierEtat);
            $this->active = ($etat === '1');
        } else {
            $this->active = $this->configuration['active'] ?? true;
        }
    }

    /**
     * Obtient le chemin du fichier d'état du cache
     */
    private function obtenirCheminFichierEtat() {
        $cheminCache = $this->configuration['chemin_cache'] ?? __DIR__ . '/../../stockage/cache';
        return $cheminCache . '/.cache_active';
    }

    /**
     * Sauvegarde l'état actif/inactif dans un fichier
     */
    private function sauvegarderEtat() {
        $fichierEtat = $this->obtenirCheminFichierEtat();
        $dossier = dirname($fichierEtat);

        // Créer le dossier si nécessaire
        if (!file_exists($dossier)) {
            mkdir($dossier, 0755, true);
        }

        // Écrire l'état (1 = actif, 0 = inactif)
        file_put_contents($fichierEtat, $this->active ? '1' : '0');
    }
    
    /**
     * Initialise le pilote de cache approprié
     */
    private function initialiserPilote() {
        $typePilote = $this->configuration['pilote'] ?? 'fichier';
        
        switch ($typePilote) {
            case 'fichier':
                $this->pilote = new CacheFichier($this->configuration);
                break;
            
            case 'memoire':
                // Support pour APCu si disponible
                if (extension_loaded('apcu') && ini_get('apc.enabled')) {
                    $this->pilote = new CacheMemoire($this->configuration);
                } else {
                    // Fallback sur fichier
                    $this->pilote = new CacheFichier($this->configuration);
                }
                break;
            
            default:
                $this->pilote = new CacheFichier($this->configuration);
        }
    }
    
    /**
     * Stocke une valeur dans le cache
     * 
     * @param string $cle Clé unique du cache
     * @param mixed $valeur Valeur à mettre en cache
     * @param int|null $dureeVie Durée de vie en secondes (null = durée par défaut)
     * @return bool Succès de l'opération
     */
    public function stocker($cle, $valeur, $dureeVie = null) {
        if (!$this->active) {
            return false;
        }
        
        $dureeVie = $dureeVie ?? $this->configuration['duree_vie_defaut'];
        $cleComplete = $this->configuration['prefixe'] . $cle;
        
        return $this->pilote->stocker($cleComplete, $valeur, $dureeVie);
    }
    
    /**
     * Récupère une valeur du cache
     * 
     * @param string $cle Clé du cache
     * @param mixed $defaut Valeur par défaut si le cache n'existe pas
     * @return mixed Valeur en cache ou valeur par défaut
     */
    public function recuperer($cle, $defaut = null) {
        if (!$this->active) {
            return $defaut;
        }
        
        $cleComplete = $this->configuration['prefixe'] . $cle;
        $valeur = $this->pilote->recuperer($cleComplete);
        
        return $valeur !== false ? $valeur : $defaut;
    }
    
    /**
     * Vérifie si une clé existe dans le cache et n'est pas expirée
     * 
     * @param string $cle Clé à vérifier
     * @return bool True si existe et valide
     */
    public function existe($cle) {
        if (!$this->active) {
            return false;
        }
        
        $cleComplete = $this->configuration['prefixe'] . $cle;
        return $this->pilote->existe($cleComplete);
    }
    
    /**
     * Supprime une entrée du cache
     * 
     * @param string $cle Clé à supprimer
     * @return bool Succès de la suppression
     */
    public function supprimer($cle) {
        if (!$this->active) {
            return false;
        }
        
        $cleComplete = $this->configuration['prefixe'] . $cle;
        return $this->pilote->supprimer($cleComplete);
    }
    
    /**
     * Vide complètement le cache
     * 
     * @return bool Succès de l'opération
     */
    public function vider() {
        if (!$this->active) {
            return false;
        }
        
        return $this->pilote->vider();
    }
    
    /**
     * Nettoie les entrées expirées du cache
     * 
     * @return bool Succès du nettoyage
     */
    public function nettoyerExpire() {
        if (!$this->active) {
            return false;
        }
        
        return $this->pilote->nettoyerExpire();
    }
    
    /**
     * Récupère ou génère une valeur en cache
     * Pattern "cache-aside" : si le cache n'existe pas, exécute le callback et met en cache
     * 
     * @param string $cle Clé du cache
     * @param callable $callback Fonction à exécuter si pas en cache
     * @param int|null $dureeVie Durée de vie en secondes
     * @return mixed Valeur du cache ou résultat du callback
     */
    public function memoriser($cle, callable $callback, $dureeVie = null) {
        // Vérifier si déjà en cache
        $valeur = $this->recuperer($cle);
        
        if ($valeur !== null) {
            return $valeur;
        }
        
        // Pas en cache : exécuter le callback
        $valeur = $callback();
        
        // Mettre en cache pour la prochaine fois
        if ($valeur !== null) {
            $this->stocker($cle, $valeur, $dureeVie);
        }
        
        return $valeur;
    }
    
    /**
     * Supprime toutes les entrées correspondant à un préfixe
     * Utile pour invalider un groupe de caches (ex: tous les articles)
     * 
     * @param string $prefixe Préfixe des clés à supprimer
     * @return int Nombre d'entrées supprimées
     */
    public function supprimerParPrefixe($prefixe) {
        if (!$this->active) {
            return 0;
        }
        
        $cleComplete = $this->configuration['prefixe'] . $prefixe;
        return $this->pilote->supprimerParPrefixe($cleComplete);
    }
    
    /**
     * Active le cache
     */
    public function activer() {
        $this->active = true;
        $this->sauvegarderEtat();
    }

    /**
     * Désactive le cache
     */
    public function desactiver() {
        $this->active = false;
        $this->sauvegarderEtat();
    }
    
    /**
     * Vérifie si le cache est actif
     * 
     * @return bool
     */
    public function estActif() {
        return $this->active;
    }
    
    /**
     * Obtient des statistiques sur le cache
     * 
     * @return array Statistiques du cache
     */
    public function obtenirStatistiques() {
        if (!$this->active) {
            return [
                'active' => false,
                'message' => 'Le cache est désactivé'
            ];
        }
        
        return $this->pilote->obtenirStatistiques();
    }
}