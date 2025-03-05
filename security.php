<?php
$pageTitle = "Sécurité du compte";
require_once 'includes/header.php';

// Rediriger si l'utilisateur n'est pas connecté
if (!isLoggedIn()) {
    setFlashMessage("Veuillez vous connecter pour accéder à cette page.", "warning");
    redirect('login.php');
}

// Récupérer les informations de l'utilisateur
$user = getUserById($_SESSION['user_id']);
if (!$user) {
    setFlashMessage("Utilisateur non trouvé.", "danger");
    redirect('index.php');
}

// Récupérer l'historique des connexions
$loginHistory = getUserLoginHistory($_SESSION['user_id'], 5);

// Récupérer les activités récentes
$userActivities = getUserActivities($_SESSION['user_id'], 5);
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            <i class="fas fa-shield-alt mr-2 text-blue-600 dark:text-blue-400"></i> Sécurité du compte
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Consultez et gérez les paramètres de sécurité de votre compte</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- État de sécurité -->
        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">État de la sécurité</h2>
                </div>
                <div class="p-6 space-y-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-12 w-12 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center">
                            <i class="fas fa-lock text-2xl text-green-600 dark:text-green-400"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Compte sécurisé</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Niveau de sécurité: Bon</p>
                        </div>
                    </div>
                    
                    <div class="space-y-2">
                        <div>
                            <div class="flex justify-between items-center text-sm mb-1">
                                <span class="text-gray-700 dark:text-gray-300">Force du mot de passe</span>
                                <span class="text-green-600 dark:text-green-400">Fort</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="bg-green-500 h-2 rounded-full" style="width: 85%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between items-center text-sm mb-1">
                                <span class="text-gray-700 dark:text-gray-300">Protection du compte</span>
                                <span class="text-green-600 dark:text-green-400">Active</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="bg-green-500 h-2 rounded-full" style="width: 100%"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                        <a href="password.php" class="w-full flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-key mr-2"></i> Modifier mon mot de passe
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden mt-6">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">Conseils de sécurité</h2>
                </div>
                <div class="p-6">
                    <ul class="space-y-4">
                        <li class="flex">
                            <i class="fas fa-check-circle text-green-500 mt-1 mr-3 flex-shrink-0"></i>
                            <div>
                                <h3 class="text-sm font-medium text-gray-900 dark:text-white">Utilisez un mot de passe fort</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Combinez lettres majuscules, minuscules, chiffres et symboles.</p>
                            </div>
                        </li>
                        <li class="flex">
                            <i class="fas fa-check-circle text-green-500 mt-1 mr-3 flex-shrink-0"></i>
                            <div>
                                <h3 class="text-sm font-medium text-gray-900 dark:text-white">Changez régulièrement de mot de passe</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Mettez à jour votre mot de passe tous les 3 mois.</p>
                            </div>
                        </li>
                        <li class="flex">
                            <i class="fas fa-check-circle text-green-500 mt-1 mr-3 flex-shrink-0"></i>
                            <div>
                                <h3 class="text-sm font-medium text-gray-900 dark:text-white">Surveillez vos connexions</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Vérifiez régulièrement l'historique des connexions.</p>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Historique des connexions et activités -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Historique des connexions -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">Dernières connexions</h2>
                </div>
                <div class="p-6">
                    <?php if (empty($loginHistory)): ?>
                        <p class="text-gray-500 dark:text-gray-400 text-center py-4">Aucune connexion récente enregistrée.</p>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead>
                                    <tr>
                                        <th class="px-4 py-3 bg-gray-50 dark:bg-gray-700 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Date</th>
                                        <th class="px-4 py-3 bg-gray-50 dark:bg-gray-700 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Adresse IP</th>
                                        <th class="px-4 py-3 bg-gray-50 dark:bg-gray-700 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Statut</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    <?php foreach ($loginHistory as $login): ?>
                                        <tr>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">
                                                <?= date('d/m/Y H:i:s', strtotime($login['login_time'])) ?>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                <?= escapeString($login['ip_address']) ?>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <?php if ($login['status'] === 'success'): ?>
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">
                                                        <i class="fas fa-check-circle mr-1"></i> Réussie
                                                    </span>
                                                <?php else: ?>
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200">
                                                        <i class="fas fa-times-circle mr-1"></i> Échouée
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mt-4 bg-blue-50 dark:bg-blue-900 border-l-4 border-blue-400 dark:border-blue-500 p-4 rounded">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-info-circle text-blue-600 dark:text-blue-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700 dark:text-blue-200">
                                    Vérifiez régulièrement cette liste. Si vous remarquez des connexions suspectes, changez immédiatement votre mot de passe.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Activités récentes -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">Activités récentes</h2>
                </div>
                <div class="p-6">
                    <?php if (empty($userActivities)): ?>
                        <p class="text-gray-500 dark:text-gray-400 text-center py-4">Aucune activité récente enregistrée.</p>
                    <?php else: ?>
                        <div class="flow-root">
                            <ul role="list" class="-mb-8">
                                <?php foreach ($userActivities as $index => $activity): ?>
                                    <li>
                                        <div class="relative pb-8">
                                            <?php if ($index < count($userActivities) - 1): ?>
                                                <span class="absolute top-5 left-5 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-700" aria-hidden="true"></span>
                                            <?php endif; ?>
                                            <div class="relative flex items-start space-x-3">
                                                <div class="relative">
                                                    <?php
                                                    $iconClass = 'bg-blue-500';
                                                    $icon = 'fas fa-info';
                                                    
                                                    switch ($activity['activity_type']) {
                                                        case 'login':
                                                            $iconClass = 'bg-green-500 dark:bg-green-700';
                                                            $icon = 'fas fa-sign-in-alt';
                                                            break;
                                                        case 'password_change':
                                                        case 'password_reset':
                                                            $iconClass = 'bg-yellow-500 dark:bg-yellow-700';
                                                            $icon = 'fas fa-key';
                                                            break;
                                                        case 'update_email':
                                                        case 'profile_update':
                                                            $iconClass = 'bg-indigo-500 dark:bg-indigo-700';
                                                            $icon = 'fas fa-user-edit';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="h-10 w-10 rounded-full flex items-center justify-center <?= $iconClass ?> text-white">
                                                        <i class="<?= $icon ?>"></i>
                                                    </span>
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <div>
                                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                                            <?= date('d/m/Y H:i', strtotime($activity['created_at'])) ?>
                                                        </p>
                                                    </div>
                                                    <div class="mt-2 text-sm text-gray-700 dark:text-gray-300">
                                                        <p><?= escapeString($activity['description']) ?></p>
                                                    </div>
                                                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                        IP: <?= escapeString($activity['ip_address']) ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Alerte de sécurité -->
            <div class="bg-yellow-50 dark:bg-yellow-900 border-l-4 border-yellow-400 dark:border-yellow-500 p-4 rounded">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-yellow-600 dark:text-yellow-400"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">Rappel de sécurité</h3>
                        <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                            <p>Ne partagez jamais vos informations de connexion. <?= APP_NAME ?> ne vous demandera jamais votre mot de passe par email ou téléphone.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>