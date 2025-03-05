<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/login-security.php';

// Si le site n'est pas en maintenance ou si l'utilisateur est admin, rediriger vers l'accueil
if (!isMaintenanceMode() || (isLoggedIn() && isAdmin())) {
    redirect('index.php');
}

// Traiter le formulaire de connexion admin
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier le token CSRF
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = "Erreur de sécurité. Veuillez réessayer.";
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Valider les champs
        if (empty($username) || empty($password)) {
            $errors[] = "Veuillez remplir tous les champs.";
        } else {
            $user = authenticateUser($username, $password);
            
            if ($user && !isset($user['banned']) && $user['user_level'] >= USER_LEVEL_ADMIN) {
                // Connecter l'administrateur
                $_SESSION['user_id'] = $user['id'];
                
                // Rediriger vers l'admin
                redirect('admin/index.php');
            } else {
                $errors[] = "Identifiants incorrects ou privilèges insuffisants.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background-color: #f3f4f6;
        }
        .maintenance-container {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 2rem;
        }
        .maintenance-card {
            max-width: 36rem;
            width: 100%;
            background-color: white;
            border-radius: 0.75rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            overflow: hidden;
        }
        .gear-animation {
            animation: spin 10s linear infinite;
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="maintenance-container">
        <div class="maintenance-card">
            <div class="bg-blue-600 text-white p-6 text-center">
                <div class="flex justify-center mb-4">
                    <div class="relative">
                        <i class="fas fa-cog text-7xl gear-animation"></i>
                        <i class="fas fa-wrench text-2xl absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2"></i>
                    </div>
                </div>
                <h1 class="text-3xl font-bold mb-2"><?= APP_NAME ?></h1>
                <p class="text-xl">Site en maintenance</p>
            </div>
            
            <div class="p-6">
                <div class="text-center mb-8">
                    <h2 class="text-2xl font-semibold text-gray-800 mb-4">Nous revenons bientôt</h2>
                    <p class="text-gray-600">Notre site est actuellement en maintenance pour améliorer votre expérience. Nous serons de retour dès que possible.</p>
                </div>
                
                <div class="border-t border-gray-200 pt-6">
                    <div class="text-center mb-4">
                        <p class="text-gray-700 font-medium">Administration</p>
                    </div>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                            <ul class="list-disc ml-5">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= escapeString($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form action="maintenance.php" method="POST" class="space-y-4">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        
                        <div>
                            <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Nom d'utilisateur</label>
                            <input type="text" id="username" name="username" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Mot de passe</label>
                            <input type="password" id="password" name="password" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        
                        <div>
                            <button type="submit" class="w-full flex justify-center items-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-sign-in-alt mr-2"></i> Connexion administrateur
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="mt-8 text-center text-gray-500 text-sm">
            <p>&copy; <?= date('Y') ?> - <?= APP_NAME ?></p>
        </div>
    </div>
</body>
</html>