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

// Statistiques des connexions récentes
$loginStatsQuery = "SELECT 
                        DATE(login_time) as login_date, 
                        COUNT(*) as total_logins,
                        SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successful_logins,
                        SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_logins
                    FROM user_logins 
                    WHERE login_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                    GROUP BY DATE(login_time)
                    ORDER BY login_date ASC";
$loginStatsResult = $conn->query($loginStatsQuery);
$loginStats = [];

while ($row = $loginStatsResult->fetch_assoc()) {
    $loginStats[] = $row;
}

// Activités récentes
$recentActivitiesQuery = "SELECT ua.activity_type, ua.description, ua.created_at, u.username 
                          FROM user_activities ua
                          JOIN users u ON ua.user_id = u.id
                          ORDER BY ua.created_at DESC
                          LIMIT 10";
$recentActivitiesResult = $conn->query($recentActivitiesQuery);
$recentActivities = [];

while ($row = $recentActivitiesResult->fetch_assoc()) {
    $recentActivities[] = $row;
}

$conn->close();
?>

<!-- Titre de la page -->
<div class="mb-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-bold flex items-center text-gray-800">
                <i class="fas fa-tachometer-alt mr-2 text-blue-600"></i> Tableau de bord
            </h1>
            <p class="text-gray-500 mt-1">Bienvenue dans le panneau d'administration du site</p>
        </div>
        <div class="mt-3 md:mt-0 flex flex-wrap gap-2">
            <a href="../index.php" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-home mr-2"></i> Retour au site
            </a>
            <?php if (isAdmin()): ?>
            <a href="users.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-users mr-2"></i> Gérer les utilisateurs
            </a>
            <?php endif; ?>
        </div>
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

<!-- Graphiques et statistiques -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Graphique des inscriptions -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200">
            <h5 class="font-semibold text-gray-700 m-0">Activité des 7 derniers jours</h5>
        </div>
        <div class="p-6">
            <canvas id="registrationsChart" height="300"></canvas>
        </div>
    </div>
    
    <!-- Répartition des utilisateurs -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200">
            <h5 class="font-semibold text-gray-700 m-0">Répartition des utilisateurs</h5>
        </div>
        <div class="p-6">
            <canvas id="userLevelsChart" height="300"></canvas>
        </div>
    </div>
</div>

<!-- Section des utilisateurs récents et activités -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Utilisateurs récents -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200">
            <h5 class="font-semibold text-gray-700 m-0">Utilisateurs récents</h5>
            <a href="users.php" class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 transition-colors duration-200">
                <i class="fas fa-users mr-2"></i> Voir tous
            </a>
        </div>
        <div class="p-6">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Utilisateur</th>
                            <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Niveau</th>
                            <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date d'inscription</th>
                            <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($recentUsers as $user): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 bg-gray-200 rounded-full flex items-center justify-center">
                                            <i class="fas fa-user text-gray-500"></i>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900"><?= escapeString($user['username']) ?></div>
                                            <div class="text-sm text-gray-500"><?= escapeString($user['email']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="user-level user-level-<?= $user['user_level'] ?>">
                                        <?= getUserLevelText($user['user_level']) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                    <?= date('d/m/Y H:i', strtotime($user['created_at'])) ?>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium">
                                    <a href="user-edit.php?id=<?= $user['id'] ?>" class="inline-flex items-center p-1.5 bg-blue-100 text-blue-600 rounded-md hover:bg-blue-200 transition-colors duration-200">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($recentUsers)): ?>
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-gray-500">Aucun utilisateur trouvé</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Activités récentes -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200">
            <h5 class="font-semibold text-gray-700 m-0">Activités récentes</h5>
        </div>
        <div class="p-6">
            <div class="flow-root">
                <ul role="list" class="-mb-8">
                    <?php if (empty($recentActivities)): ?>
                        <li class="text-center text-gray-500 py-8">Aucune activité récente</li>
                    <?php else: ?>
                        <?php foreach ($recentActivities as $index => $activity): ?>
                            <li>
                                <div class="relative pb-8">
                                    <?php if ($index < count($recentActivities) - 1): ?>
                                        <span class="absolute top-5 left-5 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                    <?php endif; ?>
                                    <div class="relative flex items-start space-x-3">
                                        <div class="relative">
                                            <?php
                                            $iconClass = 'bg-blue-500';
                                            $icon = 'fas fa-info';
                                            
                                            switch ($activity['activity_type']) {
                                                case 'login':
                                                    $iconClass = 'bg-green-500';
                                                    $icon = 'fas fa-sign-in-alt';
                                                    break;
                                                case 'register':
                                                    $iconClass = 'bg-blue-500';
                                                    $icon = 'fas fa-user-plus';
                                                    break;
                                                case 'ban':
                                                case 'suspend':
                                                    $iconClass = 'bg-red-500';
                                                    $icon = 'fas fa-ban';
                                                    break;
                                                case 'unban':
                                                    $iconClass = 'bg-green-500';
                                                    $icon = 'fas fa-user-check';
                                                    break;
                                                case 'delete_user':
                                                    $iconClass = 'bg-red-600';
                                                    $icon = 'fas fa-trash-alt';
                                                    break;
                                                case 'password_change':
                                                    $iconClass = 'bg-yellow-500';
                                                    $icon = 'fas fa-key';
                                                    break;
                                                case 'update_email':
                                                    $iconClass = 'bg-indigo-500';
                                                    $icon = 'fas fa-envelope';
                                                    break;
                                                case 'update_level':
                                                    $iconClass = 'bg-purple-500';
                                                    $icon = 'fas fa-user-shield';
                                                    break;
                                            }
                                            ?>
                                            <span class="h-10 w-10 rounded-full flex items-center justify-center <?= $iconClass ?> text-white">
                                                <i class="<?= $icon ?>"></i>
                                            </span>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?= escapeString($activity['username']) ?>
                                                </div>
                                                <p class="mt-0.5 text-sm text-gray-500">
                                                    <?= date('d/m/Y H:i', strtotime($activity['created_at'])) ?>
                                                </p>
                                            </div>
                                            <div class="mt-2 text-sm text-gray-700">
                                                <p><?= escapeString($activity['description']) ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript pour les graphiques -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Graphique des activités récentes
    const ctx = document.getElementById('registrationsChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: [
                <?php 
                // Générer les labels pour les 7 derniers jours
                for ($i = 6; $i >= 0; $i--) {
                    $date = date('d/m', strtotime("-$i days"));
                    echo "'$date'" . ($i > 0 ? ', ' : '');
                }
                ?>
            ],
            datasets: [{
                label: 'Connexions réussies',
                data: [
                    <?php 
                    // Remplir avec des données réelles ou des zéros
                    $successData = array_fill(0, 7, 0);
                    foreach ($loginStats as $stat) {
                        $dayDiff = intval((time() - strtotime($stat['login_date'])) / 86400);
                        if ($dayDiff >= 0 && $dayDiff < 7) {
                            $successData[6 - $dayDiff] = $stat['successful_logins'];
                        }
                    }
                    echo implode(', ', $successData);
                    ?>
                ],
                borderColor: 'rgba(16, 185, 129, 1)',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                borderWidth: 2,
                tension: 0.4,
                fill: true
            },
            {
                label: 'Tentatives échouées',
                data: [
                    <?php 
                    // Remplir avec des données réelles ou des zéros
                    $failedData = array_fill(0, 7, 0);
                    foreach ($loginStats as $stat) {
                        $dayDiff = intval((time() - strtotime($stat['login_date'])) / 86400);
                        if ($dayDiff >= 0 && $dayDiff < 7) {
                            $failedData[6 - $dayDiff] = $stat['failed_logins'];
                        }
                    }
                    echo implode(', ', $failedData);
                    ?>
                ],
                borderColor: 'rgba(239, 68, 68, 1)',
                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                borderWidth: 2,
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
    
    // Graphique de répartition des utilisateurs
    const doughnutCtx = document.getElementById('userLevelsChart').getContext('2d');
    new Chart(doughnutCtx, {
        type: 'doughnut',
        data: {
            labels: ['Utilisateurs', 'Modérateurs', 'Administrateurs', 'Super Admins'],
            datasets: [{
                data: [
                    <?= $levelCounts[USER_LEVEL_REGULAR] ?? 0 ?>, 
                    <?= $levelCounts[USER_LEVEL_MODERATOR] ?? 0 ?>, 
                    <?= $levelCounts[USER_LEVEL_ADMIN] ?? 0 ?>, 
                    <?= $levelCounts[USER_LEVEL_SUPERADMIN] ?? 0 ?>
                ],
                backgroundColor: [
                    'rgba(107, 114, 128, 0.8)',
                    'rgba(245, 158, 11, 0.8)',
                    'rgba(16, 185, 129, 0.8)',
                    'rgba(139, 92, 246, 0.8)'
                ],
                borderColor: [
                    'rgba(107, 114, 128, 1)',
                    'rgba(245, 158, 11, 1)',
                    'rgba(16, 185, 129, 1)',
                    'rgba(139, 92, 246, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                }
            },
            cutout: '65%'
        }
    });
});
</script>

<?php require_once '../includes/admin-footer.php'; ?>