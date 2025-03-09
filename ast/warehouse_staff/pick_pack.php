<?php
include '../components/connect.php';

session_start();

$staff_id = $_SESSION['staff_id'];

if(!isset($staff_id)){
   header('location:warehouse_staff_login.php');
}

// Handle starting picking process
if(isset($_POST['start_picking'])) {
    $order_id = $_POST['order_id'];
    $update_status = $conn->prepare("UPDATE `orders` SET order_status = 'processing' WHERE id = ?");
    $update_status->execute([$order_id]);
    $message[] = 'Order picking started!';
}

// Handle completing picking process
if(isset($_POST['complete_picking'])) {
    $order_id = $_POST['order_id'];
    $update_status = $conn->prepare("UPDATE `orders` SET order_status = 'picked_up' WHERE id = ?");
    $update_status->execute([$order_id]);
    $message[] = 'Order picking completed!';
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>AstroShop | Pick & Pack</title>

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<?php include '../components/warehouse_staff_header.php'; ?>

<section class="pick-pack">
    <div class="container-fluid px-4 py-5">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h3 mb-0">Pick & Pack Orders</h2>
            <div class="btn-group">
                <button type="button" class="btn btn-outline-primary" onclick="window.print()">
                    <i class="fas fa-print me-2"></i>Print Pick List
                </button>
            </div>
        </div>

        <!-- Orders Grid -->
        <div class="row g-4">
            <!-- Pending Orders -->
            <div class="col-12 col-lg-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-clock me-2 text-warning"></i>
                            Orders to Pick
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <?php
                                $select_pending = $conn->prepare("
                                    SELECT o.*, u.name as customer_name 
                                    FROM `orders` o 
                                    JOIN `users` u ON o.user_id = u.id 
                                    WHERE o.order_status = 'pending' 
                                    ORDER BY o.placed_on ASC
                                ");
                                $select_pending->execute();
                                
                                if($select_pending->rowCount() > 0){
                                    while($order = $select_pending->fetch(PDO::FETCH_ASSOC)){
                            ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">Order #<?= $order['id']; ?></h6>
                                        <p class="small text-muted mb-0"><?= $order['customer_name']; ?></p>
                                    </div>
                                    <form action="" method="POST">
                                        <input type="hidden" name="order_id" value="<?= $order['id']; ?>">
                                        <button type="submit" name="start_picking" class="btn btn-sm btn-primary">
                                            Start Picking
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <?php
                                    }
                                } else {
                                    echo '<div class="list-group-item text-center py-4">No pending orders</div>';
                                }
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- In Progress -->
            <div class="col-12 col-lg-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-dolly me-2 text-primary"></i>
                            In Progress
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <?php
                                $select_processing = $conn->prepare("
                                    SELECT o.*, u.name as customer_name 
                                    FROM `orders` o 
                                    JOIN `users` u ON o.user_id = u.id 
                                    WHERE o.order_status = 'processing' 
                                    ORDER BY o.placed_on ASC
                                ");
                                $select_processing->execute();
                                
                                if($select_processing->rowCount() > 0){
                                    while($order = $select_processing->fetch(PDO::FETCH_ASSOC)){
                                        // Get order items
                                        $order_id = $order['id'];
                                        $select_items = $conn->prepare("
                                            SELECT p.name, oi.quantity 
                                            FROM `order_items` oi 
                                            JOIN `products` p ON oi.product_id = p.id 
                                            WHERE oi.order_id = ?
                                        ");
                                        $select_items->execute([$order_id]);
                            ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1">Order #<?= $order['id']; ?></h6>
                                        <p class="small text-muted mb-2"><?= $order['customer_name']; ?></p>
                                        <div class="picking-list mb-3">
                                            <?php while($item = $select_items->fetch(PDO::FETCH_ASSOC)){ ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="item_<?= $order['id']; ?>">
                                                <label class="form-check-label" for="item_<?= $order['id']; ?>">
                                                    <?= $item['name']; ?> (×<?= $item['quantity']; ?>)
                                                </label>
                                            </div>
                                            <?php } ?>
                                        </div>
                                        <form action="" method="POST">
                                            <input type="hidden" name="order_id" value="<?= $order['id']; ?>">
                                            <button type="submit" name="complete_picking" class="btn btn-sm btn-success w-100">
                                                Complete Picking
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php
                                    }
                                } else {
                                    echo '<div class="list-group-item text-center py-4">No orders in progress</div>';
                                }
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ready for Delivery -->
            <div class="col-12 col-lg-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-box-check me-2 text-success"></i>
                            Ready for Delivery
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <?php
                                $select_ready = $conn->prepare("
                                    SELECT o.*, u.name as customer_name 
                                    FROM `orders` o 
                                    JOIN `users` u ON o.user_id = u.id 
                                    WHERE o.order_status = 'picked_up' 
                                    ORDER BY o.placed_on ASC
                                ");
                                $select_ready->execute();
                                
                                if($select_ready->rowCount() > 0){
                                    while($order = $select_ready->fetch(PDO::FETCH_ASSOC)){
                            ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">Order #<?= $order['id']; ?></h6>
                                        <p class="small text-muted mb-0"><?= $order['customer_name']; ?></p>
                                    </div>
                                    <a href="print_label.php?id=<?= $order['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-print me-2"></i>Print Label
                                    </a>
                                </div>
                            </div>
                            <?php
                                    }
                                } else {
                                    echo '<div class="list-group-item text-center py-4">No orders ready for delivery</div>';
                                }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
/* Custom Styles */
.card {
    border-radius: 15px;
    border: none;
}

.card-header {
    border-radius: 15px 15px 0 0 !important;
    border-bottom: 1px solid rgba(0,0,0,.05);
}

.list-group-item {
    border-left: none;
    border-right: none;
    padding: 1rem;
}

.list-group-item:first-child {
    border-top: none;
}

.list-group-item:last-child {
    border-bottom: none;
}

.btn-primary {
    background: var(--primary-color);
    border-color: var(--primary-color);
}

.btn-primary:hover {
    background: var(--secondary-color);
    border-color: var(--secondary-color);
}

.picking-list {
    max-height: 200px;
    overflow-y: auto;
}

.form-check-input:checked + .form-check-label {
    text-decoration: line-through;
    color: #6c757d;
}

@media print {
    .navbar, .btn-group, form, .card-header {
        display: none !important;
    }
    
    .card {
        border: none !important;
        box-shadow: none !important;
    }
}
</style>

</body>
</html>