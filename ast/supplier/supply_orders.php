<?php
include '../components/connect.php';

session_start();

if (!isset($_SESSION['supplier_id'])) {
    header('location:index.php');
    exit();
}

$supplier_id = $_SESSION['supplier_id'];

// Pagination setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Filter options
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Base query
$query = "FROM supply_orders WHERE supplier_id = ?";
$params = [$supplier_id];

// Apply filters
if (!empty($status_filter)) {
    $query .= " AND status = ?";
    $params[] = $status_filter;
}

if (!empty($date_from)) {
    $query .= " AND order_date >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $query .= " AND order_date <= ?";
    $params[] = $date_to;
}

// Count total records
$count_query = $conn->prepare("SELECT COUNT(*) as total $query");
$count_query->execute($params);
$total_records = $count_query->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_records / $records_per_page);

// Fetch supply orders
$orders_query = $conn->prepare("
    SELECT * $query 
    ORDER BY order_date DESC 
    LIMIT $records_per_page OFFSET $offset
");
$orders_query->execute($params);
$supply_orders = $orders_query->fetchAll(PDO::FETCH_ASSOC);

// Fetch order item counts
$order_items_query = $conn->prepare("
    SELECT supply_order_id, COUNT(*) as item_count
    FROM supply_order_items
    WHERE supply_order_id IN (
        SELECT supply_order_id 
        FROM supply_orders 
        WHERE supplier_id = ?
    )
    GROUP BY supply_order_id
");
$order_items_query->execute([$supplier_id]);
$order_items_counts = $order_items_query->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keens | Supply Orders</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
</head>
<body>
    <?php include '../components/supplier_header.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12 d-flex justify-content-between align-items-center mb-4">
                <h1>
                    <i class="fas fa-truck-loading me-2"></i>Supply Orders
                </h1>
                <a href="create_supply_order.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Create Supply Order
                </a>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="">
                    <div class="row">
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All Statuses</option>
                                <option value="pending" <?= $status_filter == 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="in_progress" <?= $status_filter == 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                <option value="completed" <?= $status_filter == 'completed' ? 'selected' : '' ?>>Completed</option>
                                <option value="cancelled" <?= $status_filter == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">From Date</label>
                            <input type="date" 
                                   name="date_from" 
                                   class="form-control" 
                                   value="<?= htmlspecialchars($date_from) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">To Date</label>
                            <input type="date" 
                                   name="date_to" 
                                   class="form-control" 
                                   value="<?= htmlspecialchars($date_to) ?>">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-filter me-2"></i>Apply Filters
                            </button>
                            <a href="supply_orders.php" class="btn btn-secondary">
                                <i class="fas fa-refresh me-2"></i>Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Supply Orders Table -->
        <div class="card">
            <div class="card-body p-0">
                <?php if (empty($supply_orders)): ?>
                    <div class="alert alert-info m-3 mb-0" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        No supply orders found.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Order Date</th>
                                    <th>Total Amount</th>
                                    <th>Items</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($supply_orders as $order): ?>
                                    <tr>
                                        <td>#<?= $order['supply_order_id'] ?></td>
                                        <td><?= date('d M Y', strtotime($order['order_date'])) ?></td>
                                        <td>$<?= number_format($order['total_amount'], 2) ?></td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <?= $order_items_counts[$order['supply_order_id']] ?? 0 ?> Items
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                switch($order['status']) {
                                                    case 'pending': echo 'warning'; break;
                                                    case 'in_progress': echo 'info'; break;
                                                    case 'completed': echo 'success'; break;
                                                    case 'cancelled': echo 'danger'; break;
                                                    default: echo 'secondary';
                                                }
                                            ?>">
                                                <?= ucfirst($order['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="view_supply_order.php?id=<?= $order['supply_order_id'] ?>" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class="fas fa-eye me-1"></i>View
                                                </a>
                                                <?php if ($order['status'] == 'pending'): ?>
                                                    <a href="edit_supply_order.php?id=<?= $order['supply_order_id'] ?>" 
                                                       class="btn btn-sm btn-secondary">
                                                        <i class="fas fa-edit me-1"></i>Edit
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav aria-label="Supply Orders Pagination" class="mt-3">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&status=<?= $status_filter ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>