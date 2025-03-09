<?php
include '../components/connect.php';

session_start();

if(!isset($_SESSION['csr_id'])) {
   header('location:index.php');
   exit();
}

$csr_id = $_SESSION['csr_id'];

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
$ticket = $ticket_query->fetch(PDO::FETCH_ASSOC);

// Check if ticket exists and belongs to this CSR
if(!$ticket || $ticket['csr_id'] != $csr_id) {
    header('location:tickets.php');
    exit();
}

// Handle status update
if(isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $old_status = $ticket['status'];
    
    try {
        $conn->beginTransaction();
        
        // Update ticket status
        $update_query = $conn->prepare("
            UPDATE customer_support_tickets 
            SET status = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE ticket_id = ?
        ");
        $update_query->execute([$new_status, $ticket_id]);
        
        // Add to ticket history
        $history_query = $conn->prepare("
            INSERT INTO ticket_history (ticket_id, csr_id, old_status, new_status)
            VALUES (?, ?, ?, ?)
        ");
        $history_query->execute([$ticket_id, $csr_id, $old_status, $new_status]);
        
        // If status changed to resolved, add to work logs
        if ($new_status == 'resolved') {
            $log_query = $conn->prepare("
                INSERT INTO csr_work_logs (csr_id, activity_type, details)
                VALUES (?, 'ticket_resolved', ?)
            ");
            $log_query->execute([$csr_id, "Resolved ticket #" . $ticket_id]);
        }
        
        $conn->commit();
        $message[] = 'Ticket status updated successfully!';
        
        // Refresh ticket data
        $ticket['status'] = $new_status;
        
    } catch (PDOException $e) {
        $conn->rollBack();
        $message[] = 'Error updating status: ' . $e->getMessage();
    }
}

// Handle adding response
if(isset($_POST['add_response'])) {
    $response = trim($_POST['response']);
    $response_type = $_POST['response_type'];
    
    if(empty($response)) {
        $message[] = 'Response cannot be empty!';
    } else {
        try {
            $conn->beginTransaction();
            
            // Add response
            $response_query = $conn->prepare("
                INSERT INTO ticket_responses (ticket_id, csr_id, response, type)
                VALUES (?, ?, ?, ?)
            ");
            $response_query->execute([$ticket_id, $csr_id, $response, $response_type]);
            
            // Update ticket if it was pending
            if($ticket['status'] == 'pending') {
                $update_query = $conn->prepare("
                    UPDATE customer_support_tickets 
                    SET status = 'in_progress', updated_at = CURRENT_TIMESTAMP 
                    WHERE ticket_id = ?
                ");
                $update_query->execute([$ticket_id]);
                
                // Add to ticket history
                $history_query = $conn->prepare("
                    INSERT INTO ticket_history (ticket_id, csr_id, old_status, new_status)
                    VALUES (?, ?, 'pending', 'in_progress')
                ");
                $history_query->execute([$ticket_id, $csr_id]);
                
                $ticket['status'] = 'in_progress';
            }
            
            $conn->commit();
            $message[] = 'Response added successfully!';
            
        } catch (PDOException $e) {
            $conn->rollBack();
            $message[] = 'Error adding response: ' . $e->getMessage();
        }
    }
}

// Fetch responses
$responses_query = $conn->prepare("
    SELECT r.*, 
           csr.name as csr_name
    FROM ticket_responses r
    LEFT JOIN customer_sales_representatives csr ON r.csr_id = csr.csr_id
    WHERE r.ticket_id = ?
    ORDER BY r.created_at ASC
");
$responses_query->execute([$ticket_id]);

// Fetch ticket history
$history_query = $conn->prepare("
    SELECT h.*, 
           csr.name as csr_name
    FROM ticket_history h
    JOIN customer_sales_representatives csr ON h.csr_id = csr.csr_id
    WHERE h.ticket_id = ?
    ORDER BY h.created_at DESC
");
$history_query->execute([$ticket_id]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>View Ticket #<?= $ticket_id ?> | CSR Dashboard</title>

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
</head>
<body>

<?php include '../components/csr_header.php'; ?>

<section class="ticket-section mt-5">
    <div class="container">
        <div class="row">
            <!-- Main Ticket Content -->
            <div class="col-lg-8">
                <div class="content-card mb-4">
                    <!-- Ticket Header -->
                    <div class="ticket-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h2>Ticket #<?= $ticket_id ?></h2>
                            <span class="status-badge <?= getStatusClass($ticket['status']) ?>">
                                <?= ucfirst($ticket['status']) ?>
                            </span>
                        </div>
                        <h4 class="ticket-subject"><?= htmlspecialchars($ticket['subject']) ?></h4>
                        <div class="ticket-meta">
                            <span><i class="fas fa-calendar-alt me-1"></i> <?= date('M d, Y H:i', strtotime($ticket['created_at'])) ?></span>
                            <span><i class="fas fa-tag me-1"></i> <?= ucfirst($ticket['priority']) ?> Priority</span>
                        </div>
                    </div>
                    
                    <!-- Ticket Description -->
                    <div class="ticket-description">
                        <h5>Description</h5>
                        <div class="description-content">
                            <?= nl2br(htmlspecialchars($ticket['description'])) ?>
                        </div>
                    </div>
                    
                    <!-- Responses -->
                    <div class="ticket-responses mt-4">
                        <h5>Responses</h5>
                        
                        <?php if($responses_query->rowCount() > 0): ?>
                            <div class="responses-list">
                                <?php while($response = $responses_query->fetch(PDO::FETCH_ASSOC)): ?>
                                    <div class="response-item <?= $response['type'] == 'note' ? 'note' : '' ?>">
                                        <div class="response-header">
                                            <div>
                                                <span class="response-author"><?= htmlspecialchars($response['csr_name']) ?></span>
                                                <?php if($response['type'] == 'note'): ?>
                                                    <span class="response-type">Internal Note</span>
                                                <?php endif; ?>
                                            </div>
                                            <span class="response-time">
                                                <?= date('M d, Y H:i', strtotime($response['created_at'])) ?>
                                            </span>
                                        </div>
                                        <div class="response-content">
                                            <?= nl2br(htmlspecialchars($response['response'])) ?>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">No responses yet.</div>
                        <?php endif; ?>
                        
                        <!-- Add Response Form -->
                        <div class="response-form mt-4">
                            <h5>Add Response</h5>
                            <form action="" method="POST">
                                <div class="mb-3">
                                    <textarea name="response" class="form-control" rows="5" placeholder="Type your response here..." required></textarea>
                                </div>
                                <div class="response-actions">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="response_type" id="typeReply" value="reply" checked>
                                        <label class="form-check-label" for="typeReply">Reply (visible to user)</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="response_type" id="typeNote" value="note">
                                        <label class="form-check-label" for="typeNote">Internal Note</label>
                                    </div>
                                    <button type="submit" name="add_response" class="btn btn-primary">
                                        Submit Response
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- User Info -->
                <div class="content-card mb-4">
                    <h5>User Information</h5>
                    <div class="user-info">
                        <div class="info-item">
                            <i class="fas fa-user"></i>
                            <span><?= htmlspecialchars($ticket['user_name']) ?></span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-envelope"></i>
                            <span><?= htmlspecialchars($ticket['user_email']) ?></span>
                        </div>
                    </div>
                    <div class="mt-3">
                        <a href="user_search.php?search=<?= urlencode($ticket['user_email']) ?>" class="btn btn-outline-primary btn-sm w-100">
                            <i class="fas fa-search me-1"></i> View User Details
                        </a>
                    </div>
                </div>
                
                <!-- Ticket Actions -->
                <div class="content-card mb-4">
                    <h5>Ticket Actions</h5>
                    <form action="" method="POST" class="mb-3">
                        <div class="mb-3">
                            <label class="form-label">Update Status</label>
                            <select name="status" class="form-select">
                                <option value="pending" <?= $ticket['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="in_progress" <?= $ticket['status'] == 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                <option value="resolved" <?= $ticket['status'] == 'resolved' ? 'selected' : '' ?>>Resolved</option>
                                <option value="closed" <?= $ticket['status'] == 'closed' ? 'selected' : '' ?>>Closed</option>
                            </select>
                        </div>
                        <button type="submit" name="update_status" class="btn btn-primary w-100">
                            <i class="fas fa-save me-1"></i> Update Status
                        </button>
                    </form>
                    <div class="action-buttons">
                        <a href="tickets.php" class="btn btn-outline-secondary btn-sm w-100 mb-2">
                            <i class="fas fa-arrow-left me-1"></i> Back to Tickets
                        </a>
                    </div>
                </div>
                
                <!-- Ticket History -->
                <div class="content-card">
                    <h5>Ticket History</h5>
                    <?php if($history_query->rowCount() > 0): ?>
                        <div class="history-timeline">
                            <?php while($history = $history_query->fetch(PDO::FETCH_ASSOC)): ?>
                                <div class="history-item">
                                    <div class="history-icon <?= getStatusClass($history['new_status']) ?>">
                                        <i class="fas fa-circle"></i>
                                    </div>
                                    <div class="history-content">
                                        <div class="history-time">
                                            <?= date('M d, Y H:i', strtotime($history['created_at'])) ?>
                                        </div>
                                        <div class="history-text">
                                            Status changed from <span class="status-text <?= getStatusClass($history['old_status']) ?>"><?= ucfirst($history['old_status']) ?></span>
                                            to <span class="status-text <?= getStatusClass($history['new_status']) ?>"><?= ucfirst($history['new_status']) ?></span>
                                            by <?= htmlspecialchars($history['csr_name']) ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">No status changes recorded.</div>
                    <?php endif; ?>
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
    border-radius: 20px;
    padding: 1.5rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.05);
}

.ticket-header {
    margin-bottom: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e9ecef;
}

.ticket-subject {
    margin: 1rem 0 0.5rem;
}

.ticket-meta {
    display: flex;
    gap: 1rem;
    color: #6c757d;
    font-size: 0.9rem;
}

.status-badge {
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.875rem;
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

.ticket-description {
    margin-bottom: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e9ecef;
}

.description-content {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 8px;
    margin-top: 0.5rem;
}

.responses-list {
    margin-top: 1rem;
}

.response-item {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
}

.response-item.note {
    background: rgba(243, 156, 18, 0.05);
    border-left: 4px solid var(--warning-color);
}

.response-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
}

.response-author {
    font-weight: 500;
}

.response-type {
    display: inline-block;
    background: rgba(243, 156, 18, 0.1);
    color: var(--warning-color);
    padding: 0.2rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    margin-left: 0.5rem;
}

.response-time {
    color: #6c757d;
    font-size: 0.875rem;
}

.response-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.user-info {
    margin-top: 1rem;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 0;
    border-bottom: 1px solid #e9ecef;
}

.info-item:last-child {
    border-bottom: none;
}

.info-item i {
    width: 20px;
    color: var(--primary-color);
}

.action-buttons {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.history-timeline {
    position: relative;
    margin-top: 1rem;
}

.history-item {
    display: flex;
    margin-bottom: 1rem;
}

.history-icon {
    margin-right: 0.75rem;
    height: 24px;
    width: 24px;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    padding-top: 4px;
    flex-shrink: 0;
}

.history-content {
    flex-grow: 1;
}

.history-time {
    font-size: 0.8rem;
    color: #6c757d;
    margin-bottom: 0.25rem;
}

.history-text {
    font-size: 0.9rem;
}

.status-text {
    font-weight: 500;
}

@media (max-width: 768px) {
    .content-card {
        padding: 1rem;
    }
    
    .response-actions {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
    }
}
</style>

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
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>