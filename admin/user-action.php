<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Vérifier si l'utilisateur est administrateur
requireAccess(USER_LEVEL_ADMIN, '../index.php');

// Vérifier le token CSRF
if (!isset($_REQUEST['csrf_token']) || !verifyCSRFToken($_REQUEST['csrf_token'])) {
    $_SESSION['flash_message'] = "Erreur de sécurité. Veuillez réessayer.";
    $_SESSION['flash_type'] = "danger";
    redirect('users.php');
}

// Récupérer l'action et l'ID de l'utilisateur
$action = $_REQUEST['action'] ?? '';
$userId = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

// Vérifier que l'utilisateur existe
$user = getUserById($userId);
if (!$user) {
    $_SESSION['flash_message'] = "Utilisateur introuvable.";
    $_SESSION['flash_type'] = "danger";
    redirect('users.php');
}

// Vérifier les permissions (seul un super admin peut modifier un autre super admin)
if ($user['user_level'] == USER_LEVEL_SUPERADMIN && !isSuperAdmin()) {
    $_SESSION['flash_message'] = "Vous n'avez pas les permissions nécessaires pour effectuer cette action sur un super administrateur.";
    $_SESSION['flash_type'] = "danger";
    redirect('users.php');
}

// Exécuter l'action demandée
switch ($action) {
    case 'ban':
        // Bannir l'utilisateur définitivement
        if (banUser($userId)) {
            $_SESSION['flash_message'] = "L'utilisateur a été banni définitivement.";
            $_SESSION['flash_type'] = "success";
        } else {
            $_SESSION['flash_message'] = "Une erreur est survenue lors du bannissement de l'utilisateur.";
            $_SESSION['flash_type'] = "danger";
        }
        break;
        
    case 'suspend':
        // Vérifier que la durée est spécifiée
        if (!isset($_POST['duration']) || !is_numeric($_POST['duration']) || $_POST['duration'] < 1) {
            $_SESSION['flash_message'] = "La durée de suspension est invalide.";
            $_SESSION['flash_type'] = "danger";
            redirect('users.php');
        }
        
        $duration = (int)$_POST['duration'];
        
        // Suspendre l'utilisateur temporairement
        if (suspendUser($userId, $duration)) {
            $_SESSION['flash_message'] = "L'utilisateur a été suspendu pour $duration jours.";
            $_SESSION['flash_type'] = "success";
        } else {
            $_SESSION['flash_message'] = "Une erreur est survenue lors de la suspension de l'utilisateur.";
            $_SESSION['flash_type'] = "danger";
        }
        break;
        
    case 'unban':
        // Réactiver un utilisateur banni
        if (unbanUser($userId)) {
            $_SESSION['flash_message'] = "L'utilisateur a été réactivé avec succès.";
            $_SESSION['flash_type'] = "success";
        } else {
            $_SESSION['flash_message'] = "Une erreur est survenue lors de la réactivation de l'utilisateur.";
            $_SESSION['flash_type'] = "danger";
        }
        break;
        
    case 'delete':
        // Supprimer un utilisateur
        if (deleteUser($userId)) {
            $_SESSION['flash_message'] = "L'utilisateur a été supprimé définitivement.";
            $_SESSION['flash_type'] = "success";
            redirect('users.php');
        } else {
            $_SESSION['flash_message'] = "Une erreur est survenue lors de la suppression de l'utilisateur.";
            $_SESSION['flash_type'] = "danger";
        }
        break;
        
    default:
        $_SESSION['flash_message'] = "Action non reconnue.";
        $_SESSION['flash_type'] = "danger";
        break;
}

// Redirection
if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'user-edit.php') !== false) {
    redirect("user-edit.php?id=$userId");
} else {
    redirect('users.php');
}
?>