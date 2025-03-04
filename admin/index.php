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

<div class="row mb-4">
    <div class="col-md-12">
        <h1><i class="fas fa-tachometer-alt me-2"></i> Tableau de bord</h1>
        <p class="text-muted">Bienvenue dans le panneau d'administration du site</p>
    </div>
</div>

<div class="row">
    <div class="col-md-3">
        <div class="stats-card primary">
            <i class="fas fa-users"></i>
            <h2><?= $userCount ?></h2>
            <p>Utilisateurs inscrits</p>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stats-card success">
            <i class="fas fa-user-shield"></i>
            <h2><?= $levelCounts[USER_LEVEL_ADMIN] ?? 0 ?></h2>
            <p>Administrateurs</p>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stats-card warning">
            <i class="fas fa-user-graduate"></i>
            <h2><?= $levelCounts[USER_LEVEL_MODERATOR] ?? 0 ?></h2>
            <p>Modérateurs</p>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stats-card danger">
            <i class="fas fa-user-lock"></i>
            <h2><?= $bannedCount ?></h2>
            <p>Utilisateurs bannis</p>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Utilisateurs récents</h5>
                <a href="users.php" class="btn btn-sm btn-primary">
                    <i class="fas fa-users"></i> Gérer tous les utilisateurs
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nom d'utilisateur</th>
                                <th>Email</th>
                                <th>Niveau</th>
                                <th>Date d'inscription</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentUsers as $user): ?>
                                <tr>
                                    <td><?= $user['id'] ?></td>
                                    <td><?= escapeString($user['username']) ?></td>
                                    <td><?= escapeString($user['email']) ?></td>
                                    <td>
                                        <span class="user-level user-level-<?= $user['user_level'] ?>">
                                            <?= getUserLevelText($user['user_level']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></td>
                                    <td>
                                        <a href="user-edit.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($recentUsers)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">Aucun utilisateur trouvé</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/admin-footer.php'; ?>