<?php

include '../components/connect.php';

session_start();

$manager_id = $_SESSION['manager_id'];

if(!isset($manager_id)){
   header('location:index.php');
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Keens | Product Manager Dashboard</title>

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<?php include '../components/product_manager_header.php'; ?>

<section class="dashboard">
<div class="container-fluid">
    <div class="row">
        <!-- Main Content -->
        <main class="col px-4 py-5">
            <!-- Welcome Banner -->
            <div class="welcome-banner mb-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="fw-bold mb-2">Welcome back, <?= $fetch_profile['name']; ?>! 👋</h2>
                        <p class="text-muted mb-0">Here's your product management overview.</p>
                    </div>
                    <a href="add_product.php" class="btn btn-glass">
                        <i class="fas fa-plus me-2"></i>Add New Product
                    </a>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row g-4 mb-4">
                <!-- Low Stock Products -->
                <div class="col-12 col-sm-6 col-xl-3">
                    <?php
                        $select_low_stock = $conn->prepare("SELECT COUNT(*) as count FROM `inventory` WHERE quantity < 10");
                        $select_low_stock->execute();
                        $low_stock_count = $select_low_stock->fetch(PDO::FETCH_ASSOC)['count'];
                    ?>
                    <div class="stat-card pending">
                        <div class="card-content">
                            <div class="icon-wrapper">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div class="stat-details">
                                <p class="stat-label">Low Stock Items</p>
                                <h3 class="stat-value"><?= number_format($low_stock_count); ?></h3>
                            </div>
                            <a href="low_stock.php" class="stat-link">
                                View Details
                                <i class="fas fa-arrow-right ms-2"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Total Categories -->
                <div class="col-12 col-sm-6 col-xl-3">
                    <?php
                        $select_categories = $conn->prepare("SELECT COUNT(*) as count FROM `category`");
                        $select_categories->execute();
                        $category_count = $select_categories->fetch(PDO::FETCH_ASSOC)['count'];
                    ?>
                    <div class="stat-card completed">
                        <div class="card-content">
                            <div class="icon-wrapper">
                                <i class="fas fa-tags"></i>
                            </div>
                            <div class="stat-details">
                                <p class="stat-label">Product Categories</p>
                                <h3 class="stat-value"><?= number_format($category_count); ?></h3>
                            </div>
                            <a href="categories.php" class="stat-link">
                                Manage Categories
                                <i class="fas fa-arrow-right ms-2"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Active Products -->
                <div class="col-12 col-sm-6 col-xl-3">
                    <?php
                        $select_products = $conn->prepare("SELECT COUNT(*) as count FROM `products`");
                        $select_products->execute();
                        $product_count = $select_products->fetch(PDO::FETCH_ASSOC)['count'];
                    ?>
                    <div class="stat-card products">
                        <div class="card-content">
                            <div class="icon-wrapper">
                                <i class="fas fa-box"></i>
                            </div>
                            <div class="stat-details">
                                <p class="stat-label">Active Products</p>
                                <h3 class="stat-value"><?= number_format($product_count); ?></h3>
                            </div>
                            <a href="products.php" class="stat-link">
                                View Products
                                <i class="fas fa-arrow-right ms-2"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- New Reviews -->
                <div class="col-12 col-sm-6 col-xl-3">
                    <?php
                        $select_reviews = $conn->prepare("SELECT COUNT(*) as count FROM `reviews` WHERE DATE(created_at) = CURDATE()");
                        $select_reviews->execute();
                        $new_reviews = $select_reviews->fetch(PDO::FETCH_ASSOC)['count'];
                    ?>
                    <div class="stat-card users">
                        <div class="card-content">
                            <div class="icon-wrapper">
                                <i class="fas fa-star"></i>
                            </div>
                            <div class="stat-details">
                                <p class="stat-label">New Reviews Today</p>
                                <h3 class="stat-value"><?= number_format($new_reviews); ?></h3>
                            </div>
                            <a href="reviews.php" class="stat-link">
                                View Reviews
                                <i class="fas fa-arrow-right ms-2"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity Section -->
            <div class="row">
                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body p-4">
                            <h4 class="card-title mb-4">Recent Activity</h4>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Action</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Add your recent activity data here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
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
    --info-color: #3498db;
    --pending-color: #e67e22;
}

/* Welcome Banner */
.welcome-banner {
    background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
    padding: 2rem;
    border-radius: 20px;
    color: white;
    box-shadow: 0 10px 30px rgba(67, 97, 238, 0.15);
}

.btn-glass {
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 12px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-glass:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: translateY(-2px);
    color: white;
}

/* Stat Cards */
.stat-card {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    transition: all 0.3s ease;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
}

.card-content {
    padding: 1.5rem;
    position: relative;
}

.icon-wrapper {
    width: 50px;
    height: 50px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-bottom: 1rem;
}

.stat-details {
    margin-bottom: 1rem;
}

.stat-label {
    color: #6c757d;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
}

.stat-value {
    font-size: 1.8rem;
    font-weight: 700;
    margin: 0;
}

.stat-link {
    display: inline-flex;
    align-items: center;
    text-decoration: none;
    font-weight: 500;
    font-size: 0.9rem;
    transition: transform 0.3s ease;
}

.stat-link:hover {
    transform: translateX(5px);
}

/* Card Variants */
.stat-card.pending .icon-wrapper {
    background: rgba(230, 126, 34, 0.1);
    color: var(--pending-color);
}

.stat-card.pending .stat-link {
    color: var(--pending-color);
}

.stat-card.completed .icon-wrapper {
    background: rgba(46, 204, 113, 0.1);
    color: var(--success-color);
}

.stat-card.completed .stat-link {
    color: var(--success-color);
}

.stat-card.products .icon-wrapper {
    background: rgba(243, 156, 18, 0.1);
    color: var(--warning-color);
}

.stat-card.products .stat-link {
    color: var(--warning-color);
}

.stat-card.users .icon-wrapper {
    background: rgba(52, 152, 219, 0.1);
    color: var(--info-color);
}

.stat-card.users .stat-link {
    color: var(--info-color);
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .welcome-banner {
        padding: 1.5rem;
    }

    .welcome-banner h2 {
        font-size: 1.5rem;
    }

    .stat-card {
        margin-bottom: 1rem;
    }

    .stat-value {
        font-size: 1.5rem;
    }

    .icon-wrapper {
        width: 40px;
        height: 40px;
        font-size: 1.2rem;
    }
}
</style>

</body>
</html>