<?php
include '../components/connect.php';

session_start();

$staff_id = $_SESSION['staff_id'];

if(!isset($staff_id)){
   header('location:index.php');
}

// Handle new stock addition
if(isset($_POST['add_stock'])) {
    $product_id = $_POST['product_id'];
    $product_id = filter_var($product_id, FILTER_SANITIZE_STRING);
    $quantity = $_POST['quantity'];
    $quantity = filter_var($quantity, FILTER_SANITIZE_STRING);
    $supplier_id = $_POST['supplier_id'];
    $supplier_id = filter_var($supplier_id, FILTER_SANITIZE_STRING);
    $unit_cost = $_POST['unit_cost'];
    $unit_cost = filter_var($unit_cost, FILTER_SANITIZE_STRING);
    $batch_number = $_POST['batch_number'];
    $batch_number = filter_var($batch_number, FILTER_SANITIZE_STRING);
    
    // Check if inventory record exists
    $check_inventory = $conn->prepare("SELECT * FROM `inventory` WHERE product_id = ?");
    $check_inventory->execute([$product_id]);
    
    if($check_inventory->rowCount() > 0) {
        // Update existing inventory
        $update_inventory = $conn->prepare("UPDATE `inventory` SET quantity = quantity + ? WHERE product_id = ?");
        $update_inventory->execute([$quantity, $product_id]);
    } else {
        // Create new inventory record
        $insert_inventory = $conn->prepare("INSERT INTO `inventory` (product_id, quantity) VALUES (?, ?)");
        $insert_inventory->execute([$product_id, $quantity]);
    }
    
    // Log the stock addition
    $insert_log = $conn->prepare("INSERT INTO `inventory_log` (product_id, previous_quantity, new_quantity, change_type, reason, supplier_id, unit_cost, batch_number) VALUES (?, (SELECT quantity - ? FROM inventory WHERE product_id = ?), (SELECT quantity FROM inventory WHERE product_id = ?), 'add', 'new_stock', ?, ?, ?)");
    $insert_log->execute([$product_id, $quantity, $product_id, $product_id, $supplier_id, $unit_cost, $batch_number]);
    
    $message[] = 'New stock added successfully!';
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Keens | Add New Stock</title>

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<?php include '../components/warehouse_staff_header.php'; ?>

<section class="new-stock py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- Page Header -->
                <div class="page-header mb-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h2 class="h3 mb-0">Add New Stock</h2>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb mb-0">
                                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="inventory.php">Inventory</a></li>
                                    <li class="breadcrumb-item active">New Stock</li>
                                </ol>
                            </nav>
                        </div>
                        <a href="scan_item.php" class="btn btn-outline-primary">
                            <i class="fas fa-barcode me-2"></i>Scan Items
                        </a>
                    </div>
                </div>

                <!-- Add Stock Form -->
                <div class="card shadow-sm">
                    <div class="card-body">
                        <form action="" method="POST" class="row g-3">
                            <!-- Product Selection -->
                            <div class="col-md-12">
                                <label class="form-label">Select Product</label>
                                <select name="product_id" class="form-select" required>
                                    <option value="">Choose product...</option>
                                    <?php
                                        $select_products = $conn->prepare("SELECT id, name, SKU FROM `products`");
                                        $select_products->execute();
                                        while($product = $select_products->fetch(PDO::FETCH_ASSOC)) {
                                            echo "<option value='{$product['id']}'>{$product['name']} (SKU: {$product['SKU']})</option>";
                                        }
                                    ?>
                                </select>
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

                            <!-- Quantity -->
                            <div class="col-md-6">
                                <label class="form-label">Quantity</label>
                                <input type="number" name="quantity" class="form-control" min="1" required>
                            </div>

                            <!-- Unit Cost -->
                            <div class="col-md-6">
                                <label class="form-label">Unit Cost</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" name="unit_cost" class="form-control" step="0.01" required>
                                </div>
                            </div>

                            <!-- Quality Check -->
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="qualityCheck" required>
                                    <label class="form-check-label" for="qualityCheck">
                                        I confirm that the quality check has been performed
                                    </label>
                                </div>
                            </div>

                            <!-- Submit Buttons -->
                            <div class="col-12">
                                <hr>
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="inventory.php" class="btn btn-light">Cancel</a>
                                    <button type="submit" name="add_stock" class="btn btn-primary">
                                        <i class="fas fa-plus me-2"></i>Add Stock
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
.page-header {
    background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
    padding: 1.5rem;
    border-radius: 15px;
    color: white;
}

.page-header .breadcrumb-item a {
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
}

.page-header .breadcrumb-item.active {
    color: white;
}

.card {
    border: none;
    border-radius: 15px;
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
    .page-header {
        padding: 1rem;
    }
}
</style>

</body>
</html>