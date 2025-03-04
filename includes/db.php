<?php
require_once 'config.php';

function connectDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Vérifier la connexion
    if ($conn->connect_error) {
        die("Erreur de connexion à la base de données: " . $conn->connect_error);
    }
    
    // Définir le jeu de caractères
    $conn->set_charset("utf8mb4");
    
    return $conn;
}

// Fonction pour exécuter une requête
function executeQuery($sql, $params = []) {
    $conn = connectDB();
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $types = '';
        $bindParams = [];
        
        // Déterminer les types de paramètres
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } elseif (is_string($param)) {
                $types .= 's';
            } else {
                $types .= 'b';
            }
            $bindParams[] = $param;
        }
        
        // Créer un tableau de références pour bind_param
        $bindParamsRef = [];
        $bindParamsRef[] = &$types;
        
        foreach ($bindParams as $key => $value) {
            $bindParamsRef[] = &$bindParams[$key];
        }
        
        // Appliquer les paramètres
        call_user_func_array([$stmt, 'bind_param'], $bindParamsRef);
    }
    
    // Exécuter la requête
    $stmt->execute();
    
    // Retourner le résultat pour les requêtes SELECT
    $result = $stmt->get_result();
    
    // Fermer la connexion
    $stmt->close();
    $conn->close();
    
    return $result;
}
?>