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

// Check if session ID is provided
if (!isset($_GET['session_id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Session ID is required'
    ]);
    exit();
}

try {
    $session_id = $_GET['session_id'];
    $csr_id = $_SESSION['csr_id'];

    // Verify that this chat session belongs to the CSR
    $verify_session = $conn->prepare("
        SELECT * FROM chat_sessions 
        WHERE session_id = ? AND csr_id = ? AND status = 'active'
    ");
    $verify_session->execute([$session_id, $csr_id]);

    if ($verify_session->rowCount() == 0) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Unauthorized access to this chat session'
        ]);
        exit();
    }

    // Fetch messages
    $messages_query = $conn->prepare("
        SELECT 
            m.*,
            CASE 
                WHEN m.sender_type = 'customer' THEN CONCAT(c.first_name, ' ', c.last_name)
                WHEN m.sender_type = 'csr' THEN csr.name
            END as sender_name
        FROM chat_messages m
        LEFT JOIN customers c ON m.sender_type = 'customer' AND m.sender_id = c.customer_id
        LEFT JOIN customer_sales_representatives csr ON m.sender_type = 'csr' AND m.sender_id = csr.csr_id
        WHERE m.session_id = ?
        ORDER BY m.sent_at ASC
    ");
    $messages_query->execute([$session_id]);

    $messages = [];
    while ($message = $messages_query->fetch(PDO::FETCH_ASSOC)) {
        $messages[] = [
            'id' => $message['message_id'],
            'message' => htmlspecialchars($message['message']),
            'sender_type' => $message['sender_type'],
            'sender_name' => htmlspecialchars($message['sender_name']),
            'sent_at' => date('M d, Y H:i', strtotime($message['sent_at'])),
            'is_sender' => ($message['sender_type'] == 'csr' && $message['sender_id'] == $csr_id)
        ];
    }

    // Get chat session details
    $session_query = $conn->prepare("
        SELECT 
            cs.*,
            CONCAT(c.first_name, ' ', c.last_name) as customer_name,
            c.email as customer_email
        FROM chat_sessions cs
        JOIN customers c ON cs.customer_id = c.customer_id
        WHERE cs.session_id = ?
    ");
    $session_query->execute([$session_id]);
    $session = $session_query->fetch(PDO::FETCH_ASSOC);

    // Return success response with messages and session details
    echo json_encode([
        'success' => true,
        'messages' => $messages,
        'session' => [
            'id' => $session['session_id'],
            'customer_name' => htmlspecialchars($session['customer_name']),
            'customer_email' => htmlspecialchars($session['customer_email']),
            'status' => $session['status'],
            'started_at' => date('M d, Y H:i', strtotime($session['created_at']))
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching messages: ' . $e->getMessage()
    ]);
}
?>