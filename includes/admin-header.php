<?php 
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/login-security.php';

// Vérifier si l'utilisateur est administrateur
requireAccess(USER_LEVEL_MODERATOR, '../index.php');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? escapeString($pageTitle) . ' - Admin' : 'Administration' ?> | <?= APP_NAME ?></title>
    
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Configuration Tailwind -->
    <script>
        tailwind.config = {
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
            },
            plugins: [
                function({ addComponents }) {
                    addComponents({
                        // User level badges
                        '.user-level': {
                            display: 'inline-flex',
                            alignItems: 'center',
                            padding: '0.25rem 0.625rem',
                            borderRadius: '9999px',
                            fontSize: '0.75rem',
                            fontWeight: '500'
                        },
                        '.user-level-1': {
                            backgroundColor: 'rgb(243 244 246)',
                            color: 'rgb(55 65 81)'
                        },
                        '.user-level-2': {
                            backgroundColor: 'rgb(219 234 254)',
                            color: 'rgb(30 64 175)'
                        },
                        '.user-level-3': {
                            backgroundColor: 'rgb(220 252 231)',
                            color: 'rgb(22 101 52)'
                        },
                        '.user-level-4': {
                            backgroundColor: 'rgb(233 213 255)',
                            color: 'rgb(91 33 182)'
                        },
                        
                        // User status classes
                        '.status-active': {
                            color: 'rgb(22 163 74)',
                            fontWeight: '500',
                            display: 'flex',
                            alignItems: 'center',
                            '&::before': {
                                content: '""',
                                width: '0.5rem',
                                height: '0.5rem',
                                backgroundColor: 'rgb(34 197 94)',
                                borderRadius: '9999px',
                                marginRight: '0.375rem'
                            }
                        },
                        '.status-banned': {
                            color: 'rgb(220 38 38)',
                            fontWeight: '500',
                            display: 'flex',
                            alignItems: 'center',
                            '&::before': {
                                content: '""',
                                width: '0.5rem',
                                height: '0.5rem',
                                backgroundColor: 'rgb(239 68 68)',
                                borderRadius: '9999px',
                                marginRight: '0.375rem'
                            }
                        },
                        '.status-suspended': {
                            color: 'rgb(202 138 4)',
                            fontWeight: '500',
                            display: 'flex',
                            alignItems: 'center',
                            '&::before': {
                                content: '""',
                                width: '0.5rem',
                                height: '0.5rem',
                                backgroundColor: 'rgb(234 179 8)',
                                borderRadius: '9999px',
                                marginRight: '0.375rem'
                            }
                        },
                        
                        // Custom card styles
                        '.stats-card': {
                            padding: '1.5rem',
                            textAlign: 'center',
                            borderRadius: '0.5rem',
                            color: 'white',
                            position: 'relative',
                            overflow: 'hidden',
                            transition: 'all 0.3s',
                            '&:hover': {
                                boxShadow: '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)',
                                transform: 'translateY(-3px)'
                            },
                            '&::before': {
                                content: '""',
                                position: 'absolute',
                                top: '0',
                                right: '0',
                                width: '8rem',
                                height: '8rem',
                                opacity: '0.1',
                                marginTop: '-2rem',
                                marginRight: '-2rem',
                                borderRadius: '9999px',
                                transition: 'all 0.3s'
                            },
                            '&:hover::before': {
                                opacity: '0.2'
                            }
                        },
                        '.stats-card.primary': {
                            background: 'linear-gradient(to bottom right, #4f46e5, #3b82f6)',
                            '&::before': {
                                backgroundColor: '#93c5fd'
                            }
                        },
                        '.stats-card.success': {
                            background: 'linear-gradient(to bottom right, #059669, #10b981)',
                            '&::before': {
                                backgroundColor: '#a7f3d0'
                            }
                        },
                        '.stats-card.warning': {
                            background: 'linear-gradient(to bottom right, #d97706, #fbbf24)',
                            '&::before': {
                                backgroundColor: '#fde68a'
                            }
                        },
                        '.stats-card.danger': {
                            background: 'linear-gradient(to bottom right, #dc2626, #f43f5e)',
                            '&::before': {
                                backgroundColor: '#fecaca'
                            }
                        },
                        
                        // Form controls
                        '.form-control': {
                            display: 'block',
                            width: '100%',
                            padding: '0.5rem 0.75rem',
                            fontSize: '1rem',
                            lineHeight: '1.5',
                            color: '#374151',
                            backgroundColor: '#fff',
                            border: '1px solid #d1d5db',
                            borderRadius: '0.375rem',
                            boxShadow: '0 1px 2px 0 rgba(0, 0, 0, 0.05)',
                            '&:focus': {
                                outline: 'none',
                                borderColor: '#3b82f6',
                                boxShadow: '0 0 0 3px rgba(59, 130, 246, 0.5)'
                            }
                        },
                        
                        // Buttons
                        '.btn': {
                            display: 'inline-flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            padding: '0.5rem 1rem',
                            border: '1px solid transparent',
                            borderRadius: '0.375rem',
                            fontSize: '0.875rem',
                            fontWeight: '500',
                            boxShadow: '0 1px 2px 0 rgba(0, 0, 0, 0.05)',
                            '&:focus': {
                                outline: 'none',
                                boxShadow: '0 0 0 3px rgba(59, 130, 246, 0.5)'
                            }
                        },
                        '.btn-primary': {
                            backgroundColor: '#3b82f6',
                            color: 'white',
                            '&:hover': {
                                backgroundColor: '#2563eb'
                            }
                        },
                        '.btn-secondary': {
                            backgroundColor: '#4b5563',
                            color: 'white',
                            '&:hover': {
                                backgroundColor: '#374151'
                            }
                        },
                        '.btn-success': {
                            backgroundColor: '#10b981',
                            color: 'white',
                            '&:hover': {
                                backgroundColor: '#059669'
                            }
                        },
                        '.btn-danger': {
                            backgroundColor: '#ef4444',
                            color: 'white',
                            '&:hover': {
                                backgroundColor: '#dc2626'
                            }
                        },
                        '.btn-warning': {
                            backgroundColor: '#f59e0b',
                            color: 'white',
                            '&:hover': {
                                backgroundColor: '#d97706'
                            }
                        },
                        '.btn-outline-primary': {
                            borderColor: '#3b82f6',
                            color: '#3b82f6',
                            backgroundColor: 'transparent',
                            '&:hover': {
                                backgroundColor: '#eff6ff'
                            }
                        },
                        '.btn-outline-secondary': {
                            borderColor: '#d1d5db',
                            color: '#4b5563',
                            backgroundColor: 'transparent',
                            '&:hover': {
                                backgroundColor: '#f3f4f6'
                            }
                        }
                    });
                }
            ]
        }
    </script>
    
    <!-- Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    
    <!-- Styles personnalisés pour l'administration -->
    <style>
        /* Styles de base */
        body {
            background-color: #f3f4f6;
            color: #1f2937;
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
        <?php include '../includes/sidebar.php'; ?>
        
        <div class="main-content">
            <!-- Barre de navigation supérieure -->
            <header class="top-header">
                <div class="container px-4">
                    <div class="header-content">
                        <button id="sidebar-toggle" class="sidebar-toggle hidden md:hidden text-gray-700 hover:text-gray-900 cursor-pointer">
                            <i class="fas fa-bars text-xl"></i>
                        </button>
                        
                        <div class="admin-badge mr-auto ml-5">
                            <span class="bg-red-600 text-white px-2 py-1 rounded text-sm font-semibold">Administration</span>
                        </div>
                        
                        <div class="user-info">
                            <?php if (isLoggedIn()): ?>
                                <?php $user = getUserById($_SESSION['user_id']); ?>
                                <div class="relative group">
                                    <button class="flex items-center space-x-2 hover:text-blue-600 focus:outline-none" type="button" id="userDropdown">
                                        <i class="fas fa-user-circle text-xl"></i>
                                        <span><?= escapeString($user['username']) ?></span>
                                        <i class="fas fa-chevron-down text-xs"></i>
                                    </button>
                                    <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                                        <a class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" href="<?= APP_URL ?>/profile.php">Mon profil</a>
                                        <a class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" href="<?= APP_URL ?>/settings.php">Paramètres</a>
                                        <a class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" href="<?= APP_URL ?>/password.php">Mot de passe</a>
                                        <a class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" href="<?= APP_URL ?>/security.php">Sécurité</a>
                                        <a class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" href="<?= APP_URL ?>/index.php">Retour au site</a>
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