<?php
include '../components/connect.php';

session_start();

if (!isset($_SESSION['supplier_id'])) {
    header('location:index.php');
    exit();
}

$supplier_id = $_SESSION['supplier_id'];

// Define alert thresholds
$critical_threshold = 10;
$low_threshold = 50;

// Fetch inventory alerts with product details
$alerts_query = $conn->prepare("
    SELECT 
        p.id as product_id, 
        p.name as product_name, 
        p.price,
        i.quantity,
        CASE 
            WHEN i.quantity < ? THEN 'Critical'
            WHEN i.quantity < ? THEN 'Low'
            ELSE 'Sufficient'
        END as stock_status
    FROM products p
    JOIN inventory i ON p.id = i.product_id
    WHERE i.supplier_id = ? AND i.quantity < ?
    ORDER BY i.quantity ASC
");
$alerts_query->execute([$critical_threshold, $low_threshold, $supplier_id, $low_threshold]);
$inventory_alerts = $alerts_query->fetchAll(PDO::FETCH_ASSOC);

// Fetch recent inventory changes
$recent_changes_query = $conn->prepare("
    SELECT 
        l.log_id,
        p.name as product_name,
        l.previous_quantity,
        l.new_quantity,
        l.change_type,
        l.reason,
        l.logged_at
    FROM inventory_log l
    JOIN products p ON l.product_id = p.id
    WHERE l.supplier_id = ?
    ORDER BY l.logged_at DESC
    LIMIT 10
");
$recent_changes_query->execute([$supplier_id]);
$recent_changes = $recent_changes_query->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keens | Inventory Alerts</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
</head>
<body>
    <?php include '../components/supplier_header.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12 d-flex justify-content-between align-items-center mb-4">
                <h1>
                    <i class="fas fa-bell me-2"></i>Inventory Alerts
                </h1>
                <a href="inventory_update.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Update Inventory
                </a>
            </div>
        </div>

        <div class="row">
            <!-- Inventory Alerts Section -->
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                            Low Stock Products
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($inventory_alerts)): ?>
                            <div class="alert alert-success m-3 mb-0" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                No low stock products. Inventory levels are looking good!
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped mb-0">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Current Quantity</th>
                                            <th>Price</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($inventory_alerts as $alert): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($alert['product_name']) ?></td>
                                                <td><?= $alert['quantity'] ?></td>
                                                <td>$<?= number_format($alert['price'], 2) ?></td>
                                                <td>
                                                    <span class="badge <?php 
                                                        echo $alert['stock_status'] == 'Critical' ? 'bg-danger' : 'bg-warning';
                                                    ?>">
                                                        <?= $alert['stock_status'] ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="inventory_update.php?id=<?= $alert['product_id'] ?>" 
                                                       class="btn btn-sm btn-primary">
                                                        <i class="fas fa-edit me-1"></i>Update
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Inventory Changes -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-history me-2"></i>
                            Recent Changes
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($recent_changes)): ?>
                            <div class="alert alert-info m-3 mb-0" role="alert">
                                <i class="fas fa-info-circle me-2"></i>
                                No recent inventory changes.
                            </div>
                        <?php else: ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($recent_changes as $change): ?>
                                    <li class="list-group-item">
                                        <div class="d-flex justify-content-between">
                                            <span>
                                                <?= htmlspecialchars($change['product_name']) ?>
                                                <small class="text-muted d-block">
                                                    <?= $change['change_type'] == 'add' ? '+' : '-' ?>
                                                    <?= abs($change['new_quantity'] - $change['previous_quantity']) ?>
                                                </small>
                                            </span>
                                            <small class="text-muted">
                                                <?= date('d M H:i', strtotime($change['logged_at'])) ?>
                                            </small>
                                        </div>
                                        <small class="text-muted d-block mt-1">
                                            <i class="fas fa-comment-dots me-1"></i>
                                            <?= htmlspecialchars($change['reason']) ?>
                                        </small>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .list-group-item {
            transition: background-color 0.3s ease;
        }

        .list-group-item:hover {
            background-color: #f8f9fa;
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>