<?php
include '../components/connect.php';

session_start();

$manager_id = $_SESSION['manager_id'];

if(!isset($manager_id)){
   header('location:index.php');
}

// Get total revenue
$total_revenue = $conn->prepare("SELECT SUM(total_price) as total FROM `orders` WHERE payment_status = 'completed'");
$total_revenue->execute();
$revenue = $total_revenue->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Get total orders
$total_orders = $conn->prepare("SELECT COUNT(*) as count FROM `orders`");
$total_orders->execute();
$orders_count = $total_orders->fetch(PDO::FETCH_ASSOC)['count'];

// Get total products
$total_products = $conn->prepare("SELECT COUNT(*) as count FROM `products`");
$total_products->execute();
$products_count = $total_products->fetch(PDO::FETCH_ASSOC)['count'];

// Get average rating
$avg_rating = $conn->prepare("SELECT AVG(rating) as avg FROM `reviews`");
$avg_rating->execute();
$rating = $avg_rating->fetch(PDO::FETCH_ASSOC)['avg'] ?? 0;

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Keens | Analytics</title>

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<?php include '../components/product_manager_header.php'; ?>

<section class="analytics">
    <div class="container-fluid px-4 py-5">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-0">Analytics Dashboard</h2>
                <p class="text-muted mb-0">Track your product performance metrics</p>
            </div>
            <div class="dropdown">
                <button class="btn btn-light dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="fas fa-calendar me-2"></i>Last 30 Days
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="#">Last 7 Days</a></li>
                    <li><a class="dropdown-item" href="#">Last 30 Days</a></li>
                    <li><a class="dropdown-item" href="#">Last 90 Days</a></li>
                    <li><a class="dropdown-item" href="#">Last Year</a></li>
                </ul>
            </div>
        </div>

        <!-- Overview Cards -->
        <div class="row g-4 mb-4">
            <!-- Revenue -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="icon-wrapper bg-primary bg-opacity-10 text-primary">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                            <div class="ms-3">
                                <p class="text-muted mb-0">Total Revenue</p>
                                <h3 class="mb-0">$<?= number_format($revenue, 2); ?></h3>
                            </div>
                        </div>
                        <div class="d-flex align-items-center">
                            <span class="badge bg-success-subtle text-success me-2">
                                <i class="fas fa-arrow-up me-1"></i>8.5%
                            </span>
                            <small class="text-muted">vs last period</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Orders -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="icon-wrapper bg-warning bg-opacity-10 text-warning">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <div class="ms-3">
                                <p class="text-muted mb-0">Total Orders</p>
                                <h3 class="mb-0"><?= number_format($orders_count); ?></h3>
                            </div>
                        </div>
                        <div class="d-flex align-items-center">
                            <span class="badge bg-success-subtle text-success me-2">
                                <i class="fas fa-arrow-up me-1"></i>12.4%
                            </span>
                            <small class="text-muted">vs last period</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Products -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="icon-wrapper bg-info bg-opacity-10 text-info">
                                <i class="fas fa-box"></i>
                            </div>
                            <div class="ms-3">
                                <p class="text-muted mb-0">Active Products</p>
                                <h3 class="mb-0"><?= number_format($products_count); ?></h3>
                            </div>
                        </div>
                        <div class="d-flex align-items-center">
                            <span class="badge bg-danger-subtle text-danger me-2">
                                <i class="fas fa-arrow-down me-1"></i>3.2%
                            </span>
                            <small class="text-muted">vs last period</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rating -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="icon-wrapper bg-success bg-opacity-10 text-success">
                                <i class="fas fa-star"></i>
                            </div>
                            <div class="ms-3">
                                <p class="text-muted mb-0">Average Rating</p>
                                <h3 class="mb-0"><?= number_format($rating, 1); ?></h3>
                            </div>
                        </div>
                        <div class="d-flex align-items-center">
                            <span class="badge bg-success-subtle text-success me-2">
                                <i class="fas fa-arrow-up me-1"></i>1.8%
                            </span>
                            <small class="text-muted">vs last period</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row g-4 mb-4">
            <!-- Sales Trend -->
            <div class="col-12 col-xl-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="card-title mb-0">Sales Trend</h5>
                            <div class="btn-group">
                                <button class="btn btn-light btn-sm active">Daily</button>
                                <button class="btn btn-light btn-sm">Weekly</button>
                                <button class="btn btn-light btn-sm">Monthly</button>
                            </div>
                        </div>
                        <div id="salesChart" style="height: 300px;">
                            <!-- Chart will be rendered here -->
                        </div>
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
                                SELECT p.name, p.price, COUNT(o.id) as order_count 
                                FROM products p 
                                LEFT JOIN orders o ON FIND_IN_SET(p.id, o.total_products) 
                                GROUP BY p.id 
                                ORDER BY order_count DESC 
                                LIMIT 5
                            ");
                            $top_products->execute();
                            while($product = $top_products->fetch(PDO::FETCH_ASSOC)){
                        ?>
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-grow-1">
                                <h6 class="mb-1"><?= $product['name']; ?></h6>
                                <small class="text-muted"><?= $product['order_count']; ?> orders</small>
                            </div>
                            <span class="badge bg-primary-subtle text-primary">
                                $<?= number_format($product['price'], 2); ?>
                            </span>
                        </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Reviews -->
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="card-title mb-0">Recent Reviews</h5>
                    <a href="reviews.php" class="btn btn-light btn-sm">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Rating</th>
                                <th>Review</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                $recent_reviews = $conn->prepare("
                                    SELECT r.*, p.name as product_name 
                                    FROM reviews r 
                                    JOIN products p ON r.product_id = p.id 
                                    ORDER BY r.created_at DESC 
                                    LIMIT 5
                                ");
                                $recent_reviews->execute();
                                while($review = $recent_reviews->fetch(PDO::FETCH_ASSOC)){
                            ?>
                            <tr>
                                <td><?= $review['product_name']; ?></td>
                                <td>
                                    <div class="rating text-warning">
                                        <?php
                                            for($i = 1; $i <= 5; $i++){
                                                if($i <= $review['rating']){
                                                    echo '<i class="fas fa-star"></i>';
                                                } else {
                                                    echo '<i class="far fa-star"></i>';
                                                }
                                            }
                                        ?>
                                    </div>
                                </td>
                                <td><?= substr($review['review_text'], 0, 100); ?>...</td>
                                <td><small class="text-muted"><?= date('M d, Y', strtotime($review['created_at'])); ?></small></td>
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

/* Table Styles */
.table {
    margin-bottom: 0;
}

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

/* Rating Stars */
.rating {
    font-size: 0.875rem;
}

/* Badge Styles */
.badge {
    font-weight: 500;
    padding: 0.5em 1em;
}

/* Button Group */
.btn-group .btn {
    border: 1px solid #eee;
}

.btn-group .btn.active {
    background-color: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
}

/* Chart Container */
#salesChart {
    width: 100%;
    background: #fff;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .container-fluid {
        padding: 1rem !important;
    }
    
    .icon-wrapper {
        width: 40px;
        height: 40px;
        font-size: 1rem;
    }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/echarts@5.4.3/dist/echarts.min.js"></script>
<script>
// Initialize charts after the page loads
document.addEventListener('DOMContentLoaded', function() {
    // Sample data - replace with real data from your database
    const salesData = {
        dates: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
        values: [820, 932, 901, 934, 1290, 1330, 1320]
    };

    // Initialize sales chart
    const salesChart = echarts.init(document.getElementById('salesChart'));
    
    // Chart options
    const option = {
        tooltip: {
            trigger: 'axis'
        },
        grid: {
            left: '3%',
            right: '4%',
            bottom: '3%',
            containLabel: true
        },
        xAxis: {
            type: 'category',
            boundaryGap: false,
            data: salesData.dates,
            axisLine: {
                lineStyle: {
                    color: '#eee'
                }
            }
        },
        yAxis: {
            type: 'value',
            axisLine: {
                lineStyle: {
                    color: '#eee'
                }
            },
            splitLine: {
                lineStyle: {
                    color: '#f5f5f5'
                }
            }
        },
        series: [{
            name: 'Sales',
            type: 'line',
            smooth: true,
            data: salesData.values,
            areaStyle: {
                color: {
                    type: 'linear',
                    x: 0,
                    y: 0,
                    x2: 0,
                    y2: 1,
                    colorStops: [{
                        offset: 0,
                        color: 'rgba(67, 97, 238, 0.3)'
                    }, {
                        offset: 1,
                        color: 'rgba(67, 97, 238, 0.1)'
                    }]
                }
            },
            itemStyle: {
                color: '#4361ee'
            },
            symbolSize: 8
        }]
    };

    // Set chart options
    salesChart.setOption(option);

    // Handle window resize
    window.addEventListener('resize', function() {
        salesChart.resize();
    });

    // Initialize other charts or data visualizations here
});
</script>

<!-- Add any additional scripts needed -->
<script>
// Utility function to format numbers
function formatNumber(num) {
    return num.toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,')
}

// Function to update charts and statistics
function updateAnalytics(period) {
    // Add AJAX call to fetch new data based on selected period
    // Update charts and statistics accordingly
}

// Initialize tooltips
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl)
});
</script>

</body>
</html>