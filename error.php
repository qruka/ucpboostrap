<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Récupérer le code d'erreur, par défaut 500
$errorCode = isset($_GET['code']) ? (int)$_GET['code'] : 500;
$errorTitle = 'Erreur serveur';
$errorMessage = 'Une erreur inattendue s\'est produite sur le serveur.';

// Messages d'erreur personnalisés selon le code
switch ($errorCode) {
    case 400:
        $errorTitle = 'Requête incorrecte';
        $errorMessage = 'La requête envoyée au serveur est incorrecte ou mal formatée.';
        break;
    case 401:
        $errorTitle = 'Non autorisé';
        $errorMessage = 'Vous devez être authentifié pour accéder à cette ressource.';
        break;
    case 403:
        $errorTitle = 'Accès interdit';
        $errorMessage = 'Vous n\'avez pas les permissions nécessaires pour accéder à cette ressource.';
        break;
    case 404:
        $errorTitle = 'Page non trouvée';
        $errorMessage = 'La page que vous recherchez n\'existe pas ou a été déplacée.';
        break;
    case 410:
        $errorTitle = 'Contenu supprimé';
        $errorMessage = 'Le contenu que vous recherchez a été définitivement supprimé.';
        break;
    case 500:
        $errorTitle = 'Erreur serveur';
        $errorMessage = 'Une erreur interne est survenue sur le serveur. Nos équipes techniques ont été notifiées.';
        break;
    case 503:
        $errorTitle = 'Service indisponible';
        $errorMessage = 'Le service est temporairement indisponible. Veuillez réessayer plus tard.';
        break;
}

// Ajouter cette erreur au log
if (function_exists('logError')) {
    logError("Page d'erreur affichée: $errorCode - $errorTitle", 'INFO');
}

// URL de redirection
$returnUrl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : APP_URL;
if (strpos($returnUrl, 'error.php') !== false) {
    $returnUrl = APP_URL;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $errorCode ?> - <?= $errorTitle ?> | <?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background-color: #f3f4f6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .error-container {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 2rem;
        }
        .error-code {
            font-size: 8rem;
            font-weight: 700;
            color: #ddd;
            text-shadow: 4px 4px 0 #3b82f6;
            margin-bottom: 1rem;
            line-height: 1;
        }
        .error-icon {
            font-size: 4rem;
            color: #3b82f6;
            margin-bottom: 1.5rem;
            animation: pulse 2s ease-in-out infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="bg-white rounded-xl shadow-xl p-8 max-w-lg w-full text-center">
            <div class="error-code"><?= $errorCode ?></div>
            
            <?php
            // Icône selon le type d'erreur
            $iconClass = 'fas fa-exclamation-circle';
            
            switch ($errorCode) {
                case 404:
                    $iconClass = 'fas fa-search';
                    break;
                case 401:
                case 403:
                    $iconClass = 'fas fa-lock';
                    break;
                case 500:
                    $iconClass = 'fas fa-bug';
                    break;
                case 503:
                    $iconClass = 'fas fa-tools';
                    break;
            }
            ?>
            
            <div class="error-icon">
                <i class="<?= $iconClass ?>"></i>
            </div>
            
            <h1 class="text-2xl font-bold text-gray-800 mb-4"><?= escapeString($errorTitle) ?></h1>
            <p class="text-gray-600 mb-8"><?= escapeString($errorMessage) ?></p>
            
            <div class="flex flex-wrap justify-center gap-4">
                <a href="<?= APP_URL ?>" class="px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-home mr-2"></i> Accueil
                </a>
                <a href="<?= $returnUrl ?>" class="px-6 py-3 bg-gray-200 text-gray-700 font-medium rounded-lg hover:bg-gray-300 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i> Retour
                </a>
            </div>
        </div>
    </div>
    
    <footer class="py-4 text-center text-gray-500 text-sm">
        <p>&copy; <?= date('Y') ?> - <?= APP_NAME ?></p>
    </footer>
</body>
</html>