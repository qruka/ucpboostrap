<?php
$pageTitle = "Mon profil";
require_once 'includes/header.php';

// Rediriger si l'utilisateur n'est pas connecté
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = 'profile.php';
    setFlashMessage("Veuillez vous connecter pour accéder à votre profil.", "warning");
    redirect('login.php');
}

// Récupérer les informations de l'utilisateur
$user = getUserById($_SESSION['user_id']);
if (!$user) {
    setFlashMessage("Utilisateur non trouvé.", "danger");
    redirect('index.php');
}

// Récupérer l'historique des connexions
$loginHistory = getUserLoginHistory($_SESSION['user_id'], 5);

// Récupérer les activités récentes
$userActivities = getUserActivities($_SESSION['user_id'], 5);

// Gérer la soumission du formulaire de mise à jour du profil
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier le token CSRF
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = "Erreur de sécurité. Veuillez réessayer.";
    } else {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        
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
        
        // Traiter l'avatar si présent
        $profileImage = $user['profile_image'] ?? null;
        
        if (!empty($_FILES['avatar']['name'])) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $maxSize = 2 * 1024 * 1024; // 2MB
            
            if (!in_array($_FILES['avatar']['type'], $allowedTypes)) {
                $errors[] = "Le format de l'image n'est pas supporté. Utilisez JPG, PNG ou GIF.";
            } elseif ($_FILES['avatar']['size'] > $maxSize) {
                $errors[] = "L'image est trop volumineuse. Taille maximale: 2MB.";
            } elseif ($_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
                $errors[] = "Erreur lors du téléchargement de l'image. Code: " . $_FILES['avatar']['error'];
            } else {
                // Créer le répertoire d'upload si nécessaire
                $uploadDir = 'uploads/avatars/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                // Générer un nom de fichier unique
                $fileExtension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
                $newFileName = 'avatar_' . $_SESSION['user_id'] . '_' . time() . '.' . $fileExtension;
                $targetFile = $uploadDir . $newFileName;
                
                // Déplacer le fichier uploadé
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $targetFile)) {
                    // Supprimer l'ancien avatar s'il existe
                    if ($profileImage && file_exists($profileImage)) {
                        unlink($profileImage);
                    }
                    
                    $profileImage = $targetFile;
                } else {
                    $errors[] = "Erreur lors de l'enregistrement de l'image.";
                }
            }
        }
        
        // Si aucune erreur, mettre à jour le profil
        if (empty($errors)) {
            $conn = connectDB();
            
            // Utiliser une transaction pour la mise à jour
            $conn->begin_transaction();
            
            try {
                $sql = "UPDATE users SET username = ?, email = ?" . ($profileImage ? ", profile_image = ?" : "") . " WHERE id = ?";
                $stmt = $conn->prepare($sql);
                
                if ($profileImage) {
                    $stmt->bind_param("sssi", $username, $email, $profileImage, $_SESSION['user_id']);
                } else {
                    $stmt->bind_param("ssi", $username, $email, $_SESSION['user_id']);
                }
                
                $stmt->execute();
                
                // Enregistrer l'activité
                $activitySql = "INSERT INTO user_activities (user_id, activity_type, description, ip_address, created_at) 
                                VALUES (?, 'profile_update', 'Mise à jour du profil', ?, NOW())";
                $activityStmt = $conn->prepare($activitySql);
                $ipAddress = $_SERVER['REMOTE_ADDR'];
                $activityStmt->bind_param("is", $_SESSION['user_id'], $ipAddress);
                $activityStmt->execute();
                
                $conn->commit();
                
                // Mettre à jour les informations de l'utilisateur
                $user['username'] = $username;
                $user['email'] = $email;
                if ($profileImage) {
                    $user['profile_image'] = $profileImage;
                }
                
                $success = true;
                setFlashMessage("Votre profil a été mis à jour avec succès.", "success");
            } catch (Exception $e) {
                $conn->rollback();
                $errors[] = "Une erreur est survenue lors de la mise à jour du profil: " . $e->getMessage();
            }
            
            $conn->close();
        }
    }
}
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Mon profil</h1>
        <p class="text-gray-600 mt-1">Gérez vos informations personnelles et préférences</p>
    </div>
    
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
            <div class="flex items-center">
                <div class="h-16 w-16 rounded-full overflow-hidden bg-gray-200 flex-shrink-0">
                    <?php if (!empty($user['profile_image']) && file_exists($user['profile_image'])): ?>
                        <img src="<?= $user['profile_image'] ?>" alt="Avatar" class="h-full w-full object-cover">
                    <?php else: ?>
                        <div class="h-full w-full flex items-center justify-center bg-blue-100 text-blue-500">
                            <i class="fas fa-user text-2xl"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="ml-4">
                    <h2 class="text-xl font-bold text-gray-900"><?= escapeString($user['username']) ?></h2>
                    <div class="text-sm text-gray-500">
                        <span class="user-level user-level-<?= $user['user_level'] ?>">
                            <?= getUserLevelText($user['user_level']) ?>
                        </span>
                        <span class="ml-3">Membre depuis <?= date('d/m/Y', strtotime($user['created_at'])) ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="px-4 py-5 sm:p-6">
            <?php if ($success): ?>
                <div class="rounded-md bg-green-50 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-green-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-800">
                                Profil mis à jour avec succès !
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="rounded-md bg-red-50 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle text-red-400"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">Des erreurs sont survenues:</h3>
                            <div class="mt-2 text-sm text-red-700">
                                <ul class="list-disc pl-5 space-y-1">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?= escapeString($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <form action="profile.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                
                <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                    <div class="sm:col-span-3">
                        <label for="username" class="block text-sm font-medium text-gray-700">Nom d'utilisateur</label>
                        <div class="mt-1">
                            <input type="text" name="username" id="username" 
                                   value="<?= escapeString($user['username']) ?>" 
                                   class="form-control">
                        </div>
                    </div>
                    
                    <div class="sm:col-span-3">
                        <label for="email" class="block text-sm font-medium text-gray-700">Adresse email</label>
                        <div class="mt-1">
                            <input type="email" name="email" id="email" 
                                   value="<?= escapeString($user['email']) ?>" 
                                   class="form-control">
                        </div>
                    </div>
                    
                    <div class="sm:col-span-6">
                        <label class="block text-sm font-medium text-gray-700">Photo de profil</label>
                        <div class="mt-1 flex items-center">
                            <div class="h-12 w-12 rounded-full overflow-hidden bg-gray-100">
                                <?php if (!empty($user['profile_image']) && file_exists($user['profile_image'])): ?>
                                    <img src="<?= $user['profile_image'] ?>" alt="Avatar" class="h-full w-full object-cover" id="avatarPreview">
                                <?php else: ?>
                                    <div class="h-full w-full flex items-center justify-center bg-gray-100 text-gray-400" id="avatarPlaceholder">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <img src="" alt="Avatar" class="h-full w-full object-cover hidden" id="avatarPreview">
                                <?php endif; ?>
                            </div>
                            <div class="ml-5">
                                <div class="relative">
                                    <input type="file" name="avatar" id="avatar" class="sr-only" accept="image/jpeg,image/png,image/gif">
                                    <label for="avatar" class="btn btn-outline-primary cursor-pointer">
                                        <i class="fas fa-upload mr-2"></i> Changer l'avatar
                                    </label>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">PNG, JPG ou GIF. 2MB maximum.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="pt-5 border-t border-gray-200">
                    <div class="flex justify-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-2"></i> Enregistrer les modifications
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Historique des connexions -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    <i class="fas fa-sign-in-alt mr-2 text-blue-500"></i> Historique des connexions
                </h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">Vos 5 dernières connexions</p>
            </div>
            
            <div class="px-4 py-5 sm:p-6">
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
        
        <!-- Activités récentes -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    <i class="fas fa-history mr-2 text-blue-500"></i> Activités récentes
                </h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">Vos 5 dernières activités</p>
            </div>
            
            <div class="px-4 py-5 sm:p-6">
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
                                                    case 'password_reset':
                                                        $iconClass = 'bg-yellow-500';
                                                        $icon = 'fas fa-key';
                                                        break;
                                                    case 'update_email':
                                                    case 'profile_update':
                                                        $iconClass = 'bg-indigo-500';
                                                        $icon = 'fas fa-user-edit';
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
    </div>
    
    <div class="mt-8 grid grid-cols-1 gap-6">
        <!-- Liens rapides -->
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Sécurité et paramètres</h3>
                <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <a href="password.php" class="btn btn-outline-primary w-full justify-center">
                            <i class="fas fa-key mr-2"></i> Changer mon mot de passe
                        </a>
                    </div>
                    <div>
                        <a href="security.php" class="btn btn-outline-primary w-full justify-center">
                            <i class="fas fa-shield-alt mr-2"></i> Sécurité du compte
                        </a>
                    </div>
                    <div>
                        <a href="settings.php" class="btn btn-outline-primary w-full justify-center">
                            <i class="fas fa-cog mr-2"></i> Paramètres
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Prévisualisation de l'avatar
document.addEventListener('DOMContentLoaded', function() {
    const avatarInput = document.getElementById('avatar');
    const avatarPreview = document.getElementById('avatarPreview');
    const avatarPlaceholder = document.getElementById('avatarPlaceholder');
    
    if (avatarInput && avatarPreview) {
        avatarInput.addEventListener('change', function() {
            const file = this.files[0];
            
            if (file) {
                const reader = new FileReader();
                
                reader.addEventListener('load', function() {
                    avatarPreview.src = reader.result;
                    
                    if (avatarPlaceholder) {
                        avatarPlaceholder.classList.add('hidden');
                    }
                    
                    avatarPreview.classList.remove('hidden');
                });
                
                reader.readAsDataURL(file);
            }
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>