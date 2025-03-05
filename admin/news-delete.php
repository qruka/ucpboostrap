<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Vérifier si l'utilisateur est administrateur
requireAccess(USER_LEVEL_ADMIN);

// Vérifier si l'ID est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlashMessage("ID d'actualité invalide.", "danger");
    redirect('../index.php');
}

$newsId = (int)$_GET['id'];

// Vérifier si l'actualité existe
$conn = connectDB();
$query = "SELECT * FROM news WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $newsId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $conn->close();
    setFlashMessage("Actualité introuvable.", "danger");
    redirect('../index.php');
}

$news = $result->fetch_assoc();

// Utiliser une transaction pour la suppression
$conn->begin_transaction();

try {
    // Enregistrer l'activité avant la suppression
    $activityType = 'delete_news';
    $description = "Suppression de l'actualité: " . $news['title'];
    $ipAddress = $_SERVER['REMOTE_ADDR'];
    $userId = $_SESSION['user_id'];
    
    $logQuery = "INSERT INTO user_activities (user_id, activity_type, description, ip_address, created_at) 
                 VALUES (?, ?, ?, ?, NOW())";
    $logStmt = $conn->prepare($logQuery);
    $logStmt->bind_param("isss", $userId, $activityType, $description, $ipAddress);
    $logStmt->execute();
    
    // Supprimer l'actualité
    $deleteSql = "DELETE FROM news WHERE id = ?";
    $deleteStmt = $conn->prepare($deleteSql);
    $deleteStmt->bind_param("i", $newsId);
    $deleteStmt->execute();
    
    $conn->commit();
    
    // Supprimer l'image si elle existe
    if ($news['image_path'] && file_exists($news['image_path'])) {
        unlink($news['image_path']);
    }
    
    setFlashMessage("L'actualité a été supprimée avec succès.", "success");
} catch (Exception $e) {
    $conn->rollback();
    setFlashMessage("Une erreur est survenue lors de la suppression de l'actualité.", "danger");
}

$conn->close();
redirect('../index.php');