<?php 
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/login-security.php';

// Récupérer le thème préféré de l'utilisateur (par défaut light)
$userTheme = $_COOKIE['theme'] ?? 'light';
?>
<!DOCTYPE html>
<html lang="fr" class="<?= $userTheme === 'dark' ? 'dark' : '' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? escapeString($pageTitle) . ' - ' . APP_NAME : APP_NAME ?></title>
    
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Configuration Tailwind avec support du dark mode -->
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: {
                            DEFAULT: '#3b82f6',
                            dark: '#2563eb',
                            light: '#93c5fd'
                        },
                        secondary: {
                            DEFAULT: '#1f2937'
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    
    <!-- Styles personnalisés -->
    <style>
        /* Styles de base */
        body {
            background-color: #f3f4f6;
            color: #1f2937;
        }
        
        /* Dark mode styles */
        .dark body {
            background-color: #111827;
            color: #f9fafb;
        }
        
        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .alert {
            animation: fadeIn 0.5s;
        }
        
        /* Dashboard layout */
        .dashboard-layout {
            display: flex;
            min-height: 100vh;
            width: 100%;
            position: relative;
        }
        
        /* Sidebar styles */
        .sidebar {
            width: 16rem;
            min-height: 100vh;
            background-color: #1f2937;
            color: white;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            z-index: 10;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease-in-out;
        }
        
        .sidebar-header {
            padding: 1.25rem;
            display: flex;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-header .logo {
            width: 2.5rem;
            height: 2.5rem;
            margin-right: 0.75rem;
            border-radius: 0.25rem;
        }
        
        .sidebar-header h3 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .sidebar-nav {
            flex-grow: 1;
            overflow-y: auto;
            padding-top: 0.75rem;
        }
        
        .sidebar-nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-nav li {
            margin: 0;
            padding: 0;
        }
        
        .sidebar-nav li.nav-divider {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 1.25rem 1.25rem 0.25rem;
            opacity: 0.7;
        }
        
        .sidebar-nav li a {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.25rem;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .sidebar-nav li a:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-nav li.active a {
            background-color: #3b82f6;
            color: white;
        }
        
        .sidebar-nav .nav-icon {
            width: 1.25rem;
            height: 1.25rem;
            margin-right: 0.75rem;
        }
        
        .sidebar-footer {
            padding: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 0.75rem;
            text-align: center;
        }
        
        /* Main content area */
        .main-content {
            flex-grow: 1;
            margin-left: 16rem;
            transition: all 0.3s ease-in-out;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background-color: #f3f4f6;
        }
        
        .dark .main-content {
            background-color: #111827;
        }
        
        /* Top header */
        .top-header {
            height: 4rem;
            background-color: white;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            padding: 0 1.25rem;
            position: sticky;
            top: 0;
            z-index: 40;
        }
        
        .dark .top-header {
            background-color: #1f2937;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.2);
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }
        
        /* Responsive adjustments */
        @media (max-width: 1024px) {
            .sidebar {
                margin-left: -16rem; /* Hidden by default on mobile */
            }
            
            .sidebar.active {
                margin-left: 0; /* Visible when active */
            }
            
            .main-content {
                margin-left: 0; /* Full width on mobile */
            }
            
            .sidebar-toggle {
                display: block; /* Visible on mobile */
            }
            
            .sidebar-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.5);
                z-index: 40;
                display: none;
            }
            
            .sidebar-overlay.active {
                display: block;
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
                        <button id="sidebar-toggle" class="sidebar-toggle hidden md:hidden text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white cursor-pointer">
                            <i class="fas fa-bars text-xl"></i>
                        </button>
                        
                        <div class="admin-badge mr-auto ml-5">
                            <?php if ($isAdmin ?? false): ?>
                                <span class="bg-red-600 text-white px-2 py-1 rounded text-sm font-semibold">Administration</span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Titre de la page actuelle -->
                        <div class="flex-grow text-center">
                            <h1 class="text-lg font-medium text-gray-700 dark:text-gray-300"><?= isset($pageTitle) ? escapeString($pageTitle) : APP_NAME ?></h1>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Conteneur principal -->
            <div class="container px-6 py-4">
                <!-- Affichage des messages flash -->
                <?php if (isset($_SESSION['flash_message'])): ?>
                    <div class="alert rounded-lg p-4 mb-6 <?= $_SESSION['flash_type'] == 'success' ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200' : ($_SESSION['flash_type'] == 'danger' ? 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200' : ($_SESSION['flash_type'] == 'warning' ? 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200' : 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200')) ?>">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <?php if ($_SESSION['flash_type'] == 'success'): ?>
                                    <i class="fas fa-check-circle text-green-600 dark:text-green-400"></i>
                                <?php elseif ($_SESSION['flash_type'] == 'danger'): ?>
                                    <i class="fas fa-times-circle text-red-600 dark:text-red-400"></i>
                                <?php elseif ($_SESSION['flash_type'] == 'warning'): ?>
                                    <i class="fas fa-exclamation-triangle text-yellow-600 dark:text-yellow-400"></i>
                                <?php else: ?>
                                    <i class="fas fa-info-circle text-blue-600 dark:text-blue-400"></i>
                                <?php endif; ?>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium"><?= $_SESSION['flash_message'] ?></p>
                            </div>
                            <div class="ml-auto pl-3">
                                <button type="button" class="close-alert inline-flex text-gray-400 hover:text-gray-900 dark:hover:text-white focus:outline-none">
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