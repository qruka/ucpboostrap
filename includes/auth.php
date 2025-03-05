<?php
require_once 'config.php';
require_once 'db.php';

/**
 * Vérifier si l'utilisateur est connecté
 * 
 * @return bool Vrai si l'utilisateur est connecté
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Récupère le niveau d'accès de l'utilisateur connecté
 * 
 * @return int Le niveau d'accès (0 si non connecté)
 */
function getUserLevel() {
    if (!isLoggedIn()) {
        return 0;
    }
    
    static $userLevel = null;
    
    // Utilisation d'un cache pour éviter des requêtes répétées
    if ($userLevel === null) {
        $sql = "SELECT user_level FROM users WHERE id = ?";
        $result = executeQuery($sql, [$_SESSION['user_id']]);
        
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $userLevel = (int)$user['user_level'];
        } else {
            $userLevel = 0;
        }
    }
    
    return $userLevel;
}

/**
 * Vérifier si l'utilisateur est un administrateur
 * 
 * @return bool Vrai si l'utilisateur est administrateur
 */
function isAdmin() {
    return getUserLevel() >= USER_LEVEL_ADMIN;
}

/**
 * Vérifier si l'utilisateur est un super administrateur
 * 
 * @return bool Vrai si l'utilisateur est super administrateur
 */
function isSuperAdmin() {
    return getUserLevel() == USER_LEVEL_SUPERADMIN;
}

/**
 * Vérifier si l'utilisateur est un modérateur
 * 
 * @return bool Vrai si l'utilisateur est modérateur
 */
function isModerator() {
    return getUserLevel() >= USER_LEVEL_MODERATOR;
}

/**
 * Vérifier si l'utilisateur a le niveau d'accès requis
 * 
 * @param int $requiredLevel Le niveau minimum requis
 * @return bool Vrai si l'utilisateur a le niveau requis
 */
function hasAccess($requiredLevel) {
    return getUserLevel() >= $requiredLevel;
}

/**
 * Vérifier si un utilisateur est banni
 * 
 * @param int $userId ID de l'utilisateur
 * @return bool|array False si non banni, un tableau avec les détails sinon
 */
function isUserBanned($userId) {
    $sql = "SELECT status, ban_expires, UNIX_TIMESTAMP(ban_expires) as ban_expires_ts FROM users WHERE id = ?";
    $result = executeQuery($sql, [$userId]);
    
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Si le statut est "banned", l'utilisateur est banni définitivement
        if ($user['status'] === USER_STATUS_BANNED) {
            return [
                'status' => USER_STATUS_BANNED,
                'permanent' => true,
                'message' => 'Compte banni définitivement'
            ];
        }
        
        // Si le statut est "suspended", vérifier si la suspension est toujours active
        if ($user['status'] === USER_STATUS_SUSPENDED && !empty($user['ban_expires'])) {
            $now = time();
            
            // Si la date d'expiration est dans le futur, l'utilisateur est toujours banni
            if ($user['ban_expires_ts'] > $now) {
                $daysLeft = ceil(($user['ban_expires_ts'] - $now) / (60 * 60 * 24));
                
                return [
                    'status' => USER_STATUS_SUSPENDED,
                    'permanent' => false,
                    'expires' => $user['ban_expires'],
                    'expires_ts' => $user['ban_expires_ts'],
                    'days_left' => $daysLeft,
                    'message' => "Compte suspendu pour encore $daysLeft jours"
                ];
            } else {
                // Sinon, mettre à jour le statut de l'utilisateur
                $updateSql = "UPDATE users SET status = ? WHERE id = ?";
                executeQuery($updateSql, [USER_STATUS_ACTIVE, $userId]);
            }
        }
    }
    
    return false;
}

/**
 * Bannir un utilisateur définitivement
 * 
 * @param int $userId ID de l'utilisateur
 * @param string $reason Raison du bannissement (optionnel)
 * @return bool True si l'opération réussit
 */
function banUser($userId, $reason = '') {
    $conn = connectDB();
    
    return executeTransaction(function($conn) use ($userId, $reason) {
        // Mettre à jour le statut de l'utilisateur
        $sql = "UPDATE users SET status = ?, ban_expires = NULL WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $banned = USER_STATUS_BANNED;
        $stmt->bind_param("si", $banned, $userId);
        $success = $stmt->execute();
        $stmt->close();
        
        if ($success && !empty($reason)) {
            // Enregistrer la raison du bannissement
            $logSql = "INSERT INTO user_activities (user_id, activity_type, description, ip_address, created_at) 
                       VALUES (?, 'ban', ?, ?, NOW())";
            $stmt = $conn->prepare($logSql);
            $ipAddress = $_SERVER['REMOTE_ADDR'];
            $stmt->bind_param("iss", $userId, $reason, $ipAddress);
            $stmt->execute();
            $stmt->close();
        }
        
        return $success;
    });
}

/**
 * Suspendre un utilisateur temporairement
 * 
 * @param int $userId ID de l'utilisateur
 * @param int $days Nombre de jours de suspension
 * @param string $reason Raison de la suspension (optionnel)
 * @return bool True si l'opération réussit
 */
function suspendUser($userId, $days, $reason = '') {
    $conn = connectDB();
    
    return executeTransaction(function($conn) use ($userId, $days, $reason) {
        // Calculer la date de fin de suspension
        $banExpires = date('Y-m-d H:i:s', strtotime("+$days days"));
        
        // Mettre à jour le statut de l'utilisateur
        $sql = "UPDATE users SET status = ?, ban_expires = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $suspended = USER_STATUS_SUSPENDED;
        $stmt->bind_param("ssi", $suspended, $banExpires, $userId);
        $success = $stmt->execute();
        $stmt->close();
        
        if ($success && !empty($reason)) {
            // Enregistrer la raison de la suspension
            $logSql = "INSERT INTO user_activities (user_id, activity_type, description, ip_address, created_at) 
                       VALUES (?, 'suspend', ?, ?, NOW())";
            $stmt = $conn->prepare($logSql);
            $description = "Suspendu pour $days jours. Raison: $reason";
            $ipAddress = $_SERVER['REMOTE_ADDR'];
            $stmt->bind_param("iss", $userId, $description, $ipAddress);
            $stmt->execute();
            $stmt->close();
        }
        
        return $success;
    });
}

/**
 * Réactiver un utilisateur banni ou suspendu
 * 
 * @param int $userId ID de l'utilisateur
 * @return bool True si l'opération réussit
 */
function unbanUser($userId) {
    $conn = connectDB();
    
    return executeTransaction(function($conn) use ($userId) {
        // Mettre à jour le statut de l'utilisateur
        $sql = "UPDATE users SET status = ?, ban_expires = NULL WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $active = USER_STATUS_ACTIVE;
        $stmt->bind_param("si", $active, $userId);
        $success = $stmt->execute();
        $stmt->close();
        
        if ($success) {
            // Enregistrer l'action
            $logSql = "INSERT INTO user_activities (user_id, activity_type, description, ip_address, created_at) 
                       VALUES (?, 'unban', 'Compte réactivé', ?, NOW())";
            $stmt = $conn->prepare($logSql);
            $ipAddress = $_SERVER['REMOTE_ADDR'];
            $stmt->bind_param("is", $userId, $ipAddress);
            $stmt->execute();
            $stmt->close();
        }
        
        return $success;
    });
}

/**
 * Rediriger si l'utilisateur n'a pas les permissions requises
 * 
 * @param int $requiredLevel Le niveau minimum requis
 * @param string $redirectUrl L'URL de redirection en cas d'échec
 */
function requireAccess($requiredLevel, $redirectUrl = 'index.php') {
    if (!isLoggedIn()) {
        $_SESSION['flash_message'] = "Veuillez vous connecter pour accéder à cette page.";
        $_SESSION['flash_type'] = "warning";
        redirect('login.php');
    }
    
    // Vérifier si l'utilisateur est banni
    $banInfo = isUserBanned($_SESSION['user_id']);
    if ($banInfo !== false) {
        // Déconnecter l'utilisateur
        session_unset();
        session_destroy();
        session_start();
        
        $_SESSION['flash_message'] = $banInfo['message'] . ". Veuillez contacter l'administrateur.";
        $_SESSION['flash_type'] = "danger";
        redirect('login.php');
    }
    
    // Vérifier le niveau d'accès
    if (!hasAccess($requiredLevel)) {
        $_SESSION['flash_message'] = "Vous n'avez pas les permissions nécessaires pour accéder à cette page.";
        $_SESSION['flash_type'] = "danger";
        redirect($redirectUrl);
    }
}

/**
 * Authentifier un utilisateur
 * 
 * @param string $username Nom d'utilisateur
 * @param string $password Mot de passe
 * @return array|bool Les données utilisateur si authentifié, sinon false
 */
function authenticateUser($username, $password) {
    $sql = "SELECT id, username, email, password, user_level, status, ban_expires FROM users WHERE username = ?";
    $result = executeQuery($sql, [$username]);
    
    // Utilisateur trouvé
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Vérification du statut de bannissement
        $banInfo = isUserBanned($user['id']);
        if ($banInfo !== false) {
            logLoginAttempt($user['id'], 'failed', 'Compte ' . ($banInfo['permanent'] ? 'banni' : 'suspendu'));
            return ['banned' => true, 'message' => $banInfo['message']];
        }
        
        // Vérifier le mot de passe
        if (password_verify($password, $user['password'])) {
            // Vérifier si le hash a besoin d'être mis à jour (si l'algorithme a changé)
            if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $updateSql = "UPDATE users SET password = ? WHERE id = ?";
                executeQuery($updateSql, [$newHash, $user['id']]);
            }
            
            // Ne pas stocker le mot de passe en session
            unset($user['password']);
            unset($user['ban_expires']);
            
            // Enregistrer la connexion réussie
            logLoginAttempt($user['id'], 'success');
            
            // Enregistrer cette activité
            logUserActivity($user['id'], 'login', 'Connexion réussie');
            
            return $user;
        } else {
            // Mot de passe incorrect
            logLoginAttempt($user['id'], 'failed', 'Mot de passe incorrect');
        }
    } else {
        // Utilisateur non trouvé
        logLoginAttempt(0, 'failed', 'Utilisateur non trouvé: ' . $username);
    }
    
    return false;
}

/**
 * Enregistre une tentative de connexion
 * 
 * @param int $userId ID de l'utilisateur
 * @param string $status Statut de la tentative ('success' ou 'failed')
 * @param string $details Détails supplémentaires (optionnel)
 * @return bool True si l'enregistrement réussit
 */
function logLoginAttempt($userId, $status = 'success', $details = '') {
    $ip = $_SERVER['REMOTE_ADDR'];
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    // Enregistrer dans la table user_logins
    $sql = "INSERT INTO user_logins (user_id, login_time, ip_address, user_agent, status) VALUES (?, NOW(), ?, ?, ?)";
    $result = executeQuery($sql, [$userId, $ip, $userAgent, $status]);
    
    // Si c'est une connexion réussie, mettre à jour ou ajouter l'IP dans user_ips
    if ($status === 'success' && $userId > 0) {
        $checkIpSql = "SELECT id, login_count FROM user_ips WHERE user_id = ? AND ip_address = ?";
        $checkResult = executeQuery($checkIpSql, [$userId, $ip]);
        
        if ($checkResult && $checkResult->num_rows > 0) {
            // Mettre à jour l'IP existante
            $ipData = $checkResult->fetch_assoc();
            $updateIpSql = "UPDATE user_ips SET last_seen = NOW(), login_count = ? WHERE id = ?";
            executeQuery($updateIpSql, [($ipData['login_count'] + 1), $ipData['id']]);
        } else {
            // Ajouter la nouvelle IP
            $insertIpSql = "INSERT INTO user_ips (user_id, ip_address, first_seen, last_seen, login_count) VALUES (?, ?, NOW(), NOW(), 1)";
            executeQuery($insertIpSql, [$userId, $ip]);
        }
    }
    
    // Journaliser l'événement
    error_log("Login attempt - User ID: $userId, Status: $status, IP: $ip" . ($details ? ", Details: $details" : ""));
    
    return ($result !== false);
}

/**
 * Enregistre une activité utilisateur
 * 
 * @param int $userId ID de l'utilisateur
 * @param string $activityType Type d'activité
 * @param string $description Description de l'activité
 * @return bool True si l'enregistrement réussit
 */
function logUserActivity($userId, $activityType, $description) {
    $ip = $_SERVER['REMOTE_ADDR'];
    
    $sql = "INSERT INTO user_activities (user_id, activity_type, description, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())";
    $result = executeQuery($sql, [$userId, $activityType, $description, $ip]);
    
    return ($result !== false);
}

/**
 * Récupère l'historique des connexions d'un utilisateur
 * 
 * @param int $userId ID de l'utilisateur
 * @param int $limit Nombre maximum de résultats à retourner
 * @return array Un tableau des connexions
 */
function getUserLoginHistory($userId, $limit = 10) {
    $sql = "SELECT login_time, ip_address, status FROM user_logins WHERE user_id = ? ORDER BY login_time DESC LIMIT ?";
    $result = executeQuery($sql, [$userId, $limit]);
    
    $history = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $history[] = $row;
        }
    }
    
    return $history;
}

/**
 * Récupère les IPs connues d'un utilisateur
 * 
 * @param int $userId ID de l'utilisateur
 * @return array Un tableau des IPs
 */
function getUserIPs($userId) {
    $sql = "SELECT ip_address, first_seen, last_seen, login_count, is_suspicious FROM user_ips WHERE user_id = ? ORDER BY last_seen DESC";
    $result = executeQuery($sql, [$userId]);
    
    $ips = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $ips[] = $row;
        }
    }
    
    return $ips;
}