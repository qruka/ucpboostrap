<?php
$pageTitle = "Paramètres";
require_once 'includes/header.php';

// Rediriger si l'utilisateur n'est pas connecté
if (!isLoggedIn()) {
    $_SESSION['flash_message'] = "Veuillez vous connecter pour accéder à cette page.";
    $_SESSION['flash_type'] = "warning";
    redirect('login.php');
}

// Récupérer les informations de l'utilisateur
$user = getUserById($_SESSION['user_id']);
if (!$user) {
    $_SESSION['flash_message'] = "Utilisateur non trouvé.";
    $_SESSION['flash_type'] = "danger";
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
        // Récupérer le nouvel email
        $newEmail = trim($_POST['email'] ?? '');
        
        // Valider l'email
        if (empty($newEmail)) {
            $errors[] = "L'adresse email est requise.";
        } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "L'adresse email n'est pas valide.";
        } elseif ($newEmail !== $user['email'] && emailExists($newEmail)) {
            $errors[] = "Cette adresse email est déjà utilisée.";
        }
        
        // Si aucune erreur, mettre à jour l'email
        if (empty($errors)) {
            if (updateUserEmail($_SESSION['user_id'], $newEmail)) {
                $success = true;
                $user['email'] = $newEmail; // Mettre à jour l'email dans les données utilisateur
                
                $_SESSION['flash_message'] = "Votre adresse email a été mise à jour avec succès !";
                $_SESSION['flash_type'] = "success";
            } else {
                $errors[] = "Une erreur est survenue lors de la mise à jour de l'email.";
            }
        }
    }
}
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h1><i class="fas fa-cog me-2"></i> Paramètres du compte</h1>
        <p class="text-muted">Gérez vos informations personnelles et préférences</p>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-body text-center">
                <div class="user-avatar mb-3">
                    <i class="fas fa-user-circle fa-5x text-primary"></i>
                </div>
                <h5 class="card-title"><?= escapeString($user['username']) ?></h5>
                <p class="card-text text-muted">
                    <span class="user-level user-level-<?= $user['user_level'] ?>">
                        <?= getUserLevelText($user['user_level']) ?>
                    </span>
                </p>
                <p class="card-text text-muted">
                    Membre depuis: <?= date('d/m/Y', strtotime($user['created_at'])) ?>
                </p>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Navigation</h5>
            </div>
            <div class="list-group list-group-flush">
                <a href="#email-settings" class="list-group-item list-group-item-action d-flex align-items-center">
                    <i class="fas fa-envelope me-2"></i> Modifier l'email
                </a>
                <a href="#account-security" class="list-group-item list-group-item-action d-flex align-items-center">
                    <i class="fas fa-shield-alt me-2"></i> Sécurité du compte
                </a>
                <?php if (isModerator()): ?>
                    <a href="admin/index.php" class="list-group-item list-group-item-action d-flex align-items-center">
                        <i class="fas fa-lock me-2"></i> Administration
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card mb-4" id="email-settings">
            <div class="card-header">
                <h5 class="mb-0">Modifier l'adresse email</h5>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        Votre adresse email a été mise à jour avec succès !
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
                
                <form action="settings.php" method="POST" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Adresse email actuelle</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" value="<?= escapeString($user['email']) ?>" required>
                        </div>
                        <div class="form-text">Cette adresse est utilisée pour les communications importantes.</div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Mettre à jour l'email
                    </button>
                </form>
            </div>
        </div>
        
        <div class="card" id="account-security">
            <div class="card-header">
                <h5 class="mb-0">Sécurité du compte</h5>
            </div>
            <div class="card-body">
                <p>Pour des raisons de sécurité, le changement de mot de passe n'est pas implémenté dans cet exemple.</p>
                <p>Dans une application réelle, vous pourriez ajouter des fonctionnalités telles que :</p>
                <ul>
                    <li>Changement de mot de passe</li>
                    <li>Authentification à deux facteurs</li>
                    <li>Journal des connexions récentes</li>
                    <li>Historique des activités</li>
                </ul>
                
                <div class="alert alert-info">
                    <h5 class="alert-heading"><i class="fas fa-info-circle"></i> Conseil de sécurité</h5>
                    <p class="mb-0">Utilisez toujours des mots de passe forts et uniques pour chacun de vos comptes en ligne. Un gestionnaire de mots de passe peut vous aider à les gérer.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>