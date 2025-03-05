<?php
$pageTitle = "Accueil";
require_once 'includes/header.php';

// Récupérer les informations de l'utilisateur connecté
$user = null;
if (isLoggedIn()) {
    $user = getUserById($_SESSION['user_id']);
}

// Récupérer les news récentes
$conn = connectDB();
$newsQuery = "SELECT n.*, u.username AS author_name 
              FROM news n 
              JOIN users u ON n.created_by = u.id 
              ORDER BY n.created_at DESC";
$newsResult = $conn->query($newsQuery);
$news = [];

if ($newsResult) {
    while ($row = $newsResult->fetch_assoc()) {
        $news[] = $row;
    }
}
$conn->close();
?>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    <div class="lg:col-span-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
            <div class="p-6">
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white mb-4">Bienvenue sur <?= APP_NAME ?></h1>
                
                <?php if ($user): ?>
                    <div class="bg-green-100 dark:bg-green-900 border-l-4 border-green-500 dark:border-green-600 text-green-700 dark:text-green-200 p-4 rounded mb-6">
                        <h4 class="font-bold text-lg">Bonjour, <?= escapeString($user['username']) ?> !</h4>
                        <p class="mt-2">Vous êtes connecté en tant que <?= getUserLevelText($user['user_level']) ?></p>
                    </div>
                    
                    <?php if (isAdmin()): ?>
                        <div class="flex justify-end mb-6">
                            <a href="admin/news-create.php" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                                <i class="fas fa-plus mr-2"></i> Créer une nouvelle actualité
                            </a>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-lg text-gray-600 dark:text-gray-400 mb-6">Pour accéder à toutes les fonctionnalités, veuillez vous connecter ou créer un compte.</p>
                    
                    <div class="flex flex-wrap gap-3 mt-6 mb-6">
                        <a href="login.php" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                            <i class="fas fa-sign-in-alt mr-2"></i> Connexion
                        </a>
                        <?php if (isRegistrationEnabled()): ?>
                            <a href="register.php" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-blue-600 dark:text-blue-400 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                                <i class="fas fa-user-plus mr-2"></i> Inscription
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Section des actualités -->
                <div class="mt-6">
                    <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-4 flex items-center">
                        <i class="fas fa-newspaper text-blue-600 dark:text-blue-400 mr-2"></i> Actualités récentes
                    </h2>
                    
                    <?php if (empty($news)): ?>
                        <div class="text-center py-8 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <i class="fas fa-inbox text-gray-400 dark:text-gray-500 text-4xl mb-2"></i>
                            <p class="text-gray-600 dark:text-gray-400">Aucune actualité n'a été publiée pour le moment.</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-6">
                            <?php foreach ($news as $item): ?>
                                <div class="relative rounded-lg overflow-hidden shadow-md border border-gray-200 dark:border-gray-700">
                                    <?php if (!empty($item['image_path']) && file_exists($item['image_path'])): ?>
                                        <div class="absolute inset-0 z-0">
                                            <img src="<?= $item['image_path'] ?>" alt="<?= escapeString($item['title']) ?>" class="w-full h-full object-cover opacity-30">
                                            <div class="absolute inset-0 bg-gradient-to-r from-black via-transparent to-transparent opacity-60"></div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="relative z-10 p-6 <?= !empty($item['image_path']) && file_exists($item['image_path']) ? 'text-white' : 'text-gray-800 dark:text-white' ?>">
                                        <div class="flex justify-between items-start mb-2">
                                            <h3 class="text-xl font-bold <?= !empty($item['image_path']) && file_exists($item['image_path']) ? 'text-white' : 'text-gray-800 dark:text-white' ?>">
                                                <?= escapeString($item['title']) ?>
                                            </h3>
                                            
                                            <?php if (isAdmin()): ?>
                                                <div class="flex space-x-2">
                                                    <a href="admin/news-edit.php?id=<?= $item['id'] ?>" class="p-1 bg-blue-600 rounded-full text-white hover:bg-blue-700 transition-colors">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="admin/news-delete.php?id=<?= $item['id'] ?>" class="p-1 bg-red-600 rounded-full text-white hover:bg-red-700 transition-colors" 
                                                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette actualité ?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="prose dark:prose-invert max-w-none mb-4 <?= !empty($item['image_path']) && file_exists($item['image_path']) ? 'text-gray-200' : 'text-gray-600 dark:text-gray-400' ?>">
                                            <?= nl2br(escapeString($item['content'])) ?>
                                        </div>
                                        
                                        <div class="flex items-center text-sm <?= !empty($item['image_path']) && file_exists($item['image_path']) ? 'text-gray-200' : 'text-gray-500 dark:text-gray-400' ?>">
                                            <i class="fas fa-user mr-1"></i>
                                            <span class="font-medium mr-3"><?= escapeString($item['author_name']) ?></span>
                                            <i class="fas fa-calendar-alt mr-1"></i>
                                            <span><?= date('d/m/Y à H:i', strtotime($item['created_at'])) ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="lg:col-span-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
            <div class="p-6">
                <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-4">Informations</h2>
                
                <div class="space-y-4">
                    <div class="bg-blue-50 dark:bg-blue-900/30 rounded-md p-4 border-l-4 border-blue-500 dark:border-blue-600">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-info-circle text-blue-600 dark:text-blue-400"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-blue-800 dark:text-blue-300">À propos de ce site</h3>
                                <div class="mt-2 text-sm text-blue-700 dark:text-blue-200">
                                    <p>Un système complet de gestion d'utilisateurs avec panneau d'administration.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                
                    <h5 class="font-semibold text-gray-700 dark:text-gray-300 mt-6 mb-3">Niveaux d'utilisateurs</h5>
                    <div class="space-y-2 rounded-md border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="p-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                            <span class="user-level user-level-1">Utilisateur</span>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Accès aux fonctionnalités de base</p>
                        </div>
                        <div class="p-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                            <span class="user-level user-level-2">Modérateur</span>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Accès au tableau de bord d'administration</p>
                        </div>
                        <div class="p-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                            <span class="user-level user-level-3">Administrateur</span>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Gestion des utilisateurs et des contenus</p>
                        </div>
                        <div class="p-3 bg-gray-50 dark:bg-gray-700">
                            <span class="user-level user-level-4">Super Admin</span>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Accès complet au système</p>
                        </div>
                    </div>
                    
                    <?php if (isLoggedIn() && isModerator()): ?>
                        <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <a href="admin/index.php" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-tachometer-alt mr-2"></i> Accéder au panneau d'administration
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>