<?php
include '../components/connect.php';

session_start();

// Ensure admin is logged in
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode([
        'success' => false, 
        'message' => 'Unauthorized access'
    ]);
    exit;
}

// Check if delete request is made
if ($_SERVER['REQUEST_METHOD'] === 'POST' && 
    isset($_POST['delete_reviews']) && 
    isset($_POST['product_id']) && 
    isset($_POST['selected_reviews'])) {

    $product_id = filter_var($_POST['product_id'], FILTER_VALIDATE_INT);
    $selected_reviews = $_POST['selected_reviews'];

    // Validate inputs
    if (!$product_id || !is_array($selected_reviews)) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'Invalid input'
        ]);
        exit;
    }

    try {
        // Begin transaction
        $conn->beginTransaction();

        // Prepare the delete statement
        $delete_stmt = $conn->prepare("
            DELETE FROM reviews 
            WHERE id = ? AND product_id = ?
        ");

        $successful_deletions = 0;
        $errors = [];

        // Delete each selected review
        foreach ($selected_reviews as $review_id) {
            // Sanitize review ID
            $clean_review_id = filter_var($review_id, FILTER_VALIDATE_INT);
            
            if (!$clean_review_id) {
                $errors[] = "Invalid review ID: $review_id";
                continue;
            }

            $delete_stmt->execute([$clean_review_id, $product_id]);
            $successful_deletions += $delete_stmt->rowCount();
        }

        // Commit the transaction
        $conn->commit();

        // Prepare response
        $response = [
            'success' => true, 
            'message' => "Successfully deleted {$successful_deletions} review(s)",
            'deleted_count' => $successful_deletions
        ];

        // Add errors to response if any
        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        // Return success response
        header('Content-Type: application/json');
        echo json_encode($response);

    } catch (PDOException $e) {
        // Rollback the transaction
        $conn->rollBack();

        // Log the error
        error_log("Review deletion error: " . $e->getMessage());

        // Return error response
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'An error occurred while deleting reviews',
            'error' => $e->getMessage()
        ]);
    }
} else {
    // Invalid request
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid request'
    ]);
}
exit;