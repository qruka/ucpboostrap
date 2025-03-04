<?php
$pageTitle = "Inscription";
require_once 'includes/header.php';

// Rediriger si l'utilisateur est déjà connecté
if (isLoggedIn()) {
    redirect('index.php');
}

$errors = [];
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
        
        // Valider le nom d'utilisateur
        if (empty($username)) {
            $errors[] = "Le nom d'utilisateur est requis.";
        } elseif (strlen($username) < 3 || strlen($username) > 30) {
            $errors[] = "Le nom d'utilisateur doit contenir entre 3 et 30 caractères.";
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
        } elseif ($password !== $confirmPassword) {
            $errors[] = "Les mots de passe ne correspondent pas.";
        }
        
        // Si aucune erreur, créer l'utilisateur
        if (empty($errors)) {
            $userId = createUser($username, $email, $password);
            
            if ($userId) {
                // Connecter automatiquement l'utilisateur
                $_SESSION['user_id'] = $userId;
                $_SESSION['flash_message'] = "Votre compte a été créé avec succès !";
                $_SESSION['flash_type'] = "success";
                
                redirect('index.php');
            } else {
                $errors[] = "Une erreur est survenue lors de la création du compte. Veuillez réessayer.";
            }
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow">
            <div class="card-body">
                <h2 class="card-title text-center mb-4">Créer un compte</h2>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?= escapeString($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form action="register.php" method="POST" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">Nom d'utilisateur</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?= escapeString($username) ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Adresse email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= escapeString($email) ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Mot de passe</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div class="form-text">Le mot de passe doit contenir au moins 8 caractères.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirmer le mot de passe</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> S'inscrire
                        </button>
                    </div>
                </form>
                
                <div class="mt-3 text-center">
                    <p>Vous avez déjà un compte ? <a href="login.php">Connectez-vous</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>