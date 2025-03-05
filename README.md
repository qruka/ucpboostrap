# Système de Gestion d'Utilisateurs Sécurisé

Un système complet de gestion d'utilisateurs avec un panneau d'administration sécurisé, basé sur PHP et MySQL. Cette application fournit un cadre robuste pour l'authentification, les autorisations et la gestion des utilisateurs, avec une interface responsive et moderne construite avec Tailwind CSS.

## Fonctionnalités

### Système d'authentification
- Inscription des utilisateurs avec validation et vérification des données
- Connexion sécurisée avec protection contre les attaques par force brute
- Système "Se souvenir de moi" pour maintenir la session
- Réinitialisation de mot de passe par email
- Déconnexion sécurisée

### Gestion des utilisateurs
- Hiérarchie des utilisateurs à 4 niveaux (utilisateur, modérateur, administrateur, super administrateur)
- Gestion des profils utilisateurs avec avatars
- Modification du mot de passe avec vérification de sécurité
- Historique des connexions et activités

### Panneau d'administration
- Tableau de bord avec statistiques et graphiques
- Gestion complète des utilisateurs (création, modification, suppression)
- Suspension et bannissement d'utilisateurs
- Journal d'activités et de connexions
- Configuration des paramètres du site

### Sécurité
- Protection contre les attaques CSRF
- Protection contre les injections SQL avec requêtes préparées
- Protection XSS avec échappement des données
- Protection contre les attaques par force brute
- Mode maintenance pour contrôler l'accès au site
- Journalisation des erreurs et activités

### Expérience utilisateur
- Interface responsive avec Tailwind CSS
- Messages flash pour le feedback utilisateur
- Validation côté client et serveur
- Indicateur de force de mot de passe
- Pages d'erreur personnalisées

## Prérequis

- PHP 7.4 ou supérieur
- MySQL 5.7 ou supérieur
- Serveur web (Apache recommandé) avec module mod_rewrite activé
- Extension PHP PDO et MySQLi

## Installation

1. **Cloner le dépôt**
   ```bash
   git clone https://github.com/votre-utilisateur/user-management-system.git
   cd user-management-system
   ```

2. **Créer la base de données**
   - Importez le fichier `mon_site_updated.sql` dans votre serveur MySQL

3. **Configuration**
   - Dupliquez le fichier `.env.example` et renommez-le en `.env`
   - Modifiez les informations de connexion à la base de données dans `.env`
   ```
   DB_HOST=localhost
   DB_USER=votre_utilisateur
   DB_PASS=votre_mot_de_passe
   DB_NAME=mon_site
   
   APP_NAME="Mon Site Web"
   APP_URL=http://localhost
   APP_ENV=development
   
   APP_SECRET=une_chaine_aleatoire_de_32_caracteres
   ```

4. **Permissions**
   - Assurez-vous que les répertoires suivants sont accessibles en écriture:
     - `/logs`
     - `/uploads`

5. **Configuration du serveur web**
   - Assurez-vous que le module mod_rewrite est activé
   - Configurez votre virtualhost pour pointer vers le répertoire public du projet

## Structure du projet

```
/
├── admin/             # Interface d'administration
├── assets/            # Ressources statiques (CSS, JS, images)
├── includes/          # Fichiers d'inclusion PHP
├── logs/              # Journaux d'erreurs et d'activités
├── uploads/           # Fichiers téléchargés par les utilisateurs
├── .env               # Variables d'environnement
├── .htaccess          # Configuration Apache
├── error.php          # Page d'erreur personnalisée
├── index.php          # Page d'accueil
├── login.php          # Page de connexion
└── ... autres fichiers PHP
```

## Architecture et sécurité

### Architecture

Le système est construit avec une architecture MVC simplifiée:
- Les fichiers dans `/includes` fournissent les fonctionnalités principales
- Les fichiers à la racine constituent les "vues" ou pages
- L'accès aux données est centralisé via des fonctions dans `db.php` et `functions.php`

### Sécurité

- Toutes les requêtes SQL utilisent des requêtes préparées pour éviter les injections SQL
- Les données utilisateur sont systématiquement échappées avant affichage
- Les mots de passe sont hachés avec password_hash() et bcrypt
- Les sessions sont sécurisées avec httponly, secure et samesite
- Toutes les actions sensibles sont protégées par des tokens CSRF
- Les attaques par force brute sont bloquées après un nombre configurable de tentatives

## Utilisation

### Comptes par défaut

Le système est livré avec trois comptes par défaut:

1. **Super Admin**
   - Utilisateur: admin
   - Mot de passe: password123

2. **Modérateur**
   - Utilisateur: moderator
   - Mot de passe: password123

3. **Utilisateur standard**
   - Utilisateur: user
   - Mot de passe: password123

> **Important**: Changez ces mots de passe immédiatement après l'installation!

### Interface d'administration

Accédez à l'interface d'administration via `/admin/index.php`. Seuls les utilisateurs avec un niveau modérateur ou supérieur peuvent y accéder.

## Personnalisation

### Apparence
Les styles sont principalement gérés via Tailwind CSS. Vous pouvez personnaliser l'apparence en modifiant:
- `/assets/css/style.css` pour les styles généraux
- `/assets/css/dashboard.css` pour l'interface d'administration

### Fonctionnalités
Pour ajouter ou modifier des fonctionnalités:
1. Ajoutez les fonctions nécessaires dans `includes/functions.php`
2. Créez ou modifiez les pages correspondantes
3. Mettez à jour la structure de la base de données si nécessaire

## Mode maintenance

Pour activer le mode maintenance:
1. Connectez-vous en tant qu'administrateur
2. Accédez à Configuration dans le panneau d'administration
3. Activez l'option "Mode maintenance"

En mode maintenance, seuls les administrateurs peuvent accéder au site. Les autres utilisateurs verront une page de maintenance.

## Mise en production

Avant de déployer en production:

1. Définissez `APP_ENV=production` dans le fichier `.env`
2. Décommentez les règles HTTPS dans `.htaccess` pour forcer la connexion sécurisée
3. Générez une nouvelle clé APP_SECRET unique
4. Modifiez les mots de passe par défaut
5. Assurez-vous que les répertoires de logs et d'uploads ont les bonnes permissions

## Licence

Ce projet est distribué sous licence MIT. Voir le fichier LICENSE pour plus de détails.

## Support et contribution

Pour signaler un bug ou proposer une amélioration, veuillez ouvrir une issue sur le dépôt GitHub.

---

Créé avec ❤️ par [Votre Nom]