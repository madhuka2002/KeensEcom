<?php
include '../components/connect.php';
session_start();

header('Content-Type: application/json');

// Check if CSR is logged in
if (!isset($_SESSION['csr_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

try {
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['available'])) {
        throw new Exception('Availability status not provided');
    }

    $csr_id = $_SESSION['csr_id'];
    $available = $data['available'] ? 1 : 0;

    // Update availability status
    $update = $conn->prepare("
        UPDATE customer_sales_representatives 
        SET is_available = ? 
        WHERE csr_id = ?
    ");
    
    $update->execute([$available, $csr_id]);

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Availability status updated successfully',
        'status' => $available
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error updating availability: ' . $e->getMessage()
    ]);
}
?>