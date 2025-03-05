<?php
$pageTitle = "Accueil";
require_once 'includes/header.php';

// Récupérer les informations de l'utilisateur connecté
$user = null;
if (isLoggedIn()) {
    $user = getUserById($_SESSION['user_id']);
}
?>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    <div class="lg:col-span-8">
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-6">
                <h1 class="text-2xl font-bold text-gray-800 mb-4">Bienvenue sur <?= APP_NAME ?></h1>
                
                <?php if ($user): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded mb-6">
                        <h4 class="font-bold text-lg">Bonjour, <?= escapeString($user['username']) ?> !</h4>
                        <p class="mt-2">Vous êtes connecté avec l'adresse email: <?= escapeString($user['email']) ?></p>
                        <p class="mt-1">Niveau d'accès: <?= getUserLevelText($user['user_level']) ?></p>
                    </div>
                    
                    <div class="flex flex-wrap gap-3 mt-6">
                        <a href="settings.php" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                            <i class="fas fa-cog mr-2"></i> Gérer mes paramètres
                        </a>
                        
                        <?php if (isModerator()): ?>
                            <!-- BOUTON D'ADMINISTRATION BIEN VISIBLE -->
                            <a href="admin/index.php" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-200">
                                <i class="fas fa-lock mr-2"></i> Administration
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <p class="text-lg text-gray-600 mb-6">Pour accéder à toutes les fonctionnalités, veuillez vous connecter ou créer un compte.</p>
                    
                    <div class="flex flex-wrap gap-3 mt-6">
                        <a href="login.php" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                            <i class="fas fa-sign-in-alt mr-2"></i> Connexion
                        </a>
                        <a href="register.php" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-blue-600 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                            <i class="fas fa-user-plus mr-2"></i> Inscription
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="lg:col-span-4">
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">À propos de ce site</h2>
                <p class="text-gray-600">Ce site web dispose de :</p>
                <ul class="mt-2 ml-5 list-disc text-gray-600">
                    <li class="mb-1">Création de compte utilisateur</li>
                    <li class="mb-1">Connexion sécurisée</li>
                    <li class="mb-1">Gestion des paramètres</li>
                    <li class="mb-1">Système d'administration à 4 niveaux</li>
                </ul>
                
                <h5 class="font-semibold text-gray-700 mt-6 mb-3">Niveaux d'utilisateurs</h5>
                <div class="space-y-2 rounded-md border border-gray-200 overflow-hidden">
                    <div class="p-3 border-b border-gray-200 bg-gray-50">
                        <span class="user-level user-level-1">Utilisateur</span>
                        <p class="text-sm text-gray-600 mt-1">Accès aux fonctionnalités de base</p>
                    </div>
                    <div class="p-3 border-b border-gray-200 bg-gray-50">
                        <span class="user-level user-level-2">Modérateur</span>
                        <p class="text-sm text-gray-600 mt-1">Accès au tableau de bord d'administration</p>
                    </div>
                    <div class="p-3 border-b border-gray-200 bg-gray-50">
                        <span class="user-level user-level-3">Administrateur</span>
                        <p class="text-sm text-gray-600 mt-1">Gestion des utilisateurs</p>
                    </div>
                    <div class="p-3 bg-gray-50">
                        <span class="user-level user-level-4">Super Admin</span>
                        <p class="text-sm text-gray-600 mt-1">Accès complet au système</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>