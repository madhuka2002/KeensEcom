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

// Handle inventory update
if(isset($_POST['update_inventory'])) {
    $storage_location = $_POST['storage_location'];
    $storage_location = filter_var($storage_location, FILTER_SANITIZE_STRING);
    $reorder_level = $_POST['reorder_level'];
    $reorder_level = filter_var($reorder_level, FILTER_SANITIZE_STRING);
    $supplier_id = $_POST['supplier_id'];
    $supplier_id = filter_var($supplier_id, FILTER_SANITIZE_STRING);
    $notes = $_POST['notes'];
    $notes = filter_var($notes, FILTER_SANITIZE_STRING);

    // Update inventory details
    $update_inventory = $conn->prepare("
        UPDATE `inventory` 
        SET storage_location = ?, 
            reorder_level = ?,
            supplier_id = ?,
            notes = ?
        WHERE inventory_id = ?
    ");
    $update_inventory->execute([$storage_location, $reorder_level, $supplier_id, $notes, $inventory_id]);

    $message[] = 'Inventory details updated successfully!';
}

// Get inventory details
$select_inventory = $conn->prepare("
    SELECT i.*, p.name as product_name, p.details, p.image_01, p.price,
           c.name as category_name, s.name as supplier_name
    FROM `inventory` i 
    JOIN `products` p ON i.product_id = p.id 
    LEFT JOIN `category` c ON p.category_id = c.category_id 
    LEFT JOIN `suppliers` s ON i.supplier_id = s.supplier_id
    WHERE i.inventory_id = ?
");
$select_inventory->execute([$inventory_id]);
$inventory = $select_inventory->fetch(PDO::FETCH_ASSOC);

// Check if inventory exists
if(!$inventory) {
    header('location:inventory.php');
}

// Ensure notes field exists in the array to prevent undefined key error
if(!isset($inventory['notes'])) {
    $inventory['notes'] = '';
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Keens | Edit Inventory</title>

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<?php include '../components/warehouse_staff_header.php'; ?>

<section class="edit-inventory py-5">
    <div class="container">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-2">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="inventory.php">Inventory</a></li>
                        <li class="breadcrumb-item active">Edit Item</li>
                    </ol>
                </nav>
                <h2 class="h3 mb-0">Edit Inventory Item</h2>
            </div>
            <div class="btn-group">
                <a href="inventory_details.php?id=<?= $inventory_id ?>" class="btn btn-outline-primary">
                    <i class="fas fa-eye me-2"></i>View Details
                </a>
                <a href="scan_item.php?id=<?= $inventory_id ?>" class="btn btn-primary">
                    <i class="fas fa-barcode me-2"></i>Scan Item
                </a>
            </div>
        </div>

        <div class="row">
            <!-- Product Information Card -->
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

            <!-- Edit Form -->
            <div class="col-md-8 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">Edit Inventory Details</h5>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST" class="row g-3">
                            <!-- Storage Location -->
                            <div class="col-md-6">
                                <label class="form-label">Storage Location</label>
                                <input type="text" name="storage_location" class="form-control" 
                                       value="<?= $inventory['storage_location'] ?>" required>
                            </div>

                            <!-- Reorder Level -->
                            <div class="col-md-6">
                                <label class="form-label">Reorder Level</label>
                                <input type="number" name="reorder_level" class="form-control" value="<?= isset($inventory['reorder_level']) ? $inventory['reorder_level'] : '' ?>" required>
                                <small class="text-muted">Minimum quantity before restocking</small>
                            </div>

                            <!-- Supplier Selection -->
                            <div class="col-md-12">
                                <label class="form-label">Primary Supplier</label>
                                <select name="supplier_id" class="form-select" required>
                                    <option value="">Select supplier...</option>
                                    <?php
                                        $select_suppliers = $conn->prepare("SELECT supplier_id, name FROM `suppliers`");
                                        $select_suppliers->execute();
                                        while($supplier = $select_suppliers->fetch(PDO::FETCH_ASSOC)) {
                                            $selected = ($supplier['supplier_id'] == $inventory['supplier_id']) ? 'selected' : '';
                                            echo "<option value='{$supplier['supplier_id']}' {$selected}>{$supplier['name']}</option>";
                                        }
                                    ?>
                                </select>
                            </div>

                            <!-- Notes -->
                            <div class="col-12">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-control" rows="3"><?= $inventory['notes'] ?></textarea>
                            </div>

                            <!-- Location Verification -->
                            <div class="col-12">
                                <div class="verification-checklist card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title">Location Verification</h6>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="check1" required>
                                            <label class="form-check-label" for="check1">
                                                Storage location physically verified
                                            </label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="check2" required>
                                            <label class="form-check-label" for="check2">
                                                Location label updated/verified
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="check3" required>
                                            <label class="form-check-label" for="check3">
                                                Storage conditions appropriate
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit Buttons -->
                            <div class="col-12">
                                <hr>
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="inventory_details.php?id=<?= $inventory_id ?>" class="btn btn-light">Cancel</a>
                                    <button type="submit" name="update_inventory" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Save Changes
                                    </button>
                                </div>
                            </div>
                        </form>
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

.verification-checklist {
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

/* Responsive Adjustments */
@media (max-width: 768px) {
    .btn-group {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .btn-group .btn {
        width: 100%;
    }
}
</style>

</body>
</html>