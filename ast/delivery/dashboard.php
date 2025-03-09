<?php
include '../components/connect.php';

session_start();

// Check if delivery agent is logged in
if (!isset($_SESSION['delivery_agent_id'])) {
    header('location:index.php');
    exit();
}

$delivery_agent_id = $_SESSION['delivery_agent_id'];

// Fetch ongoing delivery
$ongoing_delivery = $conn->prepare("
    SELECT da.*, o.* 
    FROM `delivery_assignments` da
    JOIN `orders` o ON da.order_id = o.id
    WHERE da.delivery_agent_id = ? 
    AND da.status IN ('pending', 'picked_up')
    LIMIT 1
");
$ongoing_delivery->execute([$delivery_agent_id]);
$current_delivery = $ongoing_delivery->fetch(PDO::FETCH_ASSOC);

// Fetch delivery statistics
$stats_query = $conn->prepare("
    SELECT 
        COUNT(*) as total_deliveries,
        SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as completed_deliveries,
        SUM(CASE WHEN status = 'pending' OR status = 'picked_up' THEN 1 ELSE 0 END) as ongoing_deliveries,
        AVG(TIMESTAMPDIFF(HOUR, picked_up_at, delivered_at)) as avg_delivery_time
    FROM `delivery_assignments`
    WHERE delivery_agent_id = ?
");
$stats_query->execute([$delivery_agent_id]);
$stats = $stats_query->fetch(PDO::FETCH_ASSOC);

// Fetch recent deliveries
$recent_deliveries = $conn->prepare("
    SELECT da.*, o.*
    FROM `delivery_assignments` da
    JOIN `orders` o ON da.order_id = o.id
    WHERE da.delivery_agent_id = ?
    AND da.status = 'delivered'
    ORDER BY da.delivered_at DESC
    LIMIT 5
");
$recent_deliveries->execute([$delivery_agent_id]);

// Fetch delivery agent info
$agent_info = $conn->prepare("SELECT * FROM `delivery_agents` WHERE id = ?");
$agent_info->execute([$delivery_agent_id]);
$agent_details = $agent_info->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keens | Delivery Dashboard</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
</head>
<body>

<?php 
// Include delivery agent header 
include '../components/delivery_header.php'; 
?>

<section class="delivery-dashboard mt-5">
    <div class="container-fluid">
        <div class="row">
            <!-- Welcome Card -->
            <div class="col-12 mb-4">
                <div class="welcome-card">
                    <div class="welcome-content">
                        <div class="welcome-icon">
                            <i class="fas fa-truck"></i>
                        </div>
                        <div class="welcome-text">
                            <h2>Welcome, <?= htmlspecialchars($agent_details['name']) ?></h2>
                            <p>Delivery Agent Dashboard</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ongoing Delivery Card -->
            <div class="col-lg-4 mb-4">
                <div class="content-card ongoing-delivery-card">
                    <div class="card-header">
                        <div class="header-icon">
                            <i class="fas fa-shipping-fast"></i>
                        </div>
                        <h3 class="header-title">Current Delivery</h3>
                    </div>
                    
                    <?php if ($current_delivery): ?>
                        <div class="ongoing-delivery-details">
                            <div class="delivery-info">
                                <span class="info-item">
                                    <i class="fas fa-box"></i> 
                                    Order #<?= $current_delivery['id'] ?>
                                </span>
                                <span class="info-item">
                                    <i class="fas fa-dollar-sign"></i> 
                                    Total: $<?= number_format($current_delivery['total_price'], 2) ?>
                                </span>
                                <span class="info-item">
                                    <i class="fas fa-calendar"></i> 
                                    Picked Up: <?= date('d M Y H:i', strtotime($current_delivery['picked_up_at'])) ?>
                                </span>
                            </div>
                            <a href="../delivery/pickup.php" class="btn btn-primary mt-3">
                                Manage Delivery
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-truck"></i>
                            <p>No ongoing deliveries</p>
                            <a href="../delivery/pickup.php" class="btn btn-primary mt-3">
                                Pick Up a New Order
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="col-lg-8">
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="stats-card">
                            <div class="stats-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stats-content">
                                <h4><?= $stats['completed_deliveries'] ?? 0 ?></h4>
                                <p>Completed Deliveries</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="stats-card">
                            <div class="stats-icon">
                                <i class="fas fa-truck-loading"></i>
                            </div>
                            <div class="stats-content">
                                <h4><?= $stats['ongoing_deliveries'] ?? 0 ?></h4>
                                <p>Ongoing Deliveries</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="stats-card">
                            <div class="stats-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stats-content">
                                <h4><?= number_format($stats['avg_delivery_time'] ?? 0, 1) ?> hrs</h4>
                                <p>Avg. Delivery Time</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Deliveries -->
            <div class="col-12">
                <div class="content-card recent-deliveries-card">
                    <div class="card-header">
                        <div class="header-icon">
                            <i class="fas fa-history"></i>
                        </div>
                        <h3 class="header-title">Recent Deliveries</h3>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table modern-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Total Price</th>
                                    <th>Delivery Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($recent_deliveries->rowCount() > 0): ?>
                                    <?php while($delivery = $recent_deliveries->fetch(PDO::FETCH_ASSOC)): ?>
                                        <tr>
                                            <td>#<?= $delivery['id'] ?></td>
                                            <td>$<?= number_format($delivery['total_price'], 2) ?></td>
                                            <td><?= date('d M Y H:i', strtotime($delivery['delivered_at'])) ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center">No recent deliveries</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
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
        --danger-color: #dc3545;
        --warning-color: #f39c12;
    }

    body {
        background-color: #f4f6f9;
    }

    /* Welcome Card */
    .welcome-card {
        background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
        color: white;
        border-radius: 20px;
        padding: 1.5rem;
        box-shadow: 0 10px 30px rgba(67, 97, 238, 0.2);
    }

    .welcome-content {
        display: flex;
        align-items: center;
        gap: 1.5rem;
    }

    .welcome-icon {
        background: rgba(255,255,255,0.2);
        width: 80px;
        height: 80px;
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
    }

    .welcome-text h2 {
        margin-bottom: 0.5rem;
        font-weight: 700;
    }

    /* Content Card */
    .content-card {
        background: white;
        border-radius: 20px;
        padding: 1.5rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
    }

    .card-header {
        display: flex;
        align-items: center;
        margin-bottom: 1.5rem;
        gap: 1rem;
    }

    .header-icon {
        width: 50px;
        height: 50px;
        border-radius: 15px;
        background: linear-gradient(135deg, rgba(67, 97, 238, 0.1), rgba(72, 149, 239, 0.1));
        color: var(--primary-color);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .header-title {
        margin: 0;
        font-size: 1.2rem;
        font-weight: 600;
        color: #2b3452;
    }

    /* Ongoing Delivery Card */
    .ongoing-delivery-card .ongoing-delivery-details,
    .ongoing-delivery-card .empty-state {
        text-align: center;
    }

    .ongoing-delivery-card .info-item {
        display: block;
        margin-bottom: 0.5rem;
        color: #495057;
    }

    .ongoing-delivery-card .info-item i {
        color: var(--primary-color);
        margin-right: 0.5rem;
    }

    /* Stats Card */
    .stats-card {
        background: white;
        border-radius: 20px;
        padding: 1.5rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
    }

    .stats-icon {
        background: linear-gradient(135deg, rgba(67, 97, 238, 0.1), rgba(72, 149, 239, 0.1));
        color: var(--primary-color);
        width: 60px;
        height: 60px;
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }

    .stats-content h4 {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 700;
        color: #2b3452;
    }

    .stats-content p {
        margin: 0;
        color: #6c757d;
        font-size: 0.9rem;
    }

    /* Recent Deliveries */
    .modern-table thead th {
        background-color: #f8f9fa;
        color: #495057;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.8rem;
    }

    .modern-table tbody tr:hover {
        background-color: #f8f9fa;
    }

    /* Responsive Adjustments */
    @media (max-width: 768px) {
        .welcome-content {
            flex-direction: column;
            text-align: center;
        }

        .stats-card {
            flex-direction: column;
            text-align: center;
        }

        .stats-content {
            margin-top: 1rem;
        }
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>