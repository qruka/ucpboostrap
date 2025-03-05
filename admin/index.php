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

// Utilisateurs récents
$recentUsersQuery = "SELECT id, username, email, created_at, user_level FROM users ORDER BY created_at DESC LIMIT 5";
$recentUsersResult = $conn->query($recentUsersQuery);
$recentUsers = [];

while ($row = $recentUsersResult->fetch_assoc()) {
    $recentUsers[] = $row;
}

$conn->close();
?>

<div class="mb-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-bold flex items-center text-gray-800">
                <i class="fas fa-tachometer-alt mr-2 text-blue-600"></i> Tableau de bord
            </h1>
            <p class="text-gray-500 mt-1">Bienvenue dans le panneau d'administration du site</p>
        </div>
    </div>
</div>

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
        </div>
    </div>
    
    <div>
        <div class="stats-card warning rounded-lg shadow-md">
            <i class="fas fa-user-graduate text-4xl opacity-50 mb-2"></i>
            <h2 class="text-4xl font-bold my-2"><?= $levelCounts[USER_LEVEL_MODERATOR] ?? 0 ?></h2>
            <p class="text-lg opacity-80">Modérateurs</p>
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

<div class="mt-8">
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200">
            <h5 class="font-semibold text-gray-700 m-0">Utilisateurs récents</h5>
            <a href="users.php" class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 transition-colors duration-200">
                <i class="fas fa-users mr-2"></i> Gérer tous les utilisateurs
            </a>
        </div>
        <div class="p-6">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom d'utilisateur</th>
                            <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Niveau</th>
                            <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date d'inscription</th>
                            <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($recentUsers as $user): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900"><?= $user['id'] ?></td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900"><?= escapeString($user['username']) ?></td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500"><?= escapeString($user['email']) ?></td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="user-level user-level-<?= $user['user_level'] ?>">
                                        <?= getUserLevelText($user['user_level']) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500"><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium">
                                    <a href="user-edit.php?id=<?= $user['id'] ?>" class="inline-flex items-center p-1.5 bg-blue-100 text-blue-600 rounded-md hover:bg-blue-200 transition-colors duration-200">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($recentUsers)): ?>
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-gray-500">Aucun utilisateur trouvé</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/admin-footer.php'; ?>