<?php
include '../components/connect.php';

session_start();

$staff_id = $_SESSION['staff_id'];

if(!isset($staff_id)){
   header('location:index.php');
}

// Get return ID from URL
$return_id = isset($_GET['id']) ? $_GET['id'] : '';

if(empty($return_id)) {
    header('location:returns.php');
}

// Handle inspection update
if(isset($_POST['update_inspection'])) {
    $condition = $_POST['condition'];
    $condition = filter_var($condition, FILTER_SANITIZE_STRING);
    $action = $_POST['action'];
    $action = filter_var($action, FILTER_SANITIZE_STRING);
    $notes = $_POST['notes'];
    $notes = filter_var($notes, FILTER_SANITIZE_STRING);

    // Update return request
    $update_return = $conn->prepare("
        UPDATE `return_request` 
        SET status = ?, condition_status = ?, action_taken = ?, inspection_notes = ?, 
            inspected_by = ?, inspected_at = CURRENT_TIMESTAMP 
        WHERE return_id = ?
    ");
    $update_return->execute([$action == 'approve' ? 'approved' : 'rejected', $condition, $action, $notes, $staff_id, $return_id]);

    // If approved, update inventory
    if($action == 'approve') {
        $select_items = $conn->prepare("SELECT product_id, quantity FROM `return_items` WHERE return_id = ?");
        $select_items->execute([$return_id]);
        
        while($item = $select_items->fetch(PDO::FETCH_ASSOC)) {
            // Update inventory quantity
            $update_inventory = $conn->prepare("
                UPDATE `inventory` 
                SET quantity = quantity + ? 
                WHERE product_id = ?
            ");
            $update_inventory->execute([$item['quantity'], $item['product_id']]);

            // Log inventory change
            $insert_log = $conn->prepare("
                INSERT INTO `inventory_log` 
                (product_id, previous_quantity, new_quantity, change_type, reason, notes) 
                VALUES (?, 
                    (SELECT quantity - ? FROM inventory WHERE product_id = ?),
                    (SELECT quantity FROM inventory WHERE product_id = ?),
                    'add', 'return', ?)
            ");
            $insert_log->execute([
                $item['product_id'], 
                $item['quantity'], 
                $item['product_id'],
                $item['product_id'],
                "Return #$return_id processed"
            ]);
        }
    }

    $message[] = 'Return inspection completed successfully!';
}

// Get return details
$select_return = $conn->prepare("
    SELECT r.*, u.name as customer_name, u.email, u.phone,
           o.id as order_id, o.placed_on as order_date
    FROM `return_request` r 
    JOIN `users` u ON r.user_id = u.id 
    JOIN `orders` o ON r.order_id = o.id 
    WHERE r.return_id = ?
");
$select_return->execute([$return_id]);
$return = $select_return->fetch(PDO::FETCH_ASSOC);

// Check if return exists
if(!$return) {
    header('location:returns.php');
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Keens | Process Return</title>

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<?php include '../components/warehouse_staff_header.php'; ?>

<section class="return-process py-5">
    <div class="container">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-2">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="returns.php">Returns</a></li>
                        <li class="breadcrumb-item active">Return #<?= $return_id ?></li>
                    </ol>
                </nav>
                <h2 class="h3 mb-0">Process Return</h2>
            </div>
            <div class="btn-group">
                <button type="button" class="btn btn-outline-primary" onclick="window.print()">
                    <i class="fas fa-print me-2"></i>Print Details
                </button>
            </div>
        </div>

        <div class="row">
            <!-- Return Information -->
            <div class="col-md-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Return Details</h5>
                        
                        <div class="mb-3">
                            <small class="text-muted d-block">Return ID</small>
                            <div>#<?= $return_id ?></div>
                        </div>

                        <div class="mb-3">
                            <small class="text-muted d-block">Original Order</small>
                            <div>
                                <a href="order_details.php?id=<?= $return['order_id'] ?>">#<?= $return['order_id'] ?></a>
                                <small class="text-muted d-block">
                                    <?= date('M d, Y', strtotime($return['order_date'])) ?>
                                </small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <small class="text-muted d-block">Customer</small>
                            <div><?= $return['customer_name'] ?></div>
                            <small class="text-muted d-block"><?= $return['email'] ?></small>
                            <small class="text-muted d-block"><?= $return['phone'] ?></small>
                        </div>

                        <div class="mb-3">
                            <small class="text-muted d-block">Return Reason</small>
                            <div><?= $return['reason'] ?></div>
                        </div>

                        <div>
                            <small class="text-muted d-block">Status</small>
                            <span class="badge bg-<?= getStatusColor($return['status']) ?>">
                                <?= ucfirst($return['status']) ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Return Timeline -->
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Return Timeline</h5>
                        <div class="timeline">
                            <div class="timeline-item">
                                <div class="timeline-marker done">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="timeline-content">
                                    <h6 class="mb-0">Return Requested</h6>
                                    <small class="text-muted">
                                        <?= date('M d, Y H:i', strtotime($return['requested_date'])) ?>
                                    </small>
                                </div>
                            </div>

                            <?php if($return['status'] != 'pending'): ?>
                            <div class="timeline-item">
                                <div class="timeline-marker <?= $return['status'] != 'pending' ? 'done' : '' ?>">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="timeline-content">
                                    <h6 class="mb-0">Inspection Completed</h6>
                                    <small class="text-muted">
                                        <?= date('M d, Y H:i', strtotime($return['inspected_at'])) ?>
                                    </small>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Return Items & Inspection -->
            <div class="col-md-8">
                <!-- Return Items -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">Return Items</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Quantity</th>
                                        <th>Unit Price</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                        $select_items = $conn->prepare("
                                            SELECT ri.*, p.name, p.image_01, p.price 
                                            FROM `return_items` ri 
                                            JOIN `products` p ON ri.product_id = p.id 
                                            WHERE ri.return_id = ?
                                        ");
                                        $select_items->execute([$return_id]);
                                        $total_refund = 0;
                                        
                                        while($item = $select_items->fetch(PDO::FETCH_ASSOC)) {
                                            $total_refund += $item['price'] * $item['quantity'];
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="../uploaded_img/<?= $item['image_01'] ?>" 
                                                     alt="" class="me-2" width="40" height="40" 
                                                     style="object-fit: cover;">
                                                <?= $item['name'] ?>
                                            </div>
                                        </td>
                                        <td><?= $item['quantity'] ?></td>
                                        <td>$<?= number_format($item['price'], 2) ?></td>
                                        <td>$<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Total Refund:</strong></td>
                                        <td><strong>$<?= number_format($total_refund, 2) ?></strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Inspection Form -->
                <?php if($return['status'] == 'pending'): ?>
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">Return Inspection</h5>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST" class="row g-3">
                            <!-- Item Condition -->
                            <div class="col-md-6">
                                <label class="form-label">Item Condition</label>
                                <select name="condition" class="form-select" required>
                                    <option value="">Select condition...</option>
                                    <option value="new">Like New</option>
                                    <option value="good">Good</option>
                                    <option value="damaged">Damaged</option>
                                    <option value="unusable">Unusable</option>
                                </select>
                            </div>

                            <!-- Action -->
                            <div class="col-md-6">
                                <label class="form-label">Action</label>
                                <select name="action" class="form-select" required>
                                    <option value="">Select action...</option>
                                    <option value="approve">Approve Return</option>
                                    <option value="reject">Reject Return</option>
                                </select>
                            </div>

                            <!-- Notes -->
                            <div class="col-12">
                                <label class="form-label">Inspection Notes</label>
                                <textarea name="notes" class="form-control" rows="3" required></textarea>
                            </div>

                            <!-- Inspection Checklist -->
                            <div class="col-12">
                                <div class="inspection-checklist card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title">Inspection Checklist</h6>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="check1" required>
                                            <label class="form-check-label" for="check1">
                                                Items physically inspected
                                            </label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="check2" required>
                                            <label class="form-check-label" for="check2">
                                                Original packaging verified
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="check3" required>
                                            <label class="form-check-label" for="check3">
                                                Return policy compliance verified
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit -->
                            <div class="col-12">
                                <hr>
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="returns.php" class="btn btn-light">Cancel</a>
                                    <button type="submit" name="update_inspection" class="btn btn-primary">
                                        Complete Inspection
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <?php else: ?>
                <!-- Inspection Results -->
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">Inspection Results</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <small class="text-muted d-block">Item Condition</small>
                                <div><?= ucfirst($return['condition_status']) ?></div>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">Action Taken</small>
                                <div><?= ucfirst($return['action_taken']) ?></div>
                            </div>
                            <div class="col-12">
                                <small class="text-muted d-block">Inspection Notes</small>
                                <div><?= $return['inspection_notes'] ?></div>
                            </div>
                            <div class="col-12">
                                <small class="text-muted d-block">Inspected By</small>
                                <?php
                                    $select_inspector = $conn->prepare("SELECT name FROM `warehouse_staff` WHERE staff_id = ?");
                                    $select_inspector->execute([$return['inspected_by']]);
                                    $inspector = $select_inspector->fetch(PDO::FETCH_ASSOC);
                                ?>
                                <div><?= $inspector['name'] ?></div>
                                <small class="text-muted">
                                    <?= date('M d, Y H:i', strtotime($return['inspected_at'])) ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<style>
/* Custom Styles */
.breadcrumb-item a {
    color: var(--primary-color);
    text-decoration: none;
}

.card {
    border: none;
    border-radius: 15px;
}

.card-header {
    border-bottom: 1px solid rgba(0,0,0,.05);
}

.inspection-checklist {
    border: 1px solid rgba(0,0,0,.05);
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

.form-check-input:checked {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
}

/* Timeline Styles */
.timeline {
    position: relative;
    padding-left: 3rem;
}

.timeline-item {
    position: relative;
    padding-bottom: 1.5rem;
}

.timeline-item:last-child {
    padding-bottom: 0;
}

.timeline-marker {
    position: absolute;
    left: -3rem;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: #f8f9fa;
    border: 2px solid #dee2e6;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #adb5bd;
}

.timeline-marker.done {
    background: var(--success-color);
    border-color: var(--success-color);
    color: white;
}

.timeline-item:not(:last-child) .timeline-marker::after {
    content: '';
    position: absolute;
    top: 24px;
    left: 50%;
    transform: translateX(-50%);
    width: 2px;
    height: calc(100% - 24px);
    background: #dee2e6;
}

/* Print Styles */
@media print {
    .navbar, .btn-group, form {
        display: none !important;
    }
    
    .card {
        border: none !important;
        box-shadow: none !important;
    }
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .timeline {
        padding-left: 2rem;
    }
    
    .timeline-marker {
        left: -2rem;
        width: 20px;
        height: 20px;
    }
}
</style>

<?php
function getStatusColor($status) {
    switch($status) {
        case 'pending': return 'warning';
        case 'approved': return 'success';
        case 'rejected': return 'danger';
        default: return 'secondary';
    }
}
?>

</body>
</html>