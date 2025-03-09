<?php
include '../components/connect.php';

session_start();

if (!isset($_SESSION['supplier_id'])) {
    header('location:index.php');
    exit();
}

$supplier_id = $_SESSION['supplier_id'];

// Fetch inventory with product details
$inventory_query = $conn->prepare("
    SELECT 
        p.id as product_id, 
        p.name as product_name, 
        p.price,
        i.quantity,
        i.last_updated
    FROM products p
    JOIN inventory i ON p.id = i.product_id
    WHERE i.supplier_id = ?
    ORDER BY i.quantity ASC
");
$inventory_query->execute([$supplier_id]);
$inventory_items = $inventory_query->fetchAll(PDO::FETCH_ASSOC);

// Calculate inventory statistics
$stats_query = $conn->prepare("
    SELECT 
        COUNT(*) as total_products,
        SUM(quantity) as total_quantity,
        AVG(quantity) as average_quantity,
        SUM(CASE WHEN quantity < 10 THEN 1 ELSE 0 END) as low_stock_products
    FROM inventory
    WHERE supplier_id = ?
");
$stats_query->execute([$supplier_id]);
$inventory_stats = $stats_query->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keens | Inventory Management</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
</head>
<body>
    <?php include '../components/supplier_header.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12 d-flex justify-content-between align-items-center mb-4">
                <h1>
                    <i class="fas fa-boxes me-2"></i>Inventory Management
                </h1>
                <a href="inventory_update.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Update Inventory
                </a>
            </div>
        </div>

        <!-- Inventory Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Total Products</h5>
                        <p class="h3"><?= $inventory_stats['total_products'] ?? 0 ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Total Quantity</h5>
                        <p class="h3"><?= $inventory_stats['total_quantity'] ?? 0 ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Avg Quantity</h5>
                        <p class="h3"><?= number_format($inventory_stats['average_quantity'] ?? 0, 1) ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Low Stock Products</h5>
                        <p class="h3 text-danger"><?= $inventory_stats['low_stock_products'] ?? 0 ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Inventory Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Current Inventory</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Product ID</th>
                                <th>Product Name</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Last Updated</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($inventory_items)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">
                                        No inventory items found. 
                                        <a href="inventory_update.php">Add inventory</a>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($inventory_items as $item): ?>
                                    <tr>
                                        <td><?= $item['product_id'] ?></td>
                                        <td><?= htmlspecialchars($item['product_name']) ?></td>
                                        <td>$<?= number_format($item['price'], 2) ?></td>
                                        <td><?= $item['quantity'] ?></td>
                                        <td><?= date('d M Y H:i', strtotime($item['last_updated'])) ?></td>
                                        <td>
                                            <span class="badge <?php 
                                                if ($item['quantity'] < 10) {
                                                    echo 'bg-danger';
                                                } elseif ($item['quantity'] < 50) {
                                                    echo 'bg-warning';
                                                } else {
                                                    echo 'bg-success';
                                                }
                                            ?>">
                                                <?php 
                                                if ($item['quantity'] < 10) {
                                                    echo 'Critical';
                                                } elseif ($item['quantity'] < 50) {
                                                    echo 'Low';
                                                } else {
                                                    echo 'Sufficient';
                                                }
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="inventory_update.php?id=<?= $item['product_id'] ?>" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </div>
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

    <style>
        .card {
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .table-responsive {
            overflow-x: auto;
        }

        .table thead th {
            vertical-align: middle;
            white-space: nowrap;
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>