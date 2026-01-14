<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Get JSON input
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validate input data
if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON data']);
    exit();
}

// Validate required fields
if (!isset($data['predictions']) || !isset($data['summary'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields: predictions and summary']);
    exit();
}

// Get filename if sent from client
$fileName = isset($data['fileName']) ? $data['fileName'] : null; // Retrieve fileName

try {
    // Store results in session with timestamp
    $_SESSION['prediction_results'] = [
        'predictions' => $data['predictions'],
        'summary' => $data['summary'],
        'timestamp' => date('Y-m-d H:i:s'),
        'user_id' => $_SESSION['user_id'],
        'file_name' => $fileName // Store fileName in session as well
    ];
    
    // Also store in database for future reference (optional)
    if (isset($pdo)) {
        try {
            // Check if prediction_history table exists and has the file_name column.
            // If not, you'll need to add it: ALTER TABLE prediction_history ADD COLUMN file_name VARCHAR(255);
            $stmt = $pdo->prepare("
                INSERT INTO prediction_history (user_id, file_name, results_data, created_at) 
                VALUES (?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                file_name = VALUES(file_name),             
                results_data = VALUES(results_data), 
                created_at = NOW()
            ");
            
            $stmt->execute([
                $_SESSION['user_id'],
                $fileName, // Pass fileName to the execute method
                json_encode($data)
            ]);
        } catch (PDOException $e) {
            // Log database error but don't fail the request
            error_log("Database error in store_results.php: " . $e->getMessage());
        }
    }
    
    // Return success response
    echo json_encode([
        'status' => 'success',
        'message' => 'Results stored successfully',
        'stored_predictions' => count($data['predictions']),
        'timestamp' => $_SESSION['prediction_results']['timestamp'],
        'file_name' => $fileName // Include fileName in the success response
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to store results',
        'message' => $e->getMessage()
    ]);
}
?>