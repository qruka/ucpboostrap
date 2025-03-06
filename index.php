<?php
$pageTitle = "Accueil";
require_once 'includes/header.php';

// Récupérer les informations de l'utilisateur connecté
$user = null;
if (isLoggedIn()) {
    $user = getUserById($_SESSION['user_id']);
}

// Récupérer les news récentes
$news = []; // Initialize as empty array to prevent foreach errors if query fails
try {
    $conn = connectDB();
    if ($conn) {
        $newsQuery = "SELECT n.*, u.username AS author_name 
                    FROM news n 
                    JOIN users u ON n.created_by = u.id 
                    ORDER BY n.created_at DESC";
        $newsResult = $conn->query($newsQuery);
        
        if ($newsResult) {
            while ($row = $newsResult->fetch_assoc()) {
                $news[] = $row;
            }
        } else {
            error_log("Failed to execute news query: " . $conn->error);
        }
    } else {
        error_log("Failed to connect to database in index.php");
    }
} catch (Exception $e) {
    error_log("Exception in index.php news retrieval: " . $e->getMessage());
}
?>

<div class="container mx-auto px-4 py-8">
    <!-- Barre d'action pour les admins -->
    <?php if (isLoggedIn() && isAdmin()): ?>
        <div class="flex justify-end mb-6">
            <a href="admin/news-create.php" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                <i class="fas fa-plus mr-2"></i> Créer une nouvelle actualité
            </a>
        </div>
    <?php endif; ?>
    
    <!-- Section des actualités -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
        <div class="p-6">
            <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-4 flex items-center">
                <i class="fas fa-newspaper text-blue-600 dark:text-blue-400 mr-2"></i> Actualités
            </h2>
            
            <?php if (empty($news)): ?>
                <div class="text-center py-8 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <i class="fas fa-inbox text-gray-400 dark:text-gray-500 text-4xl mb-2"></i>
                    <p class="text-gray-600 dark:text-gray-400">Aucune actualité n'a été publiée pour le moment.</p>
                </div>
            <?php else: ?>
                <div class="space-y-6">
                <?php foreach ($news as $item): ?>
    <div class="mb-8 overflow-hidden rounded-lg shadow-lg">
        <?php 
        // Improved image path handling
        $imagePath = $item['image_path'];
        $imageExists = false;
        
        // Normalize path and check if the file exists
        if (!empty($imagePath)) {
            // If the path is relative (doesn't start with a slash or drive letter)
            if (!preg_match('~^(/|[a-z]:)~i', $imagePath)) {
                // Convert relative path to absolute server path for file_exists check
                $absolutePath = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($imagePath, '/');
                $imageExists = file_exists($absolutePath);
            } else {
                // It's already an absolute path
                $imageExists = file_exists($imagePath);
            }
            
            // For debugging
            if (!$imageExists) {
                error_log("News image not found: " . $imagePath);
            }
        }
        ?>
        
        <!-- Title section with more visible background image -->
        <div class="relative">
            <!-- Background color or image -->
            <div class="<?= (!empty($imagePath) && $imageExists) ? 'h-64 md:h-80' : 'h-40' ?> bg-gradient-to-r from-blue-600 to-indigo-700">
                <?php if (!empty($imagePath) && $imageExists): ?>
                    <img src="<?= $imagePath ?>" alt="" class="absolute inset-0 w-full h-full object-cover object-center">
                    <div class="absolute inset-0 bg-gradient-to-b from-black/30 to-black/60"></div>
                <?php endif; ?>
            </div>
            
            <!-- Title content positioned at the bottom of the image for better readability -->
            <div class="absolute bottom-0 left-0 right-0 p-6">
                <h3 class="text-xl md:text-2xl font-bold text-white drop-shadow-lg">
                    <?= escapeString($item['title']) ?>
                </h3>
                <div class="flex items-center text-sm text-white mt-2">
                    <i class="fas fa-user mr-1"></i>
                    <span class="font-medium mr-3"><?= escapeString($item['author_name']) ?></span>
                    <i class="fas fa-calendar-alt mr-1"></i>
                    <span><?= date('d/m/Y à H:i', strtotime($item['created_at'])) ?></span>
                </div>
            </div>
            
            <!-- Admin controls on the top right for better visibility -->
            <?php if (isAdmin()): ?>
                <div class="absolute top-4 right-4 flex space-x-2">
                    <a href="admin/news-edit.php?id=<?= $item['id'] ?>" class="p-2 bg-blue-500 bg-opacity-80 hover:bg-opacity-100 rounded-full text-white transition-all shadow-md">
                        <i class="fas fa-edit"></i>
                    </a>
                    <a href="admin/news-delete.php?id=<?= $item['id'] ?>" class="p-2 bg-red-500 bg-opacity-80 hover:bg-opacity-100 rounded-full text-white transition-all shadow-md" 
                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette actualité ?')">
                        <i class="fas fa-trash"></i>
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Content section with white background -->
        <div class="bg-white dark:bg-gray-800 p-6 border-t border-gray-200 dark:border-gray-700">
            <div class="prose dark:prose-invert max-w-none text-gray-700 dark:text-gray-300">
                <?= nl2br(escapeString($item['content'])) ?>
            </div>
        </div>
    </div>
<?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>