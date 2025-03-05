<?php
$pageTitle = "Réinitialiser le mot de passe";
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Rediriger si l'utilisateur est déjà connecté
if (isLoggedIn()) {
    redirect('index.php');
}

$errors = [];
$success = false;
$validToken = false;
$userId = 0;
$token = $_GET['token'] ?? '';

// Vérifier si le token existe et est valide
if (!empty($token)) {
    $sql = "SELECT pr.id, pr.user_id, pr.used, pr.expires_at, u.username 
            FROM password_resets pr 
            JOIN users u ON pr.user_id = u.id 
            WHERE pr.token = ? AND pr.used = 0 AND pr.expires_at > NOW()";
    $result = executeQuery($sql, [$token]);
    
    if ($result && $result->num_rows > 0) {
        $resetData = $result->fetch_assoc();
        $validToken = true;
        $userId = $resetData['user_id'];
        $resetId = $resetData['id'];
        $username = $resetData['username'];
    }
}

// Traiter le formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    // Vérifier le token CSRF
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = "Erreur de sécurité. Veuillez réessayer.";
    } else {
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Valider le mot de passe
        if (empty($password)) {
            $errors[] = "Le mot de passe est requis.";
        } elseif (strlen($password) < 8) {
            $errors[] = "Le mot de passe doit contenir au moins 8 caractères.";
        } elseif ($password !== $confirmPassword) {
            $errors[] = "Les mots de passe ne correspondent pas.";
        } else {
            // Utiliser une transaction pour s'assurer que tout se passe bien
            $conn = connectDB();
            $conn->begin_transaction();
            
            try {
                // Mettre à jour le mot de passe
                $hashedPassword = hashPassword($password);
                $updateSql = "UPDATE users SET password = ? WHERE id = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->bind_param("si", $hashedPassword, $userId);
                $updateStmt->execute();
                
                // Marquer le token comme utilisé
                $markUsedSql = "UPDATE password_resets SET used = 1 WHERE id = ?";
                $markUsedStmt = $conn->prepare($markUsedSql);
                $markUsedStmt->bind_param("i", $resetId);
                $markUsedStmt->execute();
                
                // Journaliser l'activité
                $activitySql = "INSERT INTO user_activities (user_id, activity_type, description, ip_address, created_at) 
                                VALUES (?, 'password_reset', 'Mot de passe réinitialisé', ?, NOW())";
                $activityStmt = $conn->prepare($activitySql);
                $ipAddress = $_SERVER['REMOTE_ADDR'];
                $activityStmt->bind_param("is", $userId, $ipAddress);
                $activityStmt->execute();
                
                $conn->commit();
                $success = true;
            } catch (Exception $e) {
                $conn->rollback();
                $errors[] = "Une erreur est survenue lors de la réinitialisation du mot de passe. Veuillez réessayer.";
                error_log("Erreur de réinitialisation de mot de passe: " . $e->getMessage());
            }
            
            $conn->close();
        }
    }
}

// Afficher le header
require_once 'includes/header.php';
?>

<div class="flex justify-center">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="p-6">
                <h2 class="text-2xl font-bold text-center text-gray-800 mb-6">Réinitialiser le mot de passe</h2>
                
                <?php if ($success): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">
                        <h3 class="font-medium">Mot de passe réinitialisé avec succès !</h3>
                        <p class="mt-2">Votre mot de passe a été modifié. Vous pouvez maintenant vous connecter avec votre nouveau mot de passe.</p>
                    </div>
                    
                    <div class="text-center mt-6">
                        <a href="login.php" class="inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-sign-in-alt mr-2"></i> Se connecter
                        </a>
                    </div>
                <?php elseif ($validToken): ?>
                    <?php if (!empty($errors)): ?>
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                            <ul class="list-disc ml-5">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= escapeString($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <p class="text-gray-600 mb-6">Bonjour <strong><?= escapeString($username) ?></strong>, créez un nouveau mot de passe pour votre compte :</p>
                    
                    <form action="reset-password.php?token=<?= urlencode($token) ?>" method="POST" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        
                        <div class="mb-4">
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Nouveau mot de passe</label>
                            <input type="password" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                id="password" name="password" required minlength="8">
                            <p class="mt-1 text-sm text-gray-500">Le mot de passe doit contenir au moins 8 caractères.</p>
                        </div>
                        
                        <div class="mb-6">
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">Confirmer le mot de passe</label>
                            <input type="password" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <div>
                            <button type="submit" class="w-full flex justify-center items-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-key mr-2"></i> Réinitialiser le mot de passe
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6 rounded">
                        <h3 class="font-medium">Lien invalide ou expiré</h3>
                        <p class="mt-2">Le lien de réinitialisation de mot de passe que vous avez utilisé est invalide ou a expiré.</p>
                    </div>
                    
                    <div class="text-center mt-6">
                        <p class="mb-4">Vous pouvez demander un nouveau lien de réinitialisation.</p>
                        <a href="forgot-password.php" class="inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-redo mr-2"></i> Nouveau lien
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>