<?php
include '../components/connect.php';
session_start();

header('Content-Type: application/json');

if(!isset($_SESSION['csr_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if(!isset($_GET['ticket_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Ticket ID is required']);
    exit();
}

try {
    $ticket_id = $_GET['ticket_id'];
    $csr_id = $_SESSION['csr_id'];

    $query = $conn->prepare("
        SELECT t.*,
               csr.name as resolved_by,
               tr.response as resolution_notes,
               TIMESTAMPDIFF(HOUR, t.created_at, t.updated_at) as resolution_time
        FROM support_tickets t
        JOIN customer_sales_representatives csr ON t.csr_id = csr.csr_id
        LEFT JOIN ticket_responses tr ON t.ticket_id = tr.ticket_id
        WHERE t.ticket_id = ? AND t.csr_id = ?
        ORDER BY tr.created_at DESC
        LIMIT 1
    ");
    $query->execute([$ticket_id, $csr_id]);
    $details = $query->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'resolution_time' => $details['resolution_time'],
        'notes' => htmlspecialchars($details['resolution_notes']),
        'resolved_by' => htmlspecialchars($details['resolved_by']),
        'resolved_date' => date('M d, Y H:i', strtotime($details['updated_at']))
    ]);

} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error fetching resolution details']);
}
?>