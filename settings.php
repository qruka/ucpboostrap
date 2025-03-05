<?php
$pageTitle = "Paramètres du compte";
require_once 'includes/header.php';

// Rediriger si l'utilisateur n'est pas connecté
if (!isLoggedIn()) {
    setFlashMessage("Veuillez vous connecter pour accéder à cette page.", "warning");
    redirect('login.php');
}

// Récupérer les informations de l'utilisateur
$user = getUserById($_SESSION['user_id']);
if (!$user) {
    setFlashMessage("Utilisateur introuvable.", "danger");
    redirect('index.php');
}

$errors = [];
$success = false;

// Traiter le formulaire de modification d'email
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier le token CSRF
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = "Erreur de sécurité. Veuillez réessayer.";
    } else {
        // Récupérer les données du formulaire
        $email = trim($_POST['email'] ?? '');
        $notifications = isset($_POST['notifications']) ? 1 : 0;
        $language = $_POST['language'] ?? 'fr';
        $theme = $_POST['theme'] ?? 'light';
        
        // Valider l'email
        if (empty($email)) {
            $errors[] = "L'adresse email est requise.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "L'adresse email n'est pas valide.";
        } elseif ($email !== $user['email'] && emailExists($email)) {
            $errors[] = "Cette adresse email est déjà utilisée.";
        }
        
        // Si aucune erreur, mettre à jour le profil
        if (empty($errors)) {
            $conn = connectDB();
            $conn->begin_transaction();
            
            try {
                // Mettre à jour l'email
                $updateSql = "UPDATE users SET email = ? WHERE id = ?";
                $stmt = $conn->prepare($updateSql);
                $stmt->bind_param("si", $email, $_SESSION['user_id']);
                $stmt->execute();
                
                // Enregistrer les préférences utilisateur (simulation)
                // Dans un système réel, vous ajouteriez une table user_preferences
                
                $conn->commit();
                $success = true;
                
                // Mettre à jour les informations pour l'affichage
                $user['email'] = $email;
                
                setFlashMessage("Vos paramètres ont été mis à jour avec succès.", "success");
            } catch (Exception $e) {
                $conn->rollback();
                $errors[] = "Une erreur est survenue lors de la mise à jour de vos paramètres.";
            }
            
            $conn->close();
        }
    }
}

// Simuler des préférences utilisateur
$userPreferences = [
    'notifications' => true,
    'language' => 'fr',
    'theme' => 'light'
];
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Paramètres du compte</h1>
        <p class="text-gray-600 mt-1">Personnalisez votre expérience et gérez vos informations personnelles</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <!-- Menu de navigation latéral -->
        <div class="md:col-span-1">
            <nav class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Paramètres</h2>
                    <div class="space-y-1">
                        <a href="settings.php" class="flex items-center px-3 py-2 rounded-md text-sm font-medium text-white bg-blue-600">
                            <i class="fas fa-cog w-5 h-5 mr-2"></i>
                            <span>Général</span>
                        </a>
                        <a href="profile.php" class="flex items-center px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-user w-5 h-5 mr-2 text-gray-500"></i>
                            <span>Profil</span>
                        </a>
                        <a href="password.php" class="flex items-center px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-key w-5 h-5 mr-2 text-gray-500"></i>
                            <span>Mot de passe</span>
                        </a>
                        <a href="security.php" class="flex items-center px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-shield-alt w-5 h-5 mr-2 text-gray-500"></i>
                            <span>Sécurité</span>
                        </a>
                    </div>
                </div>
            </nav>
            
            <div class="bg-white shadow rounded-lg overflow-hidden mt-8">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center">
                        <div class="h-12 w-12 rounded-full overflow-hidden bg-blue-100 flex-shrink-0 flex items-center justify-center">
                            <?php if (!empty($user['profile_image']) && file_exists($user['profile_image'])): ?>
                                <img src="<?= $user['profile_image'] ?>" alt="Avatar" class="h-full w-full object-cover">
                            <?php else: ?>
                                <i class="fas fa-user text-blue-500 text-xl"></i>
                            <?php endif; ?>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-gray-900"><?= escapeString($user['username']) ?></h3>
                            <p class="text-sm text-gray-500">
                                <span class="user-level user-level-<?= $user['user_level'] ?>">
                                    <?= getUserLevelText($user['user_level']) ?>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Formulaire principal -->
        <div class="md:col-span-2 space-y-8">
            <!-- Paramètres du compte -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">Informations du compte</h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">Mettez à jour votre adresse email et vos préférences de contact</p>
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
                                        Paramètres mis à jour avec succès !
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
                                    <h3 class="text-sm font-medium text-red-800">Des erreurs sont survenues :</h3>
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
                    
                    <form action="settings.php" method="POST" class="space-y-6">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">Adresse email</label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-envelope text-gray-400"></i>
                                </div>
                                <input type="email" name="email" id="email" 
                                      value="<?= escapeString($user['email']) ?>" 
                                      class="form-control pl-10" 
                                      placeholder="vous@exemple.com">
                            </div>
                            <p class="mt-1 text-sm text-gray-500">Cette adresse est utilisée pour les communications importantes et la récupération de compte.</p>
                        </div>
                        
                        <div class="border-t border-gray-200 pt-6">
                            <h4 class="text-base font-medium text-gray-900 mb-4">Préférences</h4>
                            
                            <div class="space-y-4">
                                <div class="flex items-start">
                                    <div class="flex items-center h-5">
                                        <input id="notifications" name="notifications" type="checkbox" 
                                              checked="<?= $userPreferences['notifications'] ? 'checked' : '' ?>"
                                              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <label for="notifications" class="font-medium text-gray-700">Recevoir des notifications par email</label>
                                        <p class="text-gray-500">Recevez des notifications sur les activités importantes et les mises à jour.</p>
                                    </div>
                                </div>
                                
                                <div>
                                    <label for="language" class="block text-sm font-medium text-gray-700">Langue</label>
                                    <select id="language" name="language" class="mt-1 form-control">
                                        <option value="fr" <?= $userPreferences['language'] === 'fr' ? 'selected' : '' ?>>Français</option>
                                        <option value="en" <?= $userPreferences['language'] === 'en' ? 'selected' : '' ?>>English</option>
                                        <option value="es" <?= $userPreferences['language'] === 'es' ? 'selected' : '' ?>>Español</option>
                                        <option value="de" <?= $userPreferences['language'] === 'de' ? 'selected' : '' ?>>Deutsch</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label for="theme" class="block text-sm font-medium text-gray-700">Thème</label>
                                    <select id="theme" name="theme" class="mt-1 form-control">
                                        <option value="light" <?= $userPreferences['theme'] === 'light' ? 'selected' : '' ?>>Clair</option>
                                        <option value="dark" <?= $userPreferences['theme'] === 'dark' ? 'selected' : '' ?>>Sombre</option>
                                        <option value="system" <?= $userPreferences['theme'] === 'system' ? 'selected' : '' ?>>Système</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex justify-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-2"></i> Enregistrer les modifications
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Données personnelles -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">Données personnelles</h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">Gérez vos données personnelles et vos options de confidentialité</p>
                </div>
                
                <div class="px-4 py-5 sm:p-6">
                    <div class="space-y-6">
                        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-yellow-700">
                                        Nous prenons la confidentialité de vos données très au sérieux. Vous pouvez télécharger ou supprimer vos données à tout moment.
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex space-x-4">
                            <button type="button" class="btn btn-outline-primary">
                                <i class="fas fa-download mr-2"></i> Télécharger mes données
                            </button>
                            
                            <button type="button" class="btn btn-outline-secondary">
                                <i class="fas fa-trash-alt mr-2"></i> Supprimer mon compte
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>