<?php
$pageTitle = "Gestion des utilisateurs";
require_once '../includes/admin-header.php';

// Récupérer les paramètres de recherche et pagination
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;

// Récupérer les utilisateurs
$usersData = getUsers($search, $page, $perPage);
$users = $usersData['users'];
$totalPages = $usersData['totalPages'];
$currentPage = $usersData['currentPage'];
?>

<div class="mb-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-bold flex items-center text-gray-800">
                <i class="fas fa-users mr-2 text-blue-600"></i> Gestion des utilisateurs
            </h1>
            <p class="text-gray-500 mt-1">Gérez tous les utilisateurs du site</p>
        </div>
        <div class="mt-4 md:mt-0">
            <a href="index.php" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-arrow-left mr-2"></i> Retour au tableau de bord
            </a>
        </div>
    </div>
</div>

<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="p-4 border-b border-gray-200 bg-gray-50">
        <form action="" method="GET" class="flex flex-col md:flex-row md:items-end gap-4">
            <div class="flex-grow">
                <div class="relative rounded-md shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-400"></i>
                    </div>
                    <input type="text" name="search" id="searchUsers" class="block w-full pl-10 pr-12 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" 
                           placeholder="Rechercher par nom d'utilisateur ou email" value="<?= escapeString($search) ?>">
                    <div class="absolute inset-y-0 right-0 flex items-center">
                        <button type="submit" class="h-full px-4 bg-blue-600 text-white border-0 rounded-r-md hover:bg-blue-700 focus:outline-none">
                            Rechercher
                        </button>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($search)): ?>
                <div>
                    <a href="users.php" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Réinitialiser
                    </a>
                </div>
            <?php endif; ?>
        </form>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead>
                <tr>
                    <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom d'utilisateur</th>
                    <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                    <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Niveau</th>
                    <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                    <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date d'inscription</th>
                    <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($users as $user): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900"><?= $user['id'] ?></td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 font-medium"><?= escapeString($user['username']) ?></td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500"><?= escapeString($user['email']) ?></td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="user-level user-level-<?= $user['user_level'] ?>">
                                <?= getUserLevelText($user['user_level']) ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <?php
                            $statusClass = '';
                            switch ($user['status']) {
                                case USER_STATUS_ACTIVE:
                                    $statusClass = 'status-active';
                                    break;
                                case USER_STATUS_BANNED:
                                    $statusClass = 'status-banned';
                                    break;
                                case USER_STATUS_SUSPENDED:
                                    $statusClass = 'status-suspended';
                                    break;
                            }
                            ?>
                            <span class="<?= $statusClass ?>">
                                <?= getUserStatusText($user['status'], $user['ban_expires'] ?? null) ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                            <?= date('d/m/Y H:i', strtotime($user['created_at'])) ?>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium">
                            <div class="relative group">
                                <button class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none" type="button">
                                    Actions <i class="fas fa-chevron-down ml-2"></i>
                                </button>
                                <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                                    <a href="user-edit.php?id=<?= $user['id'] ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        <i class="fas fa-edit mr-2 text-blue-500"></i> Modifier
                                    </a>
                                    
                                    <?php if ($user['status'] === USER_STATUS_ACTIVE): ?>
                                        <button class="w-full text-left block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" 
                                                onclick="showBanModal(<?= $user['id'] ?>, '<?= escapeString($user['username']) ?>')">
                                            <i class="fas fa-ban mr-2 text-yellow-500"></i> Suspendre
                                        </button>
                                        <a class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100" 
                                           href="user-action.php?action=ban&id=<?= $user['id'] ?>&csrf_token=<?= generateCSRFToken() ?>" 
                                           data-confirm="Êtes-vous sûr de vouloir bannir définitivement cet utilisateur ?">
                                            <i class="fas fa-user-slash mr-2"></i> Bannir définitivement
                                        </a>
                                    <?php else: ?>
                                        <a class="block px-4 py-2 text-sm text-green-600 hover:bg-gray-100" 
                                           href="user-action.php?action=unban&id=<?= $user['id'] ?>&csrf_token=<?= generateCSRFToken() ?>">
                                            <i class="fas fa-user-check mr-2"></i> Réactiver
                                        </a>
                                    <?php endif; ?>
                                    
                                    <hr class="my-1 border-gray-200">
                                    
                                    <?php if (isSuperAdmin() || (isAdmin() && $user['user_level'] < USER_LEVEL_SUPERADMIN)): ?>
                                        <a class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100" 
                                           href="user-action.php?action=delete&id=<?= $user['id'] ?>&csrf_token=<?= generateCSRFToken() ?>" 
                                           data-confirm="Êtes-vous sûr de vouloir supprimer définitivement cet utilisateur ? Cette action est irréversible.">
                                            <i class="fas fa-trash-alt mr-2"></i> Supprimer
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">Aucun utilisateur trouvé</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <?php if ($totalPages > 1): ?>
        <div class="px-4 py-3 bg-white border-t border-gray-200 sm:px-6">
            <nav class="flex justify-center" aria-label="Pagination">
                <ul class="flex space-x-2">
                    <?php if ($currentPage > 1): ?>
                        <li>
                            <a href="?page=<?= $currentPage - 1 ?>&search=<?= urlencode($search) ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                <i class="fas fa-chevron-left mr-2"></i> Précédent
                            </a>
                        </li>
                    <?php else: ?>
                        <li>
                            <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-300 bg-gray-50 cursor-not-allowed">
                                <i class="fas fa-chevron-left mr-2"></i> Précédent
                            </span>
                        </li>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
                        <li>
                            <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>" 
                               class="relative inline-flex items-center px-4 py-2 border <?= $i === $currentPage ? 'border-blue-500 bg-blue-50 text-blue-600' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50' ?> text-sm font-medium rounded-md">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($currentPage < $totalPages): ?>
                        <li>
                            <a href="?page=<?= $currentPage + 1 ?>&search=<?= urlencode($search) ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Suivant <i class="fas fa-chevron-right ml-2"></i>
                            </a>
                        </li>
                    <?php else: ?>
                        <li>
                            <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-300 bg-gray-50 cursor-not-allowed">
                                Suivant <i class="fas fa-chevron-right ml-2"></i>
                            </span>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<!-- Modal de suspension temporaire -->
<div id="banUserModal" class="fixed inset-0 flex items-center justify-center z-50 hidden">
    <div class="fixed inset-0 bg-black opacity-50" id="banModalOverlay"></div>
    <div class="bg-white rounded-lg shadow-xl z-10 w-full max-w-md">
        <div class="flex justify-between items-center p-4 border-b border-gray-200">
            <h5 class="text-lg font-semibold text-gray-900">Suspendre l'utilisateur <span id="banModalUsername"></span></h5>
            <button type="button" class="text-gray-400 hover:text-gray-500" id="closeModal">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form action="user-action.php" method="POST">
            <input type="hidden" name="action" value="suspend">
            <input type="hidden" name="id" id="userId">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            
            <div class="p-4">
                <div class="mb-4">
                    <label for="banDuration" class="block text-sm font-medium text-gray-700 mb-2">Durée de la suspension (jours)</label>
                    <input type="number" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" 
                           id="banDuration" name="duration" min="1" max="365" value="7" required>
                </div>
                <div class="mb-4">
                    <label for="banReason" class="block text-sm font-medium text-gray-700 mb-2">Raison (optionnel)</label>
                    <textarea class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" 
                              id="banReason" name="reason" rows="3"></textarea>
                </div>
            </div>
            <div class="px-4 py-3 bg-gray-50 text-right border-t border-gray-200 rounded-b-lg">
                <button type="button" class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 mr-2" id="cancelModal">
                    Annuler
                </button>
                <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    Suspendre
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Modal handling
    function showBanModal(userId, username) {
        document.getElementById('userId').value = userId;
        document.getElementById('banModalUsername').textContent = username;
        document.getElementById('banUserModal').classList.remove('hidden');
    }
    
    document.getElementById('closeModal').addEventListener('click', function() {
        document.getElementById('banUserModal').classList.add('hidden');
    });
    
    document.getElementById('cancelModal').addEventListener('click', function() {
        document.getElementById('banUserModal').classList.add('hidden');
    });
    
    document.getElementById('banModalOverlay').addEventListener('click', function() {
        document.getElementById('banUserModal').classList.add('hidden');
    });
</script>

<?php require_once '../includes/admin-footer.php'; ?>