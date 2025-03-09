<?php
include '../components/connect.php';

session_start();

$manager_id = $_SESSION['manager_id'];

if(!isset($manager_id)){
   header('location:index.php');
}

// Get date range from request, default to last 30 days
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Get total sales
$total_sales = $conn->prepare("
    SELECT SUM(total_price) as total 
    FROM `orders` 
    WHERE payment_status = 'completed' 
    AND DATE(placed_on) BETWEEN ? AND ?
");
$total_sales->execute([$start_date, $end_date]);
$total = $total_sales->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Get total orders
$total_orders = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM `orders` 
    WHERE payment_status = 'completed' 
    AND DATE(placed_on) BETWEEN ? AND ?
");
$total_orders->execute([$start_date, $end_date]);
$orders_count = $total_orders->fetch(PDO::FETCH_ASSOC)['count'];

// Get average order value
$avg_order = $orders_count > 0 ? $total / $orders_count : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Keens | Sales Trends</title>

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
   <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<?php include '../components/product_manager_header.php'; ?>

<section class="sales-trends">
    <div class="container-fluid px-4 py-5">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-0">Sales Trends</h2>
                <p class="text-muted mb-0">Analyze your product performance</p>
            </div>
            
            <!-- Date Range Filter -->
            <form class="d-flex gap-2" method="GET">
                <input type="date" name="start_date" class="form-control" value="<?= $start_date ?>">
                <input type="date" name="end_date" class="form-control" value="<?= $end_date ?>">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter me-2"></i>Filter
                </button>
            </form>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-4 mb-4">
            <!-- Total Sales -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="icon-wrapper bg-primary bg-opacity-10 text-primary">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                            <div class="ms-3">
                                <h6 class="text-muted mb-0">Total Sales</h6>
                                <h3 class="mb-0">$<?= number_format($total, 2) ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Orders -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="icon-wrapper bg-success bg-opacity-10 text-success">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <div class="ms-3">
                                <h6 class="text-muted mb-0">Total Orders</h6>
                                <h3 class="mb-0"><?= number_format($orders_count) ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Average Order Value -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="icon-wrapper bg-info bg-opacity-10 text-info">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="ms-3">
                                <h6 class="text-muted mb-0">Avg. Order Value</h6>
                                <h3 class="mb-0">$<?= number_format($avg_order, 2) ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sales Trend Chart -->
        <div class="row g-4 mb-4">
            <div class="col-12 col-xl-8">
                <div class="card border-0 shadow-sm" style="height: 500px;">
                    <div class="card-body h-100">
                        <h5 class="card-title mb-4">Sales Trend</h5>
                        <canvas id="salesTrendChart" class="w-100 h-100"></canvas>
                    </div>
                </div>
            </div>

            <!-- Top Products -->
            <div class="col-12 col-xl-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Top Products</h5>
                        <?php
                            $top_products = $conn->prepare("
                            SELECT p.name, COUNT(o.id) as order_count, SUM(o.total_price) as total_sales
                            FROM `products` p
                            LEFT JOIN `orders` o ON o.total_products LIKE CONCAT('%', p.name, '%')
                            WHERE o.payment_status = 'completed'
                            AND DATE(o.placed_on) BETWEEN ? AND ?
                            GROUP BY p.id
                            ORDER BY total_sales DESC
                            LIMIT 5
                        ");
                            $top_products->execute([$start_date, $end_date]);
                            while($product = $top_products->fetch(PDO::FETCH_ASSOC)){
                        ?>
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-grow-1">
                                <h6 class="mb-1"><?= $product['name'] ?></h6>
                                <small class="text-muted"><?= $product['order_count'] ?> orders</small>
                            </div>
                            <span class="badge bg-primary-subtle text-primary">
                                $<?= number_format($product['total_sales'], 2) ?>
                            </span>
                        </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Daily Sales Table -->
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="card-title mb-4">Daily Sales</h5>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Orders</th>
                                <th>Sales</th>
                                <th>Avg. Order Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                $daily_sales = $conn->prepare("
                                    SELECT 
                                        DATE(placed_on) as sale_date,
                                        COUNT(*) as orders,
                                        SUM(total_price) as total_sales
                                    FROM `orders`
                                    WHERE payment_status = 'completed'
                                    AND DATE(placed_on) BETWEEN ? AND ?
                                    GROUP BY DATE(placed_on)
                                    ORDER BY sale_date DESC
                                ");
                                $daily_sales->execute([$start_date, $end_date]);
                                while($day = $daily_sales->fetch(PDO::FETCH_ASSOC)){
                                    $day_avg = $day['orders'] > 0 ? $day['total_sales'] / $day['orders'] : 0;
                            ?>
                            <tr>
                                <td><?= date('M d, Y', strtotime($day['sale_date'])) ?></td>
                                <td><?= number_format($day['orders']) ?></td>
                                <td>$<?= number_format($day['total_sales'], 2) ?></td>
                                <td>$<?= number_format($day_avg, 2) ?></td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
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
    --success-color: #2ecc71;
    --warning-color: #f39c12;
    --danger-color: #dc3545;
}

/* Card Styles */
.card {
    border-radius: 15px;
    overflow: hidden;
}

/* Icon Wrapper */
.icon-wrapper {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
}

/* Form Controls */
.form-control {
    padding: 0.75rem 1rem;
    border-radius: 8px;
    border: 1px solid #e9ecef;
}

.form-control:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
}

/* Button Styles */
.btn-primary {
    background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
    border: none;
    padding: 0.75rem 1.5rem;
    font-weight: 500;
}

/* Table Styles */
.table th {
    font-weight: 600;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.table td {
    font-size: 0.9rem;
    vertical-align: middle;
}

/* Badge Styles */
.badge {
    font-weight: 500;
    padding: 0.5em 1em;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .container-fluid {
        padding: 1rem !important;
    }

    .card-body {
        padding: 1rem;
    }

    .icon-wrapper {
        width: 40px;
        height: 40px;
        font-size: 1rem;
    }
}
</style>

<script>
// Get sales trend data
<?php
    $trend_data = $conn->prepare("
        SELECT 
            DATE(placed_on) as sale_date,
            SUM(total_price) as total_sales
        FROM `orders`
        WHERE payment_status = 'completed'
        AND DATE(placed_on) BETWEEN ? AND ?
        GROUP BY DATE(placed_on)
        ORDER BY sale_date
    ");
    $trend_data->execute([$start_date, $end_date]);
    $dates = [];
    $sales = [];
    while($trend = $trend_data->fetch(PDO::FETCH_ASSOC)){
        $dates[] = date('M d', strtotime($trend['sale_date']));
        $sales[] = $trend['total_sales'];
    }
?>

// Initialize sales trend chart
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('salesTrendChart').getContext('2d');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($dates) ?>,
            datasets: [{
                label: 'Daily Sales',
                data: <?= json_encode($sales) ?>,
                borderColor: '#4361ee',
                backgroundColor: 'rgba(67, 97, 238, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: {
                padding: {
                    top: 10,
                    bottom: 10,
                    left: 10,
                    right: 10
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
});

// Handle date range validation
document.querySelector('form').addEventListener('submit', function(e) {
    const startDate = new Date(document.querySelector('input[name="start_date"]').value);
    const endDate = new Date(document.querySelector('input[name="end_date"]').value);
    
    if(startDate > endDate) {
        e.preventDefault();
        alert('Start date cannot be after end date');
    }
});
</script>

</body>
</html>