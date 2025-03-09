<?php
include '../components/connect.php';

session_start();

$staff_id = $_SESSION['staff_id'];

if(!isset($staff_id)){
   header('location:index.php');
}

// Retrieve order ID from GET parameter
$order_id = isset($_GET['id']) ? $_GET['id'] : '';

// Validate order ID
if(empty($order_id)){
   header('location:orders.php');
   exit();
}

// Fetch order details with user information
// NOTE: Remove or replace 'u.phone' with an existing column if needed
$select_order = $conn->prepare("SELECT o.*, u.name as customer_name, u.email as customer_email 
                                FROM `orders` o 
                                JOIN `users` u ON o.user_id = u.id 
                                WHERE o.id = ?");
$select_order->execute([$order_id]);
$fetch_order = $select_order->fetch(PDO::FETCH_ASSOC);

// If no order found, redirect
if(!$fetch_order){
   header('location:orders.php');
   exit();
}

// Fetch order items
$select_order_items = $conn->prepare("SELECT * FROM `order_items` WHERE order_id = ?");
$select_order_items->execute([$order_id]);
$order_items = $select_order_items->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Order Details | Keens</title>

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
</head>
<body>
<?php include '../components/warehouse_staff_header.php'; ?>

<div class="container-fluid px-4 py-5">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h3 class="mb-0">Order #<?= htmlspecialchars($fetch_order['id']); ?> Details</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Customer Information</h5>
                            <p>
                                <strong>Name:</strong> <?= htmlspecialchars($fetch_order['customer_name']); ?><br>
                                <strong>Email:</strong> <?= htmlspecialchars($fetch_order['customer_email']); ?>
                            </p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <h5>Order Status</h5>
                            <span class="badge bg-primary">
                                <?= htmlspecialchars(ucfirst($fetch_order['order_status'])); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>