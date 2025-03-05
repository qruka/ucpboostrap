<?php
$pageTitle = "Connexion";
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Rediriger avant d'inclure le header qui génère du HTML
if (isLoggedIn()) {
    redirect('index.php');
}

// Maintenant on peut inclure le header qui va générer du HTML
require_once 'includes/header.php';


$errors = [];
$username = '';

// Traiter le formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
            
            if ($user) {
                // Stocker l'ID utilisateur en session
                $_SESSION['user_id'] = $user['id'];
                
                // Gérer "Se souvenir de moi" (à implémenter si nécessaire)
                if ($rememberMe) {
                    // Cette fonctionnalité nécessiterait d'autres tables et mécanismes
                    // Non implémentée dans cet exemple pour simplicité
                }
                
                $_SESSION['flash_message'] = "Connexion réussie !";
                $_SESSION['flash_type'] = "success";
                
                redirect('index.php');
            } else {
                $errors[] = "Nom d'utilisateur ou mot de passe incorrect.";
            }
        }
    }
}
?>

<div class="flex justify-center">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="p-6">
                <h2 class="text-2xl font-bold text-center text-gray-800 mb-6">Connexion</h2>
                
                <?php if (!empty($errors)): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                        <ul class="list-disc ml-5">
                            <?php foreach ($errors as $error): ?>
                                <li><?= escapeString($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form action="login.php" method="POST" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    
                    <div class="mb-4">
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-2">Nom d'utilisateur</label>
                        <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                               id="username" name="username" value="<?= escapeString($username) ?>" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Mot de passe</label>
                        <input type="password" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                               id="password" name="password" required>
                    </div>
                    
                    <div class="mb-6">
                        <div class="flex items-center">
                            <input type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" 
                                   id="remember_me" name="remember_me">
                            <label for="remember_me" class="ml-2 block text-sm text-gray-700">
                                Se souvenir de moi
                            </label>
                        </div>
                    </div>
                    
                    <div>
                        <button type="submit" class="w-full flex justify-center items-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-sign-in-alt mr-2"></i> Se connecter
                        </button>
                    </div>
                </form>
                
                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-600">
                        Vous n'avez pas de compte ? 
                        <a href="register.php" class="font-medium text-blue-600 hover:text-blue-500">
                            Inscrivez-vous
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>