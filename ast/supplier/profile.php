<?php
include '../components/connect.php';

session_start();

if (!isset($_SESSION['supplier_id'])) {
    header('location:index.php');
    exit();
}

$supplier_id = $_SESSION['supplier_id'];

// Fetch supplier profile
$profile_query = $conn->prepare("SELECT * FROM suppliers WHERE supplier_id = ?");
$profile_query->execute([$supplier_id]);
$supplier = $profile_query->fetch(PDO::FETCH_ASSOC);

// Fetch supplier performance
$performance_query = $conn->prepare("
    SELECT 
        COUNT(supply_order_id) as total_orders,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
        ROUND(AVG(total_amount), 2) as avg_order_value
    FROM supply_orders 
    WHERE supplier_id = ?
");
$performance_query->execute([$supplier_id]);
$performance = $performance_query->fetch(PDO::FETCH_ASSOC);

// Fetch recent supply orders
$recent_orders_query = $conn->prepare("
    SELECT supply_order_id, order_date, total_amount, status 
    FROM supply_orders 
    WHERE supplier_id = ? 
    ORDER BY order_date DESC 
    LIMIT 5
");
$recent_orders_query->execute([$supplier_id]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keens | Supplier Profile</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
</head>
<body>
    <?php include '../components/supplier_header.php'; ?>

    <div class="container mt-5">
        <div class="row">
            <!-- Profile Section -->
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="profile-avatar mb-3">
                            <?= strtoupper(substr($supplier['name'], 0, 1)) ?>
                        </div>
                        <h4 class="card-title mb-2"><?= htmlspecialchars($supplier['name']) ?></h4>
                        <p class="text-muted mb-3">Supplier</p>
                        
                        <div class="d-flex justify-content-center gap-3 mb-3">
                            <span class="badge bg-<?= $supplier['is_active'] ? 'success' : 'danger' ?>">
                                <?= $supplier['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </div>

                        <a href="update_profile.php" class="btn btn-primary">
                            <i class="fas fa-edit me-2"></i>Edit Profile
                        </a>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Contact Information</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-2">
                            <i class="fas fa-envelope me-2"></i>
                            <?= htmlspecialchars($supplier['email']) ?>
                        </p>
                        <p class="mb-2">
                            <i class="fas fa-phone me-2"></i>
                            <?= htmlspecialchars($supplier['phone']) ?>
                        </p>
                        <p class="mb-0">
                            <i class="fas fa-map-marker-alt me-2"></i>
                            <?= htmlspecialchars($supplier['address']) ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Performance and Orders Section -->
            <div class="col-md-8">
                <!-- Performance Metrics -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Performance Metrics</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="performance-metric">
                                    <h6>Total Orders</h6>
                                    <p class="h4"><?= $performance['total_orders'] ?? 0 ?></p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="performance-metric">
                                    <h6>Completed Orders</h6>
                                    <p class="h4"><?= $performance['completed_orders'] ?? 0 ?></p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="performance-metric">
                                    <h6>Avg Order Value</h6>
                                    <p class="h4">$<?= number_format($performance['avg_order_value'] ?? 0, 2) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Supply Orders -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Recent Supply Orders</h5>
                        <a href="supply_orders.php" class="btn btn-sm btn-outline-primary">
                            View All
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Date</th>
                                        <th>Total Amount</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($order = $recent_orders_query->fetch(PDO::FETCH_ASSOC)): ?>
                                    <tr>
                                        <td>#<?= $order['supply_order_id'] ?></td>
                                        <td><?= date('d M Y', strtotime($order['order_date'])) ?></td>
                                        <td>$<?= number_format($order['total_amount'], 2) ?></td>
                                        <td>
                                            <span class="badge bg-<?php
                                                switch($order['status']) {
                                                    case 'pending': echo 'warning'; break;
                                                    case 'in_progress': echo 'info'; break;
                                                    case 'completed': echo 'success'; break;
                                                    default: echo 'secondary';
                                                }
                                            ?>">
                                                <?= ucfirst($order['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="view_supply_order.php?id=<?= $order['supply_order_id'] ?>" 
                                               class="btn btn-sm btn-primary">
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background-color: #4361ee;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            margin: 0 auto 15px;
        }

        .performance-metric {
            text-align: center;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }

        .performance-metric h6 {
            color: #6c757d;
            margin-bottom: 10px;
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>