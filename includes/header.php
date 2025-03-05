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
    
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Styles personnalisés -->
    <style>
        /* Styles de base */
        body {
            @apply bg-gray-50 text-gray-800;
        }
        
        /* Dashboard layout */
        .dashboard-layout {
            @apply flex min-h-screen w-full relative;
        }
        
        /* Sidebar styles */
        .sidebar {
            @apply w-64 min-h-screen bg-gray-800 text-white fixed top-0 left-0 bottom-0 z-10 shadow-lg flex flex-col transition-all duration-300 ease-in-out;
        }
        
        .sidebar-header {
            @apply p-5 flex items-center border-b border-white/10;
        }
        
        .sidebar-header .logo {
            @apply w-10 h-10 mr-3 rounded;
        }
        
        .sidebar-header h3 {
            @apply m-0 text-lg font-semibold whitespace-nowrap overflow-hidden text-ellipsis;
        }
        
        .sidebar-nav {
            @apply flex-grow overflow-y-auto pt-3;
        }
        
        .sidebar-nav ul {
            @apply list-none p-0 m-0;
        }
        
        .sidebar-nav li {
            @apply m-0 p-0;
        }
        
        .sidebar-nav li.nav-divider {
            @apply text-xs uppercase tracking-wide py-5 px-5 pb-1 opacity-70;
        }
        
        .sidebar-nav li a {
            @apply flex items-center py-3 px-5 text-white no-underline transition-all duration-300;
        }
        
        .sidebar-nav li a:hover {
            @apply bg-white/10;
        }
        
        .sidebar-nav li.active a {
            @apply bg-blue-600 text-white;
        }
        
        .sidebar-nav .nav-icon {
            @apply w-5 h-5 mr-3;
        }
        
        .sidebar-footer {
            @apply p-4 border-t border-white/10 text-xs text-center;
        }
        
        /* Main content area */
        .main-content {
            @apply flex-grow ml-64 transition-all duration-300 ease-in-out flex flex-col min-h-screen bg-gray-50;
        }
        
        /* Top header */
        .top-header {
            @apply h-16 bg-white shadow-sm flex items-center px-5 sticky top-0 z-40;
        }
        
        .header-content {
            @apply flex justify-between items-center w-full;
        }
        
        /* User levels */
        .user-level {
            @apply inline-block px-2 py-1 text-xs font-semibold rounded;
        }
        
        .user-level-1 {
            @apply bg-gray-200 text-gray-700;
        }
        
        .user-level-2 {
            @apply bg-blue-100 text-blue-800;
        }
        
        .user-level-3 {
            @apply bg-green-100 text-green-800;
        }
        
        .user-level-4 {
            @apply bg-red-100 text-red-800;
        }
        
        /* User status */
        .status-active {
            @apply text-green-600 font-medium;
        }
        
        .status-banned {
            @apply text-red-600 font-medium;
        }
        
        .status-suspended {
            @apply text-yellow-600 font-medium;
        }
        
        /* Alert animation */
        .alert {
            animation: fadeIn 0.5s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Responsive adjustments */
        @media (max-width: 1024px) {
            .sidebar {
                @apply -ml-64; /* Hidden by default on mobile */
            }
            
            .sidebar.active {
                @apply ml-0; /* Visible when active */
            }
            
            .main-content {
                @apply ml-0; /* Full width on mobile */
            }
            
            .sidebar-toggle {
                @apply block; /* Visible on mobile */
            }
            
            .sidebar-overlay {
                @apply fixed top-0 left-0 w-full h-full bg-black/50 z-40 hidden;
            }
            
            .sidebar-overlay.active {
                @apply block;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <!-- Menu latéral (IMPORTANT: même inclusion dans toutes les pages) -->
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <!-- Barre de navigation supérieure -->
            <header class="top-header">
                <div class="container px-4">
                    <div class="header-content">
                        <button id="sidebar-toggle" class="sidebar-toggle hidden md:hidden text-gray-700 hover:text-gray-900 cursor-pointer">
                            <i class="fas fa-bars text-xl"></i>
                        </button>
                        
                        <div class="user-info ml-auto">
                            <?php if (isLoggedIn()): ?>
                                <?php $user = getUserById($_SESSION['user_id']); ?>
                                <div class="relative group">
                                    <button class="flex items-center space-x-2 hover:text-blue-600 focus:outline-none" type="button" id="userDropdown">
                                        <i class="fas fa-user-circle text-xl"></i>
                                        <span><?= escapeString($user['username']) ?></span>
                                        <i class="fas fa-chevron-down text-xs"></i>
                                    </button>
                                    <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                                        <a class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" href="<?= APP_URL ?>/settings.php">Paramètres</a>
                                        <a class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" href="<?= APP_URL ?>/password.php">Mot de passe</a>
                                        <a class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" href="<?= APP_URL ?>/security.php">Sécurité</a>
                                        <?php if (isModerator()): ?>
                                            <hr class="my-1 border-gray-200">
                                            <a class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" href="<?= APP_URL ?>/admin/index.php">Administration</a>
                                        <?php endif; ?>
                                        <hr class="my-1 border-gray-200">
                                        <a class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100" href="<?= APP_URL ?>/logout.php">Déconnexion</a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Conteneur principal -->
            <div class="container px-6 py-4">
                <!-- Affichage des messages flash -->
                <?php if (isset($_SESSION['flash_message'])): ?>
                    <div class="alert rounded-lg p-4 mb-6 <?= $_SESSION['flash_type'] == 'success' ? 'bg-green-100 text-green-800' : ($_SESSION['flash_type'] == 'danger' ? 'bg-red-100 text-red-800' : ($_SESSION['flash_type'] == 'warning' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800')) ?>">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <?php if ($_SESSION['flash_type'] == 'success'): ?>
                                    <i class="fas fa-check-circle text-green-600"></i>
                                <?php elseif ($_SESSION['flash_type'] == 'danger'): ?>
                                    <i class="fas fa-times-circle text-red-600"></i>
                                <?php elseif ($_SESSION['flash_type'] == 'warning'): ?>
                                    <i class="fas fa-exclamation-triangle text-yellow-600"></i>
                                <?php else: ?>
                                    <i class="fas fa-info-circle text-blue-600"></i>
                                <?php endif; ?>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium"><?= $_SESSION['flash_message'] ?></p>
                            </div>
                            <div class="ml-auto pl-3">
                                <button type="button" class="close-alert inline-flex text-gray-400 hover:text-gray-900 focus:outline-none">
                                    <span class="sr-only">Fermer</span>
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php 
                        // Supprime le message flash après l'avoir affiché
                        unset($_SESSION['flash_message']); 
                        unset($_SESSION['flash_type']);
                    ?>
                <?php endif; ?>