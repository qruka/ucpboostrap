<?php
$pageTitle = "Paramètres du site";
require_once '../includes/admin-header.php';

// Vérifier si l'utilisateur est administrateur
requireAccess(USER_LEVEL_ADMIN);

// Initialiser les variables
$errors = [];
$success = false;

// Récupérer les paramètres actuels
$siteSettings = [
    'site_name' => getSetting('site_name', APP_NAME),
    'site_description' => getSetting('site_description', 'Description du site'),
    'max_login_attempts' => getSetting('max_login_attempts', 5),
    'login_lockout_duration' => getSetting('login_lockout_duration', 30),
    'maintenance_mode' => getSetting('maintenance_mode', '0'),
    'enable_registrations' => getSetting('enable_registrations', '1'),
];

// Traiter le formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier le token CSRF
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = "Erreur de sécurité. Veuillez réessayer.";
    } else {
        // Récupérer les données du formulaire
        $siteName = trim($_POST['site_name'] ?? '');
        $siteDescription = trim($_POST['site_description'] ?? '');
        $maxLoginAttempts = (int)($_POST['max_login_attempts'] ?? 5);
        $loginLockoutDuration = (int)($_POST['login_lockout_duration'] ?? 30);
        $maintenanceMode = isset($_POST['maintenance_mode']) ? '1' : '0';
        $enableRegistrations = isset($_POST['enable_registrations']) ? '1' : '0';
        
        // Valider les champs
        if (empty($siteName)) {
            $errors[] = "Le nom du site est requis.";
        }
        
        if ($maxLoginAttempts < 1 || $maxLoginAttempts > 20) {
            $errors[] = "Le nombre maximal de tentatives de connexion doit être compris entre 1 et 20.";
        }
        
        if ($loginLockoutDuration < 5 || $loginLockoutDuration > 1440) {
            $errors[] = "La durée de verrouillage doit être comprise entre 5 et 1440 minutes (24 heures).";
        }
        
        // Si aucune erreur, mettre à jour les paramètres
        if (empty($errors)) {
            $conn = connectDB();
            
            // Utiliser une transaction pour les mises à jour
            $conn->begin_transaction();
            
            try {
                // Mettre à jour chaque paramètre
                updateSetting('site_name', $siteName);
                updateSetting('site_description', $siteDescription);
                updateSetting('max_login_attempts', $maxLoginAttempts);
                updateSetting('login_lockout_duration', $loginLockoutDuration);
                updateSetting('maintenance_mode', $maintenanceMode);
                updateSetting('enable_registrations', $enableRegistrations);
                
                // Enregistrer l'activité
                $activityType = 'update_settings';
                $description = "Mise à jour des paramètres du site";
                $ipAddress = $_SERVER['REMOTE_ADDR'];
                
                $logQuery = "INSERT INTO user_activities (user_id, activity_type, description, ip_address, created_at) 
                             VALUES (?, ?, ?, ?, NOW())";
                $logStmt = $conn->prepare($logQuery);
                $userId = $_SESSION['user_id'];
                $logStmt->bind_param("isss", $userId, $activityType, $description, $ipAddress);
                $logStmt->execute();
                
                $conn->commit();
                
                // Mettre à jour les paramètres affichés
                $siteSettings['site_name'] = $siteName;
                $siteSettings['site_description'] = $siteDescription;
                $siteSettings['max_login_attempts'] = $maxLoginAttempts;
                $siteSettings['login_lockout_duration'] = $loginLockoutDuration;
                $siteSettings['maintenance_mode'] = $maintenanceMode;
                $siteSettings['enable_registrations'] = $enableRegistrations;
                
                $success = true;
                setFlashMessage("Les paramètres du site ont été mis à jour avec succès.", "success");
            } catch (Exception $e) {
                $conn->rollback();
                $errors[] = "Une erreur est survenue lors de la mise à jour des paramètres: " . $e->getMessage();
            }
            
            $conn->close();
        }
    }
}
?>

<div class="mb-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-bold flex items-center text-gray-800">
                <i class="fas fa-cog mr-2 text-blue-600"></i> Paramètres du site
            </h1>
            <p class="text-gray-500 mt-1">Configuration générale de <?= escapeString(APP_NAME) ?></p>
        </div>
        <div class="mt-4 md:mt-0">
            <a href="index.php" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-arrow-left mr-2"></i> Retour au tableau de bord
            </a>
        </div>
    </div>
</div>

<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
        <h3 class="text-lg font-medium text-gray-900">Paramètres généraux</h3>
    </div>
    
    <div class="p-6">
        <?php if ($success): ?>
            <div class="rounded-md bg-green-50 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle text-green-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800">
                            Paramètres mis à jour avec succès !
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="rounded-md bg-red-50 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-red-400"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Des erreurs sont survenues:</h3>
                        <div class="mt-2 text-sm text-red-700">
                            <ul class="list-disc pl-5 space-y-1">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= escapeString($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <form action="site-settings.php" method="POST" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700">
                            <strong>Attention :</strong> Certains paramètres comme le mode maintenance affectent immédiatement l'accès au site.
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                <div class="sm:col-span-3">
                    <label for="site_name" class="block text-sm font-medium text-gray-700">Nom du site</label>
                    <div class="mt-1">
                        <input type="text" name="site_name" id="site_name" 
                               value="<?= escapeString($siteSettings['site_name']) ?>" 
                               class="form-control">
                    </div>
                </div>
                
                <div class="sm:col-span-3">
                    <label for="site_description" class="block text-sm font-medium text-gray-700">Description du site</label>
                    <div class="mt-1">
                        <input type="text" name="site_description" id="site_description" 
                               value="<?= escapeString($siteSettings['site_description']) ?>" 
                               class="form-control">
                    </div>
                </div>
                
                <div class="sm:col-span-3">
                    <label for="max_login_attempts" class="block text-sm font-medium text-gray-700">Tentatives de connexion maximales</label>
                    <div class="mt-1">
                        <input type="number" name="max_login_attempts" id="max_login_attempts" 
                               value="<?= (int)$siteSettings['max_login_attempts'] ?>" 
                               min="1" max="20" 
                               class="form-control">
                    </div>
                    <p class="mt-1 text-sm text-gray-500">Nombre de tentatives avant blocage temporaire</p>
                </div>
                
                <div class="sm:col-span-3">
                    <label for="login_lockout_duration" class="block text-sm font-medium text-gray-700">Durée de verrouillage (minutes)</label>
                    <div class="mt-1">
                        <input type="number" name="login_lockout_duration" id="login_lockout_duration" 
                               value="<?= (int)$siteSettings['login_lockout_duration'] ?>" 
                               min="5" max="1440" 
                               class="form-control">
                    </div>
                    <p class="mt-1 text-sm text-gray-500">Durée de blocage après trop de tentatives</p>
                </div>
                
                <div class="sm:col-span-3">
                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input type="checkbox" name="maintenance_mode" id="maintenance_mode" 
                                   <?= $siteSettings['maintenance_mode'] === '1' ? 'checked' : '' ?> 
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="maintenance_mode" class="font-medium text-gray-700">Mode maintenance</label>
                            <p class="text-gray-500">Rend le site inaccessible pour les utilisateurs non-administrateurs</p>
                        </div>
                    </div>
                </div>
                
                <div class="sm:col-span-3">
                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input type="checkbox" name="enable_registrations" id="enable_registrations" 
                                   <?= $siteSettings['enable_registrations'] === '1' ? 'checked' : '' ?> 
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="enable_registrations" class="font-medium text-gray-700">Autoriser les inscriptions</label>
                            <p class="text-gray-500">Permet aux visiteurs de créer un compte sur le site</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="pt-5 border-t border-gray-200">
                <div class="flex justify-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-2"></i> Enregistrer les paramètres
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
    <!-- Options avancées -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                <i class="fas fa-shield-alt mr-2 text-blue-500"></i> Sécurité
            </h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500">Options de sécurité avancées</p>
        </div>
        
        <div class="px-4 py-5 sm:p-6">
            <div class="mb-6">
                <h4 class="text-base font-medium text-gray-900">Protection contre les attaques</h4>
                <p class="mt-1 text-sm text-gray-500">Le site est configuré pour détecter et bloquer les tentatives d'intrusion.</p>
                
                <ul class="mt-4 space-y-2 text-sm">
                    <li class="flex items-center">
                        <i class="fas fa-check-circle text-green-500 mr-2"></i>
                        <span class="text-gray-700">Protection contre les attaques par force brute</span>
                    </li>
                    <li class="flex items-center">
                        <i class="fas fa-check-circle text-green-500 mr-2"></i>
                        <span class="text-gray-700">Protection CSRF (Cross-Site Request Forgery)</span>
                    </li>
                    <li class="flex items-center">
                        <i class="fas fa-check-circle text-green-500 mr-2"></i>
                        <span class="text-gray-700">Validation des entrées utilisateur</span>
                    </li>
                    <li class="flex items-center">
                        <i class="fas fa-check-circle text-green-500 mr-2"></i>
                        <span class="text-gray-700">Sessions sécurisées</span>
                    </li>
                </ul>
            </div>
            
            <div>
                <h4 class="text-base font-medium text-gray-900">Recommandations</h4>
                <ul class="mt-4 space-y-2 text-sm">
                    <li class="flex items-start">
                        <i class="fas fa-info-circle text-blue-500 mt-0.5 mr-2"></i>
                        <span class="text-gray-700">Activez HTTPS pour protéger les données utilisateur</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-info-circle text-blue-500 mt-0.5 mr-2"></i>
                        <span class="text-gray-700">Effectuez des sauvegardes régulières de la base de données</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-info-circle text-blue-500 mt-0.5 mr-2"></i>
                        <span class="text-gray-700">Mettez régulièrement à jour le code et les composants</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Journal d'activité -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                <i class="fas fa-history mr-2 text-blue-500"></i> Journal d'activité admin
            </h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500">Activités récentes des administrateurs</p>
        </div>
        
        <div class="px-4 py-5 sm:p-6">
            <?php
            // Récupérer les activités d'administration récentes
            $sql = "SELECT ua.activity_type, ua.description, ua.created_at, u.username 
                    FROM user_activities ua 
                    JOIN users u ON ua.user_id = u.id 
                    WHERE u.user_level >= ? 
                    ORDER BY ua.created_at DESC 
                    LIMIT 5";
            $adminActivities = [];
            $result = executeQuery($sql, [USER_LEVEL_ADMIN]);
            
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $adminActivities[] = $row;
                }
            }
            ?>
            
            <?php if (empty($adminActivities)): ?>
                <p class="text-gray-500 text-center py-4">Aucune activité récente enregistrée.</p>
            <?php else: ?>
                <div class="flow-root">
                    <ul role="list" class="-mb-8">
                        <?php foreach ($adminActivities as $index => $activity): ?>
                            <li>
                                <div class="relative pb-8">
                                    <?php if ($index < count($adminActivities) - 1): ?>
                                        <span class="absolute top-5 left-5 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                    <?php endif; ?>
                                    <div class="relative flex items-start space-x-3">
                                        <div class="relative">
                                            <?php
                                            $iconClass = 'bg-blue-500';
                                            $icon = 'fas fa-cog';
                                            
                                            switch ($activity['activity_type']) {
                                                case 'update_settings':
                                                    $iconClass = 'bg-green-500';
                                                    $icon = 'fas fa-cog';
                                                    break;
                                                case 'ban':
                                                case 'suspend':
                                                    $iconClass = 'bg-red-500';
                                                    $icon = 'fas fa-ban';
                                                    break;
                                                case 'unban':
                                                    $iconClass = 'bg-green-500';
                                                    $icon = 'fas fa-user-check';
                                                    break;
                                                case 'delete_user':
                                                    $iconClass = 'bg-red-600';
                                                    $icon = 'fas fa-trash-alt';
                                                    break;
                                            }
                                            ?>
                                            <span class="h-10 w-10 rounded-full flex items-center justify-center <?= $iconClass ?> text-white">
                                                <i class="<?= $icon ?>"></i>
                                            </span>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?= escapeString($activity['username']) ?>
                                                </div>
                                                <p class="mt-0.5 text-sm text-gray-500">
                                                    <?= date('d/m/Y H:i', strtotime($activity['created_at'])) ?>
                                                </p>
                                            </div>
                                            <div class="mt-2 text-sm text-gray-700">
                                                <p><?= escapeString($activity['description']) ?></p>
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
</div>

<?php require_once '../includes/admin-footer.php'; ?>