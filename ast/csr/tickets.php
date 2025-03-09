<?php
include '../components/connect.php';

session_start();

if(!isset($_SESSION['csr_id'])) {
   header('location:index.php');
   exit();
}

$csr_id = $_SESSION['csr_id'];

// Handle status filter
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Base query
$query = "
    SELECT t.*, 
           u.name as user_name,
           u.email as user_email
    FROM customer_support_tickets t
    JOIN users u ON t.user_id = u.id 
    WHERE (t.csr_id = ? OR t.csr_id IS NULL)
";

// Add status filter
if($status_filter != 'all') {
    $query .= " AND t.status = ?";
}

// Add search filter
if($search) {
    $query .= " AND (t.subject LIKE ? OR t.description LIKE ? OR u.name LIKE ?)";
}

$query .= " ORDER BY t.created_at DESC";

// Prepare and execute query
$tickets = $conn->prepare($query);

if($status_filter != 'all' && $search) {
    $tickets->execute([$csr_id, $status_filter, "%$search%", "%$search%", "%$search%"]);
} elseif($status_filter != 'all') {
    $tickets->execute([$csr_id, $status_filter]);
} elseif($search) {
    $tickets->execute([$csr_id, "%$search%", "%$search%", "%$search%"]);
} else {
    $tickets->execute([$csr_id]);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Keens | Support Tickets</title>

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
</head>
<body>

<?php include '../components/csr_header.php'; ?>

<section class="tickets-section mt-5">
    <div class="container">
        <div class="content-card">
            <div class="card-header mb-4">
                <div class="header-icon">
                    <i class="fas fa-ticket-alt"></i>
                </div>
                <h2 class="header-title">Support Tickets</h2>
                <p class="text-muted">Manage user support tickets</p>
            </div>

            <!-- Filters and Search -->
            <div class="filters mb-4">
                <form action="" method="GET" class="row g-3">
                    <div class="col-md-4">
                        <select name="status" class="form-select" onchange="this.form.submit()">
                            <option value="all" <?= $status_filter == 'all' ? 'selected' : '' ?>>All Tickets</option>
                            <option value="pending" <?= $status_filter == 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="in_progress" <?= $status_filter == 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                            <option value="resolved" <?= $status_filter == 'resolved' ? 'selected' : '' ?>>Resolved</option>
                            <option value="closed" <?= $status_filter == 'closed' ? 'selected' : '' ?>>Closed</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Search tickets..." value="<?= htmlspecialchars($search) ?>">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <a href="new_ticket.php" class="btn btn-primary w-100">
                            <i class="fas fa-plus me-2"></i>New Ticket
                        </a>
                    </div>
                </form>
            </div>

            <!-- Tickets Table -->
            <div class="table-responsive">
                <table class="table modern-table">
                    <thead>
                        <tr>
                            <th>Ticket ID</th>
                            <th>User</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($tickets->rowCount() > 0): ?>
                            <?php while($ticket = $tickets->fetch(PDO::FETCH_ASSOC)): ?>
                                <tr>
                                    <td><span class="id-badge">#<?= $ticket['ticket_id'] ?></span></td>
                                    <td>
                                        <div class="user-info">
                                            <i class="fas fa-user user-icon"></i>
                                            <div>
                                                <span class="d-block"><?= htmlspecialchars($ticket['user_name']) ?></span>
                                                <small class="text-muted"><?= htmlspecialchars($ticket['user_email']) ?></small>
                                            </div>
                                        </div>
                                    </td>
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
                                    <td><?= date('M d, Y', strtotime($ticket['created_at'])) ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            
                                            <a href="respond_ticket.php?id=<?= $ticket['ticket_id'] ?>" 
                                               class="btn btn-sm btn-success"
                                               title="Respond">
                                                <i class="fas fa-reply"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <i class="fas fa-ticket-alt fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No tickets found</p>
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
}

.content-card {
    background: white;
    border-radius: 20px;
    padding: 2rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.05);
}

.card-header {
    text-align: center;
    margin-bottom: 2rem;
}

.header-icon {
    width: 60px;
    height: 60px;
    border-radius: 15px;
    background: linear-gradient(135deg, rgba(67, 97, 238, 0.1), rgba(72, 149, 239, 0.1));
    color: var(--primary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin: 0 auto 1rem;
}

.modern-table {
    width: 100%;
    border-collapse: collapse;
}

.modern-table th {
    padding: 1rem;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.8rem;
    letter-spacing: 0.5px;
    background-color: #f8f9fa;
    border-bottom: 2px solid #e9ecef;
}

.modern-table td {
    padding: 1rem;
    vertical-align: middle;
    border-bottom: 1px solid #e9ecef;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
}

.id-badge {
    background: #e8f3ff;
    color: var(--primary-color);
    padding: 0.3rem 0.6rem;
    border-radius: 6px;
    font-size: 0.9rem;
}

@media (max-width: 768px) {
    .content-card {
        padding: 1rem;
    }
    
    .filters form {
        flex-direction: column;
    }
    
    .action-buttons {
        flex-direction: column;
    }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>