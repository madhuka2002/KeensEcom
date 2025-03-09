<?php
include '../components/connect.php';

session_start();

$staff_id = $_SESSION['staff_id'];

if(!isset($staff_id)){
   header('location:index.php');
}

// Handle status updates
if(isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $order_id = filter_var($order_id, FILTER_SANITIZE_STRING);
    $status = $_POST['status'];
    $status = filter_var($status, FILTER_SANITIZE_STRING);

    $update_status = $conn->prepare("UPDATE `orders` SET order_status = ? WHERE id = ?");
    $update_status->execute([$status, $order_id]);
    $message[] = 'Order status updated successfully!';
}

// Filter parameters
$status = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Keens | Order Management</title>

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<?php include '../components/warehouse_staff_header.php'; ?>

<section class="orders-management">
    <div class="container-fluid px-4 py-5">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h3 mb-0">Order Management</h2>
            <div>
                <a href="pick_pack.php" class="btn btn-primary">
                    <i class="fas fa-box me-2"></i>Start Picking
                </a>
            </div>
        </div>

        <!-- Search and Filter Section -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form action="" method="GET" class="row g-3">
                    <!-- Search -->
                    <div class="col-md-3">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text" name="search" class="form-control border-start-0" 
                                   placeholder="Search order ID" value="<?= htmlspecialchars($search) ?>">
                        </div>
                    </div>
                    <!-- Status Filter -->
                    <div class="col-md-2">
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="pending" <?= $status == 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="processing" <?= $status == 'processing' ? 'selected' : '' ?>>Processing</option>
                            <option value="picked_up" <?= $status == 'picked_up' ? 'selected' : '' ?>>Picked Up</option>
                            <option value="shipped" <?= $status == 'shipped' ? 'selected' : '' ?>>Shipped</option>
                            <option value="delivered" <?= $status == 'delivered' ? 'selected' : '' ?>>Delivered</option>
                        </select>
                    </div>
                    <!-- Date Range -->
                    <div class="col-md-3">
                        <div class="input-group">
                            <span class="input-group-text bg-white">From</span>
                            <input type="date" name="date_from" class="form-control" value="<?= $date_from ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="input-group">
                            <span class="input-group-text bg-white">To</span>
                            <input type="date" name="date_to" class="form-control" value="<?= $date_to ?>">
                        </div>
                    </div>
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Total Items</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Order Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                $query = "SELECT o.*, u.name as customer_name 
                                         FROM `orders` o 
                                         JOIN `users` u ON o.user_id = u.id 
                                         WHERE 1=1";
                                
                                if(!empty($search)) {
                                    $query .= " AND o.id LIKE :search";
                                }
                                if(!empty($status)) {
                                    $query .= " AND o.order_status = :status";
                                }
                                if(!empty($date_from)) {
                                    $query .= " AND DATE(o.placed_on) >= :date_from";
                                }
                                if(!empty($date_to)) {
                                    $query .= " AND DATE(o.placed_on) <= :date_to";
                                }
                                
                                $query .= " ORDER BY o.placed_on DESC";
                                
                                $select_orders = $conn->prepare($query);
                                
                                if(!empty($search)) {
                                    $searchTerm = "%{$search}%";
                                    $select_orders->bindParam(':search', $searchTerm);
                                }
                                if(!empty($status)) {
                                    $select_orders->bindParam(':status', $status);
                                }
                                if(!empty($date_from)) {
                                    $select_orders->bindParam(':date_from', $date_from);
                                }
                                if(!empty($date_to)) {
                                    // Add end of day time to include all orders on the end date
                                    $date_to_end = $date_to;
                                    $select_orders->bindParam(':date_to', $date_to_end);
                                }
                                
                                $select_orders->execute();

                                if($select_orders->rowCount() > 0){
                                    while($fetch_order = $select_orders->fetch(PDO::FETCH_ASSOC)){
                            ?>
                            <tr>
                                <td>#<?= $fetch_order['id']; ?></td>
                                <td><?= $fetch_order['customer_name']; ?></td>
                                <td>
                                    <?php
                                        $order_items = count(explode(',', $fetch_order['total_products']));
                                        echo $order_items;
                                    ?>
                                </td>
                                <td>$<?= number_format($fetch_order['total_price'], 2); ?></td>
                                <td>
                                    <form action="" method="POST" class="d-flex align-items-center gap-2">
                                        <input type="hidden" name="order_id" value="<?= $fetch_order['id']; ?>">
                                        <select name="status" class="form-select form-select-sm status-select" 
                                                style="width: 140px;" onchange="this.form.submit()">
                                            <option value="pending" <?= $fetch_order['order_status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="processing" <?= $fetch_order['order_status'] == 'processing' ? 'selected' : '' ?>>Processing</option>
                                            <option value="picked_up" <?= $fetch_order['order_status'] == 'picked_up' ? 'selected' : '' ?>>Picked Up</option>
                                            <option value="shipped" <?= $fetch_order['order_status'] == 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                            <option value="delivered" <?= $fetch_order['order_status'] == 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                        </select>
                                        <input type="submit" name="update_status" class="d-none">
                                    </form>
                                </td>
                                <td><?= date('Y-m-d H:i', strtotime($fetch_order['placed_on'])); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="order_details.php?id=<?= $fetch_order['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        
                                    </div>
                                </td>
                            </tr>
                            <?php
                                    }
                                } else {
                                    echo '<tr><td colspan="7" class="text-center py-4">No orders found</td></tr>';
                                }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
/* Custom Styles */
.table th {
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.8rem;
    letter-spacing: 0.5px;
}

.form-control:focus, .form-select:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.25);
}

.btn-primary {
    background: var(--primary-color);
    border-color: var(--primary-color);
}

.btn-primary:hover {
    background: var(--secondary-color);
    border-color: var(--secondary-color);
}

.status-select {
    font-size: 0.875rem;
    padding: 0.25rem 0.5rem;
}

.btn-group .btn {
    padding: 0.25rem 0.5rem;
}

.btn-group .btn i {
    font-size: 0.875rem;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .table-responsive {
        border: 0;
    }
    
    .btn-group {
        display: flex;
        gap: 0.5rem;
    }
}
</style>

<script>
function printOrder(orderId) {
    window.open(`print_order.php?id=${orderId}`, '_blank');
}
</script>

</body>
</html>