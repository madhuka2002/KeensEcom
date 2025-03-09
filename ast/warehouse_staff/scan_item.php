<?php
include '../components/connect.php';

session_start();

$staff_id = $_SESSION['staff_id'];

if(!isset($staff_id)){
   header('location:index.php');
}

// Handle manual item search
if(isset($_POST['search_item'])) {
    $search = $_POST['search'];
    $search = filter_var($search, FILTER_SANITIZE_STRING);
    
    $select_item = $conn->prepare("
        SELECT p.*, i.quantity, c.name as category_name 
        FROM `products` p 
        LEFT JOIN `inventory` i ON p.id = i.product_id 
        LEFT JOIN `category` c ON p.category_id = c.category_id 
        WHERE p.id = ? OR p.name LIKE ?
    ");
    $select_item->execute([$search, "%$search%"]);
    $item = $select_item->fetch(PDO::FETCH_ASSOC);
}

// Handle quantity update
if(isset($_POST['update_quantity'])) {
    $product_id = $_POST['product_id'];
    $new_quantity = $_POST['new_quantity'];
    $change_type = $_POST['change_type'];
    $reason = $_POST['reason'];
    
    // Update inventory
    $update_inventory = $conn->prepare("
        UPDATE `inventory` 
        SET quantity = CASE 
            WHEN ? = 'add' THEN quantity + ? 
            WHEN ? = 'subtract' THEN GREATEST(0, quantity - ?)
        END 
        WHERE product_id = ?
    ");
    $update_inventory->execute([$change_type, $new_quantity, $change_type, $new_quantity, $product_id]);

    // Log inventory change
    $insert_log = $conn->prepare("
        INSERT INTO `inventory_log` 
        (product_id, previous_quantity, new_quantity, change_type, reason) 
        VALUES (?, (SELECT quantity FROM inventory WHERE product_id = ?), ?, ?, ?)
    ");
    $insert_log->execute([$product_id, $product_id, $new_quantity, $change_type, $reason]);

    $message[] = 'Inventory updated successfully!';
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Keens | Scan Item</title>

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<?php include '../components/warehouse_staff_header.php'; ?>

<section class="scan-item min-vh-100 py-5">
    <div class="container">
        <div class="row justify-content-center">
            <!-- Scan/Search Section -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <div class="scanner-icon">
                                <i class="fas fa-barcode"></i>
                            </div>
                            <h4 class="mt-3">Scan Item</h4>
                            <p class="text-muted">Scan barcode or enter item ID/name</p>
                        </div>

                        <form action="" method="POST" class="mb-4">
                            <div class="input-group">
                                <input type="text" name="search" class="form-control form-control-lg" 
                                       placeholder="Scan or enter item ID/name" autofocus
                                       value="<?= isset($_POST['search']) ? $_POST['search'] : '' ?>">
                                <button type="submit" name="search_item" class="btn btn-primary">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </form>

                        <div class="text-center">
                            <button type="button" class="btn btn-outline-primary me-2" onclick="toggleCamera()">
                                <i class="fas fa-camera me-2"></i>Use Camera
                            </button>
                            <a href="inventory.php" class="btn btn-outline-secondary">
                                <i class="fas fa-list me-2"></i>View All Items
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Item Details Section -->
            <?php if(isset($item) && $item): ?>
            <div class="col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-4">
                            <img src="../uploaded_img/<?= $item['image_01'] ?>" 
                                 alt="" class="item-image me-3">
                            <div>
                                <h5 class="mb-1"><?= $item['name'] ?></h5>
                                <p class="text-muted mb-0">ID: #<?= $item['id'] ?></p>
                                <span class="badge bg-info"><?= $item['category_name'] ?></span>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-6">
                                <div class="item-stat">
                                    <label class="text-muted">Current Stock</label>
                                    <h4 class="mb-0"><?= $item['quantity'] ?? 0 ?></h4>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="item-stat">
                                    <label class="text-muted">Price</label>
                                    <h4 class="mb-0">$<?= number_format($item['price'], 2) ?></h4>
                                </div>
                            </div>
                        </div>

                        <form action="" method="POST">
                            <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Update Type</label>
                                <select name="change_type" class="form-select" required>
                                    <option value="add">Add Stock</option>
                                    <option value="subtract">Remove Stock</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Quantity</label>
                                <input type="number" name="new_quantity" class="form-control" 
                                       min="1" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Reason</label>
                                <select name="reason" class="form-select" required>
                                    <option value="new_stock">New Stock</option>
                                    <option value="damage">Damaged Items</option>
                                    <option value="return">Customer Return</option>
                                    <option value="correction">Stock Correction</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>

                            <button type="submit" name="update_quantity" class="btn btn-primary w-100">
                                Update Inventory
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<style>
.scanner-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    margin: 0 auto;
}

.item-image {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 10px;
}

.item-stat {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 10px;
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

/* Responsive Adjustments */
@media (max-width: 768px) {
    .scanner-icon {
        width: 60px;
        height: 60px;
        font-size: 1.5rem;
    }

    .item-image {
        width: 60px;
        height: 60px;
    }
}
</style>

<script>
function toggleCamera() {
    // Implement camera functionality if needed
    alert('Camera functionality not implemented in this version');
}

// Auto-focus search input on page load
window.onload = function() {
    document.querySelector('input[name="search"]').focus();
};
</script>

</body>
</html>