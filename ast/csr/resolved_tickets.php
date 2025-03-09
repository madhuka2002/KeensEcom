<?php
include '../components/connect.php';

session_start();

if(!isset($_SESSION['csr_id'])) {
   header('location:index.php');
   exit();
}

$csr_id = $_SESSION['csr_id'];

// Date filter
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Get resolved tickets
$resolved_tickets = $conn->prepare("
    SELECT t.*, 
           u.name as user_name,
           u.email as user_email,
           TIMESTAMPDIFF(HOUR, t.created_at, t.updated_at) as resolution_time
    FROM customer_support_tickets t
    JOIN users u ON t.user_id = u.id
    WHERE t.csr_id = ? 
    AND t.status = 'resolved'
    AND DATE(t.updated_at) BETWEEN ? AND ?
    ORDER BY t.updated_at DESC
");
$resolved_tickets->execute([$csr_id, $start_date, $end_date]);

// Get resolution statistics
$stats_query = $conn->prepare("
    SELECT 
        COUNT(*) as total_resolved,
        AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as avg_resolution_time,
        COUNT(CASE WHEN TIMESTAMPDIFF(HOUR, created_at, updated_at) <= 24 THEN 1 END) as resolved_within_24h
    FROM customer_support_tickets
    WHERE csr_id = ? 
    AND status = 'resolved'
    AND DATE(updated_at) BETWEEN ? AND ?
");
$stats_query->execute([$csr_id, $start_date, $end_date]);
$stats = $stats_query->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Resolved Tickets | CSR Dashboard</title>

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
</head>
<body>

<?php include '../components/csr_header.php'; ?>

<section class="resolved-tickets-section mt-5">
    <div class="container">
        <div class="content-card">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="card-title mb-0">Resolved Tickets</h2>
                <!-- Date Filter -->
                <form action="" method="GET" class="d-flex gap-2">
                    <input type="date" name="start_date" class="form-control" value="<?= $start_date ?>">
                    <input type="date" name="end_date" class="form-control" value="<?= $end_date ?>">
                    <button type="submit" class="btn btn-primary">Filter</button>
                </form>
            </div>

            <!-- Statistics Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="stats-card">
                        <div class="stats-icon success">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stats-info">
                            <h3><?= $stats['total_resolved'] ?></h3>
                            <p>Total Resolved</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card">
                        <div class="stats-icon info">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stats-info">
                            <h3><?= round($stats['avg_resolution_time'], 1) ?>h</h3>
                            <p>Avg Resolution Time</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card">
                        <div class="stats-icon primary">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <div class="stats-info">
                            <h3><?= $stats['resolved_within_24h'] ?></h3>
                            <p>Resolved within 24h</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tickets Table -->
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Ticket ID</th>
                            <th>User</th>
                            <th>Subject</th>
                            <th>Resolution Time</th>
                            <th>Resolved Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($resolved_tickets->rowCount() > 0): ?>
                            <?php while($ticket = $resolved_tickets->fetch(PDO::FETCH_ASSOC)): ?>
                                <tr>
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
                                        <span class="badge <?= $ticket['resolution_time'] <= 24 ? 'bg-success' : 'bg-warning' ?>">
                                            <?= $ticket['resolution_time'] ?>h
                                        </span>
                                    </td>
                                    <td><?= date('M d, Y H:i', strtotime($ticket['updated_at'])) ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="view_ticket.php?id=<?= $ticket['ticket_id'] ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-info"
                                                    onclick="showResolutionDetails(<?= $ticket['ticket_id'] ?>)">
                                                <i class="fas fa-info-circle"></i>
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
                                        <p class="text-muted">No resolved tickets found for this period</p>
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

<!-- Resolution Details Modal -->
<div class="modal fade" id="resolutionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Resolution Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Content will be loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

<style>
:root {
    --primary-color: #4361ee;
    --secondary-color: #3f37c9;
    --accent-color: #4895ef;
    --success-color: #2ecc71;
    --warning-color: #f39c12;
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

.stats-icon.success {
    background: rgba(46, 204, 113, 0.1);
    color: var(--success-color);
}

.stats-icon.info {
    background: rgba(52, 152, 219, 0.1);
    color: var(--info-color);
}

.stats-icon.primary {
    background: rgba(67, 97, 238, 0.1);
    color: var(--primary-color);
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
}
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function showResolutionDetails(ticketId) {
    const modal = new bootstrap.Modal(document.getElementById('resolutionModal'));
    const modalBody = document.querySelector('#resolutionModal .modal-body');
    
    // Load resolution details via AJAX
    fetch(`get_resolution_details.php?ticket_id=${ticketId}`)
        .then(response => response.json())
        .then(data => {
            modalBody.innerHTML = `
                <div class="resolution-details">
                    <p><strong>Resolution Time:</strong> ${data.resolution_time}h</p>
                    <p><strong>Resolution Notes:</strong> ${data.notes}</p>
                    <p><strong>Resolved By:</strong> ${data.resolved_by}</p>
                    <p><strong>Resolution Date:</strong> ${data.resolved_date}</p>
                </div>
            `;
            modal.show();
        })
        .catch(error => {
            console.error('Error:', error);
            modalBody.innerHTML = '<p class="text-danger">Error loading resolution details</p>';
            modal.show();
        });
}
</script>

</body>
</html>