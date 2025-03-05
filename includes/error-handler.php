<?php
/**
 * Système de gestion des erreurs personnalisé
 * Ce fichier fournit un mécanisme centralisé pour capturer et journaliser les erreurs
 */

// Créer le répertoire des logs s'il n'existe pas
$logDir = __DIR__ . '/../logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// Définir le fichier de log
$errorLogFile = $logDir . '/error_log_' . date('Y-m-d') . '.log';

/**
 * Gestionnaire d'erreurs personnalisé
 * 
 * @param int $errno Niveau de l'erreur
 * @param string $errstr Message d'erreur
 * @param string $errfile Fichier dans lequel l'erreur s'est produite
 * @param int $errline Ligne à laquelle l'erreur s'est produite
 * @return bool
 */
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    global $errorLogFile;
    
    // Récupérer le nom de l'erreur
    $errorType = 'UNKNOWN';
    switch ($errno) {
        case E_ERROR:
            $errorType = 'E_ERROR';
            break;
        case E_WARNING:
            $errorType = 'E_WARNING';
            break;
        case E_PARSE:
            $errorType = 'E_PARSE';
            break;
        case E_NOTICE:
            $errorType = 'E_NOTICE';
            break;
        case E_CORE_ERROR:
            $errorType = 'E_CORE_ERROR';
            break;
        case E_CORE_WARNING:
            $errorType = 'E_CORE_WARNING';
            break;
        case E_COMPILE_ERROR:
            $errorType = 'E_COMPILE_ERROR';
            break;
        case E_COMPILE_WARNING:
            $errorType = 'E_COMPILE_WARNING';
            break;
        case E_USER_ERROR:
            $errorType = 'E_USER_ERROR';
            break;
        case E_USER_WARNING:
            $errorType = 'E_USER_WARNING';
            break;
        case E_USER_NOTICE:
            $errorType = 'E_USER_NOTICE';
            break;
        case E_STRICT:
            $errorType = 'E_STRICT';
            break;
        case E_RECOVERABLE_ERROR:
            $errorType = 'E_RECOVERABLE_ERROR';
            break;
        case E_DEPRECATED:
            $errorType = 'E_DEPRECATED';
            break;
        case E_USER_DEPRECATED:
            $errorType = 'E_USER_DEPRECATED';
            break;
    }
    
    // Récupérer la trace d'appel
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    $backtraceStr = '';
    foreach ($backtrace as $i => $trace) {
        if ($i === 0) continue; // Ignorer la première entrée (qui est cette fonction)
        $backtraceStr .= "#$i ";
        if (isset($trace['class'])) {
            $backtraceStr .= $trace['class'] . $trace['type'];
        }
        $backtraceStr .= $trace['function'] . '() called at ';
        $backtraceStr .= (isset($trace['file']) ? $trace['file'] : '<unknown file>');
        $backtraceStr .= ':' . (isset($trace['line']) ? $trace['line'] : '<unknown line>');
        $backtraceStr .= "\n";
    }
    
    // Construire le message d'erreur complet
    $logMessage = "[" . date('Y-m-d H:i:s') . "] ";
    $logMessage .= "$errorType: $errstr in $errfile on line $errline\n";
    $logMessage .= "URL: " . (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'CLI') . "\n";
    $logMessage .= "IP: " . (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'local') . "\n";
    
    // Ajouter les informations de session si disponibles
    if (isset($_SESSION['user_id'])) {
        $logMessage .= "User ID: " . $_SESSION['user_id'] . "\n";
    }
    
    // Ajouter la trace d'appel
    if (!empty($backtraceStr)) {
        $logMessage .= "Backtrace:\n$backtraceStr";
    }
    
    $logMessage .= "-------------------------------------------------------\n";
    
    // Enregistrer dans le fichier de log
    error_log($logMessage, 3, $errorLogFile);
    
    // Afficher l'erreur si en mode développement
    if (defined('APP_ENV') && APP_ENV === 'development') {
        echo "<div style='background-color: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0; border: 1px solid #f5c6cb; border-radius: 4px;'>";
        echo "<h3>$errorType: $errstr</h3>";
        echo "<p>Dans $errfile à la ligne $errline</p>";
        
        if (!empty($backtraceStr)) {
            echo "<h4>Trace d'appel :</h4>";
            echo "<pre>" . htmlspecialchars($backtraceStr) . "</pre>";
        }
        
        echo "</div>";
    }
    
    // Ne pas exécuter le gestionnaire d'erreurs PHP interne
    return true;
}

/**
 * Gestionnaire d'exceptions personnalisé
 * 
 * @param Throwable $exception L'exception capturée
 */
function customExceptionHandler($exception) {
    global $errorLogFile;
    
    // Récupérer les informations de l'exception
    $message = $exception->getMessage();
    $file = $exception->getFile();
    $line = $exception->getLine();
    $trace = $exception->getTraceAsString();
    $exceptionClass = get_class($exception);
    
    // Construire le message d'erreur complet
    $logMessage = "[" . date('Y-m-d H:i:s') . "] ";
    $logMessage .= "EXCEPTION $exceptionClass: $message in $file on line $line\n";
    $logMessage .= "URL: " . (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'CLI') . "\n";
    $logMessage .= "IP: " . (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'local') . "\n";
    
    // Ajouter les informations de session si disponibles
    if (isset($_SESSION['user_id'])) {
        $logMessage .= "User ID: " . $_SESSION['user_id'] . "\n";
    }
    
    // Ajouter la trace d'appel
    $logMessage .= "Trace:\n$trace\n";
    $logMessage .= "-------------------------------------------------------\n";
    
    // Enregistrer dans le fichier de log
    error_log($logMessage, 3, $errorLogFile);
    
    // Afficher un message d'erreur convivial
    if (defined('APP_ENV') && APP_ENV === 'development') {
        echo "<div style='background-color: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0; border: 1px solid #f5c6cb; border-radius: 4px;'>";
        echo "<h3>Exception $exceptionClass: $message</h3>";
        echo "<p>Dans $file à la ligne $line</p>";
        echo "<h4>Trace d'appel :</h4>";
        echo "<pre>" . htmlspecialchars($trace) . "</pre>";
        echo "</div>";
    } else {
        // En production, rediriger vers une page d'erreur générique
        if (!headers_sent()) {
            header('Location: ' . APP_URL . '/error.php');
            exit;
        } else {
            echo "<h1>Une erreur est survenue</h1>";
            echo "<p>Nous sommes désolés, une erreur inattendue s'est produite. Notre équipe technique a été notifiée.</p>";
            echo "<p><a href='" . APP_URL . "/index.php'>Retour à l'accueil</a></p>";
        }
    }
}

/**
 * Gestionnaire d'erreurs fatales
 */
function fatalErrorHandler() {
    $error = error_get_last();
    
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        // Formater l'erreur comme une exception
        $exception = new ErrorException(
            $error['message'],
            0,
            $error['type'],
            $error['file'],
            $error['line']
        );
        
        // Utiliser le gestionnaire d'exceptions personnalisé
        customExceptionHandler($exception);
    }
}

// Définir les gestionnaires personnalisés
set_error_handler('customErrorHandler');
set_exception_handler('customExceptionHandler');
register_shutdown_function('fatalErrorHandler');

/**
 * Fonction pour logguer une erreur manuellement
 * 
 * @param string $message Message d'erreur
 * @param string $level Niveau d'erreur (ERROR, WARNING, INFO, DEBUG)
 */
function logError($message, $level = 'ERROR') {
    global $errorLogFile;
    
    // Récupérer la trace d'appel
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
    $caller = isset($backtrace[1]) ? $backtrace[1] : $backtrace[0];
    
    $file = isset($caller['file']) ? $caller['file'] : '<unknown>';
    $line = isset($caller['line']) ? $caller['line'] : '<unknown>';
    
    // Construire le message d'erreur
    $logMessage = "[" . date('Y-m-d H:i:s') . "] [$level] ";
    $logMessage .= "$message (in $file on line $line)\n";
    $logMessage .= "URL: " . (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'CLI') . "\n";
    $logMessage .= "IP: " . (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'local') . "\n";
    
    // Ajouter les informations de session si disponibles
    if (isset($_SESSION['user_id'])) {
        $logMessage .= "User ID: " . $_SESSION['user_id'] . "\n";
    }
    
    $logMessage .= "-------------------------------------------------------\n";
    
    // Enregistrer dans le fichier de log
    error_log($logMessage, 3, $errorLogFile);
}