<?php
$pageTitle = "Accueil";
require_once 'includes/header.php';

// Récupérer les informations de l'utilisateur connecté
$user = null;
if (isLoggedIn()) {
    $user = getUserById($_SESSION['user_id']);
}
?>

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow">
            <div class="card-body">
                <h1 class="card-title mb-4">Bienvenue sur <?= APP_NAME ?></h1>
                
                <?php if ($user): ?>
                    <div class="alert alert-success">
                        <h4>Bonjour, <?= escapeString($user['username']) ?> !</h4>
                        <p>Vous êtes connecté avec l'adresse email: <?= escapeString($user['email']) ?></p>
                        <p>Niveau d'accès: <?= getUserLevelText($user['user_level']) ?></p>
                    </div>
                    
                    <div class="d-flex gap-3 mt-4">
                        <a href="settings.php" class="btn btn-primary">
                            <i class="fas fa-cog"></i> Gérer mes paramètres
                        </a>
                        
                        <?php if (isModerator()): ?>
                            <!-- BOUTON D'ADMINISTRATION BIEN VISIBLE -->
                            <a href="admin/index.php" class="btn btn-danger">
                                <i class="fas fa-lock"></i> Administration
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <p class="lead">Pour accéder à toutes les fonctionnalités, veuillez vous connecter ou créer un compte.</p>
                    
                    <div class="d-flex gap-3 mt-4">
                        <a href="login.php" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt"></i> Connexion
                        </a>
                        <a href="register.php" class="btn btn-outline-primary">
                            <i class="fas fa-user-plus"></i> Inscription
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card shadow">
            <div class="card-body">
                <h2 class="card-title">À propos de ce site</h2>
                <p>Ce site web dispose de :</p>
                <ul>
                    <li>Création de compte utilisateur</li>
                    <li>Connexion sécurisée</li>
                    <li>Gestion des paramètres</li>
                    <li>Système d'administration à 4 niveaux</li>
                </ul>
                
                <h5 class="mt-4">Niveaux d'utilisateurs</h5>
                <div class="list-group">
                    <div class="list-group-item">
                        <span class="user-level user-level-1">Utilisateur</span>
                        <p class="mb-0 mt-1 small">Accès aux fonctionnalités de base</p>
                    </div>
                    <div class="list-group-item">
                        <span class="user-level user-level-2">Modérateur</span>
                        <p class="mb-0 mt-1 small">Accès au tableau de bord d'administration</p>
                    </div>
                    <div class="list-group-item">
                        <span class="user-level user-level-3">Administrateur</span>
                        <p class="mb-0 mt-1 small">Gestion des utilisateurs</p>
                    </div>
                    <div class="list-group-item">
                        <span class="user-level user-level-4">Super Admin</span>
                        <p class="mb-0 mt-1 small">Accès complet au système</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>