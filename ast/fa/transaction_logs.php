<?php
include '../components/connect.php';

session_start();


$auditor_id = $_SESSION['auditor_id'];

// Pagination setup
$records_per_page = 15;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Filter and search functionality
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_type = isset($_GET['type']) ? $_GET['type'] : '';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Prepare dynamic query
$query_params = [];
$where_clauses = [];

if (!empty($filter_status)) {
    $where_clauses[] = "payment_status = ?";
    $query_params[] = $filter_status;
}

if (!empty($filter_type)) {
    $where_clauses[] = "method = ?";
    $query_params[] = $filter_type;
}

if (!empty($search_query)) {
    $where_clauses[] = "(name LIKE ? OR email LIKE ? OR id LIKE ?)";
    $query_params[] = "%$search_query%";
    $query_params[] = "%$search_query%";
    $query_params[] = "%$search_query%";
}

// Construct WHERE clause
$where_sql = $where_clauses ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Total records query
$total_query = "SELECT COUNT(*) as total FROM `orders` $where_sql";
$total_stmt = $conn->prepare($total_query);
$total_stmt->execute($query_params);
$total_records = $total_stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Calculate total pages
$total_pages = ceil($total_records / $records_per_page);

// Transactions query
$transactions_query = "SELECT * FROM `orders` 
                       $where_sql 
                       ORDER BY placed_on DESC 
                       LIMIT $records_per_page OFFSET $offset";
$transactions_stmt = $conn->prepare($transactions_query);
$transactions_stmt->execute($query_params);
$transactions = $transactions_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get counts for each status for the report button badges
$status_counts = [];
$status_query = "SELECT payment_status, COUNT(*) as count FROM `orders` GROUP BY payment_status";
$status_stmt = $conn->prepare($status_query);
$status_stmt->execute();
$status_results = $status_stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($status_results as $result) {
    $status_counts[$result['payment_status']] = $result['count'];
}

// Get total revenue for completed orders
$revenue_query = "SELECT SUM(total_price) as total FROM `orders` WHERE payment_status = 'completed'";
$revenue_stmt = $conn->prepare($revenue_query);
$revenue_stmt->execute();
$total_revenue = $revenue_stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Get total value of pending orders
$pending_value_query = "SELECT SUM(total_price) as total FROM `orders` WHERE payment_status = 'pending'";
$pending_stmt = $conn->prepare($pending_value_query);
$pending_stmt->execute();
$pending_value = $pending_stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Keens.lk | Transaction Logs</title>
   
   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<?php include '../components/financial_auditor_header.php'; ?>

<div class="container-fluid px-4 py-5">
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">
                            <i class="fas fa-receipt me-2 text-primary"></i>Transaction Logs
                        </h4>
                        <div>
                            <div class="btn-group">
                                <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-file-pdf me-2"></i>Download Reports
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item d-flex justify-content-between align-items-center" href="completed_orders_report.php">
                                            <span><i class="fas fa-check-circle text-success me-2"></i>Completed Orders Report</span>
                                            <span class="badge bg-success rounded-pill"><?= $status_counts['completed'] ?? 0 ?></span>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item d-flex justify-content-between align-items-center" href="pending_orders_report.php">
                                            <span><i class="fas fa-clock text-warning me-2"></i>Pending Orders Report</span>
                                            <span class="badge bg-warning rounded-pill"><?= $status_counts['pending'] ?? 0 ?></span>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Stats Summary Cards -->
                <div class="card-body border-bottom pb-0">
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <div class="card h-100 border-0 bg-light">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="text-muted mb-1">Total Orders</h6>
                                            <h4 class="mb-0"><?= $total_records ?></h4>
                                        </div>
                                        <div class="icon-bg rounded-circle bg-primary bg-opacity-10 p-3">
                                            <i class="fas fa-shopping-cart text-primary"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card h-100 border-0 bg-light">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="text-muted mb-1">Completed Orders</h6>
                                            <h4 class="mb-0"><?= $status_counts['completed'] ?? 0 ?></h4>
                                        </div>
                                        <div class="icon-bg rounded-circle bg-success bg-opacity-10 p-3">
                                            <i class="fas fa-check-circle text-success"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card h-100 border-0 bg-light">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="text-muted mb-1">Pending Orders</h6>
                                            <h4 class="mb-0"><?= $status_counts['pending'] ?? 0 ?></h4>
                                        </div>
                                        <div class="icon-bg rounded-circle bg-warning bg-opacity-10 p-3">
                                            <i class="fas fa-clock text-warning"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card h-100 border-0 bg-light">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="text-muted mb-1">Total Revenue</h6>
                                            <h4 class="mb-0">Rs. <?= number_format($total_revenue, 2) ?></h4>
                                        </div>
                                        <div class="icon-bg rounded-circle bg-info bg-opacity-10 p-3">
                                            <i class="fas fa-dollar-sign text-info"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- Filters and Search -->
                    <form method="GET" class="mb-4">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <select name="status" class="form-select">
                                    <option value="">All Payment Statuses</option>
                                    <option value="pending" <?= $filter_status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="completed" <?= $filter_status == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="cancelled" <?= $filter_status == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select name="type" class="form-select">
                                    <option value="">All Payment Methods</option>
                                    <option value="cash on delivery" <?= $filter_type == 'cash on delivery' ? 'selected' : ''; ?>>Cash on Delivery</option>
                                    <option value="credit card" <?= $filter_type == 'credit card' ? 'selected' : ''; ?>>Credit Card</option>
                                    <option value="paytm" <?= $filter_type == 'paytm' ? 'selected' : ''; ?>>Paytm</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <div class="input-group">
                                    <input type="search" name="search" class="form-control" 
                                           placeholder="Search transactions..." 
                                           value="<?= htmlspecialchars($search_query); ?>">
                                    <button type="submit" class="btn btn-outline-secondary">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-filter me-2"></i>Apply Filters
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Alert for pending orders with significant value -->
                    <?php if (($status_counts['pending'] ?? 0) > 0 && $pending_value > 10000): ?>
                    <div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <div>
                            <strong>Attention:</strong> There are <?= $status_counts['pending'] ?? 0 ?> pending orders worth Rs. <?= number_format($pending_value, 2) ?>. Please review and process them.
                            <a href="reports/pending_orders_report.php" class="alert-link ms-2">View Report</a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Transactions Table -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Payment Method</th>
                                    <th>Total Price</th>
                                    <th>Placed On</th>
                                    <th>Payment Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($transactions)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">No transactions found.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($transactions as $transaction): ?>
                                        <tr>
                                            <td><?= $transaction['id']; ?></td>
                                            <td>
                                                <?= htmlspecialchars($transaction['name']); ?>
                                                <small class="d-block text-muted"><?= $transaction['email']; ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?= htmlspecialchars($transaction['method']); ?>
                                                </span>
                                            </td>
                                            <td>$ <?= number_format($transaction['total_price'], 2); ?></td>
                                            <td><?= date('M d, Y', strtotime($transaction['placed_on'])); ?></td>
                                            <td>
                                                <span class="badge 
                                                    <?php 
                                                    switch($transaction['payment_status']) {
                                                        case 'pending': echo 'bg-warning'; break;
                                                        case 'completed': echo 'bg-success'; break;
                                                        case 'cancelled': echo 'bg-danger'; break;
                                                        default: echo 'bg-secondary';
                                                    }
                                                    ?>
                                                ">
                                                    <?= ucfirst($transaction['payment_status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li>
                                                            <a class="dropdown-item" href="transaction_details.php?id=<?= $transaction['id']; ?>">
                                                                <i class="fas fa-eye me-2"></i>View Details
                                                            </a>
                                                        </li>
                                                        
                                                        <?php if ($transaction['payment_status'] == 'pending'): ?>
                                                        
                                                        <?php endif; ?>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <nav aria-label="Transaction page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page-1; ?>&status=<?= urlencode($filter_status); ?>&type=<?= urlencode($filter_type); ?>&search=<?= urlencode($search_query); ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php 
                            // Calculate pagination range
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $start_page + 4);
                            if ($end_page - $start_page < 4 && $total_pages > 4) {
                                $start_page = max(1, $end_page - 4);
                            }
                            
                            for ($i = $start_page; $i <= $end_page; $i++): 
                            ?>
                                <li class="page-item <?= $page == $i ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?= $i; ?>&status=<?= urlencode($filter_status); ?>&type=<?= urlencode($filter_type); ?>&search=<?= urlencode($search_query); ?>">
                                        <?= $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page+1; ?>&status=<?= urlencode($filter_status); ?>&type=<?= urlencode($filter_type); ?>&search=<?= urlencode($search_query); ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Export Modal -->
<div class="modal fade" id="exportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Export Transaction Logs</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form action="export_transactions.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label">Export Format</label>
                        <select name="format" class="form-select">
                            <option value="pdf">PDF</option>
                            <option value="csv">CSV</option>
                            <option value="excel">Excel</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date Range</label>
                        <div class="input-group">
                            <input type="date" name="date_from" class="form-control">
                            <span class="input-group-text">to</span>
                            <input type="date" name="date_to" class="form-control">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="export_status" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-download me-2"></i>Export Data
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .table-hover tbody tr:hover {
        background-color: rgba(0, 0, 0, 0.05);
    }
    .icon-bg {
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .icon-bg i {
        font-size: 1.5rem;
    }
</style>
</body>
</html>