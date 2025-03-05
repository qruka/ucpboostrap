
<?php
// Déterminer si nous sommes dans la section admin
$isAdmin = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;

// Inclure le fichier contenant la fonction isRegistrationEnabled()
require_once __DIR__ . '/login-security.php';

// Déterminer la page active
$currentPage = basename($_SERVER['PHP_SELF']);

// Identifiant unique pour les menus déroulants
$dropdownId = uniqid('dropdown_');
?>

<?php
// Déterminer si nous sommes dans la section admin
$isAdmin = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;

// Déterminer la page active
$currentPage = basename($_SERVER['PHP_SELF']);

// Identifiant unique pour les menus déroulants
$dropdownId = uniqid('dropdown_');
?>

<div class="sidebar transition-all duration-300">
    <div class="sidebar-header">
        <?php if (file_exists('assets/img/logo.png')): ?>
            <img src="<?= APP_URL ?>/assets/img/logo.png" alt="<?= APP_NAME ?>" class="logo">
        <?php else: ?>
            <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white font-bold text-xl">
                <?= strtoupper(substr(APP_NAME, 0, 1)) ?>
            </div>
        <?php endif; ?>
        <h3 class="text-lg font-semibold text-white"><?= APP_NAME ?></h3>
    </div>
    
    <nav class="sidebar-nav">
        <ul>
            <!-- Liens publics -->
            <li class="<?= $currentPage == 'index.php' && !$isAdmin ? 'active' : '' ?>">
                <a href="<?= APP_URL ?>/index.php" class="flex items-center py-3 px-5 hover:bg-white/10 transition-colors duration-200 <?= $currentPage == 'index.php' && !$isAdmin ? 'bg-blue-600' : '' ?>">
                    <i class="fas fa-home nav-icon w-5 h-5 mr-3"></i>
                    <span>Accueil</span>
                </a>
            </li>
            
            <?php if (isLoggedIn()): ?>
                <!-- Section Utilisateur -->
                <li class="nav-divider text-xs uppercase tracking-wider py-5 px-5 pb-1 opacity-70">
                    Compte Utilisateur
                </li>
                
                <!-- Profil -->
                <li class="<?= $currentPage == 'profile.php' ? 'active' : '' ?>">
                    <a href="<?= APP_URL ?>/profile.php" class="flex items-center py-3 px-5 hover:bg-white/10 transition-colors duration-200 <?= $currentPage == 'profile.php' ? 'bg-blue-600' : '' ?>">
                        <i class="fas fa-user nav-icon w-5 h-5 mr-3"></i>
                        <span>Mon profil</span>
                    </a>
                </li>
                
                <!-- Paramètres -->
                <li class="<?= in_array($currentPage, ['settings.php', 'password.php', 'security.php']) && !$isAdmin ? 'active' : '' ?>">
                    <a href="javascript:void(0);" class="flex items-center justify-between py-3 px-5 hover:bg-white/10 transition-colors duration-200 <?= in_array($currentPage, ['settings.php', 'password.php', 'security.php']) && !$isAdmin ? 'bg-blue-600' : '' ?>" data-dropdown-toggle="settingsDropdown">
                        <div class="flex items-center">
                            <i class="fas fa-cog nav-icon w-5 h-5 mr-3"></i>
                            <span>Paramètres</span>
                        </div>
                        <i class="fas fa-chevron-down text-xs"></i>
                    </a>
                    
                    <div id="settingsDropdown" class="hidden pl-12 bg-gray-900/30">
                        <ul>
                            <li class="<?= $currentPage == 'settings.php' ? 'active' : '' ?>">
                                <a href="<?= APP_URL ?>/settings.php" class="flex items-center py-2 px-5 hover:bg-white/10 transition-colors duration-200 <?= $currentPage == 'settings.php' ? 'text-blue-400' : '' ?>">
                                    <i class="fas fa-sliders-h nav-icon w-4 h-4 mr-3"></i>
                                    <span>Général</span>
                                </a>
                            </li>
                            <li class="<?= $currentPage == 'password.php' ? 'active' : '' ?>">
                                <a href="<?= APP_URL ?>/password.php" class="flex items-center py-2 px-5 hover:bg-white/10 transition-colors duration-200 <?= $currentPage == 'password.php' ? 'text-blue-400' : '' ?>">
                                    <i class="fas fa-key nav-icon w-4 h-4 mr-3"></i>
                                    <span>Mot de passe</span>
                                </a>
                            </li>
                            <li class="<?= $currentPage == 'security.php' ? 'active' : '' ?>">
                                <a href="<?= APP_URL ?>/security.php" class="flex items-center py-2 px-5 hover:bg-white/10 transition-colors duration-200 <?= $currentPage == 'security.php' ? 'text-blue-400' : '' ?>">
                                    <i class="fas fa-shield-alt nav-icon w-4 h-4 mr-3"></i>
                                    <span>Sécurité</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                
                <?php if (isModerator()): ?>
                    <!-- Section Administration -->
                    <li class="nav-divider text-xs uppercase tracking-wider py-5 px-5 pb-1 opacity-70">
                        Administration
                    </li>
                
                    <!-- Tableau de bord admin -->
                    <li class="<?= $isAdmin && $currentPage == 'index.php' ? 'active' : '' ?>">
                        <a href="<?= APP_URL ?>/admin/index.php" class="flex items-center py-3 px-5 hover:bg-white/10 transition-colors duration-200 <?= $isAdmin && $currentPage == 'index.php' ? 'bg-blue-600' : '' ?>">
                            <i class="fas fa-tachometer-alt nav-icon w-5 h-5 mr-3"></i>
                            <span>Tableau de bord</span>
                        </a>
                    </li>
                    
                    <?php if (isAdmin()): ?>
                        <!-- Utilisateurs -->
                        <li class="<?= $currentPage == 'users.php' || $currentPage == 'user-edit.php' ? 'active' : '' ?>">
                            <a href="<?= APP_URL ?>/admin/users.php" class="flex items-center py-3 px-5 hover:bg-white/10 transition-colors duration-200 <?= $currentPage == 'users.php' || $currentPage == 'user-edit.php' ? 'bg-blue-600' : '' ?>">
                                <i class="fas fa-users nav-icon w-5 h-5 mr-3"></i>
                                <span>Utilisateurs</span>
                            </a>
                        </li>
                        
                        <!-- Configuration -->
                        <li class="<?= $currentPage == 'site-settings.php' ? 'active' : '' ?>">
                            <a href="<?= APP_URL ?>/admin/site-settings.php" class="flex items-center py-3 px-5 hover:bg-white/10 transition-colors duration-200 <?= $currentPage == 'site-settings.php' ? 'bg-blue-600' : '' ?>">
                                <i class="fas fa-sliders-h nav-icon w-5 h-5 mr-3"></i>
                                <span>Configuration</span>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
                
                <!-- Déconnexion -->
                <li>
                    <a href="<?= APP_URL ?>/logout.php" class="flex items-center py-3 px-5 mx-4 my-2 bg-red-700 hover:bg-red-600 rounded-lg transition-colors duration-200 text-white">
                        <i class="fas fa-sign-out-alt nav-icon w-5 h-5 mr-3"></i>
                        <span>Déconnexion</span>
                    </a>
                </li>
            <?php else: ?>
                <!-- Connexion pour les visiteurs non connectés -->
                <li class="<?= $currentPage == 'login.php' ? 'active' : '' ?>">
                    <a href="<?= APP_URL ?>/login.php" class="flex items-center py-3 px-5 hover:bg-white/10 transition-colors duration-200 <?= $currentPage == 'login.php' ? 'bg-blue-600' : '' ?>">
                        <i class="fas fa-sign-in-alt nav-icon w-5 h-5 mr-3"></i>
                        <span>Connexion</span>
                    </a>
                </li>
                
                <!-- Inscription -->
                <?php if (isRegistrationEnabled()): ?>
                    <li class="<?= $currentPage == 'register.php' ? 'active' : '' ?>">
                        <a href="<?= APP_URL ?>/register.php" class="flex items-center py-3 px-5 hover:bg-white/10 transition-colors duration-200 <?= $currentPage == 'register.php' ? 'bg-blue-600' : '' ?>">
                            <i class="fas fa-user-plus nav-icon w-5 h-5 mr-3"></i>
                            <span>Inscription</span>
                        </a>
                    </li>
                <?php endif; ?>
            <?php endif; ?>
        </ul>
    </nav>
    
    <div class="sidebar-footer px-4 py-3 border-t border-white/10 text-xs text-center">
        <p class="text-white/70">Version <?= APP_VERSION ?? '1.0' ?></p>
        
        <?php if (isMaintenanceMode() && isAdmin()): ?>
            <div class="mt-2 px-2 py-1 bg-yellow-500 text-white text-xs rounded-md">
                <i class="fas fa-exclamation-triangle mr-1"></i> Mode maintenance activé
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Script pour les menus déroulants -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const dropdownToggles = document.querySelectorAll('[data-dropdown-toggle]');
    
    dropdownToggles.forEach(toggle => {
        const dropdownId = toggle.getAttribute('data-dropdown-toggle');
        const dropdown = document.getElementById(dropdownId);
        
        if (dropdown) {
            // Afficher automatiquement si un sous-menu contient la page active
            const hasActiveChild = dropdown.querySelector('.active');
            if (hasActiveChild) {
                dropdown.classList.remove('hidden');
                // Rotation de l'icône
                const icon = toggle.querySelector('.fa-chevron-down');
                if (icon) {
                    icon.classList.remove('fa-chevron-down');
                    icon.classList.add('fa-chevron-up');
                }
            }
            
            toggle.addEventListener('click', () => {
                dropdown.classList.toggle('hidden');
                
                // Rotation de l'icône
                const icon = toggle.querySelector('.fas');
                if (icon.classList.contains('fa-chevron-down')) {
                    icon.classList.remove('fa-chevron-down');
                    icon.classList.add('fa-chevron-up');
                } else {
                    icon.classList.remove('fa-chevron-up');
                    icon.classList.add('fa-chevron-down');
                }
            });
        }
    });
});
</script>