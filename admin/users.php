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

<div class="row mb-4">
    <div class="col-md-6">
        <h1><i class="fas fa-users me-2"></i> Gestion des utilisateurs</h1>
        <p class="text-muted">Gérez tous les utilisateurs du site</p>
    </div>
    <div class="col-md-6 text-end">
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Retour au tableau de bord
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <form action="" method="GET" class="row g-3">
            <div class="col-md-8">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" class="form-control" name="search" id="searchUsers" placeholder="Rechercher par nom d'utilisateur ou email" value="<?= escapeString($search) ?>">
                    <button type="submit" class="btn btn-primary">Rechercher</button>
                    <?php if (!empty($search)): ?>
                        <a href="users.php" class="btn btn-outline-secondary">Réinitialiser</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover users-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom d'utilisateur</th>
                        <th>Email</th>
                        <th>Niveau</th>
                        <th>Statut</th>
                        <th>Date d'inscription</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= $user['id'] ?></td>
                            <td><?= escapeString($user['username']) ?></td>
                            <td><?= escapeString($user['email']) ?></td>
                            <td>
                                <span class="user-level user-level-<?= $user['user_level'] ?>">
                                    <?= getUserLevelText($user['user_level']) ?>
                                </span>
                            </td>
                            <td>
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
                            <td><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        Actions
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item" href="user-edit.php?id=<?= $user['id'] ?>">
                                                <i class="fas fa-edit"></i> Modifier
                                            </a>
                                        </li>
                                        
                                        <?php if ($user['status'] === USER_STATUS_ACTIVE): ?>
                                            <li>
                                                <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#banUserModal" data-user-id="<?= $user['id'] ?>" data-username="<?= $user['username'] ?>">
                                                    <i class="fas fa-ban"></i> Suspendre
                                                </button>
                                            </li>
                                            <li>
                                                <a class="dropdown-item text-danger" href="user-action.php?action=ban&id=<?= $user['id'] ?>&csrf_token=<?= generateCSRFToken() ?>" data-confirm="Êtes-vous sûr de vouloir bannir définitivement cet utilisateur ?">
                                                    <i class="fas fa-user-slash"></i> Bannir définitivement
                                                </a>
                                            </li>
                                        <?php else: ?>
                                            <li>
                                                <a class="dropdown-item text-success" href="user-action.php?action=unban&id=<?= $user['id'] ?>&csrf_token=<?= generateCSRFToken() ?>">
                                                    <i class="fas fa-user-check"></i> Réactiver
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <li><hr class="dropdown-divider"></li>
                                        
                                        <?php if (isSuperAdmin() || (isAdmin() && $user['user_level'] < USER_LEVEL_SUPERADMIN)): ?>
                                            <li>
                                                <a class="dropdown-item text-danger" href="user-action.php?action=delete&id=<?= $user['id'] ?>&csrf_token=<?= generateCSRFToken() ?>" data-confirm="Êtes-vous sûr de vouloir supprimer définitivement cet utilisateur ? Cette action est irréversible.">
                                                    <i class="fas fa-trash-alt"></i> Supprimer
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="7" class="text-center">Aucun utilisateur trouvé</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($totalPages > 1): ?>
            <nav aria-label="Pagination" class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($currentPage > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $currentPage - 1 ?>&search=<?= urlencode($search) ?>">
                                <i class="fas fa-chevron-left"></i> Précédent
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="page-item disabled">
                            <span class="page-link"><i class="fas fa-chevron-left"></i> Précédent</span>
                        </li>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
                        <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($currentPage < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $currentPage + 1 ?>&search=<?= urlencode($search) ?>">
                                Suivant <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="page-item disabled">
                            <span class="page-link">Suivant <i class="fas fa-chevron-right"></i></span>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de suspension temporaire -->
<div class="modal fade" id="banUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Suspendre l'utilisateur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <form action="user-action.php" method="POST">
                <input type="hidden" name="action" value="suspend">
                <input type="hidden" name="id" id="userId">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="banDuration" class="form-label">Durée de la suspension (jours)</label>
                        <input type="number" class="form-control" id="banDuration" name="duration" min="1" max="365" value="7" required>
                    </div>
                    <div class="mb-3">
                        <label for="banReason" class="form-label">Raison (optionnel)</label>
                        <textarea class="form-control" id="banReason" name="reason" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger">Suspendre</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/admin-footer.php'; ?>