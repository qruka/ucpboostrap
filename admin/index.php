<?php
$pageTitle = "Tableau de bord";
require_once '../includes/admin-header.php';

// Récupérer des statistiques
$conn = connectDB();

// Nombre total d'utilisateurs
$userCountQuery = "SELECT COUNT(*) as total FROM users";
$userCountResult = $conn->query($userCountQuery);
$userCount = $userCountResult->fetch_assoc()['total'];

// Utilisateurs par niveau
$levelCountQuery = "SELECT user_level, COUNT(*) as count FROM users GROUP BY user_level";
$levelCountResult = $conn->query($levelCountQuery);
$levelCounts = [];

while ($row = $levelCountResult->fetch_assoc()) {
    $levelCounts[$row['user_level']] = $row['count'];
}

// Utilisateurs bannis
$bannedCountQuery = "SELECT COUNT(*) as total FROM users WHERE status = ? OR (status = ? AND ban_expires > NOW())";
$stmt = $conn->prepare($bannedCountQuery);
$banned = USER_STATUS_BANNED;
$suspended = USER_STATUS_SUSPENDED;
$stmt->bind_param("ss", $banned, $suspended);
$stmt->execute();
$bannedCount = $stmt->get_result()->fetch_assoc()['total'];

// Récupérer les utilisateurs récents
$recentUsersQuery = "SELECT id, username, email, created_at, user_level FROM users ORDER BY created_at DESC LIMIT 5";
$recentUsersResult = $conn->query($recentUsersQuery);
$recentUsers = [];

while ($row = $recentUsersResult->fetch_assoc()) {
    $recentUsers[] = $row;
}

$conn->close();
?>

<!-- Titre de la page -->
<div class="mb-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-bold flex items-center text-gray-800 dark:text-white">
                <i class="fas fa-tachometer-alt mr-2 text-blue-600 dark:text-blue-400"></i> Tableau de bord
            </h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Bienvenue dans le panneau d'administration du site</p>
        </div>
        <div class="mt-3 md:mt-0 flex flex-wrap gap-2">
            <a href="../index.php" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-home mr-2"></i> Retour au site
            </a>
        </div>
    </div>
</div>

<!-- Fonctions d'administration -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Gestion des utilisateurs -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 flex flex-col">
        <div class="flex-grow">
            <div class="flex items-center mb-4">
                <div class="h-10 w-10 bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400 rounded-full flex items-center justify-center">
                    <i class="fas fa-users"></i>
                </div>
                <h3 class="ml-3 text-lg font-medium text-gray-900 dark:text-white">Utilisateurs</h3>
            </div>
            <p class="text-gray-600 dark:text-gray-400 mb-4">Gérer les comptes utilisateurs, les permissions et les accès.</p>
        </div>
        <a href="users.php" class="inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <i class="fas fa-user-cog mr-2"></i> Gérer les utilisateurs
        </a>
    </div>
    
    <!-- Configuration du site -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 flex flex-col">
        <div class="flex-grow">
            <div class="flex items-center mb-4">
                <div class="h-10 w-10 bg-purple-100 dark:bg-purple-900 text-purple-600 dark:text-purple-400 rounded-full flex items-center justify-center">
                    <i class="fas fa-cog"></i>
                </div>
                <h3 class="ml-3 text-lg font-medium text-gray-900 dark:text-white">Configuration</h3>
            </div>
            <p class="text-gray-600 dark:text-gray-400 mb-4">Modifier les paramètres du site, la maintenance et les options de sécurité.</p>
        </div>
        <a href="site-settings.php" class="inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
            <i class="fas fa-sliders-h mr-2"></i> Paramètres du site
        </a>
    </div>
    
    <!-- Journal d'activité -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 flex flex-col">
        <div class="flex-grow">
            <div class="flex items-center mb-4">
                <div class="h-10 w-10 bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-400 rounded-full flex items-center justify-center">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <h3 class="ml-3 text-lg font-medium text-gray-900 dark:text-white">Activités</h3>
            </div>
            <p class="text-gray-600 dark:text-gray-400 mb-4">Consulter l'historique des activités et des connexions des utilisateurs.</p>
        </div>
        <a href="activity-log.php" class="inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
            <i class="fas fa-history mr-2"></i> Voir les activités
        </a>
    </div>
    
    <!-- Mode Maintenance -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 flex flex-col">
        <div class="flex-grow">
            <div class="flex items-center mb-4">
                <div class="h-10 w-10 bg-yellow-100 dark:bg-yellow-900 text-yellow-600 dark:text-yellow-400 rounded-full flex items-center justify-center">
                    <i class="fas fa-tools"></i>
                </div>
                <h3 class="ml-3 text-lg font-medium text-gray-900 dark:text-white">Maintenance</h3>
            </div>
            <p class="text-gray-600 dark:text-gray-400 mb-4">
                <?php if (isMaintenanceMode()): ?>
                    <span class="text-yellow-600 dark:text-yellow-400 font-medium">Le mode maintenance est actuellement activé</span>
                <?php else: ?>
                    Activer/désactiver le mode maintenance pour limiter l'accès au site.
                <?php endif; ?>
            </p>
        </div>
        <a href="site-settings.php#maintenance" class="inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-yellow-600 hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">
            <i class="fas fa-wrench mr-2"></i> Gérer la maintenance
        </a>
    </div>
</div>

<!-- Statistiques rapides -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div>
        <div class="stats-card primary rounded-lg shadow-md">
            <i class="fas fa-users text-4xl opacity-50 mb-2"></i>
            <h2 class="text-4xl font-bold my-2"><?= $userCount ?></h2>
            <p class="text-lg opacity-80">Utilisateurs inscrits</p>
        </div>
    </div>
    
    <div>
        <div class="stats-card success rounded-lg shadow-md">
            <i class="fas fa-user-shield text-4xl opacity-50 mb-2"></i>
            <h2 class="text-4xl font-bold my-2"><?= $levelCounts[USER_LEVEL_ADMIN] ?? 0 ?></h2>
            <p class="text-lg opacity-80">Administrateurs</p>
            <span id="adminsCount" class="hidden"><?= $levelCounts[USER_LEVEL_ADMIN] ?? 0 ?></span>
            <span id="superAdminsCount" class="hidden"><?= $levelCounts[USER_LEVEL_SUPERADMIN] ?? 0 ?></span>
        </div>
    </div>
    
    <div>
        <div class="stats-card warning rounded-lg shadow-md">
            <i class="fas fa-user-graduate text-4xl opacity-50 mb-2"></i>
            <h2 class="text-4xl font-bold my-2"><?= $levelCounts[USER_LEVEL_MODERATOR] ?? 0 ?></h2>
            <p class="text-lg opacity-80">Modérateurs</p>
            <span id="moderatorsCount" class="hidden"><?= $levelCounts[USER_LEVEL_MODERATOR] ?? 0 ?></span>
            <span id="regularUsersCount" class="hidden"><?= $levelCounts[USER_LEVEL_REGULAR] ?? 0 ?></span>
        </div>
    </div>
    
    <div>
        <div class="stats-card danger rounded-lg shadow-md">
            <i class="fas fa-user-lock text-4xl opacity-50 mb-2"></i>
            <h2 class="text-4xl font-bold my-2"><?= $bannedCount ?></h2>
            <p class="text-lg opacity-80">Utilisateurs bannis</p>
        </div>
    </div>
</div>

<!-- Utilisateurs récents -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden mb-6">
    <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h5 class="font-semibold text-gray-700 dark:text-gray-200 m-0">Utilisateurs récents</h5>
        <a href="users.php" class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 transition-colors duration-200">
            <i class="fas fa-users mr-2"></i> Voir tous
        </a>
    </div>
    <div class="p-6">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead>
                    <tr>
                        <th class="px-4 py-3 bg-gray-50 dark:bg-gray-700 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Utilisateur</th>
                        <th class="px-4 py-3 bg-gray-50 dark:bg-gray-700 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Niveau</th>
                        <th class="px-4 py-3 bg-gray-50 dark:bg-gray-700 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Date d'inscription</th>
                        <th class="px-4 py-3 bg-gray-50 dark:bg-gray-700 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <?php foreach ($recentUsers as $user): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10 bg-gray-200 dark:bg-gray-700 rounded-full flex items-center justify-center">
                                        <i class="fas fa-user text-gray-500 dark:text-gray-400"></i>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white"><?= escapeString($user['username']) ?></div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400"><?= escapeString($user['email']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="user-level user-level-<?= $user['user_level'] ?>">
                                    <?= getUserLevelText($user['user_level']) ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                <?= date('d/m/Y H:i', strtotime($user['created_at'])) ?>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium">
                                <a href="user-edit.php?id=<?= $user['id'] ?>" class="inline-flex items-center p-1.5 bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400 rounded-md hover:bg-blue-200 dark:hover:bg-blue-800 transition-colors duration-200">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($recentUsers)): ?>
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">Aucun utilisateur trouvé</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/admin-footer.php'; ?>