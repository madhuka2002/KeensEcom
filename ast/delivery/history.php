<?php
include '../components/connect.php';

session_start();

// Check if delivery agent is logged in
if (!isset($_SESSION['delivery_agent_id'])) {
    header('location:index.php');
    exit();
}

$delivery_agent_id = $_SESSION['delivery_agent_id'];

// Fetch delivery history
$delivery_history = $conn->prepare("
    SELECT da.*, o.*, da.status as delivery_status 
    FROM `delivery_assignments` da
    JOIN `orders` o ON da.order_id = o.id
    WHERE da.delivery_agent_id = ?
    AND da.status = 'delivered'
    ORDER BY da.delivered_at DESC
");
$delivery_history->execute([$delivery_agent_id]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keens | Delivery History</title>
    
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

<section class="delivery-history mt-5">
    <div class="content-card">
        <div class="card-header">
            <div class="header-icon">
                <i class="fas fa-history"></i>
            </div>
            <h2 class="header-title">Delivery History</h2>
            <p class="text-muted">View your completed deliveries</p>
        </div>

        <div class="table-container">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Order Details</th>
                        <th>Delivery Information</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($delivery_history->rowCount() > 0): ?>
                        <?php while($delivery = $delivery_history->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr>
                                <td>
                                    <span class="id-badge">#<?= $delivery['id'] ?></span>
                                </td>
                                <td>
                                    <div class="order-details">
                                        <span class="total-products">
                                            <i class="fas fa-box"></i> 
                                            Total Products: <?= $delivery['total_products'] ?>
                                        </span>
                                        <span class="order-price">
                                            <i class="fas fa-dollar-sign"></i> 
                                            Total Price: $<?= number_format($delivery['total_price'], 2) ?>
                                        </span>
                                        <span class="order-date">
                                            <i class="fas fa-calendar"></i> 
                                            Placed On: <?= date('d M Y', strtotime($delivery['placed_on'])) ?>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <div class="delivery-info">
                                        <span class="pickup-date">
                                            <i class="fas fa-truck-pickup"></i>
                                            Picked Up: <?= date('d M Y H:i', strtotime($delivery['picked_up_at'])) ?>
                                        </span>
                                        <span class="delivery-date">
                                            <i class="fas fa-check-circle"></i>
                                            Delivered: <?= date('d M Y H:i', strtotime($delivery['delivered_at'])) ?>
                                        </span>
                                        <span class="delivery-status">
                                            <i class="fas fa-info-circle"></i>
                                            Status: <span class="badge bg-success">Delivered</span>
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="empty-state">
                                <i class="fas fa-truck"></i>
                                <p>No delivery history available</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
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

    /* Card Styles */
    .content-card {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        margin: 2rem auto;
        max-width: 1200px;
    }

    .card-header {
        text-align: center;
        margin-bottom: 2rem;
    }

    .header-icon {
        width: 60px;
        height: 60px;
        border-radius: 15px;
        background: linear-gradient(135deg, rgba(67, 97, 238, 0.1), rgba(72, 149, 239, 0.1));
        color: var(--primary-color);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin: 0 auto 1rem;
    }

    .header-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #2b3452;
        margin-bottom: 0.5rem;
    }

    /* Table Styles */
    .table-container {
        overflow-x: auto;
        border-radius: 8px;
        background: white;
    }

    .modern-table {
        width: 100%;
        border-collapse: collapse;
        margin: 0;
        padding: 0;
        background-color: white;
    }

    .modern-table thead tr {
        background-color: #f8f9fa;
        border-bottom: 2px solid #e9ecef;
    }

    .modern-table th {
        padding: 16px;
        text-align: left;
        font-weight: 600;
        color: #495057;
        text-transform: uppercase;
        font-size: 13px;
        letter-spacing: 0.5px;
    }

    .modern-table td {
        padding: 16px;
        vertical-align: middle;
        border-bottom: 1px solid #e9ecef;
    }

    .modern-table tbody tr:hover {
        background-color: #f8f9fa;
    }

    /* ID Badge */
    .id-badge {
        background: #e8f3ff;
        color: #0d6efd;
        padding: 6px 12px;
        border-radius: 6px;
        font-weight: 500;
        font-size: 14px;
    }

    /* Order Details */
    .order-details, .delivery-info {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .total-products, .order-price, .order-date,
    .pickup-date, .delivery-date, .delivery-status {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        color: #495057;
    }

    .total-products i, .order-price i, .order-date i,
    .pickup-date i, .delivery-date i, .delivery-status i {
        color: var(--primary-color);
        width: 16px;
        text-align: center;
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 48px !important;
        color: #6c757d;
    }

    .empty-state i {
        font-size: 32px;
        margin-bottom: 16px;
        color: #dee2e6;
    }

    /* Delivery Status Badge */
    .delivery-status .badge {
        font-weight: 500;
        padding: 0.35em 0.65em;
    }

    /* Responsive Adjustments */
    @media (max-width: 768px) {
        .content-card {
            padding: 1rem;
            margin: 1rem;
        }

        .modern-table {
            font-size: 14px;
        }

        .order-details, .delivery-info {
            gap: 4px;
        }
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>