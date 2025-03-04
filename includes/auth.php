<?php
require_once 'config.php';
require_once 'db.php';

// Vérifier si l'utilisateur est connecté
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Vérifier le niveau d'accès de l'utilisateur
function getUserLevel() {
    if (!isLoggedIn()) {
        return 0;
    }
    
    $sql = "SELECT user_level FROM users WHERE id = ?";
    $result = executeQuery($sql, [$_SESSION['user_id']]);
    
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        return $user['user_level'];
    }
    
    return 0;
}

// Vérifier si l'utilisateur est un administrateur (niveau 3 ou plus)
function isAdmin() {
    return getUserLevel() >= USER_LEVEL_ADMIN;
}

// Vérifier si l'utilisateur est un super admin (niveau 4)
function isSuperAdmin() {
    return getUserLevel() == USER_LEVEL_SUPERADMIN;
}

// Vérifier si l'utilisateur est un modérateur (niveau 2 ou plus)
function isModerator() {
    return getUserLevel() >= USER_LEVEL_MODERATOR;
}

// Vérifier si l'utilisateur a accès à une ressource basée sur le niveau requis
function hasAccess($requiredLevel) {
    return getUserLevel() >= $requiredLevel;
}

// Vérifier si l'utilisateur est banni
function isUserBanned($userId) {
    $sql = "SELECT status, ban_expires FROM users WHERE id = ?";
    $result = executeQuery($sql, [$userId]);
    
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Si le statut est "banned", l'utilisateur est banni définitivement
        if ($user['status'] === USER_STATUS_BANNED) {
            return true;
        }
        
        // Si le statut est "suspended", vérifier si la suspension est toujours active
        if ($user['status'] === USER_STATUS_SUSPENDED && !empty($user['ban_expires'])) {
            $banExpires = strtotime($user['ban_expires']);
            $now = time();
            
            // Si la date d'expiration est dans le futur, l'utilisateur est toujours banni
            if ($banExpires > $now) {
                return true;
            } else {
                // Sinon, mettre à jour le statut de l'utilisateur
                $updateSql = "UPDATE users SET status = ? WHERE id = ?";
                executeQuery($updateSql, [USER_STATUS_ACTIVE, $userId]);
                return false;
            }
        }
    }
    
    return false;
}

// Bannir un utilisateur définitivement
function banUser($userId) {
    $sql = "UPDATE users SET status = ?, ban_expires = NULL WHERE id = ?";
    return executeQuery($sql, [USER_STATUS_BANNED, $userId]);
}

// Suspendre un utilisateur temporairement
function suspendUser($userId, $days) {
    $banExpires = date('Y-m-d H:i:s', strtotime("+$days days"));
    $sql = "UPDATE users SET status = ?, ban_expires = ? WHERE id = ?";
    return executeQuery($sql, [USER_STATUS_SUSPENDED, $banExpires, $userId]);
}

// Réactiver un utilisateur
function unbanUser($userId) {
    $sql = "UPDATE users SET status = ?, ban_expires = NULL WHERE id = ?";
    return executeQuery($sql, [USER_STATUS_ACTIVE, $userId]);
}

// Rediriger si l'utilisateur n'a pas les permissions requises
function requireAccess($requiredLevel, $redirectUrl = 'index.php') {
    if (!isLoggedIn()) {
        $_SESSION['flash_message'] = "Veuillez vous connecter pour accéder à cette page.";
        $_SESSION['flash_type'] = "warning";
        redirect('login.php');
    }
    
    // Vérifier si l'utilisateur est banni
    if (isUserBanned($_SESSION['user_id'])) {
        // Déconnecter l'utilisateur
        session_unset();
        session_destroy();
        session_start();
        
        $_SESSION['flash_message'] = "Votre compte a été suspendu. Veuillez contacter l'administrateur.";
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

// Authenticating a user with ban check
// Fonction modifiée pour enregistrer les connexions
function authenticateUser($username, $password) {
    $sql = "SELECT id, username, email, password, user_level, status, ban_expires FROM users WHERE username = ?";
    $result = executeQuery($sql, [$username]);
    
    // Utilisateur trouvé
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Vérification du statut de bannissement
        if ($user['status'] === USER_STATUS_BANNED) {
            logLoginAttempt($user['id'], 'failed'); // Enregistrer la tentative échouée
            return ['banned' => true, 'message' => 'Votre compte a été banni définitivement.'];
        }
        
        // Vérification de suspension temporaire
        if ($user['status'] === USER_STATUS_SUSPENDED && !empty($user['ban_expires'])) {
            $banExpires = strtotime($user['ban_expires']);
            $now = time();
            
            if ($banExpires > $now) {
                $timeLeft = ceil(($banExpires - $now) / (60 * 60 * 24));
                logLoginAttempt($user['id'], 'failed'); // Enregistrer la tentative échouée
                return [
                    'banned' => true, 
                    'message' => "Votre compte est suspendu pour encore $timeLeft jours."
                ];
            } else {
                // La suspension est terminée, mettre à jour le statut
                $updateSql = "UPDATE users SET status = ? WHERE id = ?";
                executeQuery($updateSql, [USER_STATUS_ACTIVE, $user['id']]);
            }
        }
        
        // Vérifier le mot de passe
        if (password_verify($password, $user['password'])) {
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
            logLoginAttempt($user['id'], 'failed');
        }
    }
    
    return false;
}
?>