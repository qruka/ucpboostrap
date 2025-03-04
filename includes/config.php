<?php
// Informations de connexion à la base de données
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // À changer en production
define('DB_PASS', ''); // À changer en production
define('DB_NAME', 'mon_site');

// Paramètres de l'application
define('APP_NAME', 'Mon Site Web');
define('APP_URL', 'http://localhost'); // URL de base du site
define('APP_VERSION', '2.0');

// Paramètres de session
session_start();

// Fuseau horaire
date_default_timezone_set('Europe/Paris');

// Niveaux d'utilisateurs
define('USER_LEVEL_REGULAR', 1); // Utilisateur standard
define('USER_LEVEL_MODERATOR', 2); // Modérateur
define('USER_LEVEL_ADMIN', 3); // Administrateur
define('USER_LEVEL_SUPERADMIN', 4); // Super Administrateur

// Statuts utilisateur
define('USER_STATUS_ACTIVE', 'active'); // Compte actif
define('USER_STATUS_BANNED', 'banned'); // Compte banni
define('USER_STATUS_SUSPENDED', 'suspended'); // Compte suspendu temporairement
?>