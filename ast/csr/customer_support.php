<?php
include '../components/connect.php';

session_start();

$csr_id = $_SESSION['csr_id'];

if(!isset($csr_id)){
   header('location:index.php');
}

// Handle Adding New Support Ticket
if(isset($_POST['create_ticket'])) {
    $customer_id = $_POST['customer_id'];
    $subject = $_POST['subject'];
    $priority = $_POST['priority'];
    $description = $_POST['description'];
    
    $create_ticket = $conn->prepare("INSERT INTO customer_support_tickets 
        (customer_id, csr_id, subject, priority, description, status) 
        VALUES (?, ?, ?, ?, ?, 'open')");
    
    $create_ticket->execute([
        $customer_id, 
        $csr_id, 
        $subject, 
        $priority, 
        $description
    ]);
    
    $message[] = 'New support ticket created successfully!';
}

// Fetch Customers for Dropdown
$customers_query = $conn->prepare("SELECT customer_id, CONCAT(first_name, ' ', last_name) as full_name FROM customers");
$customers_query->execute();
$customers = $customers_query->fetchAll(PDO::FETCH_ASSOC);

// Fetch Support Tickets
$tickets_query = $conn->prepare("
    SELECT st.*, 
           CONCAT(c.first_name, ' ', c.last_name) as customer_name,
           CONCAT(csr.first_name, ' ', csr.last_name) as csr_name
    FROM customer_support_tickets st
    LEFT JOIN customers c ON st.customer_id = c.customer_id
    LEFT JOIN csrs csr ON st.csr_id = csr.csr_id
    ORDER BY st.created_at DESC
");
$tickets_query->execute();
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Customer Support Tickets</title>

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
</head>
<body>

<?php include '../components/csr_header.php'; ?>

<section class="support-tickets-section mt-5">
    <div class="container-fluid">
        <div class="row">
            <!-- Create Ticket Section -->
            <div class="col-lg-4 mb-4">
                <div class="content-card">
                    <div class="card-header text-center mb-4">
                        <div class="header-icon">
                            <i class="fas fa-ticket-alt"></i>
                        </div>
                        <h2 class="header-title">Create Support Ticket</h2>
                        <p class="text-muted">Open a new support ticket for a customer</p>
                    </div>

                    <form action="" method="post">
                        <div class="mb-3">
                            <label class="form-label">Customer</label>
                            <select name="customer_id" class="form-select" required>
                                <option value="">Select Customer</option>
                                <?php foreach($customers as $customer): ?>
                                    <option value="<?= $customer['customer_id'] ?>">
                                        <?= $customer['full_name'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Subject</label>
                            <input type="text" name="subject" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Priority</label>
                            <select name="priority" class="form-select" required>
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="4" required></textarea>
                        </div>

                        <button type="submit" name="create_ticket" class="btn btn-primary w-100">
                            <i class="fas fa-plus-circle me-2"></i>Create Ticket
                        </button>
                    </form>
                </div>
            </div>

            <!-- Support Tickets List -->
            <div class="col-lg-8">
                <div class="content-card">
                    <div class="card-header text-center mb-4">
                        <div class="header-icon">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <h2 class="header-title">Support Tickets</h2>
                        <p class="text-muted">List of all support tickets</p>
                    </div>

                    <!-- Filters -->
                    <div class="filters mb-4">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <select class="form-select" id="statusFilter">
                                    <option value="">All Statuses</option>
                                    <option value="open">Open</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="resolved">Resolved</option>
                                    <option value="closed">Closed</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <select class="form-select" id="priorityFilter">
                                    <option value="">All Priorities</option>
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button class="btn btn-primary w-100" onclick="applyFilters()">
                                    <i class="fas fa-filter me-2"></i>Apply Filters
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Tickets Table -->
                    <div class="table-responsive">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Customer</th>
                                    <th>Subject</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Created By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($tickets_query->rowCount() > 0): ?>
                                    <?php while($ticket = $tickets_query->fetch(PDO::FETCH_ASSOC)): ?>
                                        <tr>
                                            <td>
                                                <span class="id-badge">#<?= $ticket['ticket_id']; ?></span>
                                            </td>
                                            <td>
                                                <div class="user-info">
                                                    <i class="fas fa-user user-icon"></i>
                                                    <span class="user-name"><?= $ticket['customer_name']; ?></span>
                                                </div>
                                            </td>
                                            <td><?= $ticket['subject']; ?></td>
                                            <td>
                                                <span class="badge bg-<?php
                                                    switch($ticket['priority']){
                                                        case 'low': echo 'success'; break;
                                                        case 'medium': echo 'warning'; break;
                                                        case 'high': echo 'danger'; break;
                                                        case 'urgent': echo 'dark'; break;
                                                    }
                                                ?>"><?= ucfirst($ticket['priority']); ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php
                                                    switch($ticket['status']){
                                                        case 'open': echo 'primary'; break;
                                                        case 'in_progress': echo 'info'; break;
                                                        case 'resolved': echo 'success'; break;
                                                        case 'closed': echo 'secondary'; break;
                                                    }
                                                ?>"><?= ucfirst($ticket['status']); ?></span>
                                            </td>
                                            <td><?= $ticket['csr_name']; ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn btn-sm btn-primary" onclick="viewTicket(<?= $ticket['ticket_id']; ?>)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-success" onclick="updateTicket(<?= $ticket['ticket_id']; ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">No support tickets found</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
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
}

.content-card {
    background: white;
    border-radius: 20px;
    padding: 2rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.05);
    margin: 2rem auto;
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
    background-color: #f8f9fa;
    padding: 1rem;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.8rem;
    letter-spacing: 0.5px;
    border-bottom: 2px solid #e9ecef;
}

.modern-table td {
    padding: 1rem;
    vertical-align: middle;
    border-bottom: 1px solid #e9ecef;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.id-badge {
    background: #e8f3ff;
    color: var(--primary-color);
    padding: 0.3rem 0.6rem;
    border-radius: 6px;
    font-size: 0.9rem;
}

/* Responsive Design */
@media (max-width: 768px) {
    .content-card {
        margin: 1rem;
        padding: 1rem;
    }
    
    .action-buttons {
        flex-direction: column;
    }
}
</style>

<script>
function viewTicket(id) {
    // Implement view ticket details modal or page
    window.location.href = `view_ticket.php?id=${id}`;
}

function updateTicket(id) {
    // Implement update ticket modal or page
    window.location.href = `update_ticket.php?id=${id}`;
}

function applyFilters() {
    const status = document.getElementById('statusFilter').value;
    const priority = document.getElementById('priorityFilter').value;
    // Implement filtering logic
    console.log('Filtering with:', status, priority);
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>