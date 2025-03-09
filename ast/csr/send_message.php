<?php
include '../components/connect.php';

session_start();

header('Content-Type: application/json');

// Check if CSR is logged in
if (!isset($_SESSION['csr_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit();
}

// Get JSON POST data
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($data['session_id']) || !isset($data['message']) || empty(trim($data['message']))) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Session ID and message are required'
    ]);
    exit();
}

try {
    $session_id = $data['session_id'];
    $message = trim($data['message']);
    $csr_id = $_SESSION['csr_id'];

    // Start transaction
    $conn->beginTransaction();

    // Verify chat session exists and belongs to the CSR
    $verify_session = $conn->prepare("
        SELECT status, customer_id 
        FROM chat_sessions 
        WHERE session_id = ? AND csr_id = ?
    ");
    $verify_session->execute([$session_id, $csr_id]);
    $session = $verify_session->fetch(PDO::FETCH_ASSOC);

    if (!$session) {
        $conn->rollBack();
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid chat session'
        ]);
        exit();
    }

    // Check if chat is still active
    if ($session['status'] !== 'active') {
        $conn->rollBack();
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Chat session is not active'
        ]);
        exit();
    }

    // Insert message
    $insert_message = $conn->prepare("
        INSERT INTO chat_messages (
            session_id, 
            sender_type, 
            sender_id, 
            message
        ) VALUES (?, 'csr', ?, ?)
    ");
    $insert_message->execute([
        $session_id,
        $csr_id,
        $message
    ]);

    // Update last_message_at in chat session
    $update_session = $conn->prepare("
        UPDATE chat_sessions 
        SET last_message_at = CURRENT_TIMESTAMP 
        WHERE session_id = ?
    ");
    $update_session->execute([$session_id]);

    // Commit transaction
    $conn->commit();

    // Fetch the inserted message details
    $message_query = $conn->prepare("
        SELECT 
            m.*,
            csr.name as sender_name
        FROM chat_messages m
        JOIN customer_sales_representatives csr ON m.sender_id = csr.csr_id
        WHERE m.message_id = LAST_INSERT_ID()
    ");
    $message_query->execute();
    $sent_message = $message_query->fetch(PDO::FETCH_ASSOC);

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Message sent successfully',
        'data' => [
            'message_id' => $sent_message['message_id'],
            'message' => htmlspecialchars($sent_message['message']),
            'sender_name' => htmlspecialchars($sent_message['sender_name']),
            'sent_at' => date('M d, Y H:i', strtotime($sent_message['sent_at'])),
            'is_sender' => true
        ]
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error sending message: ' . $e->getMessage()
    ]);
}

// Optional: Trigger notification to customer (if implementing real-time features)
try {
    // You could add WebSocket notification code here
    // Or trigger server events
    // Or update a notifications table
} catch (Exception $e) {
    // Log notification error but don't affect the main response
    error_log('Notification error: ' . $e->getMessage());
}
?>