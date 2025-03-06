<?php
$pageTitle = "Mon compte";
require_once 'includes/header.php';

// Rediriger si l'utilisateur n'est pas connecté
if (!isLoggedIn()) {
    setFlashMessage("Veuillez vous connecter pour accéder à cette page.", "warning");
    redirect('login.php');
    exit; // Assurez-vous que le script s'arrête ici
}

// Récupérer les informations de l'utilisateur
// Tentative avec gestion d'erreur explicite
$user = null;
try {
    $user = getUserById($_SESSION['user_id']);
    
    // Si getUserById renvoie null, c'est que l'utilisateur n'existe pas
    if (!$user) {
        // Journaliser cette erreur pour le débogage
        error_log("Utilisateur ID {$_SESSION['user_id']} introuvable dans settings.php");
        
        // Déconnecter l'utilisateur car ses données sont invalides
        session_unset();
        session_regenerate_id(true);
        
        setFlashMessage("Votre session a expiré. Veuillez vous reconnecter.", "warning");
        redirect('login.php');
        exit;
    }
} catch (Exception $e) {
    error_log("Exception dans settings.php: " . $e->getMessage());
    setFlashMessage("Une erreur est survenue lors de la récupération de vos informations.", "danger");
    redirect('index.php');
    exit;
}

// Le reste du code settings.php suit ici...

// Récupérer l'historique des connexions
$loginHistory = getUserLoginHistory($_SESSION['user_id'], 5);

// Récupérer les activités récentes
$userActivities = getUserActivities($_SESSION['user_id'], 5);

// Récupérer les IPs connues
$userIPs = getUserIPs($_SESSION['user_id']);

$errors = [];
$success = false;
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'profile';

// Traiter le formulaire de modification du profil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier le token CSRF
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = "Erreur de sécurité. Veuillez réessayer.";
    } else {
        // Déterminer quel formulaire a été soumis
        $formType = isset($_POST['form_type']) ? $_POST['form_type'] : '';
        
        // Traitement du formulaire de profil
        if ($formType === 'profile') {
            $activeTab = 'profile';
            
            // Récupérer les données du formulaire
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
        // Traitement du formulaire de paramètres
        elseif ($formType === 'preferences') {
            $activeTab = 'preferences';
            
            // Récupérer les données du formulaire
            $notifications = isset($_POST['notifications']) ? 1 : 0;
            $language = $_POST['language'] ?? 'fr';
            $theme = $_POST['theme'] ?? 'light';
            
            // Enregistrer les préférences (simulation)
            // Dans un système réel, vous ajouteriez une table user_preferences
            
            // Mise à jour du thème
            setcookie('theme', $theme, time() + 30 * 24 * 60 * 60, '/');
            
            $success = true;
            setFlashMessage("Vos préférences ont été mises à jour avec succès.", "success");
        }
        // Traitement du formulaire de mot de passe
        elseif ($formType === 'password') {
            $activeTab = 'password';
            
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            // Valider les mots de passe
            if (empty($currentPassword)) {
                $errors[] = "Le mot de passe actuel est requis.";
            }
            
            if (empty($newPassword)) {
                $errors[] = "Le nouveau mot de passe est requis.";
            } elseif (strlen($newPassword) < 8) {
                $errors[] = "Le nouveau mot de passe doit contenir au moins 8 caractères.";
            }
            
            if ($newPassword !== $confirmPassword) {
                $errors[] = "Les nouveaux mots de passe ne correspondent pas.";
            }
            
            // Si aucune erreur, changer le mot de passe
            if (empty($errors)) {
                if (changeUserPassword($_SESSION['user_id'], $currentPassword, $newPassword)) {
                    $success = true;
                    setFlashMessage("Votre mot de passe a été modifié avec succès.", "success");
                } else {
                    $errors[] = "Le mot de passe actuel est incorrect ou une erreur est survenue.";
                }
            }
        }
    }
}

// Simuler des préférences utilisateur
$userPreferences = [
    'notifications' => true,
    'language' => 'fr',
    'theme' => $_COOKIE['theme'] ?? 'light'
];
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            <i class="fas fa-user-cog mr-2 text-blue-600 dark:text-blue-400"></i> Mon compte
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Gérez votre profil, vos préférences et vos paramètres de sécurité</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
        <!-- Menu de navigation latéral -->
        <div class="md:col-span-1 space-y-4">
            <!-- Profil utilisateur -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                <div class="p-6">
                    <div class="flex flex-col items-center">
                        <div class="h-24 w-24 rounded-full overflow-hidden bg-gray-200 dark:bg-gray-700 flex items-center justify-center mb-4">
                            <?php if (!empty($user['profile_image']) && file_exists($user['profile_image'])): ?>
                                <img src="<?= $user['profile_image'] ?>" alt="Avatar" class="h-full w-full object-cover">
                            <?php else: ?>
                                <i class="fas fa-user text-gray-400 dark:text-gray-500 text-4xl"></i>
                            <?php endif; ?>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white"><?= escapeString($user['username']) ?></h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            <span class="user-level user-level-<?= $user['user_level'] ?>">
                                <?= getUserLevelText($user['user_level']) ?>
                            </span>
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Membre depuis <?= date('d/m/Y', strtotime($user['created_at'])) ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Menu de navigation -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                <div class="p-4">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Paramètres</h2>
                    <nav class="space-y-1">
                        <a href="?tab=profile" class="flex items-center px-3 py-2 rounded-md text-sm font-medium <?= $activeTab === 'profile' ? 'bg-blue-600 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
                            <i class="fas fa-user w-5 h-5 mr-2 <?= $activeTab === 'profile' ? 'text-white' : 'text-gray-500 dark:text-gray-400' ?>"></i>
                            <span>Profil</span>
                        </a>
                        <a href="?tab=preferences" class="flex items-center px-3 py-2 rounded-md text-sm font-medium <?= $activeTab === 'preferences' ? 'bg-blue-600 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
                            <i class="fas fa-cog w-5 h-5 mr-2 <?= $activeTab === 'preferences' ? 'text-white' : 'text-gray-500 dark:text-gray-400' ?>"></i>
                            <span>Préférences</span>
                        </a>
                        <a href="?tab=password" class="flex items-center px-3 py-2 rounded-md text-sm font-medium <?= $activeTab === 'password' ? 'bg-blue-600 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
                            <i class="fas fa-key w-5 h-5 mr-2 <?= $activeTab === 'password' ? 'text-white' : 'text-gray-500 dark:text-gray-400' ?>"></i>
                            <span>Mot de passe</span>
                        </a>
                        <a href="?tab=security" class="flex items-center px-3 py-2 rounded-md text-sm font-medium <?= $activeTab === 'security' ? 'bg-blue-600 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
                            <i class="fas fa-shield-alt w-5 h-5 mr-2 <?= $activeTab === 'security' ? 'text-white' : 'text-gray-500 dark:text-gray-400' ?>"></i>
                            <span>Sécurité</span>
                        </a>
                        <a href="?tab=activity" class="flex items-center px-3 py-2 rounded-md text-sm font-medium <?= $activeTab === 'activity' ? 'bg-blue-600 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
                            <i class="fas fa-history w-5 h-5 mr-2 <?= $activeTab === 'activity' ? 'text-white' : 'text-gray-500 dark:text-gray-400' ?>"></i>
                            <span>Activité</span>
                        </a>
                    </nav>
                </div>
            </div>
            
            <!-- Suppression du compte -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                <div class="p-4">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Actions</h2>
                    <button type="button" class="w-full flex justify-center items-center px-4 py-2 border border-red-300 dark:border-red-700 rounded-md shadow-sm text-sm font-medium text-red-700 dark:text-red-400 bg-white dark:bg-gray-800 hover:bg-red-50 dark:hover:bg-red-900/30 focus:outline-none">
                        <i class="fas fa-trash-alt mr-2"></i> Supprimer mon compte
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Contenu principal -->
        <div class="md:col-span-3 space-y-6">
            <?php if ($success): ?>
                <div class="bg-green-100 dark:bg-green-900 border-l-4 border-green-500 dark:border-green-600 text-green-700 dark:text-green-200 p-4 rounded">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium">Modifications enregistrées avec succès.</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="bg-red-100 dark:bg-red-900 border-l-4 border-red-500 dark:border-red-600 text-red-700 dark:text-red-200 p-4 rounded">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium">Des erreurs sont survenues:</h3>
                            <ul class="mt-1 text-sm list-disc list-inside">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= escapeString($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Onglet Profil -->
            <?php if ($activeTab === 'profile'): ?>
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Informations du profil</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Mettez à jour vos informations personnelles</p>
                    </div>
                    <div class="p-6">
                        <form action="settings.php?tab=profile" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            <input type="hidden" name="form_type" value="profile">
                            
                            <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                                <div class="sm:col-span-3">
                                    <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nom d'utilisateur</label>
                                    <div class="mt-1">
                                        <input type="text" name="username" id="username" 
                                              value="<?= escapeString($user['username']) ?>" 
                                              class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md">
                                    </div>
                                </div>
                                
                                <div class="sm:col-span-3">
                                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Adresse email</label>
                                    <div class="mt-1">
                                        <input type="email" name="email" id="email" 
                                              value="<?= escapeString($user['email']) ?>" 
                                              class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md">
                                    </div>
                                </div>
                                
                                <div class="sm:col-span-6">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Photo de profil</label>
                                    <div class="mt-2 flex items-center">
                                        <div class="h-16 w-16 rounded-full overflow-hidden bg-gray-100 dark:bg-gray-700">
                                            <?php if (!empty($user['profile_image']) && file_exists($user['profile_image'])): ?>
                                                <img src="<?= $user['profile_image'] ?>" alt="Avatar" class="h-full w-full object-cover" id="avatarPreview">
                                            <?php else: ?>
                                                <div class="h-full w-full flex items-center justify-center bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-500" id="avatarPlaceholder">
                                                    <i class="fas fa-user"></i>
                                                </div>
                                                <img src="" alt="Avatar" class="h-full w-full object-cover hidden" id="avatarPreview">
                                            <?php endif; ?>
                                        </div>
                                        <div class="ml-5">
                                            <div class="relative">
                                                <input type="file" name="avatar" id="avatar" class="sr-only" accept="image/jpeg,image/png,image/gif">
                                                <label for="avatar" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none cursor-pointer">
                                                    <i class="fas fa-upload mr-2"></i> Changer l'avatar
                                                </label>
                                            </div>
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">PNG, JPG ou GIF. 2MB maximum.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="pt-5 border-t border-gray-200 dark:border-gray-700 mt-6">
                                <div class="flex justify-end">
                                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        <i class="fas fa-save mr-2"></i> Enregistrer les modifications
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            
            <!-- Onglet Préférences -->
            <?php elseif ($activeTab === 'preferences'): ?>
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Préférences</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Personnalisez votre expérience</p>
                    </div>
                    <div class="p-6">
                        <form action="settings.php?tab=preferences" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            <input type="hidden" name="form_type" value="preferences">
                            
                            <div class="space-y-6">
                                <div>
                                    <h3 class="text-base font-medium text-gray-900 dark:text-white">Notifications</h3>
                                    <div class="mt-4">
                                        <div class="flex items-start">
                                            <div class="flex items-center h-5">
                                                <input id="notifications" name="notifications" type="checkbox" 
                                                      <?= $userPreferences['notifications'] ? 'checked' : '' ?>
                                                      class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded">
                                            </div>
                                            <div class="ml-3 text-sm">
                                                <label for="notifications" class="font-medium text-gray-700 dark:text-gray-300">Recevoir des notifications par email</label>
                                                <p class="text-gray-500 dark:text-gray-400">Recevez des notifications sur les activités importantes et les mises à jour.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div>
                                    <h3 class="text-base font-medium text-gray-900 dark:text-white">Langue et affichage</h3>
                                    <div class="mt-4 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-2">
                                        <div>
                                            <label for="language" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Langue</label>
                                            <select id="language" name="language" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                                                <option value="fr" <?= $userPreferences['language'] === 'fr' ? 'selected' : '' ?>>Français</option>
                                                <option value="en" <?= $userPreferences['language'] === 'en' ? 'selected' : '' ?>>English</option>
                                                <option value="es" <?= $userPreferences['language'] === 'es' ? 'selected' : '' ?>>Español</option>
                                                <option value="de" <?= $userPreferences['language'] === 'de' ? 'selected' : '' ?>>Deutsch</option>
                                            </select>
                                        </div>
                                        
                                        <div>
                                            <label for="theme" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Thème</label>
                                            <select id="theme" name="theme" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                                                <option value="light" <?= $userPreferences['theme'] === 'light' ? 'selected' : '' ?>>Clair</option>
                                                <option value="dark" <?= $userPreferences['theme'] === 'dark' ? 'selected' : '' ?>>Sombre</option>
                                                <option value="system" <?= $userPreferences['theme'] === 'system' ? 'selected' : '' ?>>Système</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="pt-5 border-t border-gray-200 dark:border-gray-700 mt-6">
                                <div class="flex justify-end">
                                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        <i class="fas fa-save mr-2"></i> Enregistrer les préférences
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            
            <!-- Onglet Mot de passe -->
            <?php elseif ($activeTab === 'password'): ?>
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Changer le mot de passe</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Mettez à jour votre mot de passe pour sécuriser votre compte</p>
                    </div>
                    <div class="p-6">
                        <form action="settings.php?tab=password" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            <input type="hidden" name="form_type" value="password">
                            
                            <div class="space-y-6">
                                <div>
                                    <label for="current_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Mot de passe actuel</label>
                                    <div class="mt-1">
                                        <input type="password" name="current_password" id="current_password" 
                                              class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md" 
                                              required>
                                    </div>
                                </div>
                                
                                <div>
                                    <label for="new_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nouveau mot de passe</label>
                                    <div class="mt-1">
                                        <input type="password" name="new_password" id="new_password" 
                                              class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md" 
                                              required>
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Le mot de passe doit contenir au moins 8 caractères.</p>
                                </div>
                                
                                <div>
                                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Confirmer le nouveau mot de passe</label>
                                    <div class="mt-1">
                                        <input type="password" name="confirm_password" id="confirm_password" 
                                              class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md" 
                                              required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="pt-5 border-t border-gray-200 dark:border-gray-700 mt-6">
                                <div class="flex justify-end">
                                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        <i class="fas fa-key mr-2"></i> Changer le mot de passe
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            
            <!-- Onglet Sécurité -->
            <?php elseif ($activeTab === 'security'): ?>
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Sécurité du compte</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Gérez les paramètres de sécurité de votre compte</p>
                    </div>
                    <div class="p-6">
                        <div class="space-y-6">
                            <!-- État de sécurité -->
                            <div>
                                <h3 class="text-base font-medium text-gray-900 dark:text-white">État de la sécurité</h3>
                                <div class="mt-3 flex items-center">
                                    <div class="flex-shrink-0 h-12 w-12 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center">
                                        <i class="fas fa-lock text-2xl text-green-600 dark:text-green-400"></i>
                                    </div>
                                    <div class="ml-4">
                                        <h4 class="text-lg font-medium text-gray-900 dark:text-white">Compte sécurisé</h4>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Niveau de sécurité: Bon</p>
                                    </div>
                                </div>
                                
                                <div class="mt-4 space-y-3">
                                    <div>
                                        <div class="flex justify-between items-center text-sm mb-1">
                                            <span class="text-gray-700 dark:text-gray-300">Force du mot de passe</span>
                                            <span class="text-green-600 dark:text-green-400">Fort</span>
                                        </div>
                                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                            <div class="bg-green-500 h-2 rounded-full" style="width: 85%"></div>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="flex justify-between items-center text-sm mb-1">
                                            <span class="text-gray-700 dark:text-gray-300">Protection du compte</span>
                                            <span class="text-green-600 dark:text-green-400">Active</span>
                                        </div>
                                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                            <div class="bg-green-500 h-2 rounded-full" style="width: 100%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Adresses IP connues -->
                            <div class="pt-6 border-t border-gray-200 dark:border-gray-700">
                                <h3 class="text-base font-medium text-gray-900 dark:text-white mb-3">Adresses IP connues</h3>
                                
                                <?php if (empty($userIPs)): ?>
                                    <p class="text-gray-500 dark:text-gray-400 text-center py-4">Aucune adresse IP enregistrée.</p>
                                <?php else: ?>
                                    <div class="space-y-3">
                                        <?php foreach ($userIPs as $ip): ?>
                                            <div class="p-3 border rounded-md <?= $ip['is_suspicious'] ? 'bg-red-50 dark:bg-red-900/30 border-red-200 dark:border-red-800' : 'bg-gray-50 dark:bg-gray-700 border-gray-200 dark:border-gray-600' ?>">
                                                <div class="flex justify-between items-start">
                                                    <div>
                                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                            <?= escapeString($ip['ip_address']) ?>
                                                        </div>
                                                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                            <?= $ip['login_count'] ?> connexion<?= $ip['login_count'] > 1 ? 's' : '' ?>
                                                        </div>
                                                    </div>
                                                    <?php if ($ip['is_suspicious']): ?>
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200">
                                                            <i class="fas fa-exclamation-triangle mr-1"></i> Suspect
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                                    <div>Première connexion: <?= date('d/m/Y H:i', strtotime($ip['first_seen'])) ?></div>
                                                    <div>Dernière connexion: <?= date('d/m/Y H:i', strtotime($ip['last_seen'])) ?></div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Conseils de sécurité -->
                            <div class="pt-6 border-t border-gray-200 dark:border-gray-700">
                                <h3 class="text-base font-medium text-gray-900 dark:text-white">Conseils de sécurité</h3>
                                <div class="mt-3">
                                    <ul class="space-y-3">
                                        <li class="flex">
                                            <i class="fas fa-check-circle text-green-500 dark:text-green-400 mt-0.5 mr-2 flex-shrink-0"></i>
                                            <span class="text-sm text-gray-700 dark:text-gray-300">Utilisez un mot de passe fort combinant lettres, chiffres et symboles.</span>
                                        </li>
                                        <li class="flex">
                                            <i class="fas fa-check-circle text-green-500 dark:text-green-400 mt-0.5 mr-2 flex-shrink-0"></i>
                                            <span class="text-sm text-gray-700 dark:text-gray-300">Changez régulièrement votre mot de passe (tous les 3 mois).</span>
                                        </li>
                                        <li class="flex">
                                            <i class="fas fa-check-circle text-green-500 dark:text-green-400 mt-0.5 mr-2 flex-shrink-0"></i>
                                            <span class="text-sm text-gray-700 dark:text-gray-300">Vérifiez régulièrement l'historique des connexions.</span>
                                        </li>
                                        <li class="flex">
                                            <i class="fas fa-check-circle text-green-500 dark:text-green-400 mt-0.5 mr-2 flex-shrink-0"></i>
                                            <span class="text-sm text-gray-700 dark:text-gray-300">Ne partagez jamais vos informations de connexion.</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            
            <!-- Onglet Activité -->
            <?php elseif ($activeTab === 'activity'): ?>
                <div class="space-y-6">
                    <!-- Historique des connexions -->
                    <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Historique des connexions</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Vos dernières connexions au compte</p>
                        </div>
                        <div class="p-6">
                            <?php if (empty($loginHistory)): ?>
                                <p class="text-gray-500 dark:text-gray-400 text-center py-4">Aucune connexion enregistrée.</p>
                            <?php else: ?>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                        <thead>
                                            <tr>
                                                <th class="px-4 py-3 bg-gray-50 dark:bg-gray-700 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Date et heure</th>
                                                <th class="px-4 py-3 bg-gray-50 dark:bg-gray-700 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Adresse IP</th>
                                                <th class="px-4 py-3 bg-gray-50 dark:bg-gray-700 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Statut</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                            <?php foreach ($loginHistory as $login): ?>
                                                <tr>
                                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">
                                                        <?= date('d/m/Y H:i:s', strtotime($login['login_time'])) ?>
                                                    </td>
                                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                        <?= escapeString($login['ip_address']) ?>
                                                    </td>
                                                    <td class="px-4 py-3 whitespace-nowrap">
                                                        <?php if ($login['status'] === 'success'): ?>
                                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">
                                                                <i class="fas fa-check-circle mr-1"></i> Réussie
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200">
                                                                <i class="fas fa-times-circle mr-1"></i> Échouée
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div class="mt-4 bg-blue-50 dark:bg-blue-900 border-l-4 border-blue-400 dark:border-blue-600 p-4 rounded">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-info-circle text-blue-600 dark:text-blue-400"></i>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm text-blue-700 dark:text-blue-200">
                                                Si vous remarquez des connexions suspectes, changez immédiatement votre mot de passe et contactez l'administrateur.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Activités récentes -->
                    <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Activités récentes</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Vos dernières actions sur le site</p>
                        </div>
                        <div class="p-6">
                            <?php if (empty($userActivities)): ?>
                                <p class="text-gray-500 dark:text-gray-400 text-center py-4">Aucune activité récente enregistrée.</p>
                            <?php else: ?>
                                <div class="flow-root">
                                    <ul role="list" class="-mb-8">
                                        <?php foreach ($userActivities as $index => $activity): ?>
                                            <li>
                                                <div class="relative pb-8">
                                                    <?php if ($index < count($userActivities) - 1): ?>
                                                        <span class="absolute top-5 left-5 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-700" aria-hidden="true"></span>
                                                    <?php endif; ?>
                                                    <div class="relative flex items-start space-x-3">
                                                        <div class="relative">
                                                            <?php
                                                            $iconClass = 'bg-blue-500 dark:bg-blue-700';
                                                            $icon = 'fas fa-info';
                                                            
                                                            switch ($activity['activity_type']) {
                                                                case 'login':
                                                                    $iconClass = 'bg-green-500 dark:bg-green-700';
                                                                    $icon = 'fas fa-sign-in-alt';
                                                                    break;
                                                                case 'password_change':
                                                                case 'password_reset':
                                                                    $iconClass = 'bg-yellow-500 dark:bg-yellow-700';
                                                                    $icon = 'fas fa-key';
                                                                    break;
                                                                case 'update_email':
                                                                case 'profile_update':
                                                                    $iconClass = 'bg-indigo-500 dark:bg-indigo-700';
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
                                                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                                                    <?= date('d/m/Y H:i', strtotime($activity['created_at'])) ?>
                                                                </p>
                                                            </div>
                                                            <div class="mt-2 text-sm text-gray-700 dark:text-gray-300">
                                                                <p><?= escapeString($activity['description']) ?></p>
                                                            </div>
                                                            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
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
                </div>
            <?php endif; ?>
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