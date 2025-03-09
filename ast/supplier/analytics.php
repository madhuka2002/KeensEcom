<?php
include '../components/connect.php';

session_start();

if (!isset($_SESSION['supplier_id'])) {
    header('location:index.php');
    exit();
}

$supplier_id = $_SESSION['supplier_id'];

// Total Revenue Calculation
$revenue_query = $conn->prepare("
    SELECT 
        SUM(total_amount) as total_revenue,
        MONTH(order_date) as month,
        YEAR(order_date) as year
    FROM supply_orders 
    WHERE supplier_id = ? AND status = 'completed'
    GROUP BY YEAR(order_date), MONTH(order_date)
    ORDER BY year, month
");
$revenue_query->execute([$supplier_id]);
$monthly_revenue = $revenue_query->fetchAll(PDO::FETCH_ASSOC);

// Product Performance
$product_performance = $conn->prepare("
    SELECT 
        p.name, 
        COUNT(soi.supply_order_item_id) as total_orders,
        SUM(soi.quantity) as total_quantity_sold,
        SUM(soi.subtotal) as total_revenue
    FROM products p
    JOIN supply_order_items soi ON p.id = soi.product_id
    JOIN supply_orders so ON soi.supply_order_id = so.supply_order_id
    WHERE so.supplier_id = ? AND so.status = 'completed'
    GROUP BY p.id
    ORDER BY total_revenue DESC
    LIMIT 10
");
$product_performance->execute([$supplier_id]);

// Order Status Distribution
$order_status = $conn->prepare("
    SELECT 
        status, 
        COUNT(*) as count,
        ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM supply_orders WHERE supplier_id = ?), 2) as percentage
    FROM supply_orders
    WHERE supplier_id = ?
    GROUP BY status
");
$order_status->execute([$supplier_id, $supplier_id]);
$status_distribution = $order_status->fetchAll(PDO::FETCH_ASSOC);

// Inventory Insights
$inventory_insights = $conn->prepare("
    SELECT 
        p.name,
        i.quantity,
        COALESCE(SUM(soi.quantity), 0) as total_sold
    FROM products p
    LEFT JOIN inventory i ON p.id = i.product_id
    LEFT JOIN supply_order_items soi ON p.id = soi.product_id
    LEFT JOIN supply_orders so ON soi.supply_order_id = so.supply_order_id AND so.status = 'completed'
    WHERE i.supplier_id = ?
    GROUP BY p.id
    ORDER BY total_sold DESC
    LIMIT 10
");
$inventory_insights->execute([$supplier_id]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keens | Supplier Analytics</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include '../components/supplier_header.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">
                    <i class="fas fa-chart-bar me-2"></i>Supplier Analytics
                </h1>
            </div>
        </div>

        <div class="row">
            <!-- Monthly Revenue Chart -->
            <div class="col-md-8 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Monthly Revenue</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="monthlyRevenueChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Order Status Distribution -->
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Order Status Distribution</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="orderStatusChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Top Performing Products -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Top Performing Products</h5>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Total Orders</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($product = $product_performance->fetch(PDO::FETCH_ASSOC)): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($product['name']) ?></td>
                                        <td><?= $product['total_orders'] ?></td>
                                        <td>$<?= number_format($product['total_revenue'], 2) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Inventory Insights -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Inventory Insights</h5>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Current Stock</th>
                                    <th>Total Sold</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($inventory = $inventory_insights->fetch(PDO::FETCH_ASSOC)): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($inventory['name']) ?></td>
                                        <td><?= $inventory['quantity'] ?></td>
                                        <td><?= $inventory['total_sold'] ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Monthly Revenue Chart
        const monthlyRevenueCtx = document.getElementById('monthlyRevenueChart').getContext('2d');
        new Chart(monthlyRevenueCtx, {
            type: 'bar',
            data: {
                labels: [
                    <?php 
                    foreach ($monthly_revenue as $revenue) {
                        echo '"' . date('M Y', mktime(0, 0, 0, $revenue['month'], 1, $revenue['year'])) . '", ';
                    }
                    ?>
                ],
                datasets: [{
                    label: 'Monthly Revenue',
                    data: [
                        <?php 
                        foreach ($monthly_revenue as $revenue) {
                            echo $revenue['total_revenue'] . ', ';
                        }
                        ?>
                    ],
                    backgroundColor: 'rgba(67, 97, 238, 0.6)',
                    borderColor: 'rgba(67, 97, 238, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Order Status Distribution Chart
        const orderStatusCtx = document.getElementById('orderStatusChart').getContext('2d');
        new Chart(orderStatusCtx, {
            type: 'pie',
            data: {
                labels: [
                    <?php 
                    foreach ($status_distribution as $status) {
                        echo '"' . ucfirst($status['status']) . '", ';
                    }
                    ?>
                ],
                datasets: [{
                    data: [
                        <?php 
                        foreach ($status_distribution as $status) {
                            echo $status['percentage'] . ', ';
                        }
                        ?>
                    ],
                    backgroundColor: [
                        'rgba(255, 193, 7, 0.6)',   // Pending
                        'rgba(23, 162, 184, 0.6)',  // In Progress
                        'rgba(40, 167, 69, 0.6)'    // Completed
                    ]
                }]
            },
            options: {
                responsive: true
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>