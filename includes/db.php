<?php
require_once 'config.php';

/**
 * Connexion à la base de données
 * 
 * @return mysqli L'objet de connexion à la base de données
 * @throws Exception Si la connexion échoue
 */
function connectDB() {
    static $conn = null;
    
    // Connexion singleton (évite de créer plusieurs connexions)
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        // Vérifier la connexion
        if ($conn->connect_error) {
            $error = "Erreur de connexion à la base de données: " . $conn->connect_error;
            
            // Journaliser l'erreur mais ne pas l'afficher aux utilisateurs en production
            error_log($error);
            
            if (APP_ENV === 'development') {
                throw new Exception($error);
            } else {
                throw new Exception("Une erreur de connexion à la base de données est survenue. Veuillez contacter l'administrateur.");
            }
        }
        
        // Définir le jeu de caractères
        $conn->set_charset("utf8mb4");
    }
    
    return $conn;
}

/**
 * Exécute une requête SQL préparée avec des paramètres
 * 
 * @param string $sql La requête SQL avec des placeholders
 * @param array $params Les paramètres à lier à la requête
 * @return mysqli_result|bool Le résultat de la requête ou false en cas d'erreur
 */
function executeQuery($sql, $params = []) {
    try {
        $conn = connectDB();
        $stmt = $conn->prepare($sql);
        
        if ($stmt === false) {
            throw new Exception("Erreur de préparation de la requête: " . $conn->error);
        }
        
        if (!empty($params)) {
            $types = '';
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_float($param) || is_double($param)) {
                    $types .= 'd';
                } elseif (is_string($param)) {
                    $types .= 's';
                } else {
                    $types .= 'b';
                }
            }
            
            // Créer un tableau avec les références
            $bindParams = array_merge([$types], $params);
            $bindParamsRefs = [];
            
            for ($i = 0; $i < count($bindParams); $i++) {
                $bindParamsRefs[] = &$bindParams[$i];
            }
            
            call_user_func_array([$stmt, 'bind_param'], $bindParamsRefs);
        }
        
        // Exécuter la requête
        if (!$stmt->execute()) {
            throw new Exception("Erreur d'exécution de la requête: " . $stmt->error);
        }
        
        // Récupérer le résultat pour les requêtes SELECT
        $result = $stmt->get_result();
        
        $stmt->close();
        
        return $result;
    } catch (Exception $e) {
        // Journaliser l'erreur
        error_log($e->getMessage());
        
        // Afficher l'erreur en mode développement
        if (APP_ENV === 'development') {
            echo "<div style='color:red; padding:10px; border:1px solid red;'>";
            echo "Erreur SQL: " . $e->getMessage();
            echo "</div>";
        }
        
        return false;
    }
}

/**
 * Exécute une transaction avec plusieurs requêtes
 * 
 * @param callable $callback Fonction contenant les requêtes à exécuter
 * @return bool True si la transaction réussit, false sinon
 */
function executeTransaction($callback) {
    $conn = connectDB();
    
    try {
        $conn->begin_transaction();
        
        $result = $callback($conn);
        
        if ($result) {
            $conn->commit();
            return true;
        } else {
            $conn->rollback();
            return false;
        }
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Erreur de transaction: " . $e->getMessage());
        return false;
    }
}