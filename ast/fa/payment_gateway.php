<?php
include '../components/connect.php';

session_start();

// Ensure only logged-in financial auditors can access
if(!isset($_SESSION['auditor_id'])){
   header('location:index.php');
   exit();
}

$auditor_id = $_SESSION['auditor_id'];

// Pagination setup
$records_per_page = 15;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Filter and search functionality
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_gateway = isset($_GET['gateway']) ? $_GET['gateway'] : '';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Prepare dynamic query for payment logs
$query_params = [];
$where_clauses = [];

if (!empty($filter_status)) {
    $where_clauses[] = "status = ?";
    $query_params[] = $filter_status;
}

if (!empty($filter_gateway)) {
    $where_clauses[] = "gateway_name = ?";
    $query_params[] = $filter_gateway;
}

if (!empty($search_query)) {
    $where_clauses[] = "(transaction_id LIKE ? OR gateway_name LIKE ?)";
    $query_params[] = "%$search_query%";
    $query_params[] = "%$search_query%";
}

// Construct WHERE clause
$where_sql = $where_clauses ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Total records query
$total_query = "SELECT COUNT(*) as total FROM `payment_gateway_logs` $where_sql";
$total_stmt = $conn->prepare($total_query);
$total_stmt->execute($query_params);
$total_records = $total_stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Calculate total pages
$total_pages = ceil($total_records / $records_per_page);

// Payment logs query
$logs_query = "SELECT * FROM `payment_gateway_logs` 
               $where_sql 
               ORDER BY timestamp DESC 
               LIMIT $records_per_page OFFSET $offset";
$logs_stmt = $conn->prepare($logs_query);
$logs_stmt->execute($query_params);
$payment_logs = $logs_stmt->fetchAll(PDO::FETCH_ASSOC);

// Aggregate Payment Gateway Metrics
try {
    // Total Transactions
    $total_transactions_query = $conn->prepare("
        SELECT 
            COUNT(*) as total_transactions,
            SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successful_transactions,
            SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_transactions,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_transactions,
            SUM(CASE WHEN status = 'disputed' THEN 1 ELSE 0 END) as disputed_transactions,
            SUM(amount) as total_transaction_amount
        FROM `payment_gateway_logs`
    ");
    $total_transactions_query->execute();
    $transaction_metrics = $total_transactions_query->fetch(PDO::FETCH_ASSOC);

    // Gateway Performance
    $gateway_performance_query = $conn->prepare("
        SELECT 
            gateway_name, 
            COUNT(*) as total_transactions,
            SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successful_transactions,
            ROUND(SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as success_rate
        FROM `payment_gateway_logs`
        GROUP BY gateway_name
    ");
    $gateway_performance_query->execute();
    $gateway_performance = $gateway_performance_query->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    // Log error and set default values
    error_log("Payment gateway metrics error: " . $e->getMessage());
    $transaction_metrics = [
        'total_transactions' => 0,
        'successful_transactions' => 0,
        'failed_transactions' => 0,
        'pending_transactions' => 0,
        'disputed_transactions' => 0,
        'total_transaction_amount' => 0
    ];
    $gateway_performance = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Keens | Payment Gateway</title>
   
   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
   <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<?php include '../components/financial_auditor_header.php'; ?>

<div class="container-fluid px-4 py-5">
    <div class="row">
        <!-- Payment Metrics -->
        <div class="col-12 col-xl-4 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h4 class="card-title mb-0">
                        <i class="fas fa-credit-card me-2 text-primary"></i>Payment Overview
                    </h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6 mb-3">
                            <small class="text-muted d-block">Total Transactions</small>
                            <h5><?= number_format($transaction_metrics['total_transactions']); ?></h5>
                        </div>
                        <div class="col-6 mb-3">
                            <small class="text-muted d-block">Total Amount</small>
                            <h5>$<?= number_format($transaction_metrics['total_transaction_amount'], 2); ?></h5>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <canvas id="transactionStatusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gateway Performance -->
        <div class="col-12 col-xl-8 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h4 class="card-title mb-0">
                        <i class="fas fa-chart-bar me-2 text-primary"></i>Gateway Performance
                    </h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Gateway</th>
                                    <th>Total Transactions</th>
                                    <th>Successful Transactions</th>
                                    <th>Success Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($gateway_performance)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            No gateway performance data available
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($gateway_performance as $gateway): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($gateway['gateway_name']); ?></td>
                                            <td><?= number_format($gateway['total_transactions']); ?></td>
                                            <td><?= number_format($gateway['successful_transactions']); ?></td>
                                            <td>
                                                <span class="badge 
                                                    <?php 
                                                    $success_rate = $gateway['success_rate'];
                                                    echo $success_rate >= 90 ? 'bg-success' : 
                                                         ($success_rate >= 70 ? 'bg-warning' : 'bg-danger');
                                                    ?>">
                                                    <?= number_format($gateway['success_rate'], 2); ?>%
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Logs -->
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">
                            <i class="fas fa-receipt me-2 text-primary"></i>Payment Logs
                        </h4>
                        <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#exportModal">
                            <i class="fas fa-file-export me-1"></i>Export Logs
                        </button>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- Filters and Search -->
                    <form method="GET" class="mb-4">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <select name="status" class="form-select">
                                    <option value="">All Statuses</option>
                                    <option value="success" <?= $filter_status == 'success' ? 'selected' : ''; ?>>Success</option>
                                    <option value="failed" <?= $filter_status == 'failed' ? 'selected' : ''; ?>>Failed</option>
                                    <option value="pending" <?= $filter_status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="disputed" <?= $filter_status == 'disputed' ? 'selected' : ''; ?>>Disputed</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select name="gateway" class="form-select">
                                    <option value="">All Gateways</option>
                                    <option value="Stripe" <?= $filter_gateway == 'Stripe' ? 'selected' : ''; ?>>Stripe</option>
                                    <option value="PayPal" <?= $filter_gateway == 'PayPal' ? 'selected' : ''; ?>>PayPal</option>
                                    <option value="Paytm" <?= $filter_gateway == 'Paytm' ? 'selected' : ''; ?>>Paytm</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <div class="input-group">
                                    <input type="search" name="search" class="form-control" 
                                           placeholder="Search transaction ID..." 
                                           value="<?= htmlspecialchars($search_query); ?>">
                                    <button type="submit" class="btn btn-outline-secondary">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Payment Logs Table -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Transaction ID</th>
                                    <th>Gateway</th>
                                    <th>Amount</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Timestamp</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($payment_logs)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">No payment logs found.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($payment_logs as $log): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($log['transaction_id']); ?></td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?= htmlspecialchars($log['gateway_name']); ?>
                                                </span>
                                            </td>
                                            <td>$<?= number_format($log['amount'], 2); ?></td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <?= htmlspecialchars($log['transaction_type']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge 
                                                    <?php 
                                                    switch($log['status']) {
                                                        case 'success': echo 'bg-success'; break;
                                                        case 'failed': echo 'bg-danger'; break;
                                                        case 'pending': echo 'bg-warning'; break;
                                                        case 'disputed': echo 'bg-dark'; break;
                                                        default: echo 'bg-secondary';
                                                    }
                                                    ?>
                                                ">
                                                    <?= ucfirst(htmlspecialchars($log['status'])); ?>
                                                </span>
                                            </td>
                                            <td><?= date('M d, Y H:i', strtotime($log['timestamp'])); ?></td>
                                            <td>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li>
                                                            <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#transactionDetailModal<?= $log['log_id']; ?>">
                                                                <i class="fas fa-eye me-2"></i>View Details
                                                            </a>
                                                        </li>
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
                    <nav aria-label="Payment logs page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= $page == $i ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?= $i; ?>&status=<?= urlencode($filter_status); ?>&gateway=<?= urlencode($filter_gateway); ?>&search=<?= urlencode($search_query); ?>">
                                        <?= $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
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
                <h5 class="modal-title">Export Payment Logs</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="mb-3">
                        <label class="form-label">Export Format</label>
                        <select class="form-select">
                            <option>PDF</option>
                            <option>CSV</option>
                            <option>Excel</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date Range</label>
                        <div class="input-group">
                            <input type="date" class="form-control">
                            <span class="input-group-text">to</span>
                            <input type="date" class="form-control">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-file-export me-2"></i>Export
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Transaction Detail Modals -->
<?php if (!empty($payment_logs)): ?>
    <?php foreach ($payment_logs as $log): ?>
        <div class="modal fade" id="transactionDetailModal<?= $log['log_id']; ?>" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Transaction Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Transaction Information</h6>
                                <table class="table table-borderless">
                                    <tr>
                                        <th>Transaction ID:</th>
                                        <td><?= htmlspecialchars($log['transaction_id']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Gateway:</th>
                                        <td><?= htmlspecialchars($log['gateway_name']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Amount:</th>
                                        <td>$<?= number_format($log['amount'], 2); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Transaction Type:</th>
                                        <td><?= htmlspecialchars($log['transaction_type']); ?></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6>Status Information</h6>
                                <table class="table table-borderless">
                                    <tr>
                                        <th>Status:</th>
                                        <td>
                                            <span class="badge 
                                                <?php 
                                                switch($log['status']) {
                                                    case 'success': echo 'bg-success'; break;
                                                    case 'failed': echo 'bg-danger'; break;
                                                    case 'pending': echo 'bg-warning'; break;
                                                    case 'disputed': echo 'bg-dark'; break;
                                                    default: echo 'bg-secondary';
                                                }
                                                ?>
                                            ">
                                                <?= ucfirst(htmlspecialchars($log['status'])); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Timestamp:</th>
                                        <td><?= date('M d, Y H:i:s', strtotime($log['timestamp'])); ?></td>
                                    </tr>
                                    <?php if (!empty($log['error_code'])): ?>
                                        <tr>
                                            <th>Error Code:</th>
                                            <td><?= htmlspecialchars($log['error_code']); ?></td>
                                        </tr>
                                    <?php endif; ?>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<script>
    // Transaction Status Chart
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('transactionStatusChart').getContext('2d');
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Successful', 'Failed', 'Pending', 'Disputed'],
                datasets: [{
                    data: [
                        <?= $transaction_metrics['successful_transactions']; ?>,
                        <?= $transaction_metrics['failed_transactions']; ?>,
                        <?= $transaction_metrics['pending_transactions']; ?>,
                        <?= $transaction_metrics['disputed_transactions']; ?>
                    ],
                    backgroundColor: [
                        'rgba(46, 204, 113, 0.7)',   // Successful (Green)
                        'rgba(231, 76, 60, 0.7)',    // Failed (Red)
                        'rgba(241, 196, 15, 0.7)',   // Pending (Yellow)
                        'rgba(52, 152, 219, 0.7)'    // Disputed (Blue)
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                    title: {
                        display: true,
                        text: 'Transaction Status Distribution'
                    }
                }
            }
        });
    });
</script>

<style>
    .table-borderless th {
        width: 40%;
        font-weight: 500;
    }
</style>

<?php include '../components/footer.php'; ?>
</body>
</html>