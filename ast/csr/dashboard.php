<?php
include '../components/connect.php';

session_start();

if(!isset($_SESSION['csr_id'])) {
   header('location:index.php');
   exit();
}

$csr_id = $_SESSION['csr_id'];

// Get overall ticket statistics
$ticket_stats = $conn->prepare("
    SELECT 
        COUNT(*) as total_tickets,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_tickets,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as active_tickets,
        SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_tickets
    FROM customer_support_tickets 
    WHERE csr_id = ? OR csr_id IS NULL
");
$ticket_stats->execute([$csr_id]);
$stats = $ticket_stats->fetch(PDO::FETCH_ASSOC);

// Get recent tickets
$recent_tickets = $conn->prepare("
    SELECT t.*, 
           u.name as user_name 
    FROM customer_support_tickets t
    LEFT JOIN users u ON t.user_id = u.id
    WHERE t.csr_id = ? OR t.csr_id IS NULL
    ORDER BY t.created_at DESC 
    LIMIT 5
");
$recent_tickets->execute([$csr_id]);

// Get resolved tickets for today
$today_tickets = $conn->prepare("
    SELECT COUNT(*) as resolved_today 
    FROM customer_support_tickets 
    WHERE (csr_id = ? OR csr_id IS NULL)
    AND status = 'resolved' 
    AND DATE(updated_at) = CURDATE()
");
$today_tickets->execute([$csr_id]);
$today_stats = $today_tickets->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Keens | CSR Dashboard</title>

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
</head>
<body>

<?php include '../components/csr_header.php'; ?>

<section class="dashboard">
    <div class="container-fluid">
        <!-- Welcome Banner -->
        <div class="welcome-banner mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="fw-bold mb-2">Welcome back, <?= $_SESSION['csr_name'] ?? 'CSR'; ?>! 👋</h2>
                    <p class="text-muted mb-0">Here's your support ticket overview for today.</p>
                </div>
                <a href="profile.php" class="btn btn-glass">
                    <i class="fas fa-user-edit me-2"></i>Update Profile
                </a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-4 mb-4">
            <!-- Total Tickets -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="stat-card info">
                    <div class="card-content">
                        <div class="icon-wrapper">
                            <i class="fas fa-ticket-alt"></i>
                        </div>
                        <div class="stat-details">
                            <p class="stat-label">Total Tickets</p>
                            <h3 class="stat-value"><?= $stats['total_tickets'] ?? 0 ?></h3>
                        </div>
                        <a href="tickets.php" class="stat-link">
                            View All Tickets
                            <i class="fas fa-arrow-right ms-2"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Pending Tickets -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="stat-card pending">
                    <div class="card-content">
                        <div class="icon-wrapper">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-details">
                            <p class="stat-label">Pending Tickets</p>
                            <h3 class="stat-value"><?= $stats['pending_tickets'] ?? 0 ?></h3>
                        </div>
                        <a href="pending_tickets.php" class="stat-link">
                            View Pending
                            <i class="fas fa-arrow-right ms-2"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Active Tickets -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="stat-card active">
                    <div class="card-content">
                        <div class="icon-wrapper">
                            <i class="fas fa-spinner"></i>
                        </div>
                        <div class="stat-details">
                            <p class="stat-label">Active Tickets</p>
                            <h3 class="stat-value"><?= $stats['active_tickets'] ?? 0 ?></h3>
                        </div>
                        <a href="active_tickets.php" class="stat-link">
                            View Active
                            <i class="fas fa-arrow-right ms-2"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Resolved Today -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="stat-card completed">
                    <div class="card-content">
                        <div class="icon-wrapper">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-details">
                            <p class="stat-label">Resolved Today</p>
                            <h3 class="stat-value"><?= $today_stats['resolved_today'] ?? 0 ?></h3>
                        </div>
                        <a href="resolved_tickets.php" class="stat-link">
                            View Resolved
                            <i class="fas fa-arrow-right ms-2"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recent Tickets Table -->
            <div class="col-lg-8 mb-4">
                <div class="content-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-ticket-alt me-2"></i>Recent Tickets</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Subject</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($recent_tickets->rowCount() > 0): ?>
                                    <?php while($ticket = $recent_tickets->fetch(PDO::FETCH_ASSOC)): ?>
                                        <tr>
                                            <td>#<?= $ticket['ticket_id'] ?></td>
                                            <td><?= htmlspecialchars($ticket['user_name']) ?></td>
                                            <td><?= htmlspecialchars($ticket['subject']) ?></td>
                                            <td>
                                                <span class="badge bg-<?php
                                                    switch($ticket['status']) {
                                                        case 'pending': echo 'warning'; break;
                                                        case 'in_progress': echo 'info'; break;
                                                        case 'resolved': echo 'success'; break;
                                                        case 'closed': echo 'secondary'; break;
                                                    }
                                                ?>">
                                                    <?= ucfirst($ticket['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="view_ticket.php?id=<?= $ticket['ticket_id'] ?>" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class="fas fa-eye me-1"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4">No recent tickets</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="col-lg-4 mb-4">
                <div class="content-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                    </div>
                    <div class="quick-actions">
                        <a href="new_ticket.php" class="quick-action-btn">
                            <i class="fas fa-plus-circle"></i>
                            <span>New Ticket</span>
                        </a>
                        <a href="user_search.php" class="quick-action-btn">
                            <i class="fas fa-search"></i>
                            <span>Search User</span>
                        </a>
                        <a href="reports.php" class="quick-action-btn">
                            <i class="fas fa-chart-bar"></i>
                            <span>View Reports</span>
                        </a>
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
    --info-color: #3498db;
    --pending-color: #e67e22;
}

/* Welcome Banner */
.welcome-banner {
    background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
    padding: 2rem;
    border-radius: 20px;
    color: white;
    box-shadow: 0 10px 30px rgba(67, 97, 238, 0.15);
}

.btn-glass {
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 12px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-glass:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: translateY(-2px);
    color: white;
}

/* Stat Cards */
.stat-card {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    transition: all 0.3s ease;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
}

.card-content {
    padding: 1.5rem;
    position: relative;
}

.icon-wrapper {
    width: 50px;
    height: 50px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-bottom: 1rem;
}

.stat-details {
    margin-bottom: 1rem;
}

.stat-label {
    color: #6c757d;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
}

.stat-value {
    font-size: 1.8rem;
    font-weight: 700;
    margin: 0;
}

.stat-link {
    display: inline-flex;
    align-items: center;
    text-decoration: none;
    font-weight: 500;
    font-size: 0.9rem;
    transition: transform 0.3s ease;
}

.stat-link:hover {
    transform: translateX(5px);
}

/* Content Cards */
.content-card {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
    margin-bottom: 2rem;
}

.card-header {
    padding: 1.5rem;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

/* Quick Actions */
.quick-actions {
    padding: 1.5rem;
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

.quick-action-btn {
    background: #f8f9fa;
    border-radius: 15px;
    padding: 1.5rem;
    text-align: center;
    text-decoration: none;
    color: #2b3452;
    transition: all 0.3s ease;
}

.quick-action-btn:hover {
    background: var(--primary-color);
    color: white;
    transform: translateY(-3px);
}

.quick-action-btn i {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
    display: block;
}

/* Card Variants */
.stat-card.pending .icon-wrapper {
    background: rgba(230, 126, 34, 0.1);
    color: var(--pending-color);
}

.stat-card.pending .stat-link {
    color: var(--pending-color);
}

.stat-card.active .icon-wrapper {
    background: rgba(52, 152, 219, 0.1);
    color: var(--info-color);
}

.stat-card.active .stat-link {
    color: var(--info-color);
}

.stat-card.completed .icon-wrapper {
    background: rgba(46, 204, 113, 0.1);
    color: var(--success-color);
}

.stat-card.completed .stat-link {
    color: var(--success-color);
}

.stat-card.info .icon-wrapper {
    background: rgba(67, 97, 238, 0.1);
    color: var(--primary-color);
}

.stat-card.info .stat-link {
    color: var(--primary-color);
}

/* Table Styling */
.table {
    margin: 0;
}

.table th {
    font-weight: 600;
    color: #2b3452;
    border-bottom-width: 1px;
}

.table td {
    vertical-align: middle;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .welcome-banner {
        padding: 1.5rem;
    }

    .welcome-banner h2 {
        font-size: 1.5rem;
    }

    .quick-actions {
        grid-template-columns: 1fr;
    }

    .stat-value {
        font-size: 1.5rem;
    }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>