<?php
include '../components/connect.php';

session_start();

// Check if delivery agent is logged in
if (!isset($_SESSION['delivery_agent_id'])) {
    header('location:index.php');
    exit();
}

$delivery_agent_id = $_SESSION['delivery_agent_id'];

// Handle complete delivery
if (isset($_POST['complete_delivery'])) {
    try {
        // Begin transaction
        $conn->beginTransaction();

        // Update delivery assignment status
        $complete_assignment = $conn->prepare("UPDATE `delivery_assignments` 
            SET status = 'delivered', 
                delivered_at = NOW() 
            WHERE delivery_agent_id = ? AND status = 'picked_up'");
        $complete_assignment->execute([$delivery_agent_id]);

        // Update order status
        $complete_order = $conn->prepare("UPDATE `orders` o
            JOIN `delivery_assignments` da ON o.id = da.order_id
            SET o.order_status = 'delivered'
            WHERE da.delivery_agent_id = ? AND da.status = 'delivered'");
        $complete_order->execute([$delivery_agent_id]);

        $conn->commit();
        $message[] = 'Delivery completed successfully!';
    } catch (Exception $e) {
        $conn->rollBack();
        $message[] = 'Error completing delivery: ' . $e->getMessage();
    }
}

// Check if agent already has an active delivery
$active_delivery = $conn->prepare("SELECT da.*, o.* FROM `delivery_assignments` da 
                                   JOIN `orders` o ON da.order_id = o.id 
                                   WHERE da.delivery_agent_id = ? AND da.status IN ('pending', 'picked_up')");
$active_delivery->execute([$delivery_agent_id]);
$current_delivery = $active_delivery->fetch(PDO::FETCH_ASSOC);

// Handle order pickup
if (isset($_POST['pickup_order'])) {
   $order_id = $_POST['order_id'];

   // Verify order is still available
   $verify_order = $conn->prepare("SELECT * FROM `orders` 
                                   WHERE id = ? 
                                   AND payment_status = 'completed' 
                                   AND id NOT IN (
                                       SELECT order_id FROM `delivery_assignments` 
                                       WHERE status IN ('pending', 'picked_up', 'delivered')
                                   )");
   $verify_order->execute([$order_id]);

   if ($verify_order->rowCount() == 0) {
       $message[] = 'Order is no longer available for pickup!';
   } else {
       // Begin transaction
       $conn->beginTransaction();

       try {
           // Insert new delivery assignment
           $insert_assignment = $conn->prepare("INSERT INTO `delivery_assignments` 
                                                (order_id, delivery_agent_id, status, picked_up_at) 
                                                VALUES (?, ?, 'picked_up', NOW())");
           $insert_assignment->execute([$order_id, $delivery_agent_id]);

           // Update order status
           $update_order = $conn->prepare("UPDATE `orders` SET order_status = 'picked_up' WHERE id = ?");
           $update_order->execute([$order_id]);

           $conn->commit();
           header('Location: pickup.php');
           exit();
       } catch (Exception $e) {
           $conn->rollBack();
           $message[] = 'Error picking up order: ' . $e->getMessage();
       }
   }
}

// Fetch available orders
$available_orders = $conn->prepare("SELECT o.* FROM `orders` o
                                    WHERE 
                                    o.payment_status = 'completed' 
                                    AND o.id NOT IN (
                                        SELECT order_id FROM `delivery_assignments` 
                                        WHERE status IN ('pending', 'picked_up', 'delivered')
                                    )
                                    ORDER BY o.placed_on ASC");
$available_orders->execute();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keens | Delivery Orders</title>
    
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

<section class="delivery-orders mt-5">
    <div class="content-card">
        <div class="card-header">
            <div class="header-icon">
                <i class="fas fa-truck"></i>
            </div>
            <h2 class="header-title">
                <?php echo $current_delivery ? 'Current Delivery' : 'Available Orders'; ?>
            </h2>
            <p class="text-muted">
                <?php echo $current_delivery 
                    ? 'You have an active delivery in progress' 
                    : 'Select an order to pickup and deliver'; ?>
            </p>
        </div>

        <div class="table-container">
            <?php if ($current_delivery): ?>
                <!-- Current Delivery Details -->
                <div class="current-delivery-details">
                    <div class="order-summary">
                        <h4 class="section-title">Order Details</h4>
                        <div class="info-card">
                            <p><strong>Order ID:</strong> #<?= $current_delivery['id'] ?></p>
                            <p><strong>Total Products:</strong> <?= $current_delivery['total_products'] ?></p>
                            <p><strong>Total Price:</strong> $<?= number_format($current_delivery['total_price'], 2) ?></p>
                            <p><strong>Placed On:</strong> <?= date('d M Y H:i', strtotime($current_delivery['placed_on'])) ?></p>
                        </div>
                    </div>

                    <div class="customer-details">
                        <h4 class="section-title">Customer Information</h4>
                        <div class="info-card">
                            <p><strong>Name:</strong> <?= $current_delivery['name'] ?></p>
                            <p><strong>Phone:</strong> <?= $current_delivery['number'] ?></p>
                            <p><strong>Email:</strong> <?= $current_delivery['email'] ?></p>
                            <p><strong>Delivery Address:</strong> <?= $current_delivery['address'] ?></p>
                        </div>
                    </div>

                    <div class="delivery-actions">
                        <form action="" method="post">
                            <button type="submit" name="complete_delivery" class="btn custom-button w-100">
                                <i class="fas fa-check-circle me-2"></i>Complete Delivery
                            </button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <!-- Available Orders Table -->
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Order Details</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($available_orders->rowCount() > 0): ?>
                            <?php while($order = $available_orders->fetch(PDO::FETCH_ASSOC)): ?>
                                <tr>
                                    <td>
                                        <span class="id-badge">#<?= $order['id'] ?></span>
                                    </td>
                                    <td>
                                        <div class="order-details">
                                            <span class="total-products">
                                                <i class="fas fa-box"></i> 
                                                Total Products: <?= $order['total_products'] ?>
                                            </span>
                                            <span class="order-price">
                                                <i class="fas fa-dollar-sign"></i> 
                                                Total Price: $<?= number_format($order['total_price'], 2) ?>
                                            </span>
                                            <span class="order-date">
                                                <i class="fas fa-calendar"></i> 
                                                Placed On: <?= date('d M Y', strtotime($order['placed_on'])) ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <form action="" method="post">
                                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                            <button type="submit" name="pickup_order" class="btn custom-button">
                                                <i class="fas fa-truck-pickup me-2"></i>Pickup Order
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="empty-state">
                                    <i class="fas fa-box-open"></i>
                                    <p>No orders available for pickup</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php endif; ?>
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
    .order-details {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .total-products, .order-price, .order-date {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        color: #495057;
    }

    .total-products i, .order-price i, .order-date i {
        color: var(--primary-color);
    }

    /* Current Delivery Details */
    .current-delivery-details {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    .section-title {
        margin-bottom: 1rem;
        color: #2b3452;
        font-weight: 600;
    }

    .info-card {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 20px;
        border: 1px solid #e9ecef;
    }

    .info-card p {
        margin-bottom: 10px;
        color: #495057;
    }

    .delivery-actions {
        grid-column: span 2;
        display: flex;
        justify-content: center;
        margin-top: 20px;
    }

    /* Custom Button */
    .custom-button {
        background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
        color: white;
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 12px;
        font-weight: 500;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .custom-button:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(67, 97, 238, 0.2);
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

    /* Responsive Adjustments */
    @media (max-width: 768px) {
        .current-delivery-details {
            grid-template-columns: 1fr;
        }

        .delivery-actions {
            grid-column: span 1;
        }

        .content-card {
            padding: 1rem;
            margin: 1rem;
        }

        .modern-table {
            font-size: 14px;
        }

        .order-details {
            flex-direction: column;
            gap: 4px;
        }
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>