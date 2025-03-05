<?php
$pageTitle = "Inscription";
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/login-security.php';

// Vérifier si les inscriptions sont activées
if (!isRegistrationEnabled() && !isAdmin()) {
    setFlashMessage("Les inscriptions sont actuellement désactivées.", "warning");
    redirect('index.php');
}

// Rediriger si le site est en maintenance (sauf pour les administrateurs)
if (isMaintenanceMode() && !isAdmin()) {
    redirect('maintenance.php');
}

// Rediriger si l'utilisateur est déjà connecté
if (isLoggedIn()) {
    redirect('index.php');
}

$errors = [];
$success = false;
$username = '';
$email = '';

// Traiter le formulaire d'inscription
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier le token CSRF
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = "Erreur de sécurité. Veuillez réessayer.";
    } else {
        // Récupérer et nettoyer les données
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $agreeTerms = isset($_POST['agree_terms']);
        
        // Valider les conditions d'utilisation
        if (!$agreeTerms) {
            $errors[] = "Vous devez accepter les conditions d'utilisation pour vous inscrire.";
        }
        
        // Valider le nom d'utilisateur
        if (empty($username)) {
            $errors[] = "Le nom d'utilisateur est requis.";
        } elseif (strlen($username) < 3 || strlen($username) > 30) {
            $errors[] = "Le nom d'utilisateur doit contenir entre 3 et 30 caractères.";
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors[] = "Le nom d'utilisateur ne peut contenir que des lettres, des chiffres et des underscores.";
        } elseif (usernameExists($username)) {
            $errors[] = "Ce nom d'utilisateur est déjà pris.";
        }
        
        // Valider l'email
        if (empty($email)) {
            $errors[] = "L'adresse email est requise.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "L'adresse email n'est pas valide.";
        } elseif (emailExists($email)) {
            $errors[] = "Cette adresse email est déjà utilisée.";
        }
        
        // Valider le mot de passe
        if (empty($password)) {
            $errors[] = "Le mot de passe est requis.";
        } elseif (strlen($password) < 8) {
            $errors[] = "Le mot de passe doit contenir au moins 8 caractères.";
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Le mot de passe doit contenir au moins une lettre majuscule.";
        } elseif (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Le mot de passe doit contenir au moins un chiffre.";
        } elseif ($password !== $confirmPassword) {
            $errors[] = "Les mots de passe ne correspondent pas.";
        }
        
        // Si aucune erreur, créer l'utilisateur
        if (empty($errors)) {
            $userId = createUser($username, $email, $password);
            
            if ($userId) {
                // Connecter automatiquement l'utilisateur
                $_SESSION['user_id'] = $userId;
                $success = true;
                
                // Envoyer un email de bienvenue (simulé ici)
                // En production, on enverrait un vrai email
                
                setFlashMessage("Votre compte a été créé avec succès !", "success");
                
                // Rediriger après un court délai
                header("refresh:2;url=index.php");
            } else {
                $errors[] = "Une erreur est survenue lors de la création du compte. Veuillez réessayer.";
            }
        }
    }
}

require_once 'includes/header.php';
?>

<div class="flex justify-center py-12">
    <div class="w-full max-w-xl">
        <?php if ($success): ?>
            <div class="bg-white rounded-lg shadow-xl overflow-hidden">
                <div class="px-6 py-8 text-center">
                    <div class="mb-6 rounded-full bg-green-100 p-6 w-24 h-24 mx-auto flex items-center justify-center">
                        <i class="fas fa-check text-5xl text-green-500"></i>
                    </div>
                    
                    <h2 class="text-3xl font-bold text-gray-800 mb-4">Inscription réussie !</h2>
                    <p class="text-lg text-gray-600 mb-6">Bienvenue sur <?= APP_NAME ?>, <?= escapeString($username) ?> !</p>
                    <p class="text-gray-600 mb-8">Votre compte a été créé avec succès et vous êtes maintenant connecté. Vous allez être redirigé vers la page d'accueil...</p>
                    
                    <a href="index.php" class="inline-flex justify-center items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none">
                        <i class="fas fa-home mr-2"></i> Aller à l'accueil
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-lg shadow-xl overflow-hidden">
                <div class="px-6 py-8">
                    <div class="text-center mb-8">
                        <h2 class="text-3xl font-bold text-gray-800">Créer un compte</h2>
                        <p class="text-gray-600 mt-2">Rejoignez notre communauté</p>
                    </div>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-exclamation-circle text-red-500 mt-0.5"></i>
                                </div>
                                <div class="ml-3">
                                    <ul class="list-disc space-y-1 pl-5">
                                        <?php foreach ($errors as $error): ?>
                                            <li><?= escapeString($error) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <form action="register.php" method="POST" novalidate class="space-y-6">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        
                        <div>
                            <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Nom d'utilisateur</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-user text-gray-400"></i>
                                </div>
                                <input type="text" id="username" name="username" value="<?= escapeString($username) ?>" required
                                    class="form-control pl-10" placeholder="Choisissez un nom d'utilisateur">
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Lettres, chiffres et underscores uniquement. Entre 3 et 30 caractères.</p>
                        </div>
                        
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Adresse email</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-envelope text-gray-400"></i>
                                </div>
                                <input type="email" id="email" name="email" value="<?= escapeString($email) ?>" required
                                    class="form-control pl-10" placeholder="exemple@domaine.com">
                            </div>
                        </div>
                        
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Mot de passe</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-lock text-gray-400"></i>
                                </div>
                                <input type="password" id="password" name="password" required
                                    class="form-control pl-10" placeholder="Créez un mot de passe fort">
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Au moins 8 caractères, incluant une majuscule et un chiffre.</p>
                        </div>
                        
                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirmer le mot de passe</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-lock text-gray-400"></i>
                                </div>
                                <input type="password" id="confirm_password" name="confirm_password" required
                                    class="form-control pl-10" placeholder="Retapez votre mot de passe">
                            </div>
                        </div>
                        
                        <div class="flex items-center">
                            <input type="checkbox" id="agree_terms" name="agree_terms"
                                  class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="agree_terms" class="ml-2 block text-sm text-gray-700">
                                J'accepte les <a href="#" class="text-blue-600 hover:text-blue-500">conditions d'utilisation</a> et la <a href="#" class="text-blue-600 hover:text-blue-500">politique de confidentialité</a>
                            </label>
                        </div>
                        
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                            <i class="fas fa-user-plus mr-2"></i> Créer mon compte
                        </button>
                    </form>
                    
                    <div class="mt-8 text-center">
                        <p class="text-gray-600">
                            Vous avez déjà un compte ? 
                            <a href="login.php" class="font-medium text-blue-600 hover:text-blue-500">
                                Connectez-vous
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('password');
    if (passwordInput) {
        passwordInput.addEventListener('input', checkPasswordStrength);
    }
    
    function checkPasswordStrength() {
        const password = passwordInput.value;
        let strength = 0;
        
        // Trouver ou créer l'indicateur de force
        let strengthMeter = document.getElementById('password-strength');
        if (!strengthMeter) {
            strengthMeter = document.createElement('div');
            strengthMeter.id = 'password-strength';
            strengthMeter.className = 'mt-1 h-2 rounded';
            passwordInput.parentNode.appendChild(strengthMeter);
        }
        
        // Trouver ou créer le texte de force
        let strengthText = document.getElementById('password-strength-text');
        if (!strengthText) {
            strengthText = document.createElement('div');
            strengthText.id = 'password-strength-text';
            strengthText.className = 'text-xs mt-1';
            passwordInput.parentNode.appendChild(strengthText);
        }
        
        // Réinitialiser
        strengthMeter.className = 'mt-1 h-2 rounded';
        strengthMeter.style.width = '0%';
        strengthText.className = 'text-xs mt-1';
        strengthText.textContent = '';
        
        if (password.length === 0) {
            return;
        }
        
        // Calculer la force
        if (password.length >= 8) strength += 1;
        if (password.length >= 12) strength += 1;
        if (/[A-Z]/.test(password)) strength += 1;
        if (/[0-9]/.test(password)) strength += 1;
        if (/[^A-Za-z0-9]/.test(password)) strength += 1;
        
        // Afficher la force
        const percentage = Math.min(100, (strength / 5) * 100);
        strengthMeter.style.width = percentage + '%';
        
        if (strength < 2) {
            strengthMeter.classList.add('bg-red-500');
            strengthText.classList.add('text-red-600');
            strengthText.textContent = 'Très faible';
        } else if (strength < 3) {
            strengthMeter.classList.add('bg-orange-500');
            strengthText.classList.add('text-orange-600');
            strengthText.textContent = 'Faible';
        } else if (strength < 4) {
            strengthMeter.classList.add('bg-yellow-500');
            strengthText.classList.add('text-yellow-600');
            strengthText.textContent = 'Moyen';
        } else if (strength < 5) {
            strengthMeter.classList.add('bg-blue-500');
            strengthText.classList.add('text-blue-600');
            strengthText.textContent = 'Fort';
        } else {
            strengthMeter.classList.add('bg-green-500');
            strengthText.classList.add('text-green-600');
            strengthText.textContent = 'Très fort';
        }
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>