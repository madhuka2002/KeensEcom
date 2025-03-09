<?php
include '../components/connect.php';

session_start();

if(!isset($_SESSION['csr_id'])) {
   header('location:index.php');
   exit();
}

$csr_id = $_SESSION['csr_id'];

// Get pending tickets with user info
$pending_tickets = $conn->prepare("
    SELECT t.*, 
           u.name as user_name,
           u.email as user_email,
           TIMESTAMPDIFF(HOUR, t.created_at, NOW()) as waiting_time
    FROM customer_support_tickets t
    JOIN users u ON t.user_id = u.id
    WHERE t.csr_id = ? 
    AND t.status = 'pending'
    ORDER BY t.priority DESC, t.created_at ASC
");
$pending_tickets->execute([$csr_id]);

// Get pending tickets statistics
$stats_query = $conn->prepare("
    SELECT 
        COUNT(*) as total_pending,
        SUM(CASE WHEN priority = 'high' THEN 1 ELSE 0 END) as high_count,
        SUM(CASE WHEN priority = 'medium' THEN 1 ELSE 0 END) as medium_count,
        SUM(CASE WHEN priority = 'low' THEN 1 ELSE 0 END) as low_count,
        SUM(CASE WHEN TIMESTAMPDIFF(HOUR, created_at, NOW()) > 24 THEN 1 ELSE 0 END) as overdue_count
    FROM customer_support_tickets
    WHERE csr_id = ? AND status = 'pending'
");
$stats_query->execute([$csr_id]);
$stats = $stats_query->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Pending Tickets | CSR Dashboard</title>

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
</head>
<body>

<?php include '../components/csr_header.php'; ?>

<section class="pending-tickets-section mt-5">
    <div class="container">
        <div class="content-card">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="card-title mb-0">Pending Tickets</h2>
                    <p class="text-muted mb-0">Manage your pending support tickets</p>
                </div>
                <a href="new_ticket.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>New Ticket
                </a>
            </div>

            <!-- Statistics Cards -->
            <div class="row g-4 mb-4">
                <div class="col">
                    <div class="stats-card">
                        <div class="stats-icon warning">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stats-info">
                            <h3><?= $stats['total_pending'] ?></h3>
                            <p>Total Pending</p>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="stats-card">
                        <div class="stats-icon danger">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                        <div class="stats-info">
                            <h3><?= $stats['high_count'] ?></h3>
                            <p>High Priority</p>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="stats-card">
                        <div class="stats-icon info">
                            <i class="fas fa-hourglass-half"></i>
                        </div>
                        <div class="stats-info">
                            <h3><?= $stats['overdue_count'] ?></h3>
                            <p>Overdue (>24h)</p>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Tickets Table -->
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Priority</th>
                            <th>Ticket ID</th>
                            <th>User</th>
                            <th>Subject</th>
                            <th>Waiting Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($pending_tickets->rowCount() > 0): ?>
                            <?php while($ticket = $pending_tickets->fetch(PDO::FETCH_ASSOC)): ?>
                                <tr>
                                    <td>
                                        <span class="priority-badge <?= getPriorityClass($ticket['priority']) ?>">
                                            <?= ucfirst($ticket['priority']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark">
                                            #<?= $ticket['ticket_id'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="fw-medium"><?= htmlspecialchars($ticket['user_name']) ?></span>
                                            <small class="text-muted"><?= htmlspecialchars($ticket['user_email']) ?></small>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($ticket['subject']) ?></td>
                                    <td>
                                        <span class="badge <?= $ticket['waiting_time'] > 24 ? 'bg-danger' : 'bg-warning' ?>">
                                            <?= formatWaitingTime($ticket['waiting_time']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="view_ticket.php?id=<?= $ticket['ticket_id'] ?>" 
                                               class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye me-1"></i>View
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-sm btn-success"
                                                    onclick="startProcessing(<?= $ticket['ticket_id'] ?>)">
                                                <i class="fas fa-play me-1"></i>Start
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <div class="empty-state">
                                        <i class="fas fa-check-circle fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">No pending tickets found</p>
                                        <a href="new_ticket.php" class="btn btn-primary mt-2">
                                            Create New Ticket
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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
    padding: 2rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.05);
}

.stats-card {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

.stats-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
}

.stats-icon.warning {
    background: rgba(243, 156, 18, 0.1);
    color: var(--warning-color);
}

.stats-icon.danger {
    background: rgba(231, 76, 60, 0.1);
    color: var(--danger-color);
}

.stats-icon.info {
    background: rgba(52, 152, 219, 0.1);
    color: var(--info-color);
}

.priority-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 500;
}

.priority-badge.high {
    background: rgba(231, 76, 60, 0.1);
    color: var(--danger-color);
}

.priority-badge.medium {
    background: rgba(243, 156, 18, 0.1);
    color: var(--warning-color);
}

.priority-badge.low {
    background: rgba(52, 152, 219, 0.1);
    color: var(--info-color);
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
}

.empty-state {
    padding: 2rem;
    text-align: center;
}

@media (max-width: 768px) {
    .content-card {
        padding: 1rem;
    }
    
    .stats-card {
        flex-direction: column;
        text-align: center;
    }
    
    .action-buttons {
        flex-direction: column;
    }
}
</style>

<script>
<?php
function getPriorityClass($priority) {
    switch($priority) {
        case 'high': return 'high';
        case 'medium': return 'medium';
        case 'low': return 'low';
        default: return '';
    }
}

function formatWaitingTime($hours) {
    if ($hours < 1) {
        return 'Just now';
    } elseif ($hours < 24) {
        return $hours . 'h';
    } else {
        $days = floor($hours / 24);
        return $days . 'd ' . ($hours % 24) . 'h';
    }
}
?>

function startProcessing(ticketId) {
    if(confirm('Start processing this ticket?')) {
        fetch('update_ticket_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                ticket_id: ticketId,
                status: 'in_progress'
            })
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                location.reload();
            } else {
                alert('Error updating ticket status');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating ticket status');
        });
    }
}
</script>

</body>
</html>