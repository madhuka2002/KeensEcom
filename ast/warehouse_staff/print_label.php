<?php
include '../components/connect.php';

session_start();

$staff_id = $_SESSION['staff_id'];

if(!isset($staff_id)){
   header('location:warehouse_staff_login.php');
   exit();
}

// Check if order ID is provided
if(!isset($_GET['id'])){
   header('location:pick_pack.php');
   exit();
}

$order_id = $_GET['id'];

// Fetch order details - check what columns actually exist in your database
$select_order = $conn->prepare("
    SELECT o.*, o.name as customer_name, o.email, o.number, o.address 
    FROM `orders` o 
    JOIN `users` u ON o.user_id = u.id 
    WHERE o.id = ? AND o.order_status = 'picked_up'
");
$select_order->execute([$order_id]);

if($select_order->rowCount() == 0){
   $message[] = 'Order not found or not ready for delivery!';
   header('location:pick_pack.php');
   exit();
}

$order = $select_order->fetch(PDO::FETCH_ASSOC);

// Get order items
$select_items = $conn->prepare("
    SELECT p.name, o.total_products 
    FROM `orders` o 
    JOIN `products` p ON o.id = p.id 
    WHERE o.id = ?
");
$select_items->execute([$order_id]);
$items = $select_items->fetchAll(PDO::FETCH_ASSOC);

// We're not using tracking number anymore
// Generate an order reference for QR code and display purposes
$order_reference = 'AST' . date('Ymd') . str_pad($order_id, 6, '0', STR_PAD_LEFT);
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Keens | Shipping Label - Order #<?= $order_id; ?></title>

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
   <style>
       @media print {
           .no-print {
               display: none !important;
           }
           body {
               padding: 0;
               margin: 0;
           }
           .container {
               width: 100%;
               max-width: 100%;
               padding: 0;
               margin: 0;
           }
       }
   </style>
</head>
<body>

<?php include '../components/warehouse_staff_header.php'; ?>

<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <h2>Shipping Label - Order #<?= $order_id; ?></h2>
        <div>
            <a href="pick_pack.php" class="btn btn-outline-secondary me-2">
                <i class="fas fa-arrow-left me-2"></i>Back
            </a>
            <button type="button" class="btn btn-primary" onclick="window.print()">
                <i class="fas fa-print me-2"></i>Print Label
            </button>
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-md-8 mx-auto">
            <!-- Shipping Label -->
            <div class="card shipping-label">
                <div class="card-body p-4">
                    <!-- Header with Logo and Shipping Method -->
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <div class="company-info">
                            <h3 class="mb-0">Keens</h3>
                            <p class="mb-0">123 Cosmic Way</p>
                            <p class="mb-0">Starfield, ST 12345</p>
                            <p class="mb-0">contact@keens.lk</p>
                        </div>
                        <div class="shipping-method">
                            <!-- Fixed: check if payment_method exists, otherwise show default -->
                            <div class="badge bg-dark p-2 fs-6 mb-2">
                                <?= isset($order['payment_method']) ? strtoupper($order['payment_method']) : 'STANDARD SHIPPING'; ?>
                            </div>
                            <div class="qr-code">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=<?= $order_reference; ?>" 
                                     alt="QR Code" class="img-fluid">
                            </div>
                        </div>
                    </div>

                    <!-- Order Information -->
                    <div class="mb-4">
                        <h6 class="text-uppercase text-muted mb-2">Order Information</h6>
                        <div class="row">
                            <div class="col-6">
                                <p class="mb-1"><strong>Order #:</strong> <?= $order_id; ?></p>
                                <p class="mb-1"><strong>Date:</strong> <?= date('F j, Y', strtotime($order['placed_on'])); ?></p>
                            </div>
                            <div class="col-6">
                                <p class="mb-1"><strong>Reference:</strong> <?= $order_reference; ?></p>
                                <!-- Fixed: check if payment_method exists -->
                                <p class="mb-1"><strong>Payment:</strong> 
                                    <?= isset($order['payment_method']) ? ucfirst($order['payment_method']) : 'Cash on Delivery'; ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Address -->
                    <div class="mb-4">
                        <h6 class="text-uppercase text-muted mb-2">Ship To</h6>
                        <div class="address-box p-3 border">
                            <h5 class="mb-1"><?= $order['customer_name']; ?></h5>
                            <p class="mb-1"><?= $order['address']; ?></p>
                            <!-- Fixed: check if phone exists, otherwise use number field -->
                            <p class="mb-1">Phone: <?= isset($order['phone']) ? $order['phone'] : (isset($order['number']) ? $order['number'] : 'N/A'); ?></p>
                            <p class="mb-0">Email: <?= $order['email']; ?></p>
                        </div>
                    </div>

                    <!-- Package Contents Summary -->
                    <div class="mb-4">
                        <h6 class="text-uppercase text-muted mb-2">Package Contents</h6>
                        <div class="border p-3">
                            <p class="mb-1"><strong>Total Items:</strong> <?= count($items); ?></p>
                            <p class="mb-0"><strong>Contents:</strong> 
                                <?php
                                $item_list = [];
                                foreach($items as $item) {
                                    $item_list[] = $item['name'] . ' (×' . $item['quantity'] . ')';
                                }
                                echo implode(', ', $item_list);
                                ?>
                            </p>
                        </div>
                    </div>

                    <!-- Barcode -->
                    <div class="text-center">
                <div class="border-top pt-3 mt-3">
                    <svg id="barcode"></svg>
                    <h5><?= $order_reference; ?></h5>
                </div>
            </div>
                </div>
            </div>

            <!-- Package Slip -->
            <div class="card mt-4">
                <div class="card-header bg-light">
                    <h4 class="mb-0">Packing Slip</h4>
                </div>
                <div class="card-body p-4">
                    <div class="row mb-4">
                        <div class="col-6">
                            <h5>Keens</h5>
                            <p class="mb-0">123 Cosmic Way</p>
                            <p class="mb-0">Starfield, ST 12345</p>
                            <p class="mb-0">contact@keens.lk.com</p>
                        </div>
                        <div class="col-6 text-end">
                            <h6>Order #<?= $order_id; ?></h6>
                            <p class="mb-0">Date: <?= date('F j, Y', strtotime($order['placed_on'])); ?></p>
                            <p class="mb-0">Reference: <?= $order_reference; ?></p>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-6">
                            <h6>Billing Address</h6>
                            <p class="mb-1"><?= $order['customer_name']; ?></p>
                            <p class="mb-1"><?= $order['address']; ?></p>
                            <!-- Fixed: check if phone exists, otherwise use number field -->
                            <p class="mb-0">Phone: <?= isset($order['phone']) ? $order['phone'] : (isset($order['number']) ? $order['number'] : 'N/A'); ?></p>
                        </div>
                        <div class="col-6">
                            <h6>Shipping Address</h6>
                            <p class="mb-1"><?= $order['customer_name']; ?></p>
                            <p class="mb-1"><?= $order['address']; ?></p>
                            <!-- Fixed: check if phone exists, otherwise use number field -->
                            <p class="mb-0">Phone: <?= isset($order['phone']) ? $order['phone'] : (isset($order['number']) ? $order['number'] : 'N/A'); ?></p>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($items as $item): ?>
                                <tr>
                                    <td><?= $item['name']; ?></td>
                                    <td><?= $item['quantity']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <p class="mb-1"><strong>Total Amount:</strong> $<?= number_format($order['total_price'], 2); ?></p>
                            <!-- Fixed: check if payment_method exists -->
                            <p class="mb-1"><strong>Payment Method:</strong> 
                                <?= isset($order['payment_method']) ? ucfirst($order['payment_method']) : 'Cash on Delivery'; ?>
                            </p>
                            <p class="mb-0"><strong>Notes:</strong> <?= $order['notes'] ?? 'None'; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
/* Custom Styles */
.shipping-label {
    border: 2px solid #000;
    border-radius: 10px;
}

.company-info h3 {
    font-weight: 700;
}

.address-box {
    background-color: #f8f9fa;
    border-radius: 5px;
}

.qr-code {
    padding: 5px;
    background: white;
    border: 1px solid #ddd;
}

@media print {
    .no-print {
        display: none !important;
    }
    
    .navbar, .btn-group, form {
        display: none !important;
    }
    
    .container {
        width: 100%;
        max-width: 100%;
        padding: 0;
        margin: 0;
    }
    
    .card {
        box-shadow: none !important;
        margin-bottom: 0 !important;
    }
    
    body {
        padding: 0;
        margin: 0;
    }
    
    .shipping-label {
        page-break-after: always;
    }
}
</style>

<!-- Include JsBarcode for barcode generation -->
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
<script>
    // Generate barcode when page loads
    document.addEventListener('DOMContentLoaded', function() {
        JsBarcode("#barcode", "<?= $order['tracking_number']; ?>", {
            format: "CODE128",
            lineColor: "#000",
            width: 2,
            height: 50,
            displayValue: true
        });
    });
</script>

</body>
</html>