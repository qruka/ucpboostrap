<?php
// Déterminer si nous sommes dans l'admin
$isAdmin = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;

// Déterminer la page active
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar">
    <div class="sidebar-header">
        <img src="<?= APP_URL ?>/assets/img/logo.png" alt="<?= APP_NAME ?>" class="logo">
        <h3 class="text-lg font-semibold text-white"><?= APP_NAME ?></h3>
    </div>
    
    <nav class="sidebar-nav">
        <ul>
            <li class="<?= $currentPage == 'index.php' && !$isAdmin ? 'active' : '' ?>">
                <a href="<?= APP_URL ?>/index.php" class="flex items-center py-3 px-5 hover:bg-white/10 transition-colors duration-200 <?= $currentPage == 'index.php' && !$isAdmin ? 'bg-blue-600' : '' ?>">
                    <i class="fas fa-home nav-icon w-5 h-5 mr-3"></i>
                    <span>Accueil</span>
                </a>
            </li>
            
            <?php if (isLoggedIn()): ?>
                <li class="<?= $currentPage == 'settings.php' ? 'active' : '' ?>">
                    <a href="<?= APP_URL ?>/settings.php" class="flex items-center py-3 px-5 hover:bg-white/10 transition-colors duration-200 <?= $currentPage == 'settings.php' ? 'bg-blue-600' : '' ?>">
                        <i class="fas fa-cog nav-icon w-5 h-5 mr-3"></i>
                        <span>Paramètres</span>
                    </a>
                </li>
                
                <!-- Lien Mot de passe (nouveau) -->
                <li class="<?= $currentPage == 'password.php' ? 'active' : '' ?>">
                    <a href="<?= APP_URL ?>/password.php" class="flex items-center py-3 px-5 hover:bg-white/10 transition-colors duration-200 <?= $currentPage == 'password.php' ? 'bg-blue-600' : '' ?>">
                        <i class="fas fa-key nav-icon w-5 h-5 mr-3"></i>
                        <span>Mot de passe</span>
                    </a>
                </li>
                
                <!-- Lien Sécurité (nouveau) -->
                <li class="<?= $currentPage == 'security.php' ? 'active' : '' ?>">
                    <a href="<?= APP_URL ?>/security.php" class="flex items-center py-3 px-5 hover:bg-white/10 transition-colors duration-200 <?= $currentPage == 'security.php' ? 'bg-blue-600' : '' ?>">
                        <i class="fas fa-shield-alt nav-icon w-5 h-5 mr-3"></i>
                        <span>Sécurité</span>
                    </a>
                </li>
                
                <?php if (isModerator()): ?>
                    <li class="nav-divider text-xs uppercase tracking-wider py-5 px-5 opacity-70">
                        Administration
                    </li>
                
                    <li class="<?= $isAdmin && $currentPage == 'index.php' ? 'active' : '' ?>">
                        <a href="<?= APP_URL ?>/admin/index.php" class="flex items-center py-3 px-5 hover:bg-white/10 transition-colors duration-200 <?= $isAdmin && $currentPage == 'index.php' ? 'bg-blue-600' : '' ?>">
                            <i class="fas fa-tachometer-alt nav-icon w-5 h-5 mr-3"></i>
                            <span>Tableau de bord</span>
                        </a>
                    </li>
                    
                    <?php if (isAdmin()): ?>
                        <li class="<?= $currentPage == 'users.php' ? 'active' : '' ?>">
                            <a href="<?= APP_URL ?>/admin/users.php" class="flex items-center py-3 px-5 hover:bg-white/10 transition-colors duration-200 <?= $currentPage == 'users.php' ? 'bg-blue-600' : '' ?>">
                                <i class="fas fa-users nav-icon w-5 h-5 mr-3"></i>
                                <span>Gérer les comptes</span>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
                
                <li>
                    <a href="<?= APP_URL ?>/logout.php" class="flex items-center py-3 px-5 mx-4 my-2 bg-red-700 hover:bg-red-600 rounded-lg transition-colors duration-200 text-white">
                        <i class="fas fa-sign-out-alt nav-icon w-5 h-5 mr-3"></i>
                        <span>Déconnexion</span>
                    </a>
                </li>
            <?php else: ?>
                <li class="<?= $currentPage == 'login.php' ? 'active' : '' ?>">
                    <a href="<?= APP_URL ?>/login.php" class="flex items-center py-3 px-5 hover:bg-white/10 transition-colors duration-200 <?= $currentPage == 'login.php' ? 'bg-blue-600' : '' ?>">
                        <i class="fas fa-sign-in-alt nav-icon w-5 h-5 mr-3"></i>
                        <span>Connexion</span>
                    </a>
                </li>
                
                <li class="<?= $currentPage == 'register.php' ? 'active' : '' ?>">
                    <a href="<?= APP_URL ?>/register.php" class="flex items-center py-3 px-5 hover:bg-white/10 transition-colors duration-200 <?= $currentPage == 'register.php' ? 'bg-blue-600' : '' ?>">
                        <i class="fas fa-user-plus nav-icon w-5 h-5 mr-3"></i>
                        <span>Inscription</span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
    
    <div class="sidebar-footer px-4 py-3 border-t border-white/10 text-xs text-center">
        <p class="text-white/70">Version <?= APP_VERSION ?? '1.0' ?></p>
    </div>
</div>