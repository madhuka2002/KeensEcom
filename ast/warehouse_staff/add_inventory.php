<?php
include '../components/connect.php';

session_start();

$staff_id = $_SESSION['staff_id'];

if(!isset($staff_id)){
   header('location:index.php');
}

// Handle new inventory addition
if(isset($_POST['add_inventory'])) {
    $product_id = $_POST['product_id'];
    $product_id = filter_var($product_id, FILTER_SANITIZE_STRING);
    $quantity = $_POST['quantity'];
    $quantity = filter_var($quantity, FILTER_SANITIZE_STRING);
    $supplier_id = $_POST['supplier_id'];
    $supplier_id = filter_var($supplier_id, FILTER_SANITIZE_STRING);
    $batch_number = $_POST['batch_number'];
    $batch_number = filter_var($batch_number, FILTER_SANITIZE_STRING);
    $storage_location = $_POST['storage_location'];
    $storage_location = filter_var($storage_location, FILTER_SANITIZE_STRING);
    $notes = $_POST['notes'];
    $notes = filter_var($notes, FILTER_SANITIZE_STRING);

    // Check if product already exists in inventory
    $check_inventory = $conn->prepare("SELECT * FROM `inventory` WHERE product_id = ?");
    $check_inventory->execute([$product_id]);

    if($check_inventory->rowCount() > 0) {
        $message[] = 'Product already exists in inventory. Please use stock update instead.';
    } else {
        // Add new inventory record
        $insert_inventory = $conn->prepare("INSERT INTO `inventory` (product_id, quantity, supplier_id, storage_location) VALUES (?,?,?,?)");
        $insert_inventory->execute([$product_id, $quantity, $supplier_id, $storage_location]);

        // Log the addition
        $insert_log = $conn->prepare("INSERT INTO `inventory_log` (product_id, previous_quantity, new_quantity, change_type, reason, supplier_id, batch_number, notes) VALUES (?, 0, ?, 'add', 'initial_stock', ?, ?, ?)");
        $insert_log->execute([$product_id, $quantity, $supplier_id, $batch_number, $notes]);

        $message[] = 'New inventory item added successfully!';
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Keens | Add Inventory</title>

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<?php include '../components/warehouse_staff_header.php'; ?>

<section class="add-inventory py-5">
    <div class="container">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-2">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="inventory.php">Inventory</a></li>
                        <li class="breadcrumb-item active">Add New Item</li>
                    </ol>
                </nav>
                <h2 class="h3 mb-0">Add New Inventory Item</h2>
            </div>
            <a href="scan_item.php" class="btn btn-primary">
                <i class="fas fa-barcode me-2"></i>Scan Items
            </a>
        </div>

        <!-- Add Inventory Form -->
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <form action="" method="POST" class="row g-3">
                            <!-- Product Selection -->
                            <div class="col-md-12">
                                <label class="form-label">Select Product</label>
                                <select name="product_id" class="form-select" required>
                                    <option value="">Choose product...</option>
                                    <?php
                                        $select_products = $conn->prepare("
                                            SELECT p.id, p.name, p.SKU, c.name as category_name 
                                            FROM `products` p 
                                            LEFT JOIN `category` c ON p.category_id = c.category_id 
                                            WHERE p.id NOT IN (SELECT product_id FROM inventory)
                                        ");
                                        $select_products->execute();
                                        while($product = $select_products->fetch(PDO::FETCH_ASSOC)) {
                                            echo "<option value='{$product['id']}'>{$product['name']} - {$product['SKU']} ({$product['category_name']})</option>";
                                        }
                                    ?>
                                </select>
                            </div>

                            <!-- Initial Quantity -->
                            <div class="col-md-6">
                                <label class="form-label">Initial Quantity</label>
                                <input type="number" name="quantity" class="form-control" required min="0">
                            </div>

                            <!-- Storage Location -->
                            <div class="col-md-6">
                                <label class="form-label">Storage Location</label>
                                <input type="text" name="storage_location" class="form-control" 
                                       placeholder="e.g., Shelf A-12" required>
                            </div>

                            <!-- Supplier Selection -->
                            <div class="col-md-6">
                                <label class="form-label">Supplier</label>
                                <select name="supplier_id" class="form-select" required>
                                    <option value="">Select supplier...</option>
                                    <?php
                                        $select_suppliers = $conn->prepare("SELECT supplier_id, name FROM `suppliers`");
                                        $select_suppliers->execute();
                                        while($supplier = $select_suppliers->fetch(PDO::FETCH_ASSOC)) {
                                            echo "<option value='{$supplier['supplier_id']}'>{$supplier['name']}</option>";
                                        }
                                    ?>
                                </select>
                            </div>

                            <!-- Batch Number -->
                            <div class="col-md-6">
                                <label class="form-label">Batch Number</label>
                                <input type="text" name="batch_number" class="form-control" required>
                            </div>

                            <!-- Notes -->
                            <div class="col-12">
                                <label class="form-label">Additional Notes</label>
                                <textarea name="notes" class="form-control" rows="3"></textarea>
                            </div>

                            <!-- Quality Check -->
                            <div class="col-12">
                                <div class="quality-checklist card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title">Quality Check</h6>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="check1" required>
                                            <label class="form-check-label" for="check1">
                                                Product condition verified
                                            </label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="check2" required>
                                            <label class="form-check-label" for="check2">
                                                Storage location confirmed
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="check3" required>
                                            <label class="form-check-label" for="check3">
                                                Safety standards met
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit Buttons -->
                            <div class="col-12">
                                <hr>
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="inventory.php" class="btn btn-light">Cancel</a>
                                    <button type="submit" name="add_inventory" class="btn btn-primary">
                                        <i class="fas fa-plus me-2"></i>Add Inventory
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

.quality-checklist {
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
    .container {
        padding: 0 1rem;
    }
}
</style>

</body>
</html>