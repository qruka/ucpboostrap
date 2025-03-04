<?php
// Déterminer si nous sommes dans l'admin
$isAdmin = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;

// Déterminer la page active
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar">
    <div class="sidebar-header">
        <img src="<?= APP_URL ?>/assets/img/logo.png" alt="<?= APP_NAME ?>" class="logo">
        <h3><?= APP_NAME ?></h3>
    </div>
    
    <nav class="sidebar-nav">
        <ul>
            <li class="<?= $currentPage == 'index.php' && !$isAdmin ? 'active' : '' ?>">
                <a href="<?= APP_URL ?>/index.php">
                    <i class="fas fa-home nav-icon"></i>
                    <span>Accueil</span>
                </a>
            </li>
            
            <?php if (isLoggedIn()): ?>
                <li class="<?= $currentPage == 'settings.php' ? 'active' : '' ?>">
                    <a href="<?= APP_URL ?>/settings.php">
                        <i class="fas fa-cog nav-icon"></i>
                        <span>Paramètres</span>
                    </a>
                </li>
                
                <!-- Lien Mot de passe (nouveau) -->
                <li class="<?= $currentPage == 'password.php' ? 'active' : '' ?>">
                    <a href="<?= APP_URL ?>/password.php">
                        <i class="fas fa-key nav-icon"></i>
                        <span>Mot de passe</span>
                    </a>
                </li>
                
                <!-- Lien Sécurité (nouveau) -->
                <li class="<?= $currentPage == 'security.php' ? 'active' : '' ?>">
                    <a href="<?= APP_URL ?>/security.php">
                        <i class="fas fa-shield-alt nav-icon"></i>
                        <span>Sécurité</span>
                    </a>
                </li>
                
                <?php if (isModerator()): ?>
                    <li class="nav-divider">Administration</li>
                
                    <li class="<?= $isAdmin && $currentPage == 'index.php' ? 'active' : '' ?>">
                        <a href="<?= APP_URL ?>/admin/index.php">
                            <i class="fas fa-tachometer-alt nav-icon"></i>
                            <span>Tableau de bord</span>
                        </a>
                    </li>
                    
                    <?php if (isAdmin()): ?>
                        <li class="<?= $currentPage == 'users.php' ? 'active' : '' ?>">
                            <a href="<?= APP_URL ?>/admin/users.php">
                                <i class="fas fa-users nav-icon"></i>
                                <span>Gérer les comptes</span>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
                
                <li>
                    <a href="<?= APP_URL ?>/logout.php" class="logout-link">
                        <i class="fas fa-sign-out-alt nav-icon"></i>
                        <span>Déconnexion</span>
                    </a>
                </li>
            <?php else: ?>
                <li class="<?= $currentPage == 'login.php' ? 'active' : '' ?>">
                    <a href="<?= APP_URL ?>/login.php">
                        <i class="fas fa-sign-in-alt nav-icon"></i>
                        <span>Connexion</span>
                    </a>
                </li>
                
                <li class="<?= $currentPage == 'register.php' ? 'active' : '' ?>">
                    <a href="<?= APP_URL ?>/register.php">
                        <i class="fas fa-user-plus nav-icon"></i>
                        <span>Inscription</span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
    
    <div class="sidebar-footer">
        <p>Version <?= APP_VERSION ?? '1.0' ?></p>
    </div>
</div>