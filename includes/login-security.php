<?php
/**
 * Fonctions de sécurité pour la protection contre les attaques par force brute
 */

/**
 * Vérifie si l'adresse IP est bloquée en raison de trop nombreuses tentatives de connexion
 * 
 * @param string $ipAddress L'adresse IP à vérifier
 * @return bool|array False si l'IP n'est pas bloquée, sinon un tableau avec les détails du blocage
 */
function isIPBlocked($ipAddress) {
    // Récupérer les paramètres de sécurité
    $maxAttempts = getSetting('max_login_attempts', 5);
    $blockDuration = getSetting('login_lockout_duration', 30); // en minutes
    
    // Vérifier les tentatives récentes
    $sql = "SELECT COUNT(*) as attempts, MAX(login_time) as last_attempt 
            FROM user_logins 
            WHERE ip_address = ? 
            AND status = 'failed' 
            AND login_time > DATE_SUB(NOW(), INTERVAL ? MINUTE)";
    
    $result = executeQuery($sql, [$ipAddress, $blockDuration]);
    
    if ($result && $row = $result->fetch_assoc()) {
        $attempts = (int)$row['attempts'];
        $lastAttempt = $row['last_attempt'];
        
        if ($attempts >= $maxAttempts) {
            // Calculer le temps restant avant déblocage
            $blockExpiry = strtotime($lastAttempt) + ($blockDuration * 60);
            $timeRemaining = max(0, $blockExpiry - time());
            $minutesRemaining = ceil($timeRemaining / 60);
            
            return [
                'blocked' => true,
                'attempts' => $attempts,
                'minutes_remaining' => $minutesRemaining,
                'seconds_remaining' => $timeRemaining,
                'block_expiry' => date('Y-m-d H:i:s', $blockExpiry)
            ];
        }
    }
    
    return false;
}

/**
 * Récupère le nombre de tentatives de connexion récentes pour une adresse IP
 * 
 * @param string $ipAddress L'adresse IP à vérifier
 * @return int Le nombre de tentatives récentes
 */
function getRecentLoginAttempts($ipAddress) {
    // Récupérer la durée du blocage en minutes
    $blockDuration = getSetting('login_lockout_duration', 30);
    
    // Compter les tentatives récentes
    $sql = "SELECT COUNT(*) as attempts 
            FROM user_logins 
            WHERE ip_address = ? 
            AND status = 'failed' 
            AND login_time > DATE_SUB(NOW(), INTERVAL ? MINUTE)";
    
    $result = executeQuery($sql, [$ipAddress, $blockDuration]);
    
    if ($result && $row = $result->fetch_assoc()) {
        return (int)$row['attempts'];
    }
    
    return 0;
}

/**
 * Récupère une valeur de configuration depuis la base de données
 * 
 * @param string $key Clé de la configuration
 * @param mixed $default Valeur par défaut si la clé n'existe pas
 * @return mixed La valeur de la configuration
 */
function getSetting($key, $default = null) {
    static $settings = [];
    
    // Utiliser le cache si disponible
    if (!isset($settings[$key])) {
        $sql = "SELECT setting_value FROM settings WHERE setting_key = ?";
        $result = executeQuery($sql, [$key]);
        
        if ($result && $row = $result->fetch_assoc()) {
            $settings[$key] = $row['setting_value'];
        } else {
            $settings[$key] = $default;
        }
    }
    
    return $settings[$key];
}

/**
 * Met à jour une valeur de configuration dans la base de données
 * 
 * @param string $key Clé de la configuration
 * @param mixed $value Nouvelle valeur
 * @return bool True si la mise à jour a réussi
 */
function updateSetting($key, $value) {
    $conn = connectDB();
    
    // Vérifier si la clé existe déjà
    $sql = "SELECT id FROM settings WHERE setting_key = ?";
    $result = $conn->prepare($sql);
    $result->bind_param("s", $key);
    $result->execute();
    $result->store_result();
    
    if ($result->num_rows > 0) {
        // Mettre à jour la valeur existante
        $updateSql = "UPDATE settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("ss", $value, $key);
        $success = $updateStmt->execute();
        $updateStmt->close();
    } else {
        // Insérer une nouvelle valeur
        $insertSql = "INSERT INTO settings (setting_key, setting_value, created_at, updated_at) VALUES (?, ?, NOW(), NOW())";
        $insertStmt = $conn->prepare($insertSql);
        $insertStmt->bind_param("ss", $key, $value);
        $success = $insertStmt->execute();
        $insertStmt->close();
    }
    
    $result->close();
    
    // Réinitialiser le cache statique
    if ($success) {
        $settings = [];
    }
    
    return $success;
}

/**
 * Vérifie si le site est en mode maintenance
 * 
 * @return bool True si le site est en mode maintenance
 */
function isMaintenanceMode() {
    return getSetting('maintenance_mode', '0') === '1';
}

/**
 * Vérifie si les inscriptions sont activées
 * 
 * @return bool True si les inscriptions sont activées
 */
function isRegistrationEnabled() {
    return getSetting('enable_registrations', '1') === '1';
}