# Fablabrobot - Site Web AJC FABLAB

## À propos du projet

Fablabrobot est une plateforme web complète développée pour le FabLab AJC. Le site permet de présenter les activités du FabLab, de partager des projets en robotique et technologies, de publier des articles techniques et des vidéos éducatives, tout en offrant un espace d'interaction pour la communauté.

J'ai développé ce projet en PHP natif avec une architecture MVC personnalisée, ce qui m'a permis de mettre en pratique mes compétences en développement web full-stack et de comprendre en profondeur le fonctionnement d'une application web moderne.

## Objectifs

- Créer une vitrine attractive pour le FabLab AJC
- Permettre aux membres de partager leurs projets techniques et robotiques
- Publier des articles et tutoriels pour la communauté
- Diffuser des contenus vidéo éducatifs via une WebTV
- Gérer une communauté d'utilisateurs avec système d'authentification
- Fournir un back-office complet pour l'administration du site

## Architecture technique

### Structure MVC

Le projet utilise une architecture MVC (Modèle-Vue-Contrôleur), sans framework. Voici comment le projet est organisé :

```
Fablabrobot/
│
├── app/
│   ├── config/              # Configuration de la base de données
│   ├── contrôleurs/         # Logique métier et gestion des requêtes
│   ├── modèles/             # Interaction avec la base de données
│   └── vues/                # Templates et interface utilisateur
│       ├── accueil/         # Page d'accueil
│       ├── admin/           # Interface d'administration
│       ├── articles/        # Gestion des articles
│       ├── contact/         # Formulaire de contact
│       ├── parties/         # Composants réutilisables (header, footer)
│       ├── profil/          # Profil utilisateur
│       ├── projets/         # Galerie de projets
│       ├── utilisateurs/    # Authentification
│       └── webtv/           # Plateforme vidéo
│
├── config/                  # Configuration globale
│
└── public/                  # Dossier accessible publiquement
    ├── css/                 # Styles CSS
    ├── images/              # Images et médias
    ├── uploads/             # Fichiers uploadés
    └── index.php            # Point d'entrée (routeur)
```

### Fonctionnement du routeur

J'ai créé un système de routing personnalisé dans `public/index.php` qui fonctionne avec le paramètre GET `page`. Par exemple :

- `?page=accueil` charge le contrôleur AccueilControleur
- `?page=projets` charge le contrôleur ProjetsControleur
- `?page=admin` charge le dashboard administrateur (si connecté en tant qu'admin)

## Fonctionnalités principales

### Partie utilisateur (front-end)

**Page d'accueil**

- Carousel dynamique avec Bootstrap pour présenter les activités
- Cartes de présentation des différentes sections du site
- Design entièrement responsive (mobile, tablette, desktop)
- Animations CSS pour un rendu moderne

**Gestion des projets**

- Galerie complète des projets du FabLab
- Pages de détail avec descriptions, images et documentation
- Possibilité pour les utilisateurs connectés de créer leurs propres projets
- Upload d'images et gestion des médias

**Articles**

- Système de publication d'articles techniques
- Gestion d'images dans les articles
- Interface de création intuitive

**WebTV**

- Plateforme de diffusion de vidéos éducatives
- Intégration de vidéos YouTube/Vimeo
- Système de commentaires sur les vidéos
- Organisation par catégories

**Page contact**

- Formulaire de contact avec validation
- Envoi de messages stockés en base de données
- Validation côté client (JavaScript) et serveur (PHP)

**Profil utilisateur**

- Gestion des informations personnelles
- Upload de photo de profil
- Modification du mot de passe
- Historique des contributions

### Partie administrateur (back-end)

**Dashboard administrateur**

- Vue d'ensemble avec statistiques
- Accès rapide à toutes les fonctionnalités d'administration

**Gestion complète du contenu**

- **Articles** : Créer, modifier, supprimer des articles
- **Projets** : Gérer tous les projets publiés
- **Vidéos** : Administrer la WebTV
- **Utilisateurs** : Gérer les comptes et les rôles (user/admin)
- **Commentaires** : Modérer les commentaires
- **Messages** : Consulter les messages du formulaire de contact

**Interface CRUD**
Toutes les sections admin disposent d'une interface complète pour :

- Créer de nouveaux contenus
- Lire et consulter les données
- Mettre à jour les informations
- Supprimer des éléments

### Système d'authentification

- Inscription avec validation des données
- Connexion / déconnexion avec gestion de sessions
- Récupération de mot de passe (fonctionnalité de base)
- Hashage sécurisé des mots de passe avec `password_hash()`
- Contrôle d'accès basé sur les rôles (user/admin)

## Technologies utilisées

### Backend

- **PHP 7.4+** : Langage principal du projet
- **MySQL** : Base de données relationnelle
- **PDO** : Pour les requêtes préparées et la sécurité
- **Sessions PHP** : Gestion de l'authentification

### Frontend

- **HTML5** : Structure sémantique du site
- **CSS3** : Styles personnalisés
- **Bootstrap 5.3.3** : Framework CSS pour le responsive
- **JavaScript** : Interactivité et validations
- **Font Awesome 6.4.2** : Icônes

### Concepts appliqués

- **Architecture MVC** : Séparation des responsabilités
- **Routing personnalisé** : Gestion centralisée des URLs
- **Sécurité** : Protection contre les injections SQL, XSS, etc.

## Installation et configuration

### Prérequis

Pour faire fonctionner le projet, vous aurez besoin de :

- Un serveur web (Apache recommandé)
- PHP 7.4 ou version supérieure
- MySQL 5.7 ou supérieur
- L'extension PDO de PHP activée

### Étapes d'installation

#### 1. Récupération du projet

```bash
git clone [url-du-repo]
cd Fablabrobot
```

#### 2. Configuration de la base de données

La base de données est fournie dans le fichier :

```
fablab.sql
```

Créer une base vide :

```sql
CREATE DATABASE fablab CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Importer ensuite le fichier `fablab.sql` via phpMyAdmin.

Ce fichier contient :

- la structure des tables
- des données de test (utilisateurs, projets, articles, vidéos)

#### 3. Configuration des paramètres

Modifiez le fichier `config/database.php` avec les identifiants MySQL :

```php
private $host = '127.0.0.1:3306';
private $db_name = 'fablab';
private $username = 'root';
private $password = '';
```

Ajustez également le chemin de base dans `public/index.php` selon votre environnement :

```php
$GLOBALS['baseUrl'] = '/Fablabrobot/public/';
```

#### 4. Permissions des fichiers

Assurez-vous que le dossier uploads est accessible en écriture :

```bash
chmod -R 755 public/uploads/
```

#### 5. Lancement du serveur

**Option 1 : Avec XAMPP/WAMP/MAMP**

- Placez le dossier `Fablabrobot` dans `htdocs/` (XAMPP) ou `www/` (WAMP)
- Démarrez Apache et MySQL
- Accédez à `http://localhost/Fablabrobot/public/`

**Option 2 : Avec le serveur PHP intégré**

```bash
cd public
php -S localhost:8000
```

Puis accédez à `http://localhost:8000`

## Utilisation

### Pour les utilisateurs

1. Créez un compte depuis la page d'inscription
2. Connectez-vous avec vos identifiants
3. Vous pouvez maintenant :
   - Créer et partager vos projets
   - Rédiger des articles
   - Commenter les vidéos
   - Modifier votre profil et votre photo

### Pour les administrateurs

1. Connectez-vous avec un compte ayant le rôle "admin"
2. Accédez au dashboard admin via `?page=admin`
3. Gérez tout le contenu du site :
   - Valider ou supprimer des projets
   - Modérer les commentaires
   - Gérer les utilisateurs
   - Consulter les messages de contact

### Navigation

Le site utilise un système d'URLs simple avec le paramètre `page` :

- `?page=accueil` - Page d'accueil
- `?page=projets` - Liste des projets
- `?page=projet&id=X` - Détail d'un projet
- `?page=articles` - Liste des articles
- `?page=article-detail&id=X` - Détail d'un article
- `?page=webtv` - Plateforme vidéo
- `?page=contact` - Formulaire de contact
- `?page=profil` - Profil utilisateur
- `?page=admin` - Dashboard administrateur

## Aspects de sécurité

J'ai porté une attention particulière à la sécurité du site en implémentant plusieurs mesures :

**Protection contre les injections SQL**

- Utilisation exclusive de requêtes préparées avec PDO
- Tous les paramètres sont bindés proprement
- Aucune concaténation directe de variables dans les requêtes

**Gestion sécurisée des mots de passe**

- Hashage avec `password_hash()` utilisant bcrypt
- Vérification avec `password_verify()`
- Les mots de passe ne sont jamais stockés en clair

**Protection contre les failles XSS**

- Utilisation de `htmlspecialchars()` pour l'affichage des données utilisateur
- Échappement de toutes les sorties non fiables

**Validation des données**

- Validation côté serveur pour tous les formulaires
- Vérification des types de fichiers pour les uploads
- Contrôle des tailles de fichiers

**Gestion des sessions**

- Sessions PHP pour l'authentification
- Vérification du rôle pour l'accès aux pages admin
- Régénération de l'ID de session lors de la connexion

**Points d'amélioration identifiés**

- Ajouter une protection CSRF avec des tokens
- Implémenter un système de limitation des tentatives de connexion
- Ajouter des logs de sécurité
- Mettre en place une validation CAPTCHA pour les formulaires publics

## Design et expérience utilisateur

**Responsive Design**
Le site est entièrement responsive grâce à Bootstrap 5. Il s'adapte automatiquement aux différentes tailles d'écran (mobile, tablette, desktop).

**Accessibilité**

- Structure HTML5 sémantique
- Navigation au clavier possible
- Contrastes de couleurs respectés
- Formulaires avec labels appropriés

**Performance**

- Images optimisées
- CSS minifié pour la production
- Chargement asynchrone de certaines ressources

**Cohérence visuelle**

- Charte graphique uniforme sur tout le site
- Utilisation cohérente de Bootstrap
- Navigation intuitive avec menu clair

## Tests effectués

J'ai testé le site sur plusieurs aspects :

**Fonctionnalités**

- Inscription et connexion d'utilisateurs
- Création, modification et suppression de projets
- Publication d'articles
- Upload de fichiers (images)
- Système de commentaires
- Formulaire de contact

**Sécurité**

- Tentatives d'injection SQL (toutes bloquées grâce à PDO)
- Test d'upload de fichiers dangereux (validation OK)
- Accès aux pages admin sans autorisation (redirection OK)
- Validation des formulaires

**Compatibilité**

- Test sur Chrome, Firefox, Safari
- Test sur mobile (iOS et Android)
- Test sur tablette

## Crédits et licence

**Développement**
Projet développé dans le cadre de ma formation en développement web.

**Technologies tierces**

- Bootstrap (Framework CSS) - Licence MIT
- Font Awesome (Icônes) - Licence Font Awesome Free
- PHP - Licence PHP v3.01
- MySQL - Licence GPL

**Ressources**

- Les images et logos appartiennent à AJC FABLAB
- Documentation consultée : PHP.net, MDN Web Docs, Stack Overflow

---

**Projet réalisé entre 2025-2026**
