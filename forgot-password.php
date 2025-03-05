<?php
$pageTitle = "Mot de passe oublié";
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Rediriger si l'utilisateur est déjà connecté
if (isLoggedIn()) {
    redirect('index.php');
}

$errors = [];
$success = false;
$email = '';

// Traiter le formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier le token CSRF
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = "Erreur de sécurité. Veuillez réessayer.";
    } else {
        $email = trim($_POST['email'] ?? '');
        
        // Valider l'email
        if (empty($email)) {
            $errors[] = "L'adresse email est requise.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "L'adresse email n'est pas valide.";
        } else {
            // Vérifier si l'email existe
            $sql = "SELECT id, username FROM users WHERE email = ? AND status = ?";
            $result = executeQuery($sql, [$email, USER_STATUS_ACTIVE]);
            
            if ($result && $result->num_rows > 0) {
                $user = $result->fetch_assoc();
                
                // Générer un token unique
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', time() + 3600); // 1 heure
                
                // Stocker le token dans la base de données
                $resetSql = "INSERT INTO password_resets (user_id, token, created_at, expires_at) VALUES (?, ?, NOW(), ?)";
                $resetResult = executeQuery($resetSql, [$user['id'], $token, $expires]);
                
                if ($resetResult) {
                    // Construire le lien de réinitialisation
                    $resetLink = APP_URL . "/reset-password.php?token=" . $token;
                    
                    // En production, on enverrait un email
                    // Pour cet exemple, on affiche simplement le lien
                    $success = true;
                    
                    // Journaliser l'activité
                    logUserActivity($user['id'], 'password_reset_request', 'Demande de réinitialisation de mot de passe');
                } else {
                    $errors[] = "Une erreur est survenue. Veuillez réessayer plus tard.";
                }
            } else {
                // Ne pas révéler si l'email existe ou non pour des raisons de sécurité
                $success = true;
            }
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
                <h2 class="text-2xl font-bold text-center text-gray-800 mb-6">Mot de passe oublié</h2>
                
                <?php if ($success): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">
                        <h3 class="font-medium">Instructions envoyées !</h3>
                        <p class="mt-2">Si l'adresse email est associée à un compte, vous recevrez un email avec les instructions pour réinitialiser votre mot de passe.</p>
                        
                        <?php if (APP_ENV === 'development'): ?>
                            <div class="mt-4 p-3 bg-gray-100 rounded">
                                <p class="text-gray-700 text-sm font-medium">Lien de réinitialisation (affiché uniquement en mode développement) :</p>
                                <a href="<?= isset($resetLink) ? $resetLink : '#' ?>" class="text-blue-600 hover:text-blue-800 break-all text-sm"><?= isset($resetLink) ? $resetLink : 'Lien non généré' ?></a>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="text-center mt-6">
                        <a href="login.php" class="text-blue-600 hover:text-blue-800">Retour à la page de connexion</a>
                    </div>
                <?php else: ?>
                    <?php if (!empty($errors)): ?>
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                            <ul class="list-disc ml-5">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= escapeString($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <p class="text-gray-600 mb-6">Entrez votre adresse email et nous vous enverrons un lien pour réinitialiser votre mot de passe.</p>
                    
                    <form action="forgot-password.php" method="POST" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        
                        <div class="mb-6">
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Adresse email</label>
                            <input type="email" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                id="email" name="email" value="<?= escapeString($email) ?>" required>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <button type="submit" class="w-full flex justify-center items-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-envelope mr-2"></i> Envoyer les instructions
                            </button>
                        </div>
                    </form>
                    
                    <div class="text-center mt-6">
                        <a href="login.php" class="text-blue-600 hover:text-blue-800">Retour à la page de connexion</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>