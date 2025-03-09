<?php
include '../components/connect.php';

session_start();

$staff_id = $_SESSION['staff_id'];

if(!isset($staff_id)){
   header('location:index.php');
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Keens | Warehouse Dashboard</title>

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<?php include '../components/warehouse_staff_header.php'; ?>

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
                        <p class="text-muted mb-0">Current Shift: <?= $fetch_profile['shift']; ?></p>
                    </div>
                    <a href="profile.php" class="btn btn-glass">
                        <i class="fas fa-user-edit me-2"></i>Update Profile
                    </a>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row g-4 mb-4">
                <!-- Pending Orders -->
                <div class="col-12 col-sm-6 col-xl-3">
                    <?php
                        $select_pending = $conn->prepare("SELECT COUNT(*) as count FROM `orders` WHERE order_status = ?");
                        $select_pending->execute(['pending']);
                        $pending_count = $select_pending->fetch(PDO::FETCH_ASSOC)['count'];
                    ?>
                    <div class="stat-card pending">
                        <div class="card-content">
                            <div class="icon-wrapper">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-details">
                                <p class="stat-label">Orders to Pick</p>
                                <h3 class="stat-value"><?= $pending_count; ?></h3>
                            </div>
                            <a href="pick_pack.php" class="stat-link">
                                Start Picking
                                <i class="fas fa-arrow-right ms-2"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Ready for Delivery -->
                <div class="col-12 col-sm-6 col-xl-3">
                    <?php
                        $select_ready = $conn->prepare("SELECT COUNT(*) as count FROM `orders` WHERE order_status = ?");
                        $select_ready->execute(['picked_up']);
                        $ready_count = $select_ready->fetch(PDO::FETCH_ASSOC)['count'];
                    ?>
                    <div class="stat-card completed">
                        <div class="card-content">
                            <div class="icon-wrapper">
                                <i class="fas fa-box-check"></i>
                            </div>
                            <div class="stat-details">
                                <p class="stat-label">Ready for Delivery</p>
                                <h3 class="stat-value"><?= $ready_count; ?></h3>
                            </div>
                            <a href="delivery.php" class="stat-link">
                                View Details
                                <i class="fas fa-arrow-right ms-2"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Low Stock Items -->
                <div class="col-12 col-sm-6 col-xl-3">
                    <?php
                        $select_low_stock = $conn->prepare("SELECT COUNT(*) as count FROM `inventory` WHERE quantity <= 10");
                        $select_low_stock->execute();
                        $low_stock_count = $select_low_stock->fetch(PDO::FETCH_ASSOC)['count'];
                    ?>
                    <div class="stat-card products">
                        <div class="card-content">
                            <div class="icon-wrapper">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div class="stat-details">
                                <p class="stat-label">Low Stock Items</p>
                                <h3 class="stat-value"><?= $low_stock_count; ?></h3>
                            </div>
                            <a href="inventory.php" class="stat-link">
                                Check Inventory
                                <i class="fas fa-arrow-right ms-2"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Returns Pending -->
                <div class="col-12 col-sm-6 col-xl-3">
                    <?php
                        $returns_count = 0;
                        try {
                            $select_returns = $conn->prepare("SELECT COUNT(*) as count FROM `return_request` WHERE status = ?");
                            $select_returns->execute(['pending']);
                            $returns_count = $select_returns->fetch(PDO::FETCH_ASSOC)['count'];
                        } catch(PDOException $e) {
                            // Handle the error gracefully
                            error_log("Error fetching returns: " . $e->getMessage());
                        }
                    ?>
                    <div class="stat-card users">
                        <div class="card-content">
                            <div class="icon-wrapper">
                                <i class="fas fa-undo-alt"></i>
                            </div>
                            <div class="stat-details">
                                <p class="stat-label">Returns Pending</p>
                                <h3 class="stat-value"><?= $returns_count; ?></h3>
                            </div>
                            <a href="returns.php" class="stat-link">
                                Process Returns
                                <i class="fas fa-arrow-right ms-2"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Tasks Section -->
            <div class="row">
                <div class="col-12">
                    <div class="card border-0 rounded-4 shadow-sm">
                        <div class="card-body p-4">
                            <h4 class="card-title mb-4">Recent Tasks</h4>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Order ID</th>
                                            <th>Type</th>
                                            <th>Status</th>
                                            <th>Time</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                            $select_orders = $conn->prepare("SELECT * FROM `orders` WHERE order_status = ? OR order_status = ? ORDER BY id DESC LIMIT 5");
                                            $select_orders->execute(['pending', 'picked_up']);
                                            if($select_orders->rowCount() > 0){
                                                while($fetch_order = $select_orders->fetch(PDO::FETCH_ASSOC)){
                                        ?>
                                        <tr>
                                            <td>#<?= $fetch_order['id']; ?></td>
                                            <td>
                                                <span class="badge bg-primary">Order</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $fetch_order['order_status'] == 'pending' ? 'warning' : 'success' ?>">
                                                    <?= ucfirst($fetch_order['order_status']); ?>
                                                </span>
                                            </td>
                                            <td><?= $fetch_order['placed_on']; ?></td>
                                            <td>
                                                <a href="order_details.php?id=<?= $fetch_order['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    View Details
                                                </a>
                                            </td>
                                        </tr>
                                        <?php
                                                }
                                            }
                                        ?>
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