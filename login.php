<?php
$pageTitle = "Connexion";
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/login-security.php';

// Rediriger si le site est en maintenance (sauf pour les administrateurs)
if (isMaintenanceMode() && !isAdmin()) {
    redirect('maintenance.php');
}

// Rediriger si déjà connecté
if (isLoggedIn()) {
    redirect('index.php');
}

// Vérifier si l'IP est bloquée
$ipAddress = $_SERVER['REMOTE_ADDR'];
$ipBlocked = isIPBlocked($ipAddress);

$errors = [];
$username = '';
$attemptsRemaining = 0;

// Calculer les tentatives restantes
if (!$ipBlocked) {
    $recentAttempts = getRecentLoginAttempts($ipAddress);
    $maxAttempts = getSetting('max_login_attempts', 5);
    $attemptsRemaining = max(0, $maxAttempts - $recentAttempts);
}

// Traiter le formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$ipBlocked) {
    // Vérifier le token CSRF
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = "Erreur de sécurité. Veuillez réessayer.";
    } else {
        // Récupérer les données
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $rememberMe = isset($_POST['remember_me']);
        
        // Valider les champs
        if (empty($username)) {
            $errors[] = "Le nom d'utilisateur est requis.";
        }
        
        if (empty($password)) {
            $errors[] = "Le mot de passe est requis.";
        }
        
        // Si aucune erreur, tenter l'authentification
        if (empty($errors)) {
            $user = authenticateUser($username, $password);
            
            if ($user && !isset($user['banned'])) {
                // Stocker l'ID utilisateur en session
                $_SESSION['user_id'] = $user['id'];
                
                // Mettre à jour la dernière connexion
                $updateSql = "UPDATE users SET last_login = NOW() WHERE id = ?";
                executeQuery($updateSql, [$user['id']]);
                
                // Gérer "Se souvenir de moi"
                if ($rememberMe) {
                    // Générer un token unique
                    $selector = bin2hex(random_bytes(8));
                    $validator = bin2hex(random_bytes(32));
                    
                    // Stocker le hash du token dans la base de données
                    $hashedValidator = password_hash($validator, PASSWORD_DEFAULT);
                    $expires = date('Y-m-d H:i:s', time() + 30 * 24 * 60 * 60); // 30 jours
                    
                    $cookieSql = "INSERT INTO user_remember_tokens (user_id, selector, token, expires) VALUES (?, ?, ?, ?)";
                    executeQuery($cookieSql, [$user['id'], $selector, $hashedValidator, $expires]);
                    
                    // Créer le cookie
                    $cookieValue = $selector . ':' . $validator;
                    setcookie(
                        'remember_me',
                        $cookieValue,
                        time() + 30 * 24 * 60 * 60, // 30 jours
                        '/',
                        '',
                        true, // Secure
                        true  // HttpOnly
                    );
                }
                
                setFlashMessage("Connexion réussie !", "success");
                
                // Rediriger vers la page demandée ou la page d'accueil
                $redirect = isset($_SESSION['redirect_after_login']) 
                    ? $_SESSION['redirect_after_login'] 
                    : 'index.php';
                unset($_SESSION['redirect_after_login']);
                
                redirect($redirect);
            } elseif (isset($user['banned']) && $user['banned']) {
                $errors[] = $user['message'] ?? "Votre compte a été banni. Veuillez contacter l'administrateur.";
            } else {
                $errors[] = "Nom d'utilisateur ou mot de passe incorrect.";
                
                // Mettre à jour le nombre de tentatives restantes
                $recentAttempts = getRecentLoginAttempts($ipAddress);
                $attemptsRemaining = max(0, $maxAttempts - $recentAttempts);
            }
        }
    }
}

require_once 'includes/header.php';
?>

<div class="flex justify-center py-12">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-lg shadow-xl overflow-hidden">
            <div class="px-6 py-8">
                <div class="text-center mb-8">
                    <h2 class="text-3xl font-bold text-gray-800">Connexion</h2>
                    <p class="text-gray-600 mt-2">Accédez à votre compte</p>
                </div>
                
                <?php if ($ipBlocked): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-circle text-red-500 mt-0.5"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-red-800 font-medium">Accès temporairement bloqué</h3>
                                <p class="mt-2">Trop de tentatives de connexion échouées. Veuillez réessayer dans <?= $ipBlocked['minutes_remaining'] ?> minute<?= $ipBlocked['minutes_remaining'] > 1 ? 's' : '' ?>.</p>
                            </div>
                        </div>
                    </div>
                <?php elseif (!empty($errors)): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-circle text-red-500 mt-0.5"></i>
                            </div>
                            <div class="ml-3">
                                <ul class="list-disc space-y-1 pl-5">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?= escapeString($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                
                                <?php if ($attemptsRemaining > 0): ?>
                                    <p class="mt-2 text-sm">Tentatives restantes: <?= $attemptsRemaining ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (!$ipBlocked): ?>
                    <form action="login.php" method="POST" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        
                        <div class="mb-6">
                            <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Nom d'utilisateur</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-user text-gray-400"></i>
                                </div>
                                <input type="text" id="username" name="username" value="<?= escapeString($username) ?>" required
                                    class="form-control pl-10" placeholder="Entrez votre nom d'utilisateur">
                            </div>
                        </div>
                        
                        <div class="mb-6">
                            <div class="flex items-center justify-between mb-1">
                                <label for="password" class="block text-sm font-medium text-gray-700">Mot de passe</label>
                                <a href="forgot-password.php" class="text-sm text-blue-600 hover:text-blue-800">Mot de passe oublié ?</a>
                            </div>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-lock text-gray-400"></i>
                                </div>
                                <input type="password" id="password" name="password" required
                                    class="form-control pl-10" placeholder="Entrez votre mot de passe">
                            </div>
                        </div>
                        
                        <div class="flex items-center mb-6">
                            <input type="checkbox" id="remember_me" name="remember_me"
                                  class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="remember_me" class="ml-2 block text-sm text-gray-700">
                                Se souvenir de moi
                            </label>
                        </div>
                        
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                            <i class="fas fa-sign-in-alt mr-2"></i> Se connecter
                        </button>
                    </form>
                <?php endif; ?>
                
                <?php if (isRegistrationEnabled()): ?>
                    <div class="mt-8 text-center">
                        <p class="text-gray-600">
                            Vous n'avez pas de compte ? 
                            <a href="register.php" class="font-medium text-blue-600 hover:text-blue-500">
                                Inscrivez-vous
                            </a>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>