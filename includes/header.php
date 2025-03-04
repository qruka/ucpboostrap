<?php 
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? escapeString($pageTitle) . ' - ' . APP_NAME : APP_NAME ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Styles personnalisés -->
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/dashboard.css">
</head>
<body>
    <div class="dashboard-layout">
        <!-- Menu latéral (IMPORTANT: même inclusion dans toutes les pages) -->
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <!-- Barre de navigation supérieure -->
            <header class="top-header">
                <div class="container-fluid">
                    <div class="header-content">
                        <button id="sidebar-toggle" class="sidebar-toggle">
                            <i class="fas fa-bars"></i>
                        </button>
                        
                        <div class="user-info">
                            <?php if (isLoggedIn()): ?>
                                <?php $user = getUserById($_SESSION['user_id']); ?>
                                <div class="dropdown">
                                    <button class="btn dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-user-circle"></i>
                                        <span><?= escapeString($user['username']) ?></span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                        <li><a class="dropdown-item" href="<?= APP_URL ?>/settings.php">Paramètres</a></li>
                                        <li><a class="dropdown-item" href="<?= APP_URL ?>/password.php">Mot de passe</a></li>
                                        <li><a class="dropdown-item" href="<?= APP_URL ?>/security.php">Sécurité</a></li>
                                        <?php if (isModerator()): ?>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item" href="<?= APP_URL ?>/admin/index.php">Administration</a></li>
                                        <?php endif; ?>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item" href="<?= APP_URL ?>/logout.php">Déconnexion</a></li>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Conteneur principal -->
            <div class="container-fluid py-4 px-4">
                <!-- Affichage des messages flash -->
                <?php if (isset($_SESSION['flash_message'])): ?>
                    <div class="alert alert-<?= $_SESSION['flash_type'] ?? 'info' ?> alert-dismissible fade show">
                        <?= $_SESSION['flash_message'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php 
                        // Supprime le message flash après l'avoir affiché
                        unset($_SESSION['flash_message']); 
                        unset($_SESSION['flash_type']);
                    ?>
                <?php endif; ?>