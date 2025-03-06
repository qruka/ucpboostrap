<?php
require_once 'config.php';

/**
 * Creates a new database connection
 * 
 * @return mysqli|null The database connection object or null on failure
 */
function connectDB() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            error_log("Database connection error: " . $conn->connect_error);
            return null;
        }
        
        $conn->set_charset("utf8mb4");
        return $conn;
    } catch (Exception $e) {
        error_log("Exception in connectDB: " . $e->getMessage());
        return null;
    }
}

/**
 * Safely executes a prepared SQL query with parameters
 * 
 * @param string $sql The SQL query with placeholders
 * @param array $params Parameters to bind
 * @return mysqli_result|bool Query result or false on failure
 */
function executeQuery($sql, $params = []) {
    // Create a fresh connection for this query
    $conn = connectDB();
    
    if (!$conn) {
        return false;
    }
    
    try {
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            error_log("SQL prepare error: " . $conn->error . " for query: " . $sql);
            return false;
        }
        
        // Bind parameters if any
        if (!empty($params)) {
            $types = '';
            $bindParams = [];
            
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
            
            // Create array of references
            $bindValues = array_merge([$types], $bindParams);
            $refs = [];
            
            foreach ($bindValues as $key => $value) {
                $refs[$key] = &$bindValues[$key];
            }
            
            call_user_func_array([$stmt, 'bind_param'], $refs);
        }
        
        // Execute the query
        $stmt->execute();
        
        // Get result for SELECT queries
        $result = $stmt->get_result();
        
        $stmt->close();
        
        // Close the connection immediately after use
        $conn->close();
        
        return $result;
    } catch (Exception $e) {
        error_log("Exception in executeQuery: " . $e->getMessage() . " for query: " . $sql);
        
        // Try to close the connection even if there was an error
        try {
            $conn->close();
        } catch (Exception $closeError) {
            // Ignore close errors
        }
        
        return false;
    }
}

/**
 * Execute a series of queries within a transaction
 * 
 * @param callable $callback Function containing the queries
 * @return bool True on success, false on failure
 */
function executeTransaction($callback) {
    // Create a fresh connection
    $conn = connectDB();
    
    if (!$conn) {
        return false;
    }
    
    try {
        $conn->begin_transaction();
        $result = $callback($conn);
        
        if ($result) {
            $conn->commit();
        } else {
            $conn->rollback();
        }
        
        // Close the connection
        $conn->close();
        
        return $result;
    } catch (Exception $e) {
        error_log("Transaction error: " . $e->getMessage());
        
        try {
            $conn->rollback();
            $conn->close();
        } catch (Exception $closeError) {
            // Ignore rollback and close errors
        }
        
        return false;
    }
}

// NO SHUTDOWN FUNCTION - We don't need one as each function manages its own connections