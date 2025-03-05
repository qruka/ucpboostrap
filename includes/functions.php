<?php
require_once 'db.php';
require_once 'auth.php';

/**
 * Rediriger vers une autre page
 * 
 * @param string $location L'URL de redirection
 */
function redirect($location) {
    // S'assurer que la sortie est bien envoyée avant la redirection
    if (ob_get_length()) {
        ob_end_clean();
    }
    
    header("Location: $location");
    exit;
}

/**
 * Échapper les chaînes pour prévenir les injections XSS
 * 
 * @param string $string La chaîne à échapper
 * @return string La chaîne échappée
 */
function escapeString($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Générer un hash sécurisé pour les mots de passe
 * 
 * @param string $password Le mot de passe en clair
 * @return string Le hash du mot de passe
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);
}

/**
 * Générer un token CSRF avec une durée de vie
 * 
 * @param int $expiry Durée de vie du token en secondes (par défaut 1 heure)
 * @return string Le token CSRF
 */
function generateCSRFToken($expiry = 3600) {
    $token = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = $token;
    $_SESSION['csrf_token_expiry'] = time() + $expiry;
    
    return $token;
}

/**
 * Vérifier un token CSRF
 * 
 * @param string $token Le token à vérifier
 * @return bool True si le token est valide
 */
function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_expiry'])) {
        return false;
    }
    
    // Vérifier si le token a expiré
    if (time() > $_SESSION['csrf_token_expiry']) {
        unset($_SESSION['csrf_token']);
        unset($_SESSION['csrf_token_expiry']);
        return false;
    }
    
    // Vérifier si le token correspond
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Récupérer les informations utilisateur par ID
 * 
 * @param int $userId ID de l'utilisateur
 * @return array|null Les données utilisateur ou null si introuvable
 */
function getUserById($userId) {
    static $users = []; // Cache pour éviter les requêtes répétées
    
    if (!isset($users[$userId])) {
        $sql = "SELECT * FROM users WHERE id = ?";
        $result = executeQuery($sql, [$userId]);
        
        if ($result && $result->num_rows > 0) {
            $users[$userId] = $result->fetch_assoc();
        } else {
            $users[$userId] = null;
        }
    }
    
    return $users[$userId];
}

/**
 * Vérifier si un email existe déjà
 * 
 * @param string $email L'email à vérifier
 * @param int|null $excludeUserId ID de l'utilisateur à exclure (optionnel)
 * @return bool True si l'email existe
 */
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

/**
 * Vérifier si un nom d'utilisateur existe déjà
 * 
 * @param string $username Le nom d'utilisateur à vérifier
 * @param int|null $excludeUserId ID de l'utilisateur à exclure (optionnel)
 * @return bool True si le nom d'utilisateur existe
 */
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

/**
 * Créer un nouvel utilisateur
 * 
 * @param string $username Nom d'utilisateur
 * @param string $email Adresse email
 * @param string $password Mot de passe (en clair)
 * @param int $userLevel Niveau d'accès (par défaut USER_LEVEL_REGULAR)
 * @return int|bool ID de l'utilisateur créé ou false en cas d'échec
 */
function createUser($username, $email, $password, $userLevel = USER_LEVEL_REGULAR) {
    $hashedPassword = hashPassword($password);
    $conn = connectDB();
    
    return executeTransaction(function($conn) use ($username, $email, $hashedPassword, $userLevel) {
        $sql = "INSERT INTO users (username, email, password, user_level, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
        $status = USER_STATUS_ACTIVE;
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssis", $username, $email, $hashedPassword, $userLevel, $status);
        
        if ($stmt->execute()) {
            $userId = $conn->insert_id;
            
            // Enregistrer l'activité
            $logSql = "INSERT INTO user_activities (user_id, activity_type, description, ip_address, created_at) 
                       VALUES (?, 'register', 'Création du compte', ?, NOW())";
            $ipAddress = $_SERVER['REMOTE_ADDR'];
            
            $logStmt = $conn->prepare($logSql);
            $logStmt->bind_param("is", $userId, $ipAddress);
            $logStmt->execute();
            $logStmt->close();
            
            $stmt->close();
            return $userId;
        }
        
        $stmt->close();
        return false;
    });
}

/**
 * Mettre à jour l'email d'un utilisateur
 * 
 * @param int $userId ID de l'utilisateur
 * @param string $newEmail Nouvel email
 * @return bool True si la mise à jour réussit
 */
function updateUserEmail($userId, $newEmail) {
    $conn = connectDB();
    
    return executeTransaction(function($conn) use ($userId, $newEmail) {
        $sql = "UPDATE users SET email = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $newEmail, $userId);
        $success = $stmt->execute();
        $stmt->close();
        
        if ($success) {
            // Enregistrer l'activité
            $logSql = "INSERT INTO user_activities (user_id, activity_type, description, ip_address, created_at) 
                       VALUES (?, 'update_email', 'Modification de l\'adresse email', ?, NOW())";
            $ipAddress = $_SERVER['REMOTE_ADDR'];
            
            $logStmt = $conn->prepare($logSql);
            $logStmt->bind_param("is", $userId, $ipAddress);
            $logStmt->execute();
            $logStmt->close();
        }
        
        return $success;
    });
}

/**
 * Mettre à jour le niveau d'un utilisateur
 * 
 * @param int $userId ID de l'utilisateur
 * @param int $newLevel Nouveau niveau
 * @return bool True si la mise à jour réussit
 */
function updateUserLevel($userId, $newLevel) {
    // Vérifier que le nouveau niveau est valide
    if (!in_array($newLevel, [USER_LEVEL_REGULAR, USER_LEVEL_MODERATOR, USER_LEVEL_ADMIN, USER_LEVEL_SUPERADMIN])) {
        return false;
    }
    
    $conn = connectDB();
    
    return executeTransaction(function($conn) use ($userId, $newLevel) {
        $sql = "UPDATE users SET user_level = ? WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $newLevel, $userId);
        $success = $stmt->execute();
        $stmt->close();
        
        if ($success) {
            // Enregistrer l'activité
            $logSql = "INSERT INTO user_activities (user_id, activity_type, description, ip_address, created_at) 
                       VALUES (?, 'update_level', ?, ?, NOW())";
            $levelName = getLevelName($newLevel);
            $description = "Niveau d'accès modifié à $levelName";
            $ipAddress = $_SERVER['REMOTE_ADDR'];
            
            $logStmt = $conn->prepare($logSql);
            $logStmt->bind_param("iss", $userId, $description, $ipAddress);
            $logStmt->execute();
            $logStmt->close();
        }
        
        return $success;
    });
}

/**
 * Supprimer un utilisateur
 * 
 * @param int $userId ID de l'utilisateur
 * @return bool True si la suppression réussit
 */
function deleteUser($userId) {
    // Vérifier que l'utilisateur n'est pas un super admin
    $user = getUserById($userId);
    if (!$user || $user['user_level'] == USER_LEVEL_SUPERADMIN) {
        return false;
    }
    
    $conn = connectDB();
    
    return executeTransaction(function($conn) use ($userId, $user) {
        // Enregistrer l'activité avant la suppression
        $logSql = "INSERT INTO user_activities (user_id, activity_type, description, ip_address, created_at) 
                   VALUES (?, 'delete_user', ?, ?, NOW())";
        $description = "Utilisateur supprimé: " . $user['username'] . " (ID: $userId)";
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        $admin = $_SESSION['user_id'] ?? 0;
        
        $logStmt = $conn->prepare($logSql);
        $logStmt->bind_param("iss", $admin, $description, $ipAddress);
        $logStmt->execute();
        $logStmt->close();
        
        // Supprimer l'utilisateur
        $sql = "DELETE FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    });
}

/**
 * Convertir le niveau utilisateur en texte
 * 
 * @param int $level Niveau numérique
 * @return string Texte correspondant au niveau
 */
function getUserLevelText($level) {
    return getLevelName($level);
}

/**
 * Convertir le statut utilisateur en texte
 * 
 * @param string $status Code du statut
 * @param string|null $banExpires Date d'expiration de la suspension
 * @return string Texte décrivant le statut
 */
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

/**
 * Récupérer tous les utilisateurs avec pagination et filtres
 * 
 * @param string $search Terme de recherche (nom ou email)
 * @param int $page Numéro de page
 * @param int $perPage Nombre d'éléments par page
 * @return array Les utilisateurs et les informations de pagination
 */
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
        'totalPages' => $totalPages > 0 ? $totalPages : 1,
        'currentPage' => $page
    ];
}

/**
 * Formater une date en français
 * 
 * @param string $dateString Date au format string
 * @return string Date formatée
 */
function formatDate($dateString) {
    $date = new DateTime($dateString);
    
    if (class_exists('IntlDateFormatter')) {
        $formatter = new IntlDateFormatter(
            'fr_FR',
            IntlDateFormatter::LONG,
            IntlDateFormatter::SHORT
        );
        return $formatter->format($date);
    } else {
        // Fallback si intl n'est pas disponible
        setlocale(LC_TIME, 'fr_FR.utf8', 'fra');
        return strftime('%d %B %Y à %H:%M', $date->getTimestamp());
    }
}

/**
 * Fonction pour changer le mot de passe d'un utilisateur
 * 
 * @param int $userId ID de l'utilisateur
 * @param string $currentPassword Mot de passe actuel
 * @param string $newPassword Nouveau mot de passe
 * @return bool True si le changement réussit
 */
function changeUserPassword($userId, $currentPassword, $newPassword) {
    $conn = connectDB();
    
    return executeTransaction(function($conn) use ($userId, $currentPassword, $newPassword) {
        // Récupérer le mot de passe actuel
        $sql = "SELECT password FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        
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
        $hashedPassword = hashPassword($newPassword);
        
        // Mettre à jour le mot de passe
        $updateSql = "UPDATE users SET password = ? WHERE id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("si", $hashedPassword, $userId);
        $success = $updateStmt->execute();
        $updateStmt->close();
        
        if ($success) {
            // Enregistrer l'activité
            $logSql = "INSERT INTO user_activities (user_id, activity_type, description, ip_address, created_at) 
                       VALUES (?, 'password_change', 'Mot de passe modifié', ?, NOW())";
            $ipAddress = $_SERVER['REMOTE_ADDR'];
            
            $logStmt = $conn->prepare($logSql);
            $logStmt->bind_param("is", $userId, $ipAddress);
            $logStmt->execute();
            $logStmt->close();
        }
        
        return $success;
    });
}

/**
 * Générer une suite unique aléatoire
 * 
 * @param int $length Longueur de la chaîne
 * @return string Une chaîne aléatoire
 */
function generateRandomString($length = 16) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Récupère le nom du niveau d'accès
 * 
 * @param int $level Niveau d'accès numérique
 * @return string Nom du niveau
 */
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

/**
 * Nettoyer les entrées utilisateur
 * 
 * @param string $input Donnée à nettoyer
 * @return string Donnée nettoyée
 */
function sanitizeInput($input) {
    return trim(strip_tags($input));
}

/**
 * Ajouter un message flash à afficher sur la prochaine page
 * 
 * @param string $message Le message à afficher
 * @param string $type Type de message (success, danger, warning, info)
 */
function setFlashMessage($message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

/**
 * Récupérer les activités d'un utilisateur
 * 
 * @param int $userId ID de l'utilisateur
 * @param int $limit Nombre maximum d'activités à récupérer
 * @return array Les activités
 */
function getUserActivities($userId, $limit = 10) {
    $sql = "SELECT activity_type, description, ip_address, created_at 
            FROM user_activities 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?";
    $result = executeQuery($sql, [$userId, $limit]);
    
    $activities = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $activities[] = $row;
        }
    }
    
    return $activities;
}