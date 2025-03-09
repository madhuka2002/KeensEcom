<?php
include '../components/connect.php';

session_start();

$staff_id = $_SESSION['staff_id'];

if(!isset($staff_id)){
   header('location:index.php');
}

// Get inventory ID from URL
$inventory_id = isset($_GET['id']) ? $_GET['id'] : '';

if(empty($inventory_id)) {
    header('location:inventory.php');
}

// Handle stock update
if(isset($_POST['update_stock'])) {
    $new_quantity = $_POST['quantity'];
    $new_quantity = filter_var($new_quantity, FILTER_SANITIZE_STRING);
    $reason = $_POST['reason'];
    $reason = filter_var($reason, FILTER_SANITIZE_STRING);
    $notes = $_POST['notes'];
    $notes = filter_var($notes, FILTER_SANITIZE_STRING);

    // Get current quantity
    $get_current = $conn->prepare("SELECT quantity FROM `inventory` WHERE inventory_id = ?");
    $get_current->execute([$inventory_id]);
    $current_quantity = $get_current->fetch(PDO::FETCH_ASSOC)['quantity'];

    // Update inventory
    $update_inventory = $conn->prepare("UPDATE `inventory` SET quantity = ? WHERE inventory_id = ?");
    $update_inventory->execute([$new_quantity, $inventory_id]);

    // Log the change
    $change_type = $new_quantity > $current_quantity ? 'add' : 'remove';
    $change_amount = abs($new_quantity - $current_quantity);
    
    $insert_log = $conn->prepare("INSERT INTO `inventory_log` (product_id, previous_quantity, new_quantity, change_type, reason, notes) VALUES ((SELECT product_id FROM inventory WHERE inventory_id = ?), ?, ?, ?, ?, ?)");
    $insert_log->execute([$inventory_id, $current_quantity, $new_quantity, $change_type, $reason, $notes]);

    $message[] = 'Inventory updated successfully!';
}

// Get inventory details with related information
$select_inventory = $conn->prepare("
    SELECT i.*, p.name as product_name, p.details, p.image_01, p.price,
           c.name as category_name
    FROM `inventory` i 
    JOIN `products` p ON i.product_id = p.id 
    LEFT JOIN `category` c ON p.category_id = c.category_id 
    WHERE i.inventory_id = ?
");
$select_inventory->execute([$inventory_id]);
$inventory = $select_inventory->fetch(PDO::FETCH_ASSOC);

// Check if inventory exists
if(!$inventory) {
    header('location:inventory.php');
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Keens | Inventory Details</title>

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<?php include '../components/warehouse_staff_header.php'; ?>

<section class="inventory-details py-5">
    <div class="container">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-2">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="inventory.php">Inventory</a></li>
                        <li class="breadcrumb-item active"><?= $inventory['product_name'] ?></li>
                    </ol>
                </nav>
                <h2 class="h3 mb-0">Inventory Details</h2>
            </div>
            <div class="btn-group">
                <button type="button" class="btn btn-outline-primary" onclick="window.print()">
                    <i class="fas fa-print me-2"></i>Print Details
                </button>
                <a href="scan_item.php?id=<?= $inventory['inventory_id'] ?>" class="btn btn-primary">
                    <i class="fas fa-barcode me-2"></i>Scan Item
                </a>
            </div>
        </div>

        <div class="row">
            <!-- Product Information -->
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <img src="../uploaded_img/<?= $inventory['image_01'] ?>" 
                                 alt="" class="img-fluid mb-3 rounded" style="max-height: 200px;">
                            <h4 class="mb-1"><?= $inventory['product_name'] ?></h4>
                            
                            <span class="badge bg-info"><?= $inventory['category_name'] ?></span>
                        </div>

                        <hr>

                        <div class="mb-3">
                            <h6>Product Details</h6>
                            <p class="mb-0"><?= $inventory['details'] ?></p>
                        </div>

                        <div class="row g-3">
                            <div class="col-6">
                                <div class="stat-card">
                                    <label class="text-muted">Current Stock</label>
                                    <h3 class="mb-0 <?= $inventory['quantity'] <= 10 ? 'text-danger' : '' ?>">
                                        <?= number_format($inventory['quantity']) ?>
                                    </h3>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stat-card">
                                    <label class="text-muted">Unit Price</label>
                                    <h3 class="mb-0">$<?= number_format($inventory['price'], 2) ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stock Update Form -->
            <div class="col-md-8 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">Update Stock</h5>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">New Quantity</label>
                                    <input type="number" name="quantity" class="form-control" 
                                           value="<?= $inventory['quantity'] ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Reason for Change</label>
                                    <select name="reason" class="form-select" required>
                                        <option value="">Select reason...</option>
                                        <option value="new_stock">New Stock Arrival</option>
                                        <option value="adjustment">Stock Adjustment</option>
                                        <option value="damage">Damaged Items</option>
                                        <option value="return">Customer Return</option>
                                        <option value="correction">Inventory Correction</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Notes</label>
                                    <textarea name="notes" class="form-control" rows="3"></textarea>
                                </div>
                                <div class="col-12">
                                    <button type="submit" name="update_stock" class="btn btn-primary">
                                        Update Stock
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Stock History -->
                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">Stock History</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Previous</th>
                                        <th>New</th>
                                        <th>Change</th>
                                        <th>Reason</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                        $select_history = $conn->prepare("
                                            SELECT * FROM `inventory_log` 
                                            WHERE product_id = (SELECT product_id FROM inventory WHERE inventory_id = ?) 
                                            ORDER BY logged_at DESC
                                        ");
                                        $select_history->execute([$inventory_id]);
                                        
                                        while($history = $select_history->fetch(PDO::FETCH_ASSOC)) {
                                            $change = $history['new_quantity'] - $history['previous_quantity'];
                                    ?>
                                    <tr>
                                        <td><?= date('Y-m-d H:i', strtotime($history['logged_at'])) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $history['change_type'] == 'add' ? 'success' : 'danger' ?>">
                                                <?= ucfirst($history['change_type']) ?>
                                            </span>
                                        </td>
                                        <td><?= number_format($history['previous_quantity']) ?></td>
                                        <td><?= number_format($history['new_quantity']) ?></td>
                                        <td class="<?= $change >= 0 ? 'text-success' : 'text-danger' ?>">
                                            <?= $change >= 0 ? '+' : '' ?><?= number_format($change) ?>
                                        </td>
                                        <td><?= ucfirst($history['reason']) ?></td>
                                        <td><?= $history['notes'] ?? '-' ?></td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
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

.stat-card {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 10px;
    text-align: center;
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

.table th {
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.8rem;
    letter-spacing: 0.5px;
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
</style>

</body>
</html>