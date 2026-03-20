<?php



return [
    
    
    'active' => true,
    
    
    'pilote' => 'fichier',
    
    
    'duree_vie_defaut' => 3600, 
    
    
    'chemin_cache' => __DIR__ . '/../stockage/cache',
    
    
    'prefixe' => 'fablab_',
    
    
    'nettoyer_expire' => true,
    
    
    'probabilite_nettoyage' => 5,
    
    
    'durees_vie' => [
        
        
        'page_accueil' => 1800,        
        'liste_articles' => 900,       
        'liste_projets' => 900,        
        'liste_videos' => 600,         
        'liste_actualites' => 900,     
        'actualites_sources' => 1800,  
        
        
        'article' => 3600,             
        'projet' => 3600,              
        'video' => 3600,               
        'actualite' => 3600,           
        'profil_utilisateur' => 1800,  
        
        
        'compteur_articles' => 300,    
        'compteur_projets' => 300,     
        'statistiques' => 600,         
        
        
        'recherche' => 1800,           
        'commentaires' => 600,         
        
        
        'dashboard_stats' => 300,      
        'liste_utilisateurs' => 600,   
        
    ],
    
    
    'cache_pages' => [
        'accueil' => true,
        'articles' => true,
        'projets' => true,
        'webtv' => true,
        'actualites' => true,
        'contact' => false,  
        'profil' => true,
        'admin' => false,    
    ],
    
];
