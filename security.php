<?php
$pageTitle = "Sécurité du compte";
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

// Simuler des données pour la démonstration
$lastLogins = [
    ['date' => date('Y-m-d H:i:s', strtotime('-1 hour')), 'ip' => $_SERVER['REMOTE_ADDR'], 'status' => 'success'],
    ['date' => date('Y-m-d H:i:s', strtotime('-1 day')), 'ip' => $_SERVER['REMOTE_ADDR'], 'status' => 'success'],
    ['date' => date('Y-m-d H:i:s', strtotime('-2 day')), 'ip' => '192.168.1.1', 'status' => 'failed'],
];

?>

<div class="row mb-4">
    <div class="col-md-12">
        <h1><i class="fas fa-shield-alt me-2"></i> Sécurité du compte</h1>
        <p class="text-muted">Consultez et gérez les paramètres de sécurité de votre compte</p>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Navigation</h5>
            </div>
            <div class="list-group list-group-flush">
                <a href="#logins-section" class="list-group-item list-group-item-action d-flex align-items-center">
                    <i class="fas fa-sign-in-alt me-2"></i> Connexions récentes
                </a>
                <a href="#security-tips" class="list-group-item list-group-item-action d-flex align-items-center">
                    <i class="fas fa-info-circle me-2"></i> Conseils de sécurité
                </a>
                <a href="password.php" class="list-group-item list-group-item-action d-flex align-items-center">
                    <i class="fas fa-key me-2"></i> Changer le mot de passe
                </a>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Statut de sécurité</h5>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="me-3">
                        <i class="fas fa-lock fa-2x text-success"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">Protection du compte</h6>
                        <p class="text-muted mb-0">Statut: <span class="text-success">Actif</span></p>
                    </div>
                </div>
                
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <i class="fas fa-shield-alt fa-2x text-primary"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">Dernière connexion</h6>
                        <p class="text-muted mb-0">Aujourd'hui à <?= date('H:i') ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card mb-4" id="logins-section">
            <div class="card-header">
                <h5 class="mb-0">Connexions récentes</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date et heure</th>
                                <th>Adresse IP</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lastLogins as $login): ?>
                                <tr>
                                    <td><?= date('d/m/Y H:i', strtotime($login['date'])) ?></td>
                                    <td><?= $login['ip'] ?></td>
                                    <td>
                                        <?php if ($login['status'] === 'success'): ?>
                                            <span class="badge bg-success">Réussie</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Échouée</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle me-2"></i> Cette liste montre les connexions récentes à votre compte. En cas d'activité suspecte, changez immédiatement votre mot de passe.
                </div>
            </div>
        </div>
        
        <div class="card" id="security-tips">
            <div class="card-header">
                <h5 class="mb-0">Conseils de sécurité</h5>
            </div>
            <div class="card-body">
                <h6 class="mb-3">Pour sécuriser votre compte :</h6>
                
                <div class="d-flex mb-3">
                    <div class="me-3">
                        <i class="fas fa-check-circle text-success"></i>
                    </div>
                    <div>
                        <strong>Utilisez un mot de passe unique et fort</strong>
                        <p class="text-muted mb-0">Au moins 8 caractères avec des lettres majuscules, minuscules, chiffres et caractères spéciaux.</p>
                    </div>
                </div>
                
                <div class="d-flex mb-3">
                    <div class="me-3">
                        <i class="fas fa-check-circle text-success"></i>
                    </div>
                    <div>
                        <strong>Changez régulièrement votre mot de passe</strong>
                        <p class="text-muted mb-0">Il est recommandé de changer votre mot de passe tous les 3 mois.</p>
                    </div>
                </div>
                
                <div class="d-flex mb-3">
                    <div class="me-3">
                        <i class="fas fa-check-circle text-success"></i>
                    </div>
                    <div>
                        <strong>Vérifiez régulièrement l'activité de votre compte</strong>
                        <p class="text-muted mb-0">Consultez l'historique des connexions pour détecter tout accès non autorisé.</p>
                    </div>
                </div>
                
                <div class="alert alert-warning mt-3">
                    <i class="fas fa-exclamation-triangle me-2"></i> Ne partagez jamais votre mot de passe avec d'autres personnes et ne l'utilisez pas sur d'autres sites web.
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>