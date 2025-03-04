<?php
require_once 'db.php';
require_once 'auth.php';

// Rediriger vers une autre page
function redirect($location) {
    header("Location: $location");
    exit;
}

// Échapper les chaînes pour prévenir les injections XSS
function escapeString($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Générer un hash sécurisé pour les mots de passe
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Générer un token CSRF
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Vérifier un token CSRF
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Récupérer les informations utilisateur par ID
function getUserById($userId) {
    $sql = "SELECT * FROM users WHERE id = ?";
    $result = executeQuery($sql, [$userId]);
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

// Vérifier si un email existe déjà
function emailExists($email, $excludeUserId = null) {
    if ($excludeUserId) {
        $sql = "SELECT id FROM users WHERE email = ? AND id != ?";
        $result = executeQuery($sql, [$email, $excludeUserId]);
    } else {
        $sql = "SELECT id FROM users WHERE email = ?";
        $result = executeQuery($sql, [$email]);
    }
    
    return $result && $result->num_rows > 0;
}

// Vérifier si un nom d'utilisateur existe déjà
function usernameExists($username, $excludeUserId = null) {
    if ($excludeUserId) {
        $sql = "SELECT id FROM users WHERE username = ? AND id != ?";
        $result = executeQuery($sql, [$username, $excludeUserId]);
    } else {
        $sql = "SELECT id FROM users WHERE username = ?";
        $result = executeQuery($sql, [$username]);
    }
    
    return $result && $result->num_rows > 0;
}

// Créer un nouvel utilisateur
function createUser($username, $email, $password, $userLevel = USER_LEVEL_REGULAR) {
    $hashedPassword = hashPassword($password);
    $sql = "INSERT INTO users (username, email, password, user_level, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
    
    $conn = connectDB();
    $stmt = $conn->prepare($sql);
    $status = USER_STATUS_ACTIVE;
    $stmt->bind_param("sssss", $username, $email, $hashedPassword, $userLevel, $status);
    
    $success = $stmt->execute();
    $userId = $success ? $conn->insert_id : null;
    
    $stmt->close();
    $conn->close();
    
    return $userId;
}

// Mettre à jour l'email d'un utilisateur
function updateUserEmail($userId, $newEmail) {
    $sql = "UPDATE users SET email = ? WHERE id = ?";
    
    $conn = connectDB();
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $newEmail, $userId);
    
    $success = $stmt->execute();
    
    $stmt->close();
    $conn->close();
    
    return $success;
}

// Mettre à jour le niveau d'un utilisateur
function updateUserLevel($userId, $newLevel) {
    // Vérifier que le nouveau niveau est valide
    if (!in_array($newLevel, [USER_LEVEL_REGULAR, USER_LEVEL_MODERATOR, USER_LEVEL_ADMIN, USER_LEVEL_SUPERADMIN])) {
        return false;
    }
    
    $sql = "UPDATE users SET user_level = ? WHERE id = ?";
    
    $conn = connectDB();
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $newLevel, $userId);
    
    $success = $stmt->execute();
    
    $stmt->close();
    $conn->close();
    
    return $success;
}

// Supprimer un utilisateur
function deleteUser($userId) {
    // Vérifier que l'utilisateur n'est pas un super admin
    $user = getUserById($userId);
    if ($user && $user['user_level'] == USER_LEVEL_SUPERADMIN) {
        return false;
    }
    
    $sql = "DELETE FROM users WHERE id = ?";
    
    $conn = connectDB();
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    
    $success = $stmt->execute();
    
    $stmt->close();
    $conn->close();
    
    return $success;
}

// Convertir le niveau utilisateur en texte
function getUserLevelText($level) {
    switch ($level) {
        case USER_LEVEL_REGULAR:
            return "Utilisateur";
        case USER_LEVEL_MODERATOR:
            return "Modérateur";
        case USER_LEVEL_ADMIN:
            return "Administrateur";
        case USER_LEVEL_SUPERADMIN:
            return "Super Admin";
        default:
            return "Inconnu";
    }
}

// Convertir le statut utilisateur en texte
function getUserStatusText($status, $banExpires = null) {
    switch ($status) {
        case USER_STATUS_ACTIVE:
            return "Actif";
        case USER_STATUS_BANNED:
            return "Banni définitivement";
        case USER_STATUS_SUSPENDED:
            if ($banExpires) {
                $banDate = new DateTime($banExpires);
                $now = new DateTime();
                
                if ($banDate > $now) {
                    $interval = $now->diff($banDate);
                    if ($interval->days > 0) {
                        return "Suspendu ({$interval->days} jours restants)";
                    } else {
                        return "Suspendu (moins de 24h)";
                    }
                } else {
                    return "Actif";
                }
            }
            return "Suspendu";
        default:
            return "Statut inconnu";
    }
}

// Récupérer tous les utilisateurs avec pagination et filtres
function getUsers($search = '', $page = 1, $perPage = 10) {
    $offset = ($page - 1) * $perPage;
    
    // Construire la requête avec ou sans recherche
    if (!empty($search)) {
        $searchParam = "%$search%";
        $sql = "SELECT * FROM users WHERE username LIKE ? OR email LIKE ? ORDER BY id DESC LIMIT ? OFFSET ?";
        $params = [$searchParam, $searchParam, $perPage, $offset];
    } else {
        $sql = "SELECT * FROM users ORDER BY id DESC LIMIT ? OFFSET ?";
        $params = [$perPage, $offset];
    }
    
    $result = executeQuery($sql, $params);
    $users = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }
    
    // Compter le nombre total d'utilisateurs pour la pagination
    if (!empty($search)) {
        $countSql = "SELECT COUNT(*) as total FROM users WHERE username LIKE ? OR email LIKE ?";
        $countParams = [$searchParam, $searchParam];
    } else {
        $countSql = "SELECT COUNT(*) as total FROM users";
        $countParams = [];
    }
    
    $countResult = executeQuery($countSql, $countParams);
    $totalUsers = 0;
    
    if ($countResult && $row = $countResult->fetch_assoc()) {
        $totalUsers = $row['total'];
    }
    
    $totalPages = ceil($totalUsers / $perPage);
    
    return [
        'users' => $users,
        'total' => $totalUsers,
        'totalPages' => $totalPages,
        'currentPage' => $page
    ];
}

// Formater une date en français
function formatDate($dateString) {
    $date = new DateTime($dateString);
    $formatter = new IntlDateFormatter(
        'fr_FR',
        IntlDateFormatter::LONG,
        IntlDateFormatter::SHORT
    );
    return $formatter->format($date);
}


// Fonction pour changer le mot de passe d'un utilisateur
function changeUserPassword($userId, $currentPassword, $newPassword) {
    // Récupérer le mot de passe actuel
    $conn = connectDB();
    $sql = "SELECT password FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return false;
    }
    
    $user = $result->fetch_assoc();
    
    // Vérifier que le mot de passe actuel est correct
    if (!password_verify($currentPassword, $user['password'])) {
        return false;
    }
    
    // Vérifier que le nouveau mot de passe est suffisamment fort
    if (strlen($newPassword) < 8) {
        return false;
    }
    
    // Hacher le nouveau mot de passe
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // Mettre à jour le mot de passe
    $sql = "UPDATE users SET password = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $hashedPassword, $userId);
    
    return $stmt->execute();
}


/**
 * Enregistre une tentative de connexion (version simplifiée)
 * Cette fonction est temporaire jusqu'à la mise en place complète du système de sécurité
 * 
 * @param int $userId ID de l'utilisateur tentant de se connecter
 * @param string $status Statut de la tentative ('success' ou 'failed')
 * @return void
 */
function logLoginAttempt($userId, $status = 'success') {
    // Version temporaire qui ne fait rien mais évite l'erreur
    // Cette fonction sera améliorée plus tard pour enregistrer réellement les tentatives
    
    // Si vous voulez faire un enregistrement minimal dans les logs du serveur:
    error_log("Tentative de connexion pour l'utilisateur ID: $userId - Statut: $status");
    
    // Une fois les tables de la base de données créées, nous pourrons 
    // implémenter l'enregistrement complet des tentatives de connexion
}


/**
 * Enregistre une activité utilisateur (version simplifiée)
 * Cette fonction est temporaire jusqu'à la mise en place complète du système de sécurité
 * 
 * @param int $userId ID de l'utilisateur
 * @param string $activityType Type d'activité (ex: 'login', 'password_change')
 * @param string $description Description de l'activité
 * @return void
 */
function logUserActivity($userId, $activityType, $description) {
    // Version temporaire qui ne fait rien mais évite l'erreur
    // Une information minimale est enregistrée dans les logs du serveur
    error_log("Activité utilisateur ID: $userId - Type: $activityType - Description: $description");
    
    // Sera implémentée complètement plus tard avec stockage en base de données
}



// Obtenez le nom du niveau d'accès en fonction d'un ID de niveau
function getLevelName($level) {
    switch ($level) {
        case USER_LEVEL_REGULAR:
            return "Utilisateur";
        case USER_LEVEL_MODERATOR:
            return "Modérateur";
        case USER_LEVEL_ADMIN:
            return "Administrateur";
        case USER_LEVEL_SUPERADMIN:
            return "Super Administrateur";
        default:
            return "Niveau inconnu";
    }
}
?>