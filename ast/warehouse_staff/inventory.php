<?php
include '../components/connect.php';

session_start();

$staff_id = $_SESSION['staff_id'];

if(!isset($staff_id)){
   header('location:index.php');
}

// Handle stock updates
if(isset($_POST['update_stock'])){
   $inventory_id = $_POST['inventory_id'];
   $inventory_id = filter_var($inventory_id, FILTER_SANITIZE_STRING);
   $quantity = $_POST['quantity'];
   $quantity = filter_var($quantity, FILTER_SANITIZE_STRING);

   $update_stock = $conn->prepare("UPDATE `inventory` SET quantity = ? WHERE inventory_id = ?");
   $update_stock->execute([$quantity, $inventory_id]);
   $message[] = 'Stock updated successfully!';
}

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';

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

<?php include '../components/warehouse_staff_header.php'; ?>

<section class="inventory-management">
    <div class="container-fluid px-4 py-5">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h3 mb-0">Inventory Management</h2>
            
        </div>

        <!-- Search and Filter Section -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form action="" method="GET" class="row g-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text" name="search" class="form-control border-start-0" 
                                   placeholder="Search by product name or ID" value="<?= htmlspecialchars($search) ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <select name="category" class="form-select">
                            <option value="">All Categories</option>
                            <?php
                                $select_categories = $conn->prepare("SELECT * FROM `category`");
                                $select_categories->execute();
                                while($category_row = $select_categories->fetch(PDO::FETCH_ASSOC)){
                                    $selected = ($category == $category_row['category_id']) ? 'selected' : '';
                                    echo "<option value='{$category_row['category_id']}' $selected>{$category_row['name']}</option>";
                                }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Inventory Table -->
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th>ID</th>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Stock Level</th>
                                <th>Status</th>
                                <th>Last Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                $query = "SELECT i.*, p.name as product_name, p.image_01, c.name as category_name 
                                         FROM `inventory` i 
                                         JOIN `products` p ON i.product_id = p.id 
                                         LEFT JOIN `category` c ON p.category_id = c.category_id 
                                         WHERE 1=1";
                                
                                if(!empty($search)) {
                                    $query .= " AND (p.name LIKE :search OR p.id LIKE :search)";
                                }
                                if(!empty($category)) {
                                    $query .= " AND p.category_id = :category";
                                }
                                
                                $select_inventory = $conn->prepare($query);
                                
                                if(!empty($search)) {
                                    $searchTerm = "%{$search}%";
                                    $select_inventory->bindParam(':search', $searchTerm);
                                }
                                if(!empty($category)) {
                                    $select_inventory->bindParam(':category', $category);
                                }
                                
                                $select_inventory->execute();

                                if($select_inventory->rowCount() > 0){
                                    while($fetch_inventory = $select_inventory->fetch(PDO::FETCH_ASSOC)){
                            ?>
                            <tr>
                                <td><?= $fetch_inventory['inventory_id']; ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="../uploaded_img/<?= $fetch_inventory['image_01']; ?>" 
                                             alt="" class="rounded" width="40">
                                        <span class="ms-2"><?= $fetch_inventory['product_name']; ?></span>
                                    </div>
                                </td>
                                <td><?= $fetch_inventory['category_name']; ?></td>
                                <td>
                                    <form action="" method="POST" class="d-flex align-items-center gap-2">
                                        <input type="hidden" name="inventory_id" value="<?= $fetch_inventory['inventory_id']; ?>">
                                        <input type="number" name="quantity" 
                                               value="<?= $fetch_inventory['quantity']; ?>" 
                                               class="form-control form-control-sm" style="width: 80px;">
                                        <button type="submit" name="update_stock" 
                                                class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <?php if($fetch_inventory['quantity'] <= 10): ?>
                                        <span class="badge bg-danger">Low Stock</span>
                                    <?php elseif($fetch_inventory['quantity'] <= 20): ?>
                                        <span class="badge bg-warning">Medium Stock</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">In Stock</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('Y-m-d H:i', strtotime($fetch_inventory['last_updated'])); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="inventory_details.php?id=<?= $fetch_inventory['inventory_id']; ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit_inventory.php?id=<?= $fetch_inventory['inventory_id']; ?>" 
                                           class="btn btn-sm btn-outline-secondary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php
                                    }
                                } else {
                                    echo '<tr><td colspan="7" class="text-center py-4">No inventory items found</td></tr>';
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

.badge {
    padding: 0.5em 0.8em;
    font-weight: 500;
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

</body>
</html>