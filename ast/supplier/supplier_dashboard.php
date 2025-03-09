<?php
include '../components/connect.php';

session_start();

if(!isset($_SESSION['supplier_id'])) {
   header('location:index.php');
   exit();
}

$supplier_id = $_SESSION['supplier_id'];

// Get overall supply order statistics
$supply_order_stats = $conn->prepare("
    SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as active_orders,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders
    FROM supply_orders 
    WHERE supplier_id = ?
");
$supply_order_stats->execute([$supplier_id]);
$stats = $supply_order_stats->fetch(PDO::FETCH_ASSOC);

// Get recent supply orders
$recent_orders = $conn->prepare("
    SELECT * 
    FROM supply_orders 
    WHERE supplier_id = ? 
    ORDER BY order_date DESC 
    LIMIT 5
");
$recent_orders->execute([$supplier_id]);

// Get inventory low stock alerts
$low_stock_products = $conn->prepare("
    SELECT p.name, i.quantity 
    FROM products p
    JOIN inventory i ON p.id = i.product_id
    WHERE i.quantity < 10
    LIMIT 5
");
$low_stock_products->execute();
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Keens | Supplier Dashboard</title>

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   
   <style>
    :root {
        --primary-color: #4361ee;
        --secondary-color: #3f37c9;
        --accent-color: #4895ef;
        --background-light: #f4f7fe;
        --text-dark: #2b3452;
        --text-muted: #6c757d;
        --border-color: #e9ecef;
        --card-shadow: 0 8px 20px rgba(67, 97, 238, 0.07);
        --hover-shadow: 0 10px 25px rgba(67, 97, 238, 0.15);
    }

    body {
        background-color: var(--background-light);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        color: var(--text-dark);
    }

    /* Dashboard Layout */
    .dashboard-container {
        display: flex;
        min-height: calc(100vh - 140px); /* Account for nav heights */
    }

    /* Sidebar */
    .sidebar {
        width: 280px;
        background-color: white;
        box-shadow: var(--card-shadow);
        border-right: 1px solid var(--border-color);
        position: sticky;
        top: 140px; /* Account for nav heights */
        height: calc(100vh - 140px);
        overflow-y: auto;
        transition: all 0.3s ease;
        z-index: 10;
    }

    .sidebar-header {
        padding: 1.5rem;
        border-bottom: 1px solid var(--border-color);
    }

    .sidebar-title {
        display: flex;
        align-items: center;
        font-weight: 600;
        margin-bottom: 0;
        color: var(--text-dark);
    }

    .sidebar-title i {
        margin-right: 0.75rem;
        color: var(--primary-color);
        font-size: 1.25rem;
    }

    .sidebar-link {
        display: flex;
        align-items: center;
        padding: 1rem 1.5rem;
        color: var(--text-dark);
        text-decoration: none;
        transition: all 0.3s ease;
        border-left: 3px solid transparent;
    }

    .sidebar-link i {
        margin-right: 0.75rem;
        width: 1.5rem;
        text-align: center;
        color: var(--primary-color);
        font-size: 1.1rem;
        transition: all 0.3s ease;
    }

    .sidebar-link:hover {
        background-color: rgba(67, 97, 238, 0.05);
        color: var(--primary-color);
        border-left: 3px solid var(--primary-color);
    }

    .sidebar-link:hover i {
        transform: translateX(3px);
    }

    /* Main Content */
    .main-content {
        flex: 1;
        padding: 1.5rem;
        overflow-x: hidden;
    }

    /* Stats Cards */
    .stats-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .stats-card {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        display: flex;
        align-items: center;
        gap: 1.25rem;
        box-shadow: var(--card-shadow);
        transition: all 0.3s ease;
    }

    .stats-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--hover-shadow);
    }

    .stats-icon {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        background: rgba(67, 97, 238, 0.1);
        color: var(--primary-color);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }

    .stats-icon.warning {
        background: rgba(255, 193, 7, 0.1);
        color: #ffc107;
    }

    .stats-icon.info {
        background: rgba(23, 162, 184, 0.1);
        color: #17a2b8;
    }

    .stats-icon.success {
        background: rgba(40, 167, 69, 0.1);
        color: #28a745;
    }

    .stats-info h3 {
        font-size: 1.75rem;
        font-weight: 700;
        margin-bottom: 0.25rem;
    }

    .stats-info p {
        font-size: 0.95rem;
        color: var(--text-muted);
        margin-bottom: 0;
    }

    /* Content Cards */
    .content-card {
        background: white;
        border-radius: 12px;
        box-shadow: var(--card-shadow);
        margin-bottom: 1.5rem;
        overflow: hidden;
    }

    .card-header {
        background: transparent;
        border-bottom: 1px solid var(--border-color);
        padding: 1.25rem 1.5rem;
    }

    .card-header h5 {
        margin-bottom: 0;
        font-weight: 600;
        display: flex;
        align-items: center;
    }

    .card-header h5 i {
        margin-right: 0.75rem;
        color: var(--primary-color);
    }

    .card-body {
        padding: 1.5rem;
    }

    /* Tables */
    .table {
        margin-bottom: 0;
    }

    .table th {
        font-weight: 600;
        color: var(--text-dark);
        border-top: none;
        padding: 1rem 1.5rem;
    }

    .table td {
        vertical-align: middle;
        padding: 1rem 1.5rem;
    }

    /* Low Stock Items */
    .low-stock-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 1rem;
        background: var(--background-light);
        border-radius: 8px;
        margin-bottom: 0.75rem;
        transition: all 0.3s ease;
    }

    .low-stock-item:hover {
        transform: scale(1.02);
        background: rgba(220, 53, 69, 0.05);
    }

    .product-name {
        font-weight: 500;
    }

    .product-stock {
        font-size: 0.8rem;
        padding: 0.35rem 0.65rem;
    }

    /* Responsive Adjustments */
    @media (max-width: 992px) {
        .dashboard-container {
            flex-direction: column;
        }
        
        .sidebar {
            width: 100%;
            position: relative;
            top: 0;
            height: auto;
            margin-bottom: 1.5rem;
        }
        
        .sidebar-links {
            display: flex;
            flex-wrap: wrap;
        }
        
        .sidebar-link {
            flex: 1 0 50%;
            padding: 0.75rem 1rem;
        }
    }

    @media (max-width: 768px) {
        .stats-row {
            grid-template-columns: 1fr;
            gap: 1rem;
        }
        
        .sidebar-link {
            flex: 1 0 100%;
        }
    }
   </style>
</head>
<body>

<?php include '../components/supplier_header.php'; ?>

<div class="dashboard-container">
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h5 class="sidebar-title"><i class="fas fa-bolt"></i>Quick Actions</h5>
        </div>
        <div class="sidebar-links">
            <a href="new_product.php" class="sidebar-link">
                <i class="fas fa-plus-circle"></i>
                <span>Add New Product</span>
            </a>
            <a href="inventory_update.php" class="sidebar-link">
                <i class="fas fa-boxes"></i>
                <span>Update Inventory</span>
            </a>
            <a href="supply_request.php" class="sidebar-link">
                <i class="fas fa-truck"></i>
                <span>Create Supply Request</span>
            </a>
            <a href="analytics.php" class="sidebar-link">
                <i class="fas fa-chart-bar"></i>
                <span>View Analytics</span>
            </a>
            
            <a href="communication.php" class="sidebar-link">
                <i class="fas fa-comments"></i>
                <span>Communications</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Statistics Cards -->
        <div class="stats-row">
            <div class="stats-card">
                <div class="stats-icon">
                    <i class="fas fa-truck-loading"></i>
                </div>
                <div class="stats-info">
                    <h3><?= $stats['total_orders'] ?? 0 ?></h3>
                    <p>Total Supply Orders</p>
                </div>
            </div>
            <div class="stats-card">
                <div class="stats-icon warning">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stats-info">
                    <h3><?= $stats['pending_orders'] ?? 0 ?></h3>
                    <p>Pending Orders</p>
                </div>
            </div>
            <div class="stats-card">
                <div class="stats-icon info">
                    <i class="fas fa-spinner"></i>
                </div>
                <div class="stats-info">
                    <h3><?= $stats['active_orders'] ?? 0 ?></h3>
                    <p>Active Orders</p>
                </div>
            </div>
            <div class="stats-card">
                <div class="stats-icon success">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stats-info">
                    <h3><?= $stats['completed_orders'] ?? 0 ?></h3>
                    <p>Completed Orders</p>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recent Supply Orders -->
            <div class="col-lg-8">
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-truck-loading"></i>Recent Supply Orders</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Order Date</th>
                                    <th>Total Amount</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($recent_orders->rowCount() > 0): ?>
                                    <?php while($order = $recent_orders->fetch(PDO::FETCH_ASSOC)): ?>
                                        <tr>
                                            <td>#<?= $order['supply_order_id'] ?></td>
                                            <td><?= date('d M Y', strtotime($order['order_date'])) ?></td>
                                            <td>$<?= number_format($order['total_amount'], 2) ?></td>
                                            <td>
                                                <span class="badge rounded-pill bg-<?php
                                                    switch($order['status']) {
                                                        case 'pending': echo 'warning'; break;
                                                        case 'in_progress': echo 'info'; break;
                                                        case 'completed': echo 'success'; break;
                                                        default: echo 'secondary';
                                                    }
                                                ?>">
                                                    <?= ucfirst(str_replace('_', ' ', $order['status'])) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="view_supply_order.php?id=<?= $order['supply_order_id'] ?>" 
                                                   class="btn btn-sm btn-primary rounded-pill">
                                                    <i class="fas fa-eye me-1"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4">
                                            <i class="fas fa-box-open text-muted mb-2" style="font-size: 2rem;"></i>
                                            <p class="text-muted mb-0">No recent supply orders found</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer bg-white border-top text-end py-3">
                        <a href="supply_orders.php" class="btn btn-outline-primary btn-sm rounded-pill">
                            View All Orders <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Inventory Alerts -->
            <div class="col-lg-4">
                <div class="content-card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-bell"></i>Low Stock Alerts</h5>
                        <span class="badge bg-danger rounded-pill">
                            <?= $low_stock_products->rowCount() ?> Items
                        </span>
                    </div>
                    <div class="card-body">
                        <?php if($low_stock_products->rowCount() > 0): ?>
                            <?php while($product = $low_stock_products->fetch(PDO::FETCH_ASSOC)): ?>
                                <div class="low-stock-item">
                                    <span class="product-name"><?= htmlspecialchars($product['name']) ?></span>
                                    <span class="product-stock badge bg-danger rounded-pill">
                                        <?= $product['quantity'] ?> left
                                    </span>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-check-circle text-success mb-3" style="font-size: 2.5rem;"></i>
                                <p class="text-muted mb-0">All stock levels are healthy</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer bg-white border-top text-center py-3">
                        <a href="inventory_alert.php" class="btn btn-danger btn-sm rounded-pill">
                            <i class="fas fa-exclamation-circle me-1"></i> Manage Low Stock
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Activity Section -->
        <div class="content-card">
            <div class="card-header">
                <h5><i class="fas fa-history"></i>Recent Activity</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Activity tracking feature coming soon. This will show your recent actions on the platform.
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>