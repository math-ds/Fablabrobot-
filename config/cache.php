<?php

/**
 * Configuration du Système de Cache
 * 
 * Modifiez ce fichier pour ajuster les paramètres du cache selon vos besoins
 */

return [
    
    /*
    |--------------------------------------------------------------------------
    | Activation du Cache
    |--------------------------------------------------------------------------
    |
    | Active ou désactive complètement le système de cache.
    | En développement, vous pouvez mettre à false pour désactiver le cache.
    |
    */
    'active' => true,
    
    /*
    |--------------------------------------------------------------------------
    | Pilote de Cache
    |--------------------------------------------------------------------------
    |
    | Détermine quel système de cache utiliser :
    | - 'fichier' : Stockage sur disque (fonctionne partout, pas de dépendance)
    | - 'memoire' : APCu en mémoire (très rapide, nécessite extension APCu)
    |
    | Recommandé : 'fichier' pour débuter, 'memoire' pour la production
    |
    */
    'pilote' => 'fichier',
    
    /*
    |--------------------------------------------------------------------------
    | Durée de Vie par Défaut
    |--------------------------------------------------------------------------
    |
    | Durée de vie par défaut des entrées en cache (en secondes)
    | 
    | Exemples :
    | - 60 : 1 minute
    | - 300 : 5 minutes
    | - 3600 : 1 heure
    | - 86400 : 24 heures
    |
    */
    'duree_vie_defaut' => 3600, // 1 heure
    
    /*
    |--------------------------------------------------------------------------
    | Chemin du Cache (pour pilote 'fichier')
    |--------------------------------------------------------------------------
    |
    | Dossier où seront stockés les fichiers de cache.
    | Assurez-vous que PHP a les permissions d'écriture sur ce dossier.
    |
    */
    'chemin_cache' => __DIR__ . '/../stockage/cache',
    
    /*
    |--------------------------------------------------------------------------
    | Préfixe des Clés
    |--------------------------------------------------------------------------
    |
    | Préfixe ajouté à toutes les clés de cache.
    | Utile si vous partagez le système de cache avec d'autres applications.
    |
    */
    'prefixe' => 'fablab_',
    
    /*
    |--------------------------------------------------------------------------
    | Nettoyage Automatique
    |--------------------------------------------------------------------------
    |
    | Si true, nettoie automatiquement les entrées expirées de temps en temps.
    | Recommandé : true
    |
    */
    'nettoyer_expire' => true,
    
    /*
    |--------------------------------------------------------------------------
    | Probabilité de Nettoyage
    |--------------------------------------------------------------------------
    |
    | Probabilité (en %) qu'un nettoyage automatique soit déclenché.
    | Valeur entre 0 et 100.
    | 
    | Exemples :
    | - 1 : 1 chance sur 100 (1%)
    | - 5 : 5 chances sur 100 (5%)
    | - 10 : 10 chances sur 100 (10%)
    |
    */
    'probabilite_nettoyage' => 5,
    
    /*
    |--------------------------------------------------------------------------
    | Durées de Vie Personnalisées
    |--------------------------------------------------------------------------
    |
    | Vous pouvez définir des durées de vie spécifiques pour différents types
    | de contenu. Ces valeurs seront utilisées par défaut pour chaque type.
    |
    */
    'durees_vie' => [
        
        // Pages et vues
        'page_accueil' => 1800,        // 30 minutes
        'liste_articles' => 900,       // 15 minutes
        'liste_projets' => 900,        // 15 minutes
        'liste_videos' => 600,         // 10 minutes
        
        // Détails individuels
        'article' => 3600,             // 1 heure
        'projet' => 3600,              // 1 heure
        'video' => 3600,               // 1 heure
        'profil_utilisateur' => 1800,  // 30 minutes
        
        // Données utilisateur
        'compteur_articles' => 300,    // 5 minutes
        'compteur_projets' => 300,     // 5 minutes
        'statistiques' => 600,         // 10 minutes
        
        // Requêtes lourdes
        'recherche' => 1800,           // 30 minutes
        'commentaires' => 600,         // 10 minutes
        
        // Administration
        'dashboard_stats' => 300,      // 5 minutes
        'liste_utilisateurs' => 600,   // 10 minutes
        
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Pages à Mettre en Cache
    |--------------------------------------------------------------------------
    |
    | Liste des pages qui doivent automatiquement utiliser le cache.
    | true = activer le cache pour cette page
    | false = désactiver le cache pour cette page
    |
    */
    'cache_pages' => [
        'accueil' => true,
        'articles' => true,
        'projets' => true,
        'webtv' => true,
        'contact' => false,  // Pas de cache pour les formulaires
        'profil' => true,
        'admin' => false,    // Généralement pas de cache en admin
    ],
    
];