<?php
include '../components/connect.php';

session_start();

$staff_id = $_SESSION['staff_id'];

if(!isset($staff_id)){
   header('location:index.php');
}

// Get report parameters
$report_type = isset($_GET['type']) ? $_GET['type'] : 'inventory';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

function getInventoryReport($conn, $start_date, $end_date) {
    // Get inventory movements
    $movements = $conn->prepare("
        SELECT 
            p.name as product_name,
            p.SKU,
            il.change_type,
            SUM(CASE WHEN il.change_type = 'add' THEN il.new_quantity - il.previous_quantity ELSE 0 END) as total_added,
            SUM(CASE WHEN il.change_type = 'remove' THEN il.previous_quantity - il.new_quantity ELSE 0 END) as total_removed,
            i.quantity as current_stock
        FROM inventory_log il
        JOIN products p ON il.product_id = p.id
        JOIN inventory i ON i.product_id = p.id
        WHERE il.logged_at BETWEEN ? AND ?
        GROUP BY p.id
    ");
    $movements->execute([$start_date, $end_date]);
    return $movements->fetchAll(PDO::FETCH_ASSOC);
}

function getOrdersReport($conn, $start_date, $end_date) {
    // Get order statistics
    $orders = $conn->prepare("
        SELECT 
            COUNT(*) as total_orders,
            SUM(CASE WHEN order_status = 'delivered' THEN 1 ELSE 0 END) as completed_orders,
            SUM(CASE WHEN order_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
            AVG(TIMESTAMPDIFF(HOUR, placed_on, CASE WHEN order_status = 'delivered' THEN updated_at ELSE NULL END)) as avg_delivery_time
        FROM orders
        WHERE placed_on BETWEEN ? AND ?
    ");
    $orders->execute([$start_date, $end_date]);
    return $orders->fetch(PDO::FETCH_ASSOC);
}

function getReturnsReport($conn, $start_date, $end_date) {
    // Get returns statistics
    $returns = $conn->prepare("
        SELECT 
            COUNT(*) as total_returns,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_returns,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_returns,
            COUNT(DISTINCT product_id) as unique_products_returned
        FROM return_request r
        JOIN return_items ri ON r.return_id = ri.return_id
        WHERE r.requested_date BETWEEN ? AND ?
    ");
    $returns->execute([$start_date, $end_date]);
    return $returns->fetch(PDO::FETCH_ASSOC);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Keens | Generate Report</title>

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<?php include '../components/warehouse_staff_header.php'; ?>

<section class="generate-report py-5">
    <div class="container">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-2">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Generate Report</li>
                    </ol>
                </nav>
                <h2 class="h3 mb-0">Warehouse Reports</h2>
            </div>
            <button type="button" class="btn btn-primary" onclick="window.print()">
                <i class="fas fa-print me-2"></i>Print Report
            </button>
        </div>

        <!-- Report Filters -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form action="" method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Report Type</label>
                        <select name="type" class="form-select" onchange="this.form.submit()">
                            <option value="inventory" <?= $report_type == 'inventory' ? 'selected' : '' ?>>Inventory Movement</option>
                            <option value="orders" <?= $report_type == 'orders' ? 'selected' : '' ?>>Order Processing</option>
                            <option value="returns" <?= $report_type == 'returns' ? 'selected' : '' ?>>Returns Analysis</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control" 
                               value="<?= $start_date ?>" max="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-control" 
                               value="<?= $end_date ?>" max="<?= date('Y-m-d') ?>">
                    </div>
                </form>
            </div>
        </div>

        <!-- Report Content -->
        <div class="card shadow-sm">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <?php
                        switch($report_type) {
                            case 'inventory': echo 'Inventory Movement Report';
                                break;
                            case 'orders': echo 'Order Processing Report';
                                break;
                            case 'returns': echo 'Returns Analysis Report';
                                break;
                        }
                    ?>
                </h5>
                <small class="text-muted">
                    <?= date('M d, Y', strtotime($start_date)) ?> - 
                    <?= date('M d, Y', strtotime($end_date)) ?>
                </small>
            </div>
            <div class="card-body">
                <?php if($report_type == 'inventory'): ?>
                    <!-- Inventory Movement Report -->
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>SKU</th>
                                    <th>Items Added</th>
                                    <th>Items Removed</th>
                                    <th>Net Change</th>
                                    <th>Current Stock</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                    $inventory_data = getInventoryReport($conn, $start_date, $end_date);
                                    foreach($inventory_data as $item):
                                        $net_change = $item['total_added'] - $item['total_removed'];
                                ?>
                                <tr>
                                    <td><?= $item['product_name'] ?></td>
                                    <td><?= $item['SKU'] ?></td>
                                    <td class="text-success">+<?= number_format($item['total_added']) ?></td>
                                    <td class="text-danger">-<?= number_format($item['total_removed']) ?></td>
                                    <td class="<?= $net_change >= 0 ? 'text-success' : 'text-danger' ?>">
                                        <?= $net_change >= 0 ? '+' : '' ?><?= number_format($net_change) ?>
                                    </td>
                                    <td><?= number_format($item['current_stock']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                <?php elseif($report_type == 'orders'): ?>
                    <!-- Order Processing Report -->
                    <?php $order_data = getOrdersReport($conn, $start_date, $end_date); ?>
                    <div class="row g-4">
                        <div class="col-md-3">
                            <div class="stat-card">
                                <h3 class="mb-1"><?= number_format($order_data['total_orders']) ?></h3>
                                <p class="mb-0 text-muted">Total Orders</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <h3 class="mb-1"><?= number_format($order_data['completed_orders']) ?></h3>
                                <p class="mb-0 text-muted">Completed Orders</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <h3 class="mb-1"><?= number_format($order_data['cancelled_orders']) ?></h3>
                                <p class="mb-0 text-muted">Cancelled Orders</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <h3 class="mb-1"><?= round($order_data['avg_delivery_time'], 1) ?> hrs</h3>
                                <p class="mb-0 text-muted">Avg. Delivery Time</p>
                            </div>
                        </div>
                    </div>

                <?php else: ?>
                    <!-- Returns Analysis Report -->
                    <?php $return_data = getReturnsReport($conn, $start_date, $end_date); ?>
                    <div class="row g-4">
                        <div class="col-md-3">
                            <div class="stat-card">
                                <h3 class="mb-1"><?= number_format($return_data['total_returns']) ?></h3>
                                <p class="mb-0 text-muted">Total Returns</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <h3 class="mb-1"><?= number_format($return_data['approved_returns']) ?></h3>
                                <p class="mb-0 text-muted">Approved Returns</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <h3 class="mb-1"><?= number_format($return_data['rejected_returns']) ?></h3>
                                <p class="mb-0 text-muted">Rejected Returns</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <h3 class="mb-1"><?= number_format($return_data['unique_products_returned']) ?></h3>
                                <p class="mb-0 text-muted">Unique Products</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<style>
/* Custom Styles */
.breadcrumb-item a {
    color: var(--primary-color);
    text-decoration: none;
}

.card {
    border: none;
    border-radius: 15px;
}

.card-header {
    border-bottom: 1px solid rgba(0,0,0,.05);
}

.stat-card {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 15px;
    text-align: center;
}

.form-control:focus, .form-select:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.25);
}

.btn-primary {
    background: var(--primary-color);
    border-color: var(--primary-color);
}

.btn-primary:hover {
    background: var(--secondary-color);
    border-color: var(--secondary-color);
}

.table th {
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.8rem;
    letter-spacing: 0.5px;
}

/* Print Styles */
@media print {
    .navbar, form, .btn-primary {
        display: none !important;
    }
    
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    
    .stat-card {
        border: 1px solid #dee2e6;
    }
}
</style>

</body>
</html>