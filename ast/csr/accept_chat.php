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

// Validate session_id is provided
if (!isset($data['session_id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Session ID is required'
    ]);
    exit();
}

try {
    $session_id = $data['session_id'];
    $csr_id = $_SESSION['csr_id'];

    // Start transaction
    $conn->beginTransaction();

    // Check if CSR is available
    $check_csr = $conn->prepare("
        SELECT is_available 
        FROM customer_sales_representatives 
        WHERE csr_id = ?
    ");
    $check_csr->execute([$csr_id]);
    $csr_status = $check_csr->fetch(PDO::FETCH_ASSOC);

    if (!$csr_status['is_available']) {
        $conn->rollBack();
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'You must be available to accept chats'
        ]);
        exit();
    }

    // Check if chat session is still waiting and not assigned
    $check_session = $conn->prepare("
        SELECT cs.*, 
               CONCAT(c.first_name, ' ', c.last_name) as customer_name,
               c.email as customer_email
        FROM chat_sessions cs
        JOIN customers c ON cs.customer_id = c.customer_id
        WHERE cs.session_id = ? 
        AND cs.status = 'waiting' 
        AND cs.csr_id IS NULL
    ");
    $check_session->execute([$session_id]);
    $session = $check_session->fetch(PDO::FETCH_ASSOC);

    if (!$session) {
        $conn->rollBack();
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Chat session is no longer available'
        ]);
        exit();
    }

    // Update chat session
    $update_session = $conn->prepare("
        UPDATE chat_sessions 
        SET csr_id = ?,
            status = 'active',
            last_message_at = CURRENT_TIMESTAMP 
        WHERE session_id = ?
    ");
    $update_session->execute([$csr_id, $session_id]);

    // Add system message about CSR joining
    $add_system_message = $conn->prepare("
        INSERT INTO chat_messages (
            session_id,
            sender_type,
            sender_id,
            message
        ) VALUES (?, 'csr', ?, 'CSR has joined the chat.')
    ");
    $add_system_message->execute([$session_id, $csr_id]);

    // Check active chat count for CSR
    $check_active_chats = $conn->prepare("
        SELECT COUNT(*) as active_count 
        FROM chat_sessions 
        WHERE csr_id = ? AND status = 'active'
    ");
    $check_active_chats->execute([$csr_id]);
    $active_chats = $check_active_chats->fetch(PDO::FETCH_ASSOC);

    // If CSR has reached max chats, set availability to false
    if ($active_chats['active_count'] >= 3) { // Assuming max 3 concurrent chats
        $update_availability = $conn->prepare("
            UPDATE customer_sales_representatives 
            SET is_available = 0 
            WHERE csr_id = ?
        ");
        $update_availability->execute([$csr_id]);
    }

    // Commit transaction
    $conn->commit();

    // Return success response with session details
    echo json_encode([
        'success' => true,
        'message' => 'Chat session accepted successfully',
        'data' => [
            'session_id' => $session_id,
            'customer_name' => htmlspecialchars($session['customer_name']),
            'customer_email' => htmlspecialchars($session['customer_email']),
            'started_at' => date('M d, Y H:i', strtotime($session['created_at'])),
            'active_chats' => $active_chats['active_count']
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
        'message' => 'Error accepting chat: ' . $e->getMessage()
    ]);
}

// Optional: Notify customer that CSR has joined
try {
    // Add notification logic here (WebSocket, Server-Sent Events, etc.)
    // This could include:
    // - Updating a notifications table
    // - Triggering a WebSocket event
    // - Sending an email notification
} catch (Exception $e) {
    // Log notification error but don't affect the main response
    error_log('Notification error: ' . $e->getMessage());
}
?>