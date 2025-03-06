<?php
$pageTitle = "Modifier une actualité";
require_once '../includes/admin-header.php';

// Vérifier si l'utilisateur est administrateur
requireAccess(USER_LEVEL_ADMIN);

// Vérifier si l'ID est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlashMessage("ID d'actualité invalide.", "danger");
    redirect('../index.php');
}

$newsId = (int)$_GET['id'];

// Récupérer les informations de l'actualité
$conn = connectDB();
$query = "SELECT * FROM news WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $newsId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    setFlashMessage("Actualité introuvable.", "danger");
    redirect('../index.php');
}

$news = $result->fetch_assoc();
$stmt->close();
// Ne pas fermer la connexion ici, nous en aurons besoin plus tard

$errors = [];
$success = false;

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier le token CSRF
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = "Erreur de sécurité. Veuillez réessayer.";
    } else {
        // Récupérer les données du formulaire
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        
        // Valider les champs
        if (empty($title)) {
            $errors[] = "Le titre est requis.";
        }
        
        if (empty($content)) {
            $errors[] = "Le contenu est requis.";
        }
        
        // Traiter l'image si présente
        $imagePath = $news['image_path']; // Conserver l'image existante par défaut
        
        if (!empty($_FILES['image']['name'])) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $maxSize = 5 * 1024 * 1024; // 5MB
            
            if (!in_array($_FILES['image']['type'], $allowedTypes)) {
                $errors[] = "Le format de l'image n'est pas supporté. Utilisez JPG, PNG ou GIF.";
            } elseif ($_FILES['image']['size'] > $maxSize) {
                $errors[] = "L'image est trop volumineuse. Taille maximale: 5MB.";
            } elseif ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                $errors[] = "Erreur lors du téléchargement de l'image. Code: " . $_FILES['image']['error'];
            } else {
                // Créer le répertoire d'upload si nécessaire
                $uploadDir = '../uploads/news/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                // Générer un nom de fichier unique
                $fileExtension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $newFileName = 'news_' . time() . '_' . rand(1000, 9999) . '.' . $fileExtension;
                $targetFile = $uploadDir . $newFileName;
                
                // Déplacer le fichier uploadé
                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                    // Supprimer l'ancienne image s'il y en a une
                    if ($news['image_path'] && file_exists($news['image_path'])) {
                        unlink($news['image_path']);
                    }
                    
                    $imagePath = $targetFile;
                } else {
                    $errors[] = "Erreur lors de l'enregistrement de l'image.";
                }
            }
        } else if (isset($_POST['remove_image']) && $_POST['remove_image'] === '1') {
            // Supprimer l'image existante si demandé
            if ($news['image_path'] && file_exists($news['image_path'])) {
                unlink($news['image_path']);
            }
            $imagePath = null;
        }
        
        // Si aucune erreur, mettre à jour la news
        if (empty($errors)) {
            // La connexion est déjà ouverte, mais vérifions qu'elle est toujours valide
            if (!$conn || !$conn->ping()) {
                // Si la connexion n'est plus valide, créons-en une nouvelle
                $conn = connectDB();
            }
            
            // Utiliser une transaction
            try {
                $conn->begin_transaction();
                
                // Mettre à jour la news
                $sql = "UPDATE news SET title = ?, content = ?, image_path = ?, updated_at = NOW() WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssi", $title, $content, $imagePath, $newsId);
                $stmt->execute();
                
                // Enregistrer l'activité
                $activityType = 'update_news';
                $description = "Mise à jour d'une actualité: " . $title;
                $ipAddress = $_SERVER['REMOTE_ADDR'];
                $userId = $_SESSION['user_id'];
                
                $logQuery = "INSERT INTO user_activities (user_id, activity_type, description, ip_address, created_at) 
                             VALUES (?, ?, ?, ?, NOW())";
                $logStmt = $conn->prepare($logQuery);
                $logStmt->bind_param("isss", $userId, $activityType, $description, $ipAddress);
                $logStmt->execute();
                
                $conn->commit();
                $success = true;
                
                // Mettre à jour les données pour l'affichage
                $news['title'] = $title;
                $news['content'] = $content;
                $news['image_path'] = $imagePath;
                
                setFlashMessage("L'actualité a été mise à jour avec succès.", "success");
                redirect('../index.php');
            } catch (Exception $e) {
                $conn->rollback();
                $errors[] = "Une erreur est survenue lors de la mise à jour de l'actualité: " . $e->getMessage();
                
                // Supprimer la nouvelle image uploadée en cas d'erreur
                if ($imagePath !== $news['image_path'] && $imagePath && file_exists($imagePath)) {
                    unlink($imagePath);
                }
            } finally {
                // Fermer la connexion quoi qu'il arrive
                $conn->close();
            }
        }
    }
} else {
    // Si on n'est pas dans un traitement de formulaire, on peut fermer la connexion
    $conn->close();
}
?>

<div class="mb-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white flex items-center">
            <i class="fas fa-edit mr-2 text-blue-600 dark:text-blue-400"></i> Modifier une actualité
        </h1>
        <a href="../index.php" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <i class="fas fa-arrow-left mr-2"></i> Retour à l'accueil
        </a>
    </div>
</div>

<div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Modifier l'actualité</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Vous pouvez modifier le titre, le contenu et l'image de cette actualité</p>
    </div>
    
    <div class="p-6">
        <?php if (!empty($errors)): ?>
            <div class="bg-red-50 dark:bg-red-900/30 border-l-4 border-red-500 dark:border-red-700 text-red-800 dark:text-red-200 p-4 mb-6 rounded">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium">Des erreurs sont survenues:</h3>
                        <ul class="mt-1 text-sm list-disc list-inside">
                            <?php foreach ($errors as $error): ?>
                                <li><?= escapeString($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <form action="news-edit.php?id=<?= $newsId ?>" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            
            <div class="grid grid-cols-1 gap-y-6">
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Titre</label>
                    <div class="mt-1">
                        <input type="text" name="title" id="title" 
                              value="<?= escapeString($news['title']) ?>" 
                              class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md" 
                              required>
                    </div>
                </div>
                
                <div>
                    <label for="content" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Contenu</label>
                    <div class="mt-1">
                        <textarea name="content" id="content" rows="8" 
                                 class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md" 
                                 required><?= escapeString($news['content']) ?></textarea>
                    </div>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Vous pouvez utiliser des sauts de ligne pour structurer votre texte.</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Image de fond (optionnelle)</label>
                    
                    <?php if (!empty($news['image_path']) && file_exists($news['image_path'])): ?>
                        <div class="mt-2 mb-4">
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Image actuelle:</p>
                            <div class="relative">
                                <img src="<?= $news['image_path'] ?>" alt="Image actuelle" class="max-h-48 rounded border border-gray-200 dark:border-gray-700">
                                <div class="mt-2 flex items-center">
                                    <input type="checkbox" id="remove_image" name="remove_image" value="1" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded">
                                    <label for="remove_image" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                                        Supprimer l'image actuelle
                                    </label>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 dark:border-gray-600 border-dashed rounded-md">
                        <div class="space-y-1 text-center">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-image mx-auto h-12 w-12 text-gray-400 dark:text-gray-500"></i>
                                <div class="mt-4 flex text-sm text-gray-600 dark:text-gray-400">
                                    <label for="image" class="relative cursor-pointer bg-white dark:bg-gray-700 rounded-md font-medium text-blue-600 dark:text-blue-400 hover:text-blue-500 focus-within:outline-none">
                                        <span>Télécharger une nouvelle image</span>
                                        <input id="image" name="image" type="file" class="sr-only" accept="image/jpeg,image/png,image/gif">
                                    </label>
                                    <p class="pl-1">ou glisser-déposer</p>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">PNG, JPG ou GIF jusqu'à 5MB</p>
                            </div>
                            <div id="imagePreviewContainer" class="mt-4 hidden">
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Nouvelle image:</p>
                                <img id="imagePreview" src="#" alt="Aperçu de l'image" class="mx-auto max-h-48 rounded">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-6 pt-5 border-t border-gray-200 dark:border-gray-700">
                <div class="flex justify-end">
                    <a href="../index.php" class="inline-flex justify-center py-2 px-4 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 mr-3">
                        Annuler
                    </a>
                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-save mr-2"></i> Enregistrer les modifications
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Prévisualisation de l'image
document.addEventListener('DOMContentLoaded', function() {
    const imageInput = document.getElementById('image');
    const imagePreview = document.getElementById('imagePreview');
    const imagePreviewContainer = document.getElementById('imagePreviewContainer');
    const removeImageCheckbox = document.getElementById('remove_image');
    
    if (imageInput && imagePreview) {
        imageInput.addEventListener('change', function() {
            const file = this.files[0];
            
            if (file) {
                const reader = new FileReader();
                
                reader.addEventListener('load', function() {
                    imagePreview.src = reader.result;
                    imagePreviewContainer.classList.remove('hidden');
                    
                    // Décocher l'option de suppression d'image si une nouvelle est sélectionnée
                    if (removeImageCheckbox) {
                        removeImageCheckbox.checked = false;
                    }
                });
                
                reader.readAsDataURL(file);
            } else {
                imagePreviewContainer.classList.add('hidden');
            }
        });
    }
    
    // Gérer les interactions entre le checkbox et l'upload d'image
    if (removeImageCheckbox && imageInput) {
        removeImageCheckbox.addEventListener('change', function() {
            if (this.checked) {
                // Vider l'input de fichier si l'utilisateur choisit de supprimer l'image
                imageInput.value = '';
                imagePreviewContainer.classList.add('hidden');
            }
        });
    }
});
</script>

<?php require_once '../includes/admin-footer.php'; ?>