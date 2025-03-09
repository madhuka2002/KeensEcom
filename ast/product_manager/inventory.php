<?php
include '../components/connect.php';

session_start();

$manager_id = $_SESSION['manager_id'];

if(!isset($manager_id)){
   header('location:index.php');
}

// Handle Update Stock
if(isset($_POST['update_stock'])) {
    $product_id = $_POST['product_id'];
    $new_quantity = $_POST['quantity'];
    
    $update_stock = $conn->prepare("UPDATE `inventory` SET quantity = ? WHERE product_id = ?");
    $update_stock->execute([$new_quantity, $product_id]);
    
    $message[] = 'Stock quantity updated successfully!';
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Keens | Inventory Management</title>

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<?php include '../components/product_manager_header.php'; ?>

<section class="inventory">
    <div class="container-fluid px-4 py-5">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-0">Inventory Management</h2>
                <p class="text-muted mb-0">Monitor and manage your stock levels</p>
            </div>
            <button class="btn btn-primary rounded-3" data-bs-toggle="modal" data-bs-target="#addStockModal">
                <i class="fas fa-plus me-2"></i>Add Stock Entry
            </button>
        </div>

        <!-- Filters -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text" class="form-control bg-light border-0" 
                                   placeholder="Search products...">
                        </div>
                    </div>
                    <div class="col-12 col-md-3">
                        <select class="form-select bg-light border-0">
                            <option value="">All Stock Status</option>
                            <option value="in_stock">In Stock</option>
                            <option value="low_stock">Low Stock</option>
                            <option value="out_of_stock">Out of Stock</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Inventory Table -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Product</th>
                                <th>SKU</th>
                                <th>Stock Level</th>
                                <th>Status</th>
                                <th>Last Updated</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                $select_inventory = $conn->prepare("
                                    SELECT p.*, i.quantity, i.storage_location 
                                    FROM `products` p 
                                    LEFT JOIN `inventory` i ON p.id = i.product_id
                                ");
                                $select_inventory->execute();
                                if($select_inventory->rowCount() > 0){
                                    while($fetch_inventory = $select_inventory->fetch(PDO::FETCH_ASSOC)){
                            ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <img src="../uploaded_img/<?= $fetch_inventory['image_01']; ?>" 
                                             alt="<?= $fetch_inventory['name']; ?>"
                                             class="rounded-3"
                                             style="width: 40px; height: 40px; object-fit: cover;">
                                        <div class="ms-3">
                                            <h6 class="mb-0"><?= $fetch_inventory['name']; ?></h6>
                                            <small class="text-muted"><?= $fetch_inventory['storage_location'] ?? 'No location set'; ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?= $fetch_inventory['id']; // You might want to add a proper SKU field ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="me-3"><?= $fetch_inventory['quantity'] ?? 0; ?></span>
                                        <button class="btn btn-sm btn-light" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#updateStockModal<?= $fetch_inventory['id']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                        $quantity = $fetch_inventory['quantity'] ?? 0;
                                        if($quantity > 10) {
                                            echo '<span class="badge bg-success-subtle text-success">In Stock</span>';
                                        } elseif($quantity > 0) {
                                            echo '<span class="badge bg-warning-subtle text-warning">Low Stock</span>';
                                        } else {
                                            echo '<span class="badge bg-danger-subtle text-danger">Out of Stock</span>';
                                        }
                                    ?>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?= date('M d, Y', strtotime($fetch_inventory['updated_at'] ?? 'now')); ?>
                                    </small>
                                </td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-sm btn-light me-2" 
                                            onclick="window.location.href='product_details.php?id=<?= $fetch_inventory['id']; ?>'">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-light" 
                                            onclick="window.location.href='restock_request.php?id=<?= $fetch_inventory['id']; ?>'">
                                        <i class="fas fa-truck-loading"></i>
                                    </button>
                                </td>
                            </tr>

                            <!-- Update Stock Modal for each product -->
                            <div class="modal fade" id="updateStockModal<?= $fetch_inventory['id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content border-0">
                                        <form method="post" action="">
                                            <div class="modal-header border-0">
                                                <h5 class="modal-title fw-bold">Update Stock Level</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="product_id" value="<?= $fetch_inventory['id']; ?>">
                                                <div class="mb-3">
                                                    <label class="form-label">Current Stock: <?= $fetch_inventory['quantity'] ?? 0; ?></label>
                                                    <input type="number" name="quantity" class="form-control" 
                                                           value="<?= $fetch_inventory['quantity'] ?? 0; ?>" required>
                                                </div>
                                            </div>
                                            <div class="modal-footer border-0">
                                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" name="update_stock" class="btn btn-primary">Update Stock</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php
                                    }
                                } else {
                                    echo '<tr><td colspan="6" class="text-center py-4">No inventory records found</td></tr>';
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
:root {
    --primary-color: #4361ee;
    --secondary-color: #3f37c9;
    --accent-color: #4895ef;
    --success-color: #2ecc71;
    --warning-color: #f39c12;
    --danger-color: #dc3545;
}

/* Card Styles */
.card {
    border-radius: 15px;
    overflow: hidden;
}

/* Table Styles */
.table {
    margin-bottom: 0;
}

.table th {
    font-weight: 600;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.table td {
    font-size: 0.9rem;
}

/* Form Controls */
.form-control, .form-select {
    padding: 0.75rem 1rem;
    font-size: 0.9rem;
}

.form-control:focus, .form-select:focus {
    box-shadow: none;
    border-color: var(--primary-color);
}

/* Button Styles */
.btn-primary {
    background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
    border: none;
    padding: 0.75rem 1.5rem;
    font-weight: 500;
}

.btn-light {
    background: #f8f9fa;
    border: 1px solid #eee;
}

/* Badge Styles */
.badge {
    font-weight: 500;
    padding: 0.5em 1em;
}

/* Modal Styles */
.modal-content {
    border-radius: 15px;
}

.modal-header, .modal-footer {
    padding: 1.25rem 1.5rem;
}

.modal-body {
    padding: 1.5rem;
}

/* Status Colors */
.bg-success-subtle {
    background-color: rgba(46, 204, 113, 0.1) !important;
}

.bg-warning-subtle {
    background-color: rgba(243, 156, 18, 0.1) !important;
}

.bg-danger-subtle {
    background-color: rgba(220, 53, 69, 0.1) !important;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .container-fluid {
        padding: 1rem !important;
    }
    
    .btn-primary {
        width: 100%;
    }
}
</style>

</body>
</html>