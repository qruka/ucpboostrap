<?php
// Déterminer si nous sommes dans la section admin
$isAdmin = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;

// S'assurer que la fonction isRegistrationEnabled est disponible
if (!function_exists('isRegistrationEnabled')) {
    require_once __DIR__ . '/login-security.php';
}

// Déterminer la page active
$currentPage = basename($_SERVER['PHP_SELF']);

// Obtenir les informations de l'utilisateur si connecté
$loggedUser = isLoggedIn() ? getUserById($_SESSION['user_id']) : null;

// Récupérer le thème préféré de l'utilisateur (par défaut light)
$userTheme = $_COOKIE['theme'] ?? 'light';
?>

<div class="sidebar transition-all duration-300">
    <!-- Entête de la barre latérale avec logo -->
    <div class="sidebar-header">
        <?php if (file_exists(__DIR__ . '/../assets/img/logo.png')): ?>
            <img src="<?= APP_URL ?>/assets/img/logo.png" alt="<?= APP_NAME ?>" class="logo">
        <?php else: ?>
            <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center text-white font-bold text-xl shadow-md">
                <?= strtoupper(substr(APP_NAME, 0, 1)) ?>
            </div>
        <?php endif; ?>
        <h3 class="text-lg font-semibold text-white"><?= APP_NAME ?></h3>
    </div>
    
    <!-- Section de profil utilisateur si connecté -->
    <?php if ($loggedUser): ?>
    <div class="px-4 py-3 border-b border-gray-700">
        <div class="flex items-center space-x-3">
            <div class="flex-shrink-0">
                <?php if (!empty($loggedUser['profile_image'])): ?>
                    <img src="<?= $loggedUser['profile_image'] ?>" alt="Avatar" class="h-10 w-10 rounded-full object-cover border-2 border-blue-400">
                <?php else: ?>
                    <div class="h-10 w-10 rounded-full bg-gradient-to-br from-gray-700 to-gray-800 flex items-center justify-center text-blue-300 font-medium">
                        <?= strtoupper(substr($loggedUser['username'], 0, 1)) ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-white truncate">
                    <?= escapeString($loggedUser['username']) ?>
                </p>
                <p class="text-xs text-gray-400 truncate">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-900 text-blue-200">
                        <?= getUserLevelText($loggedUser['user_level']) ?>
                    </span>
                </p>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Sélecteur de thème -->
    <div class="px-4 py-2 border-b border-gray-700">
        <div class="flex items-center justify-between">
            <span class="text-sm text-gray-400">Thème</span>
            <button id="theme-toggle" class="p-2 rounded-full bg-gray-700 text-white">
                <?php if ($userTheme === 'dark'): ?>
                    <i class="fas fa-sun"></i>
                <?php else: ?>
                    <i class="fas fa-moon"></i>
                <?php endif; ?>
            </button>
        </div>
    </div>
    
    <!-- Navigation principale -->
    <nav class="sidebar-nav">
        <ul class="py-2">
            <!-- Accueil -->
            <li class="<?= $currentPage == 'index.php' && !$isAdmin ? 'active' : '' ?>">
                <a href="<?= APP_URL ?>/index.php" class="sidebar-link <?= $currentPage == 'index.php' && !$isAdmin ? 'active-link' : '' ?>">
                    <i class="fas fa-home nav-icon"></i>
                    <span>Accueil</span>
                </a>
            </li>
            
            <?php if (isLoggedIn()): ?>
                <!-- Section Utilisateur -->
                <li class="nav-divider">
                    <span>Mon compte</span>
                </li>
                
                <!-- Paramètres (maintenant avec icône utilisateur pour montrer la fusion) -->
                <li class="<?= $currentPage == 'settings.php' ? 'active' : '' ?>">
                    <a href="<?= APP_URL ?>/settings.php" class="sidebar-link <?= $currentPage == 'settings.php' ? 'active-link' : '' ?>">
                        <i class="fas fa-user-cog nav-icon"></i>
                        <span>Mon compte</span>
                    </a>
                </li>
                
                <?php if (isModerator()): ?>
                    <!-- Section Administration -->
                    <li class="nav-divider">
                        <span>Administration</span>
                    </li>
                
                    <!-- Tableau de bord admin centralisé -->
                    <li class="<?= $isAdmin ? 'active' : '' ?>">
                        <a href="<?= APP_URL ?>/admin/index.php" class="sidebar-link <?= $isAdmin ? 'active-link' : '' ?>">
                            <i class="fas fa-tachometer-alt nav-icon"></i>
                            <span>Administration</span>
                        </a>
                    </li>
                <?php endif; ?>
                
                <!-- Déconnexion -->
                <li class="mt-4">
                    <a href="<?= APP_URL ?>/logout.php" class="logout-link">
                        <i class="fas fa-sign-out-alt nav-icon"></i>
                        <span>Déconnexion</span>
                    </a>
                </li>
            <?php else: ?>
                <!-- Connexion pour les visiteurs non connectés -->
                <li class="nav-divider">
                    <span>Compte</span>
                </li>
                
                <li class="<?= $currentPage == 'login.php' ? 'active' : '' ?>">
                    <a href="<?= APP_URL ?>/login.php" class="sidebar-link <?= $currentPage == 'login.php' ? 'active-link' : '' ?>">
                        <i class="fas fa-sign-in-alt nav-icon"></i>
                        <span>Connexion</span>
                    </a>
                </li>
                
                <!-- Inscription (seulement si activée) -->
                <?php if (function_exists('isRegistrationEnabled') && isRegistrationEnabled()): ?>
                    <li class="<?= $currentPage == 'register.php' ? 'active' : '' ?>">
                        <a href="<?= APP_URL ?>/register.php" class="sidebar-link <?= $currentPage == 'register.php' ? 'active-link' : '' ?>">
                            <i class="fas fa-user-plus nav-icon"></i>
                            <span>Inscription</span>
                        </a>
                    </li>
                <?php endif; ?>
            <?php endif; ?>
        </ul>
    </nav>
    
    <!-- Pied de la barre latérale -->
    <div class="sidebar-footer">
        <p class="text-white/70">Version <?= APP_VERSION ?? '1.0' ?></p>
        
        <?php if (isMaintenanceMode() && isAdmin()): ?>
            <div class="mt-2 px-2 py-1 bg-yellow-500 text-white text-xs rounded-md text-center">
                <i class="fas fa-exclamation-triangle mr-1"></i> Mode maintenance activé
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Script pour le toggle de thème -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const themeToggle = document.getElementById('theme-toggle');
    
    if (themeToggle) {
        themeToggle.addEventListener('click', function() {
            // Vérifier le thème actuel
            const currentTheme = document.documentElement.classList.contains('dark') ? 'dark' : 'light';
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            // Mettre à jour les classes
            if (newTheme === 'dark') {
                document.documentElement.classList.add('dark');
                themeToggle.innerHTML = '<i class="fas fa-sun"></i>';
            } else {
                document.documentElement.classList.remove('dark');
                themeToggle.innerHTML = '<i class="fas fa-moon"></i>';
            }
            
            // Sauvegarder la préférence dans un cookie (valide 30 jours)
            document.cookie = `theme=${newTheme};path=/;max-age=${60*60*24*30}`;
        });
    }
});
</script>

<!-- Styles pour la barre latérale -->
<style>
    /* Styles spécifiques à la sidebar */
    .sidebar-link {
        display: flex;
        align-items: center;
        padding: 0.625rem 1rem;
        color: rgba(255, 255, 255, 0.8);
        border-radius: 0.375rem;
        margin: 0.125rem 0.5rem;
        transition: all 0.15s ease-in-out;
    }
    
    .sidebar-link:hover {
        background-color: rgba(255, 255, 255, 0.1);
        color: white;
        transform: translateX(3px);
    }
    
    .active-link, .sidebar-link.active-link {
        background-color: rgba(59, 130, 246, 0.9);
        color: white;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }
    
    .nav-divider {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: rgba(255, 255, 255, 0.4);
        margin-top: 1.25rem;
        margin-bottom: 0.5rem;
        padding: 0 1.5rem;
    }
    
    .nav-icon {
        width: 1.25rem;
        height: 1.25rem;
        margin-right: 0.75rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .logout-link {
        display: flex;
        align-items: center;
        padding: 0.625rem 1rem;
        color: rgba(255, 255, 255, 0.9);
        border-radius: 0.375rem;
        margin: 0.125rem 0.5rem;
        background-color: rgba(239, 68, 68, 0.9);
        transition: all 0.15s ease-in-out;
    }
    
    .logout-link:hover {
        background-color: rgba(220, 38, 38, 1);
        transform: translateY(-2px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }
</style>