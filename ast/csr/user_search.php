<?php
include '../components/connect.php';

session_start();

if(!isset($_SESSION['csr_id'])) {
   header('location:index.php');
   exit();
}

$csr_id = $_SESSION['csr_id'];

// Get search parameter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Initialize variables
$user = null;
$tickets = null;
$orders = null;

// If search is provided, search for user
if(!empty($search)) {
    // Search for user
    $user_query = $conn->prepare("
        SELECT * FROM users 
        WHERE email LIKE ? OR name LIKE ? OR id = ?
    ");
    $user_query->execute(["%$search%", "%$search%", $search]);
    
    if($user_query->rowCount() > 0) {
        $user = $user_query->fetch(PDO::FETCH_ASSOC);
        
        // Get user tickets
        $tickets_query = $conn->prepare("
            SELECT t.*, 
                  TIMESTAMPDIFF(HOUR, t.created_at, NOW()) as age
            FROM customer_support_tickets t
            WHERE t.user_id = ?
            ORDER BY t.created_at DESC
        ");
        $tickets_query->execute([$user['id']]);
        $tickets = $tickets_query->fetchAll(PDO::FETCH_ASSOC);
        
        // Get user orders
        $orders_query = $conn->prepare("
            SELECT * FROM orders
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT 5
        ");
        $orders_query->execute([$user['id']]);
        $orders = $orders_query->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Keens | User Search</title>

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
</head>
<body>

<?php include '../components/csr_header.php'; ?>

<section class="user-search-section mt-5">
    <div class="container">
        <div class="content-card mb-4">
            <div class="card-header mb-4">
                <div class="header-icon">
                    <i class="fas fa-search"></i>
                </div>
                <h2 class="header-title">User Search</h2>
                <p class="text-muted">Search for users by name or email</p>
            </div>

            <!-- Search Form -->
            <form action="" method="GET" class="mb-4">
                <div class="row g-3">
                    <div class="col-md-10">
                        <div class="input-group">
                            <input type="text" 
                                   name="search" 
                                   class="form-control form-control-lg" 
                                   placeholder="Search by name, email, or ID..."
                                   value="<?= htmlspecialchars($search) ?>">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i>Search
                            </button>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <a href="new_ticket.php" class="btn btn-success w-100 h-100 d-flex align-items-center justify-content-center">
                            <i class="fas fa-plus-circle me-2"></i>New Ticket
                        </a>
                    </div>
                </div>
            </form>

            <?php if(!empty($search) && $user === null): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    No users found matching your search criteria.
                </div>
            <?php endif; ?>
        </div>

        <?php if($user): ?>
            <div class="row">
                <!-- User Profile Card -->
                <div class="col-lg-4 mb-4">
                    <div class="content-card h-100">
                        <div class="user-profile-header">
                            <div class="user-avatar">
                                <?= strtoupper(substr($user['name'], 0, 1)) ?>
                            </div>
                            <h3 class="user-name"><?= htmlspecialchars($user['name']) ?></h3>
                            <p class="user-id">User ID: #<?= $user['id'] ?></p>
                        </div>
                        <div class="user-details">
                            <div class="user-info-item">
                                <i class="fas fa-envelope"></i>
                                <span><?= htmlspecialchars($user['email']) ?></span>
                            </div>
                            <?php if(isset($user['phone']) && !empty($user['phone'])): ?>
                            <div class="user-info-item">
                                <i class="fas fa-phone"></i>
                                <span><?= htmlspecialchars($user['phone']) ?></span>
                            </div>
                            <?php endif; ?>
                            <!-- <div class="user-info-item">
                                <i class="fas fa-calendar-alt"></i>
                                <span>Joined: <?= date('M d, Y', strtotime($user['created_at'])) ?></span>
                            </div> -->
                        </div>
                        <div class="user-actions">
                            <a href="new_ticket.php?user_id=<?= $user['id'] ?>" class="btn btn-primary w-100 mb-2">
                                <i class="fas fa-plus-circle me-2"></i>Create New Ticket
                            </a>
                            <button type="button" class="btn btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#sendEmailModal">
                                <i class="fas fa-envelope me-2"></i>Send Email
                            </button>
                        </div>
                    </div>
                </div>

                <!-- User Activity -->
                <div class="col-lg-8">
                    <!-- Support Tickets -->
                    <div class="content-card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4 class="mb-0"><i class="fas fa-ticket-alt me-2"></i>Support Tickets</h4>
                            <?php if(is_array($tickets) && count($tickets) > 0): ?>
                                <span class="badge bg-primary"><?= count($tickets) ?> tickets</span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body p-0">
                            <?php if(is_array($tickets) && count($tickets) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Subject</th>
                                                <th>Status</th>
                                                <th>Created</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($tickets as $ticket): ?>
                                                <tr>
                                                    <td>#<?= $ticket['ticket_id'] ?></td>
                                                    <td><?= htmlspecialchars($ticket['subject']) ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php
                                                            switch($ticket['status']) {
                                                                case 'pending': echo 'warning'; break;
                                                                case 'in_progress': echo 'info'; break;
                                                                case 'resolved': echo 'success'; break;
                                                                case 'closed': echo 'secondary'; break;
                                                                default: echo 'primary';
                                                            }
                                                        ?>">
                                                            <?= ucfirst($ticket['status']) ?>
                                                        </span>
                                                    </td>
                                                    <td><?= formatTimeAgo($ticket['age']) ?></td>
                                                    <td>
                                                        <a href="view_ticket.php?id=<?= $ticket['ticket_id'] ?>" class="btn btn-sm btn-primary">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="p-4 text-center">
                                    <i class="fas fa-ticket-alt fa-3x text-muted mb-3"></i>
                                    <p class="text-muted mb-0">This user has no support tickets yet.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Recent Orders -->
                    <div class="content-card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4 class="mb-0"><i class="fas fa-shopping-cart me-2"></i>Recent Orders</h4>
                            <?php if(is_array($orders) && count($orders) > 0): ?>
                                <a href="user_orders.php?user_id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-primary">
                                    View All
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="card-body p-0">
                            <?php if(is_array($orders) && count($orders) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>Order ID</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                                <th>Date</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($orders as $order): ?>
                                                <tr>
                                                    <td>#<?= $order['id'] ?></td>
                                                    <td>$<?= number_format($order['total_price'], 2) ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php
                                                            switch($order['payment_status'] ?? 'pending') {
                                                                case 'completed': echo 'success'; break;
                                                                case 'pending': echo 'warning'; break;
                                                                case 'cancelled': echo 'danger'; break;
                                                                default: echo 'info';
                                                            }
                                                        ?>">
                                                            <?= ucfirst($order['payment_status'] ?? 'pending') ?>
                                                        </span>
                                                    </td>
                                                    <td><?= date('M d, Y', strtotime($order['created_at'])) ?></td>
                                                    <td>
                                                        <a href="view_order.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-primary">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="p-4 text-center">
                                    <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                                    <p class="text-muted mb-0">This user has no orders yet.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Email Modal -->
            <div class="modal fade" id="sendEmailModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Send Email to <?= htmlspecialchars($user['name']) ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form action="send_email.php" method="POST">
                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                <input type="hidden" name="email" value="<?= htmlspecialchars($user['email']) ?>">
                                
                                <div class="mb-3">
                                    <label class="form-label">Subject</label>
                                    <input type="text" name="subject" class="form-control" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Message</label>
                                    <textarea name="message" class="form-control" rows="5" required></textarea>
                                </div>
                                
                                <div class="text-end">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary">Send Email</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
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
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,0.05);
}

.card-header {
    padding: 1.5rem;
    border-bottom: 1px solid rgba(0,0,0,0.05);
    text-align: center;
}

.card-body {
    padding: 1.5rem;
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

/* User Profile Styling */
.user-profile-header {
    background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
    color: white;
    padding: 2rem;
    text-align: center;
    border-radius: 20px 20px 0 0;
}

.user-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background-color: rgba(255, 255, 255, 0.2);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    font-weight: 600;
    margin: 0 auto 1rem;
}

.user-name {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.user-id {
    opacity: 0.8;
    margin-bottom: 0;
}

.user-details {
    padding: 1.5rem;
}

.user-info-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f0f0f0;
}

.user-info-item:last-child {
    border-bottom: none;
}

.user-info-item i {
    color: var(--primary-color);
    width: 20px;
    text-align: center;
}

.user-actions {
    padding: 0 1.5rem 1.5rem;
}

@media (max-width: 768px) {
    .content-card {
        margin-bottom: 1.5rem;
    }
}

/* Helper function to format time */
<?php
function formatTimeAgo($hours) {
    if ($hours < 1) {
        return 'Just now';
    } elseif ($hours < 24) {
        return $hours . ' hour' . ($hours != 1 ? 's' : '') . ' ago';
    } else {
        $days = floor($hours / 24);
        return $days . ' day' . ($days != 1 ? 's' : '') . ' ago';
    }
}
?>
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>