<?php
include '../components/connect.php';

session_start();

if(!isset($_SESSION['csr_id'])) {
   header('location:index.php');
   exit();
}

$csr_id = $_SESSION['csr_id'];
$csr_name = $_SESSION['csr_name'] ?? 'CSR Agent';

// Check if ticket ID is provided
if(!isset($_GET['id'])) {
   header('location:tickets.php');
   exit();
}

$ticket_id = $_GET['id'];

// Fetch ticket details
$ticket_query = $conn->prepare("
    SELECT t.*, 
           u.name as user_name,
           u.email as user_email
    FROM customer_support_tickets t
    JOIN users u ON t.user_id = u.id 
    WHERE t.ticket_id = ?
");
$ticket_query->execute([$ticket_id]);

// Check if ticket exists
if($ticket_query->rowCount() == 0) {
    header('location:tickets.php');
    exit();
}

$ticket = $ticket_query->fetch(PDO::FETCH_ASSOC);

// Handle response submission
if(isset($_POST['submit_response'])) {
    $response = trim($_POST['response']);
    $response_type = $_POST['response_type'];
    
    if(empty($response)) {
        $message[] = 'Response cannot be empty!';
    } else {
        try {
            // Begin transaction
            $conn->beginTransaction();
            
            // Add response
            $response_query = $conn->prepare("
                INSERT INTO ticket_responses (ticket_id, csr_id, response, type, user_id) 
                VALUES (?, ?, ?, ?, NULL)
            ");
            $response_query->execute([$ticket_id, $csr_id, $response, $response_type]);
            
            // Update ticket status if needed
            if($ticket['status'] == 'pending' && $response_type != 'note') {
                $update_status = $conn->prepare("
                    UPDATE customer_support_tickets
                    SET status = 'in_progress', 
                        csr_id = ?,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE ticket_id = ?
                ");
                $update_status->execute([$csr_id, $ticket_id]);
                
                // Refresh ticket data
                $ticket['status'] = 'in_progress';
                $ticket['csr_id'] = $csr_id;
            }
            
            // If response type is 'resolve', update ticket status to resolved
            if($response_type == 'resolve') {
                $resolve_ticket = $conn->prepare("
                    UPDATE customer_support_tickets
                    SET status = 'resolved',
                        updated_at = CURRENT_TIMESTAMP
                    WHERE ticket_id = ?
                ");
                $resolve_ticket->execute([$ticket_id]);
                
                // Refresh ticket data
                $ticket['status'] = 'resolved';
            }
            
            // Commit transaction
            $conn->commit();
            
            $message[] = 'Response submitted successfully!';
            
            // Optionally redirect back to tickets page after successful response
            // header('location:tickets.php');
            // exit();
            
        } catch (PDOException $e) {
            // Rollback transaction on error
            $conn->rollBack();
            $message[] = 'Error: ' . $e->getMessage();
        }
    }
}

// Fetch responses
$responses_query = $conn->prepare("
    SELECT r.*, 
           CASE
               WHEN r.type = 'user' THEN u.name
               WHEN (r.type = 'reply' OR r.type = 'note' OR r.type = 'resolve') THEN csr.name
               ELSE 'System'
           END as author_name
    FROM ticket_responses r
    LEFT JOIN users u ON r.user_id = u.id
    LEFT JOIN customer_sales_representatives csr ON r.csr_id = csr.csr_id
    WHERE r.ticket_id = ?
    ORDER BY r.created_at ASC
");
$responses_query->execute([$ticket_id]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Keens | Respond to Ticket #<?= $ticket_id ?></title>

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
</head>
<body>

<?php include '../components/csr_header.php'; ?>

<section class="respond-ticket-section mt-5">
    <div class="container">
    
        <div class="row">
            <!-- Main Content: Ticket Details and Response Form -->
            <div class="col-lg-8">
                <!-- Ticket Card -->
                <div class="content-card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge rounded-pill bg-primary">#<?= $ticket_id ?></span>
                            <h5 class="mb-0"><?= htmlspecialchars($ticket['subject']) ?></h5>
                        </div>
                        <span class="status-badge <?= getStatusClass($ticket['status']) ?>">
                            <?= ucfirst($ticket['status']) ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="ticket-info mb-4">
                            <div class="row mb-2">
                                <div class="col-md-6">
                                    <small class="text-muted">From:</small>
                                    <p class="mb-0"><?= htmlspecialchars($ticket['user_name']) ?> (<?= htmlspecialchars($ticket['user_email']) ?>)</p>
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <small class="text-muted">Created:</small>
                                    <p class="mb-0"><?= date('F j, Y, g:i a', strtotime($ticket['created_at'])) ?></p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <small class="text-muted">Priority:</small>
                                    <p class="mb-0">
                                        <span class="badge bg-<?php
                                            switch($ticket['priority']) {
                                                case 'high': echo 'danger'; break;
                                                case 'medium': echo 'warning'; break;
                                                default: echo 'success'; break;
                                            }
                                        ?>"><?= ucfirst($ticket['priority']) ?></span>
                                    </p>
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <small class="text-muted">Assigned To:</small>
                                    <p class="mb-0"><?= $ticket['csr_id'] == $csr_id ? 'You' : ($ticket['csr_id'] ? 'Another CSR' : 'Unassigned') ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="ticket-description p-3 rounded-3 mb-3" style="background-color: #f8f9fa;">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge bg-secondary">Original Request</span>
                                <small class="text-muted"><?= date('M d, Y h:i A', strtotime($ticket['created_at'])) ?></small>
                            </div>
                            <div class="description-content">
                                <?= nl2br(htmlspecialchars($ticket['description'])) ?>
                            </div>
                        </div>
                        
                        <!-- Responses -->
                        <?php if($responses_query->rowCount() > 0): ?>
                            <div class="ticket-responses mb-4">
                                <h6 class="fw-bold mb-3">Response History</h6>
                                
                                <?php while($response = $responses_query->fetch(PDO::FETCH_ASSOC)): ?>
                                    <div class="response-item p-3 rounded-3 mb-3 <?= getResponseClass($response['type']) ?>" 
                                         style="border-left: 4px solid <?= getResponseBorderColor($response['type']) ?>; background-color: <?= getResponseBackgroundColor($response['type']) ?>;">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="<?= getResponseIcon($response['type']) ?>"></i>
                                                <span class="fw-bold"><?= htmlspecialchars($response['author_name']) ?></span>
                                                <?php if($response['type'] == 'note'): ?>
                                                    <span class="badge bg-warning text-dark">Internal Note</span>
                                                <?php elseif($response['type'] == 'resolve'): ?>
                                                    <span class="badge bg-success">Resolution</span>
                                                <?php endif; ?>
                                            </div>
                                            <small class="text-muted"><?= date('M d, Y h:i A', strtotime($response['created_at'])) ?></small>
                                        </div>
                                        <div class="response-content">
                                            <?= nl2br(htmlspecialchars($response['response'])) ?>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info mb-4">No responses yet.</div>
                        <?php endif; ?>
                        
                        <!-- Response Form -->
                        <div class="response-form">
                            <h6 class="fw-bold mb-3">Add Your Response</h6>
                            <form action="" method="POST">
                                <div class="mb-3">
                                    <textarea name="response" class="form-control" rows="5" 
                                              placeholder="Type your response here..." required></textarea>
                                </div>
                                <div class="d-flex flex-wrap gap-3 align-items-center justify-content-between">
                                    <div class="response-types">
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="response_type" 
                                                   id="typeReply" value="reply" checked>
                                            <label class="form-check-label" for="typeReply">
                                                <i class="fas fa-reply text-primary me-1"></i> Reply to Customer
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="response_type" 
                                                   id="typeNote" value="note">
                                            <label class="form-check-label" for="typeNote">
                                                <i class="fas fa-sticky-note text-warning me-1"></i> Internal Note
                                            </label>
                                        </div>
                                        <?php if($ticket['status'] != 'resolved' && $ticket['status'] != 'closed'): ?>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="response_type" 
                                                       id="typeResolve" value="resolve">
                                                <label class="form-check-label" for="typeResolve">
                                                    <i class="fas fa-check-circle text-success me-1"></i> Resolve Ticket
                                                </label>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <button type="submit" name="submit_response" class="btn btn-primary">
                                        <i class="fas fa-paper-plane me-1"></i> Submit Response
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar: User Info and Quick Actions -->
            <div class="col-lg-4">
                <!-- User Info Card -->
                <div class="content-card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-user me-2"></i>User Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="user-info-item mb-3">
                            <small class="text-muted d-block">Name:</small>
                            <p class="mb-0 fw-bold"><?= htmlspecialchars($ticket['user_name']) ?></p>
                        </div>
                        <div class="user-info-item mb-3">
                            <small class="text-muted d-block">Email:</small>
                            <p class="mb-0"><?= htmlspecialchars($ticket['user_email']) ?></p>
                        </div>
                        <a href="user_search.php?search=<?= urlencode($ticket['user_email']) ?>" class="btn btn-outline-primary w-100">
                            <i class="fas fa-search me-1"></i> View User Details
                        </a>
                    </div>
                </div>
                
                <!-- Quick Actions Card -->
                <div class="content-card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <?php if($ticket['status'] != 'resolved'): ?>
                                <button class="btn btn-success" onclick="submitResolveForm()">
                                    <i class="fas fa-check-circle me-1"></i> Mark as Resolved
                                </button>
                            <?php endif; ?>
                            
                            <?php if($ticket['csr_id'] != $csr_id): ?>
                                <form action="" method="POST" id="assignForm">
                                    <input type="hidden" name="assign_ticket" value="1">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-user-check me-1"></i> Assign to Me
                                    </button>
                                </form>
                            <?php endif; ?>
                            
                            <a href="tickets.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Back to Tickets
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Response Templates Card -->
                <div class="content-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i>Response Templates</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            <button type="button" class="list-group-item list-group-item-action" 
                                    onclick="insertTemplate('greeting')">
                                Greeting
                            </button>
                            <button type="button" class="list-group-item list-group-item-action" 
                                    onclick="insertTemplate('troubleshooting')">
                                Troubleshooting Steps
                            </button>
                            <button type="button" class="list-group-item list-group-item-action" 
                                    onclick="insertTemplate('follow_up')">
                                Follow Up Request
                            </button>
                            <button type="button" class="list-group-item list-group-item-action" 
                                    onclick="insertTemplate('closure')">
                                Ticket Resolution
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
:root {
    --primary-color: #4361ee;
    --secondary-color: #3f37c9;
    --accent-color: #4895ef;
    --success-color: #2ecc71;
    --warning-color: #f39c12;
    --danger-color: #e74c3c;
    --info-color: #3498db;
}

.content-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    margin-bottom: 1.5rem;
}

.card-header {
    padding: 1.25rem;
    border-bottom: 1px solid rgba(0,0,0,0.05);
    background-color: #fff;
}

.card-body {
    padding: 1.25rem;
}

.status-badge {
    padding: 0.5rem 1rem;
    border-radius: 30px;
    font-weight: 500;
}

.status-badge.pending {
    background: rgba(243, 156, 18, 0.1);
    color: var(--warning-color);
}

.status-badge.in_progress {
    background: rgba(52, 152, 219, 0.1);
    color: var(--info-color);
}

.status-badge.resolved {
    background: rgba(46, 204, 113, 0.1);
    color: var(--success-color);
}

.status-badge.closed {
    background: rgba(142, 142, 147, 0.1);
    color: #8e8e93;
}

.response-user {
    background-color: rgba(52, 152, 219, 0.05);
}

.response-reply {
    background-color: rgba(46, 204, 113, 0.05);
}

.response-note {
    background-color: rgba(243, 156, 18, 0.05);
}

.response-resolve {
    background-color: rgba(46, 204, 113, 0.1);
}

.response-item {
    transition: all 0.3s ease;
}

.response-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
}

.form-control:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
}

.btn-primary {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
}

.btn-primary:hover {
    background-color: var(--secondary-color);
    border-color: var(--secondary-color);
}

@media (max-width: 992px) {
    .content-card {
        margin-bottom: 1rem;
    }
}
</style>

<script>
// Helper functions
<?php
function getStatusClass($status) {
    switch($status) {
        case 'pending': return 'pending';
        case 'in_progress': return 'in_progress';
        case 'resolved': return 'resolved';
        case 'closed': return 'closed';
        default: return '';
    }
}

function getResponseClass($type) {
    switch($type) {
        case 'user': return 'response-user';
        case 'reply': return 'response-reply';
        case 'note': return 'response-note';
        case 'resolve': return 'response-resolve';
        default: return '';
    }
}

function getResponseBorderColor($type) {
    switch($type) {
        case 'user': return '#3498db';
        case 'reply': return '#2ecc71';
        case 'note': return '#f39c12';
        case 'resolve': return '#27ae60';
        default: return '#e9ecef';
    }
}

function getResponseBackgroundColor($type) {
    switch($type) {
        case 'user': return 'rgba(52, 152, 219, 0.05)';
        case 'reply': return 'rgba(46, 204, 113, 0.05)';
        case 'note': return 'rgba(243, 156, 18, 0.05)';
        case 'resolve': return 'rgba(46, 204, 113, 0.1)';
        default: return '#f8f9fa';
    }
}

function getResponseIcon($type) {
    switch($type) {
        case 'user': return 'fas fa-user text-primary';
        case 'reply': return 'fas fa-reply text-success';
        case 'note': return 'fas fa-sticky-note text-warning';
        case 'resolve': return 'fas fa-check-circle text-success';
        default: return 'fas fa-comment text-secondary';
    }
}
?>

// JavaScript functions
function submitResolveForm() {
    document.getElementById('typeResolve').checked = true;
    document.querySelector('textarea[name="response"]').value = 
        document.querySelector('textarea[name="response"]').value || 
        "This ticket has been resolved. Please let us know if you need any further assistance.";
    
    if(confirm('Are you sure you want to mark this ticket as resolved?')) {
        document.querySelector('button[name="submit_response"]').click();
    }
}

function insertTemplate(template) {
    const textarea = document.querySelector('textarea[name="response"]');
    
    switch(template) {
        case 'greeting':
            textarea.value = "Hello <?= htmlspecialchars($ticket['user_name']) ?>,\n\nThank you for contacting our support team. I'm happy to help you with your inquiry.\n\n";
            break;
        case 'troubleshooting':
            textarea.value = "To help troubleshoot this issue, please try the following steps:\n\n1. Step one\n2. Step two\n3. Step three\n\nPlease let me know if this resolves the issue or if you need further assistance.";
            break;
        case 'follow_up':
            textarea.value = "I wanted to follow up on your recent inquiry. Have you had a chance to try the solution I suggested? Please let me know if you're still experiencing issues or if there's anything else I can help with.";
            break;
        case 'closure':
            textarea.value = "I'm pleased to inform you that this issue has been resolved. Here's a summary of what was done:\n\n- Action taken\n- Result achieved\n\nIs there anything else I can assist you with today?";
            break;
    }
    
    // Focus on textarea
    textarea.focus();
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>