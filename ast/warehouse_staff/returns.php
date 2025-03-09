<?php
include '../components/connect.php';

session_start();

$staff_id = $_SESSION['staff_id'];

if(!isset($staff_id)){
   header('location:index.php');
}

// Handle return status updates
if(isset($_POST['update_status'])) {
    $return_id = $_POST['return_id'];
    $return_id = filter_var($return_id, FILTER_SANITIZE_STRING);
    $status = $_POST['status'];
    $status = filter_var($status, FILTER_SANITIZE_STRING);

    $update_status = $conn->prepare("UPDATE `return_request` SET status = ? WHERE return_id = ?");
    $update_status->execute([$status, $return_id]);
    
    if($status == 'approved') {
        // Update inventory if return is approved
        $select_items = $conn->prepare("SELECT product_id, quantity FROM `return_items` WHERE return_id = ?");
        $select_items->execute([$return_id]);
        while($item = $select_items->fetch(PDO::FETCH_ASSOC)) {
            $update_inventory = $conn->prepare("UPDATE `inventory` SET quantity = quantity + ? WHERE product_id = ?");
            $update_inventory->execute([$item['quantity'], $item['product_id']]);
        }
    }
    
    $message[] = 'Return status updated successfully!';
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
   <title>Keens | Returns Management</title>

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<?php include '../components/warehouse_staff_header.php'; ?>

<section class="returns-management">
    <div class="container-fluid px-4 py-5">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h3 mb-0">Returns Management</h2>
            <div class="btn-group">
                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#scanReturnModal">
                    <i class="fas fa-barcode me-2"></i>Scan Return
                </button>
                
            </div>
        </div>

        <!-- Search and Filter Section -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form action="" method="GET" class="row g-3">
                    <div class="col-md-3">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text" name="search" class="form-control border-start-0" 
                                   placeholder="Search return ID" value="<?= htmlspecialchars($search) ?>">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="pending" <?= $status == 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="inspecting" <?= $status == 'inspecting' ? 'selected' : '' ?>>Inspecting</option>
                            <option value="approved" <?= $status == 'approved' ? 'selected' : '' ?>>Approved</option>
                            <option value="rejected" <?= $status == 'rejected' ? 'selected' : '' ?>>Rejected</option>
                        </select>
                    </div>
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

        <!-- Returns Table -->
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th>Return ID</th>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Items</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                $query = "SELECT r.*, u.name as customer_name, o.id as order_id 
                                         FROM `return_request` r 
                                         JOIN `users` u ON r.user_id = u.id 
                                         JOIN `orders` o ON r.order_id = o.id 
                                         WHERE 1=1";
                                
                                if(!empty($search)) {
                                    $query .= " AND r.return_id LIKE :search";
                                }
                                if(!empty($status)) {
                                    $query .= " AND r.status = :status";
                                }
                                if(!empty($date_from)) {
                                    $query .= " AND r.requested_date >= :date_from";
                                }
                                if(!empty($date_to)) {
                                    $query .= " AND r.requested_date <= :date_to";
                                }
                                
                                $query .= " ORDER BY r.requested_date DESC";
                                
                                $select_returns = $conn->prepare($query);
                                
                                if(!empty($search)) {
                                    $searchTerm = "%{$search}%";
                                    $select_returns->bindParam(':search', $searchTerm);
                                }
                                if(!empty($status)) {
                                    $select_returns->bindParam(':status', $status);
                                }
                                if(!empty($date_from)) {
                                    $select_returns->bindParam(':date_from', $date_from);
                                }
                                if(!empty($date_to)) {
                                    $select_returns->bindParam(':date_to', $date_to);
                                }
                                
                                $select_returns->execute();

                                if($select_returns->rowCount() > 0){
                                    while($return = $select_returns->fetch(PDO::FETCH_ASSOC)){
                                        // Get return items
                                        $select_items = $conn->prepare("
                                            SELECT p.name, ri.quantity 
                                            FROM `return_items` ri 
                                            JOIN `products` p ON ri.product_id = p.id 
                                            WHERE ri.return_id = ?
                                        ");
                                        $select_items->execute([$return['return_id']]);
                            ?>
                            <tr>
                                <td>#<?= $return['return_id']; ?></td>
                                <td>
                                    <a href="order_details.php?id=<?= $return['order_id']; ?>" 
                                       class="text-decoration-none">
                                        #<?= $return['order_id']; ?>
                                    </a>
                                </td>
                                <td><?= $return['customer_name']; ?></td>
                                <td>
                                    <?php
                                        while($item = $select_items->fetch(PDO::FETCH_ASSOC)){
                                            echo "<div>{$item['name']} (×{$item['quantity']})</div>";
                                        }
                                    ?>
                                </td>
                                <td><?= $return['reason']; ?></td>
                                <td>
                                    <form action="" method="POST" class="d-flex align-items-center gap-2">
                                        <input type="hidden" name="return_id" value="<?= $return['return_id']; ?>">
                                        <select name="status" class="form-select form-select-sm status-select" 
                                                style="width: 140px;" onchange="this.form.submit()">
                                            <option value="pending" <?= $return['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="inspecting" <?= $return['status'] == 'inspecting' ? 'selected' : '' ?>>Inspecting</option>
                                            <option value="approved" <?= $return['status'] == 'approved' ? 'selected' : '' ?>>Approved</option>
                                            <option value="rejected" <?= $return['status'] == 'rejected' ? 'selected' : '' ?>>Rejected</option>
                                        </select>
                                        <input type="submit" name="update_status" class="d-none">
                                    </form>
                                </td>
                                <td><?= date('Y-m-d', strtotime($return['requested_date'])); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="return_details.php?id=<?= $return['return_id']; ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" 
                                                onclick="printReturnLabel(<?= $return['return_id']; ?>)">
                                            <i class="fas fa-print"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php
                                    }
                                } else {
                                    echo '<tr><td colspan="8" class="text-center py-4">No returns found</td></tr>';
                                }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Scan Return Modal -->
<div class="modal fade" id="scanReturnModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Scan Return Package</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="form-group mb-3">
                    <label class="form-label">Return ID or Barcode</label>
                    <input type="text" class="form-control" id="returnBarcode" autofocus>
                </div>
                <div class="text-center">
                    <i class="fas fa-barcode fa-3x text-muted"></i>
                    <p class="text-muted mt-2">Scan the return package barcode or enter the return ID</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="processReturn()">Process Return</button>
            </div>
        </div>
    </div>
</div>

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

/* Modal styles */
.modal-content {
    border-radius: 15px;
    border: none;
}

.modal-header {
    border-bottom: 1px solid rgba(0,0,0,.05);
}

.modal-footer {
    border-top: 1px solid rgba(0,0,0,.05);
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
function printReturnLabel(returnId) {
    window.open(`print_return_label.php?id=${returnId}`, '_blank');
}

function processReturn() {
    const barcode = document.getElementById('returnBarcode').value;
    if(barcode) {
        window.location.href = `return_details.php?id=${barcode}`;
    }
}

// Auto-focus barcode input when modal opens
document.getElementById('scanReturnModal').addEventListener('shown.bs.modal', function () {
    document.getElementById('returnBarcode').focus();
});
</script>

</body>
</html>