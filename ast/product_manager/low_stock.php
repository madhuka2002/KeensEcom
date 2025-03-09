<?php
include '../components/connect.php';

session_start();

$manager_id = $_SESSION['manager_id'];

if(!isset($manager_id)){
   header('location:index.php');
}

// Stock threshold settings
$critical_threshold = 5;  // Critical stock level (red)
$warning_threshold = 10;  // Warning stock level (yellow)

// Create quick restock request
if(isset($_POST['quick_restock'])){
   $product_id = $_POST['product_id'];
   $supplier_id = $_POST['supplier_id'];
   $requested_quantity = $_POST['requested_quantity'];
   
   // Validate inputs
   if($requested_quantity <= 0){
      $message[] = 'Requested quantity must be greater than zero!';
   } else {
      // Check if there's already a pending request
      $check_request = $conn->prepare("SELECT * FROM `restock_requests` 
                                    WHERE product_id = ? AND supplier_id = ? 
                                    AND (status = 'pending' OR status = 'approved') 
                                    AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)");
      $check_request->execute([$product_id, $supplier_id]);
      
      if($check_request->rowCount() > 0){
         $existing = $check_request->fetch(PDO::FETCH_ASSOC);
         if($existing['status'] == 'pending') {
            $message[] = 'A pending request already exists for this product!';
         } else {
            $message[] = 'This product was already approved for restock within the last 7 days!';
         }
      } else {
         // Insert new request
         $insert_request = $conn->prepare("INSERT INTO `restock_requests`(supplier_id, product_id, requested_quantity, request_notes, status) VALUES(?,?,?,?,'pending')");
         $insert_request->execute([$supplier_id, $product_id, $requested_quantity, 'Quick restock from low stock management']);
         $message[] = 'Restock request submitted successfully!';
      }
   }
}

// Filter settings
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$threshold_filter = isset($_GET['threshold']) ? $_GET['threshold'] : '';
$supplier_filter = isset($_GET['supplier']) ? $_GET['supplier'] : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'stock_asc';

// Build the query
$query = "
    SELECT p.*, i.quantity, i.supplier_id, s.name as supplier_name, c.name as category_name
    FROM products p
    LEFT JOIN inventory i ON p.id = i.product_id
    LEFT JOIN suppliers s ON i.supplier_id = s.supplier_id
    LEFT JOIN category c ON p.category_id = c.category_id
    WHERE (i.quantity IS NULL OR i.quantity <= ?)
";
$params = [$warning_threshold];

// Apply filters
if(!empty($category_filter)){
    $query .= " AND p.category_id = ?";
    $params[] = $category_filter;
}

if(!empty($supplier_filter)){
    $query .= " AND i.supplier_id = ?";
    $params[] = $supplier_filter;
}

if($threshold_filter == 'critical'){
    $query .= " AND (i.quantity IS NULL OR i.quantity <= ?)";
    $params[] = $critical_threshold;
} elseif($threshold_filter == 'warning'){
    $query .= " AND i.quantity > ? AND i.quantity <= ?";
    $params[] = $critical_threshold;
    $params[] = $warning_threshold;
}

// Add sorting
if($sort_by == 'stock_asc'){
    $query .= " ORDER BY i.quantity ASC, p.name ASC";
} elseif($sort_by == 'stock_desc'){
    $query .= " ORDER BY i.quantity DESC, p.name ASC";
} elseif($sort_by == 'name_asc'){
    $query .= " ORDER BY p.name ASC";
} elseif($sort_by == 'name_desc'){
    $query .= " ORDER BY p.name DESC";
}

// Execute query
$select_products = $conn->prepare($query);
$select_products->execute($params);
$low_stock_products = $select_products->fetchAll(PDO::FETCH_ASSOC);

// Count products by threshold
$count_critical = 0;
$count_warning = 0;
$count_no_stock = 0;

foreach($low_stock_products as $product){
    if($product['quantity'] === null || $product['quantity'] == 0){
        $count_no_stock++;
    } elseif($product['quantity'] <= $critical_threshold){
        $count_critical++;
    } elseif($product['quantity'] <= $warning_threshold){
        $count_warning++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Keens | Low Stock Management</title>

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<?php include '../components/product_manager_header.php'; ?>

<section class="low-stock">
    <div class="container-fluid px-4 py-5">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-0">Low Stock Management</h2>
                <p class="text-muted mb-0">Track and restock products with low inventory</p>
            </div>
            <a href="restock_requests.php" class="btn btn-primary rounded-3">
                <i class="fas fa-truck-loading me-2"></i>View Restock Requests
            </a>
        </div>

        <!-- Overview Cards -->
        <div class="row g-4 mb-4">
            <div class="col-12 col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stock-icon stock-none">
                                <i class="fas fa-times"></i>
                            </div>
                            <div class="ms-3">
                                <h3 class="mb-0"><?= $count_no_stock ?></h3>
                                <p class="text-muted mb-0">Out of Stock</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stock-icon stock-critical">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div class="ms-3">
                                <h3 class="mb-0"><?= $count_critical ?></h3>
                                <p class="text-muted mb-0">Critical Stock (1-<?= $critical_threshold ?>)</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stock-icon stock-warning">
                                <i class="fas fa-exclamation"></i>
                            </div>
                            <div class="ms-3">
                                <h3 class="mb-0"><?= $count_warning ?></h3>
                                <p class="text-muted mb-0">Warning Stock (<?= $critical_threshold+1 ?>-<?= $warning_threshold ?>)</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select bg-light border-0">
                            <option value="">All Categories</option>
                            <?php
                                $categories = $conn->prepare("SELECT * FROM category ORDER BY name");
                                $categories->execute();
                                while($category = $categories->fetch(PDO::FETCH_ASSOC)){
                                    $selected = ($category_filter == $category['category_id']) ? 'selected' : '';
                                    echo "<option value='".$category['category_id']."' $selected>".$category['name']."</option>";
                                }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Stock Level</label>
                        <select name="threshold" class="form-select bg-light border-0">
                            <option value="" <?= $threshold_filter == '' ? 'selected' : '' ?>>All Low Stock</option>
                            <option value="critical" <?= $threshold_filter == 'critical' ? 'selected' : '' ?>>Critical Only</option>
                            <option value="warning" <?= $threshold_filter == 'warning' ? 'selected' : '' ?>>Warning Only</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Supplier</label>
                        <select name="supplier" class="form-select bg-light border-0">
                            <option value="">All Suppliers</option>
                            <?php
                                $suppliers = $conn->prepare("SELECT * FROM suppliers ORDER BY name");
                                $suppliers->execute();
                                while($supplier = $suppliers->fetch(PDO::FETCH_ASSOC)){
                                    $selected = ($supplier_filter == $supplier['supplier_id']) ? 'selected' : '';
                                    echo "<option value='".$supplier['supplier_id']."' $selected>".$supplier['name']."</option>";
                                }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Sort By</label>
                        <select name="sort" class="form-select bg-light border-0">
                            <option value="stock_asc" <?= $sort_by == 'stock_asc' ? 'selected' : '' ?>>Stock Level (Low to High)</option>
                            <option value="stock_desc" <?= $sort_by == 'stock_desc' ? 'selected' : '' ?>>Stock Level (High to Low)</option>
                            <option value="name_asc" <?= $sort_by == 'name_asc' ? 'selected' : '' ?>>Product Name (A-Z)</option>
                            <option value="name_desc" <?= $sort_by == 'name_desc' ? 'selected' : '' ?>>Product Name (Z-A)</option>
                        </select>
                    </div>
                    <div class="col-12 text-end">
                        <a href="low_stock.php" class="btn btn-light me-2">
                            <i class="fas fa-redo me-2"></i>Reset Filters
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter me-2"></i>Apply Filters
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Products Table -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Product</th>
                                <th>Category</th>
                                <th>Stock Level</th>
                                <th>Supplier</th>
                                <th>Price</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($low_stock_products) > 0): ?>
                                <?php foreach($low_stock_products as $product): ?>
                                    <?php
                                        // Determine stock status for styling
                                        if($product['quantity'] === null || $product['quantity'] == 0){
                                            $stock_status = 'none';
                                            $status_text = 'Out of Stock';
                                        } elseif($product['quantity'] <= $critical_threshold){
                                            $stock_status = 'critical';
                                            $status_text = 'Critical';
                                        } else {
                                            $stock_status = 'warning';
                                            $status_text = 'Low';
                                        }
                                    ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center">
                                                <img src="../uploaded_img/<?= $product['image_01'] ?>" 
                                                     alt="<?= $product['name'] ?>" 
                                                     class="product-thumbnail">
                                                <div class="ms-3">
                                                    <h6 class="mb-0"><?= $product['name'] ?></h6>
                                                    <small class="text-muted">ID: <?= $product['id'] ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?= $product['category_name'] ?? 'Uncategorized' ?>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="stock-badge stock-<?= $stock_status ?>">
                                                    <?= $product['quantity'] ?? 0 ?>
                                                </div>
                                                <span class="ms-2"><?= $status_text ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <?= $product['supplier_name'] ?? 'Not assigned' ?>
                                        </td>
                                        <td>
                                            $<?= number_format($product['price'], 2) ?>
                                        </td>
                                        <td class="text-end pe-4">
                                            <button class="btn btn-sm btn-primary me-2" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#restockModal<?= $product['id'] ?>">
                                                <i class="fas fa-truck-loading me-1"></i>Restock
                                            </button>
                                            <a href="update_product.php?update=<?= $product['id'] ?>" class="btn btn-sm btn-light">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </td>
                                    </tr>

                                    <!-- Quick Restock Modal -->
                                    <div class="modal fade" id="restockModal<?= $product['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content border-0">
                                                <form method="post" action="">
                                                    <div class="modal-header border-0">
                                                        <h5 class="modal-title fw-bold">Quick Restock Request</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="text-center mb-4">
                                                            <img src="../uploaded_img/<?= $product['image_01'] ?>" 
                                                                 alt="<?= $product['name'] ?>" 
                                                                 class="product-image-lg">
                                                            <h5 class="mt-3"><?= $product['name'] ?></h5>
                                                            <div class="stock-badge stock-<?= $stock_status ?> mx-auto">
                                                                Current Stock: <?= $product['quantity'] ?? 0 ?>
                                                            </div>
                                                        </div>
                                                        
                                                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label">Select Supplier</label>
                                                            <select name="supplier_id" class="form-select" required>
                                                                <?php if($product['supplier_id']): ?>
                                                                    <option value="<?= $product['supplier_id'] ?>"><?= $product['supplier_name'] ?> (Current)</option>
                                                                <?php else: ?>
                                                                    <option value="">Select a supplier</option>
                                                                    <?php
                                                                        $suppliers = $conn->prepare("SELECT * FROM suppliers ORDER BY name");
                                                                        $suppliers->execute();
                                                                        while($supplier = $suppliers->fetch(PDO::FETCH_ASSOC)){
                                                                            echo "<option value='".$supplier['supplier_id']."'>".$supplier['name']."</option>";
                                                                        }
                                                                    ?>
                                                                <?php endif; ?>
                                                            </select>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label">Quantity to Request</label>
                                                            <?php 
                                                                // Calculate suggested quantity
                                                                $current = $product['quantity'] ?? 0;
                                                                $suggested = max(20 - $current, 10); // Either refill to 20 or at least 10 units
                                                            ?>
                                                            <input type="number" name="requested_quantity" required class="form-control" 
                                                                   min="1" value="<?= $suggested ?>" placeholder="Enter quantity">
                                                            <small class="text-muted">Suggested quantity to restore optimal stock levels</small>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer border-0">
                                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" name="quick_restock" class="btn btn-primary">
                                                            Send Restock Request
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <div class="empty-state">
                                            <i class="fas fa-check-circle empty-icon"></i>
                                            <p>No products with low stock!</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
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
    --none-color: #6c757d;
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

/* Stock Icon */
.stock-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
}

.stock-none {
    background: rgba(108, 117, 125, 0.1);
    color: var(--none-color);
}

.stock-critical {
    background: rgba(220, 53, 69, 0.1);
    color: var(--danger-color);
}

.stock-warning {
    background: rgba(243, 156, 18, 0.1);
    color: var(--warning-color);
}

/* Stock Badge */
.stock-badge {
    min-width: 40px;
    padding: 0.25rem 0.5rem;
    text-align: center;
    border-radius: 6px;
    font-weight: 600;
    font-size: 0.85rem;
}

.stock-none {
    background: rgba(108, 117, 125, 0.1);
    color: var(--none-color);
}

.stock-critical {
    background: rgba(220, 53, 69, 0.1);
    color: var(--danger-color);
}

.stock-warning {
    background: rgba(243, 156, 18, 0.1);
    color: var(--warning-color);
}

/* Product Images */
.product-thumbnail {
    width: 40px;
    height: 40px;
    object-fit: cover;
    border-radius: 8px;
}

.product-image-lg {
    width: 120px;
    height: 120px;
    object-fit: cover;
    border-radius: 12px;
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

/* Empty State */
.empty-state {
    padding: 2rem;
    text-align: center;
}

.empty-icon {
    font-size: 3rem;
    color: var(--success-color);
    margin-bottom: 1rem;
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

/* Responsive Adjustments */
@media (max-width: 768px) {
    .container-fluid {
        padding: 1rem !important;
    }
    
    .btn-primary {
        width: 100%;
    }
    
    .stock-badge {
        min-width: 35px;
        padding: 0.2rem 0.4rem;
    }
}
</style>

</body>
</html>