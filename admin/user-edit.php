<?php
$pageTitle = "Modifier l'utilisateur";
require_once '../includes/admin-header.php';

// Vérifier si l'ID est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['flash_message'] = "ID d'utilisateur invalide.";
    $_SESSION['flash_type'] = "danger";
    redirect('users.php');
}

$userId = (int)$_GET['id'];

// Récupérer les informations de l'utilisateur
$user = getUserById($userId);

if (!$user) {
    $_SESSION['flash_message'] = "Utilisateur introuvable.";
    $_SESSION['flash_type'] = "danger";
    redirect('users.php');
}

// Vérifier les permissions (seul un super admin peut modifier un autre super admin)
if ($user['user_level'] == USER_LEVEL_SUPERADMIN && !isSuperAdmin()) {
    $_SESSION['flash_message'] = "Vous n'avez pas les permissions nécessaires pour modifier ce super administrateur.";
    $_SESSION['flash_type'] = "danger";
    redirect('users.php');
}

$errors = [];
$success = false;

// Traiter le formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier le token CSRF
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = "Erreur de sécurité. Veuillez réessayer.";
    } else {
        // Récupérer les données du formulaire
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $userLevel = (int)($_POST['user_level'] ?? 1);
        
        // Si l'utilisateur n'est pas super admin, il ne peut pas promouvoir quelqu'un à super admin
        if (!isSuperAdmin() && $userLevel == USER_LEVEL_SUPERADMIN) {
            $errors[] = "Vous n'avez pas les permissions nécessaires pour promouvoir un utilisateur au niveau Super Admin.";
        }
        
        // Valider le nom d'utilisateur
        if (empty($username)) {
            $errors[] = "Le nom d'utilisateur est requis.";
        } elseif (strlen($username) < 3 || strlen($username) > 30) {
            $errors[] = "Le nom d'utilisateur doit contenir entre 3 et 30 caractères.";
        } elseif ($username !== $user['username'] && usernameExists($username)) {
            $errors[] = "Ce nom d'utilisateur est déjà pris.";
        }
        
        // Valider l'email
        if (empty($email)) {
            $errors[] = "L'adresse email est requise.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "L'adresse email n'est pas valide.";
        } elseif ($email !== $user['email'] && emailExists($email)) {
            $errors[] = "Cette adresse email est déjà utilisée.";
        }
        
        // Si aucune erreur, mettre à jour l'utilisateur
        if (empty($errors)) {
            $conn = connectDB();
            $updateQuery = "UPDATE users SET username = ?, email = ?, user_level = ? WHERE id = ?";
            $stmt = $conn->prepare($updateQuery);
            $stmt->bind_param("ssii", $username, $email, $userLevel, $userId);
            
            if ($stmt->execute()) {
                $success = true;
                
                // Mettre à jour les informations de l'utilisateur pour l'affichage
                $user['username'] = $username;
                $user['email'] = $email;
                $user['user_level'] = $userLevel;
                
                $_SESSION['flash_message'] = "Utilisateur mis à jour avec succès.";
                $_SESSION['flash_type'] = "success";
            } else {
                $errors[] = "Une erreur est survenue lors de la mise à jour de l'utilisateur.";
            }
            
            $stmt->close();
            $conn->close();
        }
    }
}
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h1><i class="fas fa-user-edit me-2"></i> Modifier l'utilisateur</h1>
        <p class="text-muted">ID: <?= $user['id'] ?></p>
    </div>
    <div class="col-md-6 text-end">
        <a href="users.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Retour à la liste
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Informations de l'utilisateur</h5>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        Les informations de l'utilisateur ont été mises à jour avec succès.
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?= escapeString($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form action="user-edit.php?id=<?= $user['id'] ?>" method="POST" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">Nom d'utilisateur</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?= escapeString($user['username']) ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Adresse email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= escapeString($user['email']) ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="user_level" class="form-label">Niveau d'accès</label>
                        <select class="form-select" id="user_level" name="user_level">
                            <option value="<?= USER_LEVEL_REGULAR ?>" <?= $user['user_level'] == USER_LEVEL_REGULAR ? 'selected' : '' ?>>
                                Utilisateur
                            </option>
                            <option value="<?= USER_LEVEL_MODERATOR ?>" <?= $user['user_level'] == USER_LEVEL_MODERATOR ? 'selected' : '' ?>>
                                Modérateur
                            </option>
                            <?php if (isAdmin()): ?>
                                <option value="<?= USER_LEVEL_ADMIN ?>" <?= $user['user_level'] == USER_LEVEL_ADMIN ? 'selected' : '' ?>>
                                    Administrateur
                                </option>
                            <?php endif; ?>
                            <?php if (isSuperAdmin()): ?>
                                <option value="<?= USER_LEVEL_SUPERADMIN ?>" <?= $user['user_level'] == USER_LEVEL_SUPERADMIN ? 'selected' : '' ?>>
                                    Super Administrateur
                                </option>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Enregistrer les modifications
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Statut du compte</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Statut actuel</label>
                    <div class="p-2 border rounded">
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
                        <span class="<?= $statusClass ?> fs-5">
                            <?= getUserStatusText($user['status'], $user['ban_expires'] ?? null) ?>
                        </span>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Date d'inscription</label>
                    <div class="p-2 border rounded">
                        <?= date('d/m/Y H:i', strtotime($user['created_at'])) ?>
                    </div>
                </div>
                
                <?php if ($user['status'] !== USER_STATUS_ACTIVE): ?>
                    <a href="user-action.php?action=unban&id=<?= $user['id'] ?>&csrf_token=<?= generateCSRFToken() ?>" class="btn btn-success d-block mb-2">
                        <i class="fas fa-user-check"></i> Réactiver le compte
                    </a>
                <?php else: ?>
                    <button class="btn btn-warning d-block mb-2" data-bs-toggle="modal" data-bs-target="#banUserModal" data-user-id="<?= $user['id'] ?>" data-username="<?= $user['username'] ?>">
                        <i class="fas fa-ban"></i> Suspendre temporairement
                    </button>
                    
                    <a href="user-action.php?action=ban&id=<?= $user['id'] ?>&csrf_token=<?= generateCSRFToken() ?>" class="btn btn-danger d-block mb-2" data-confirm="Êtes-vous sûr de vouloir bannir définitivement cet utilisateur ?">
                        <i class="fas fa-user-slash"></i> Bannir définitivement
                    </a>
                <?php endif; ?>
                
                <?php if (isSuperAdmin() || (isAdmin() && $user['user_level'] < USER_LEVEL_SUPERADMIN)): ?>
                    <a href="user-action.php?action=delete&id=<?= $user['id'] ?>&csrf_token=<?= generateCSRFToken() ?>" class="btn btn-outline-danger d-block" data-confirm="Êtes-vous sûr de vouloir supprimer définitivement cet utilisateur ? Cette action est irréversible.">
                        <i class="fas fa-trash-alt"></i> Supprimer le compte
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal de suspension temporaire -->
<div class="modal fade" id="banUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Suspendre l'utilisateur <?= escapeString($user['username']) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <form action="user-action.php" method="POST">
                <input type="hidden" name="action" value="suspend">
                <input type="hidden" name="id" value="<?= $user['id'] ?>">
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