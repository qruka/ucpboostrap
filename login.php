<?php
$pageTitle = "Connexion";
require_once 'includes/header.php';

// Rediriger si l'utilisateur est déjà connecté
if (isLoggedIn()) {
    redirect('index.php');
}

$errors = [];
$username = '';

// Traiter le formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier le token CSRF
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = "Erreur de sécurité. Veuillez réessayer.";
    } else {
        // Récupérer les données
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $rememberMe = isset($_POST['remember_me']);
        
        // Valider les champs
        if (empty($username)) {
            $errors[] = "Le nom d'utilisateur est requis.";
        }
        
        if (empty($password)) {
            $errors[] = "Le mot de passe est requis.";
        }
        
        // Si aucune erreur, tenter l'authentification
        if (empty($errors)) {
            $user = authenticateUser($username, $password);
            
            if ($user) {
                // Stocker l'ID utilisateur en session
                $_SESSION['user_id'] = $user['id'];
                
                // Gérer "Se souvenir de moi" (à implémenter si nécessaire)
                if ($rememberMe) {
                    // Cette fonctionnalité nécessiterait d'autres tables et mécanismes
                    // Non implémentée dans cet exemple pour simplicité
                }
                
                $_SESSION['flash_message'] = "Connexion réussie !";
                $_SESSION['flash_type'] = "success";
                
                redirect('index.php');
            } else {
                $errors[] = "Nom d'utilisateur ou mot de passe incorrect.";
            }
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow">
            <div class="card-body">
                <h2 class="card-title text-center mb-4">Connexion</h2>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?= escapeString($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form action="login.php" method="POST" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">Nom d'utilisateur</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?= escapeString($username) ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Mot de passe</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="remember_me" name="remember_me">
                        <label class="form-check-label" for="remember_me">Se souvenir de moi</label>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt"></i> Se connecter
                        </button>
                    </div>
                </form>
                
                <div class="mt-3 text-center">
                    <p>Vous n'avez pas de compte ? <a href="register.php">Inscrivez-vous</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>