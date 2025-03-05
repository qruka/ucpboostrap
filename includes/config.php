<?php
// Charger les variables d'environnement depuis .env
if (file_exists(__DIR__ . '/../.env')) {
    $env = parse_ini_file(__DIR__ . '/../.env');
    foreach ($env as $key => $value) {
        $_ENV[$key] = $value;
        putenv("$key=$value");
    }
}

// Fonction pour récupérer les variables d'environnement avec valeur par défaut
function env($key, $default = null) {
    return isset($_ENV[$key]) ? $_ENV[$key] : $default;
}

// Informations de connexion à la base de données
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));
define('DB_NAME', env('DB_NAME', 'mon_site'));

// Paramètres de l'application
define('APP_NAME', env('APP_NAME', 'DASHBOARD'));
define('APP_URL', env('APP_URL', 'http://localhost'));
define('APP_VERSION', env('APP_VERSION', '2.1'));
define('APP_ENV', env('APP_ENV', 'production'));
define('APP_SECRET', env('APP_SECRET', 'default_secret_change_this'));

// Niveaux d'utilisateurs
define('USER_LEVEL_REGULAR', 1); // Utilisateur standard
define('USER_LEVEL_MODERATOR', 2); // Modérateur
define('USER_LEVEL_ADMIN', 3); // Administrateur
define('USER_LEVEL_SUPERADMIN', 4); // Super Administrateur

// Statuts utilisateur
define('USER_STATUS_ACTIVE', 'active'); // Compte actif
define('USER_STATUS_BANNED', 'banned'); // Compte banni
define('USER_STATUS_SUSPENDED', 'suspended'); // Compte suspendu temporairement

// Paramètres de session sécurisés
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', APP_ENV === 'production' ? 1 : 0);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.gc_maxlifetime', 3600); // 1 heure
ini_set('session.use_strict_mode', 1);

// Démarrer la session
session_start();

// Régénérer l'ID de session à chaque démarrage de session pour éviter la fixation de session
if (!isset($_SESSION['last_regeneration']) || (time() - $_SESSION['last_regeneration']) > 1800) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

// Fuseau horaire
date_default_timezone_set('Europe/Paris');