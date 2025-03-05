<?php
$pageTitle = "Modifier l'utilisateur";
require_once '../includes/admin-header.php';

// Vérifier si l'ID est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlashMessage("ID d'utilisateur invalide.", "danger");
    redirect('users.php');
}

$userId = (int)$_GET['id'];

// Récupérer les informations de l'utilisateur
$user = getUserById($userId);

if (!$user) {
    setFlashMessage("Utilisateur introuvable.", "danger");
    redirect('users.php');
}

// Vérifier les permissions (seul un super admin peut modifier un autre super admin)
if ($user['user_level'] == USER_LEVEL_SUPERADMIN && !isSuperAdmin()) {
    setFlashMessage("Vous n'avez pas les permissions nécessaires pour modifier ce super administrateur.", "danger");
    redirect('users.php');
}

// Récupérer l'historique des connexions
$loginHistory = getUserLoginHistory($userId, 5);

// Récupérer les activités récentes
$userActivities = getUserActivities($userId, 5);

// Récupérer les IPs connues
$userIPs = getUserIPs($userId);

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
            
            // Utiliser une transaction pour les mises à jour
            $conn->begin_transaction();
            
            try {
                // Mettre à jour les informations de base
                $updateQuery = "UPDATE users SET username = ?, email = ?, user_level = ? WHERE id = ?";
                $stmt = $conn->prepare($updateQuery);
                $stmt->bind_param("ssii", $username, $email, $userLevel, $userId);
                $stmt->execute();
                $stmt->close();
                
                // Enregistrer l'activité
                $activityType = 'update_user';
                $description = "Mise à jour des informations utilisateur";
                $ipAddress = $_SERVER['REMOTE_ADDR'];
                
                $logQuery = "INSERT INTO user_activities (user_id, activity_type, description, ip_address, created_at) 
                             VALUES (?, ?, ?, ?, NOW())";
                $logStmt = $conn->prepare($logQuery);
                $logStmt->bind_param("isss", $userId, $activityType, $description, $ipAddress);
                $logStmt->execute();
                $logStmt->close();
                
                $conn->commit();
                $success = true;
                
                // Mettre à jour les informations de l'utilisateur pour l'affichage
                $user['username'] = $username;
                $user['email'] = $email;
                $user['user_level'] = $userLevel;
                
                setFlashMessage("Utilisateur mis à jour avec succès.", "success");
            } catch (Exception $e) {
                $conn->rollback();
                $errors[] = "Une erreur est survenue lors de la mise à jour de l'utilisateur: " . $e->getMessage();
            }
            
            $conn->close();
        }
    }
}
?>

<div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between">
    <div>
        <h1 class="text-2xl font-bold flex items-center text-gray-800">
            <i class="fas fa-user-edit mr-2 text-blue-600"></i> Modifier l'utilisateur
        </h1>
        <p class="text-gray-500 mt-1">ID: <?= $user['id'] ?> | Membre depuis: <?= date('d/m/Y', strtotime($user['created_at'])) ?></p>
    </div>
    <div class="mt-3 md:mt-0">
        <a href="users.php" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <i class="fas fa-arrow-left mr-2"></i> Retour à la liste
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <!-- Formulaire principal -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h3 class="text-lg font-medium text-gray-900">Informations de l'utilisateur</h3>
            </div>
            <div class="p-6">
                <?php if ($success): ?>
                    <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check-circle text-green-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-green-700">Les informations de l'utilisateur ont été mises à jour avec succès.</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($errors)): ?>
                    <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-circle text-red-400"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">Des erreurs sont survenues:</h3>
                                <ul class="mt-1 text-sm text-red-700 list-disc list-inside">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?= escapeString($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <form action="user-edit.php?id=<?= $user['id'] ?>" method="POST" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    
                    <div class="mb-4">
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Nom d'utilisateur</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?= escapeString($user['username']) ?>" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Adresse email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= escapeString($user['email']) ?>" required>
                    </div>
                    
                    <div class="mb-6">
                        <label for="user_level" class="block text-sm font-medium text-gray-700 mb-1">Niveau d'accès</label>
                        <select class="form-control" id="user_level" name="user_level">
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
                    
                    <button type="submit" class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-save mr-2"></i> Enregistrer les modifications
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Activités récentes -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h3 class="text-lg font-medium text-gray-900">Activités récentes</h3>
            </div>
            <div class="p-6">
                <?php if (empty($userActivities)): ?>
                    <p class="text-gray-500 text-center py-4">Aucune activité récente enregistrée.</p>
                <?php else: ?>
                    <div class="flow-root">
                        <ul role="list" class="-mb-8">
                            <?php foreach ($userActivities as $index => $activity): ?>
                                <li>
                                    <div class="relative pb-8">
                                        <?php if ($index < count($userActivities) - 1): ?>
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
                                                    case 'password_change':
                                                        $iconClass = 'bg-yellow-500';
                                                        $icon = 'fas fa-key';
                                                        break;
                                                    case 'update_email':
                                                        $iconClass = 'bg-indigo-500';
                                                        $icon = 'fas fa-envelope';
                                                        break;
                                                    case 'register':
                                                        $iconClass = 'bg-blue-500';
                                                        $icon = 'fas fa-user-plus';
                                                        break;
                                                }
                                                ?>
                                                <span class="h-10 w-10 rounded-full flex items-center justify-center <?= $iconClass ?> text-white">
                                                    <i class="<?= $icon ?>"></i>
                                                </span>
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <div>
                                                    <p class="text-sm text-gray-500">
                                                        <?= date('d/m/Y H:i', strtotime($activity['created_at'])) ?>
                                                    </p>
                                                </div>
                                                <div class="mt-2 text-sm text-gray-700">
                                                    <p><?= escapeString($activity['description']) ?></p>
                                                </div>
                                                <div class="mt-1 text-xs text-gray-500">
                                                    IP: <?= escapeString($activity['ip_address']) ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Historique des connexions -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h3 class="text-lg font-medium text-gray-900">Historique des connexions</h3>
            </div>
            <div class="p-6">
                <?php if (empty($loginHistory)): ?>
                    <p class="text-gray-500 text-center py-4">Aucune connexion enregistrée.</p>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date et heure</th>
                                    <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Adresse IP</th>
                                    <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($loginHistory as $login): ?>
                                    <tr>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                            <?= date('d/m/Y H:i:s', strtotime($login['login_time'])) ?>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                            <?= escapeString($login['ip_address']) ?>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <?php if ($login['status'] === 'success'): ?>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                    Réussie
                                                </span>
                                            <?php else: ?>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                    Échouée
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="lg:col-span-1">
        <!-- Statut du compte -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h3 class="text-lg font-medium text-gray-900">Statut du compte</h3>
            </div>
            <div class="p-6">
                <div class="flex items-center mb-6">
                    <div class="flex-shrink-0 h-16 w-16 bg-gray-200 rounded-full flex items-center justify-center">
                        <i class="fas fa-user-circle text-gray-500 text-3xl"></i>
                    </div>
                    <div class="ml-4">
                        <h2 class="text-xl font-medium text-gray-900"><?= escapeString($user['username']) ?></h2>
                        <p class="text-sm text-gray-500"><?= escapeString($user['email']) ?></p>
                    </div>
                </div>
                
                <div class="mb-4">
                    <div class="flex justify-between mb-1">
                        <span class="text-sm font-medium text-gray-700">Statut actuel</span>
                    </div>
                    <div class="px-3 py-2 rounded-md border border-gray-200">
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
                    </div>
                </div>
                
                <div class="mb-4">
                    <div class="flex justify-between mb-1">
                        <span class="text-sm font-medium text-gray-700">Niveau d'accès</span>
                    </div>
                    <div class="px-3 py-2 rounded-md border border-gray-200">
                        <span class="user-level user-level-<?= $user['user_level'] ?>">
                            <?= getUserLevelText($user['user_level']) ?>
                        </span>
                    </div>
                </div>
                
                <div class="mb-6">
                    <div class="flex justify-between mb-1">
                        <span class="text-sm font-medium text-gray-700">Date d'inscription</span>
                    </div>
                    <div class="px-3 py-2 rounded-md border border-gray-200">
                        <?= date('d/m/Y H:i', strtotime($user['created_at'])) ?>
                    </div>
                </div>
                
                <hr class="my-4 border-t border-gray-200">
                
                <div class="space-y-2">
                    <?php if ($user['status'] !== USER_STATUS_ACTIVE): ?>
                        <a href="user-action.php?action=unban&id=<?= $user['id'] ?>&csrf_token=<?= generateCSRFToken() ?>" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            <i class="fas fa-user-check mr-2"></i> Réactiver le compte
                        </a>
                    <?php else: ?>
                        <button class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-yellow-500 hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500" 
                                onclick="showBanModal(<?= $user['id'] ?>, '<?= escapeString($user['username']) ?>')"
                                data-action="show-ban-modal"
                                data-user-id="<?= $user['id'] ?>"
                                data-username="<?= escapeString($user['username']) ?>">
                            <i class="fas fa-ban mr-2"></i> Suspendre temporairement
                        </button>
                        
                        <a href="user-action.php?action=ban&id=<?= $user['id'] ?>&csrf_token=<?= generateCSRFToken() ?>" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500" data-confirm="Êtes-vous sûr de vouloir bannir définitivement cet utilisateur ?">
                            <i class="fas fa-user-slash mr-2"></i> Bannir définitivement
                        </a>
                    <?php endif; ?>
                    
                    <?php if (isSuperAdmin() || (isAdmin() && $user['user_level'] < USER_LEVEL_SUPERADMIN)): ?>
                        <a href="user-action.php?action=delete&id=<?= $user['id'] ?>&csrf_token=<?= generateCSRFToken() ?>" class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-red-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500" data-confirm="Êtes-vous sûr de vouloir supprimer définitivement cet utilisateur ? Cette action est irréversible.">
                            <i class="fas fa-trash-alt mr-2"></i> Supprimer le compte
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Adresses IP connues -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h3 class="text-lg font-medium text-gray-900">Adresses IP connues</h3>
            </div>
            <div class="p-6">
                <?php if (empty($userIPs)): ?>
                    <p class="text-gray-500 text-center py-4">Aucune adresse IP enregistrée.</p>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($userIPs as $ip): ?>
                            <div class="p-3 border rounded-md <?= $ip['is_suspicious'] ? 'bg-red-50 border-red-200' : 'bg-gray-50 border-gray-200' ?>">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">
                                            <?= escapeString($ip['ip_address']) ?>
                                        </div>
                                        <div class="mt-1 text-xs text-gray-500">
                                            <?= $ip['login_count'] ?> connexion<?= $ip['login_count'] > 1 ? 's' : '' ?>
                                        </div>
                                    </div>
                                    <?php if ($ip['is_suspicious']): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            <i class="fas fa-exclamation-triangle mr-1"></i> Suspect
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="mt-2 text-xs text-gray-500">
                                    <div>Première connexion: <?= date('d/m/Y H:i', strtotime($ip['first_seen'])) ?></div>
                                    <div>Dernière connexion: <?= date('d/m/Y H:i', strtotime($ip['last_seen'])) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
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

<?php require_once '../includes/admin-footer.php'; ?>