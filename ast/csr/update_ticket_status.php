<?php
include '../components/connect.php';
session_start();

header('Content-Type: application/json');

if(!isset($_SESSION['csr_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if(!isset($data['ticket_id']) || !isset($data['status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

try {
    $csr_id = $_SESSION['csr_id'];
    $ticket_id = $data['ticket_id'];
    $status = $data['status'];

    // Update ticket status
    $update = $conn->prepare("
        UPDATE support_tickets 
        SET status = ?, 
            updated_at = CURRENT_TIMESTAMP 
        WHERE ticket_id = ? 
        AND csr_id = ?
    ");
    $update->execute([$status, $ticket_id, $csr_id]);

    // Add history record
    $add_history = $conn->prepare("
        INSERT INTO ticket_history (
            ticket_id, 
            csr_id, 
            old_status, 
            new_status
        ) VALUES (?, ?, 'pending', ?)
    ");
    $add_history->execute([$ticket_id, $csr_id, $status]);

    echo json_encode(['success' => true]);

} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error updating ticket']);
}
?>