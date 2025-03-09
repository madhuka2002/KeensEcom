<?php
include '../components/connect.php';

session_start();

$manager_id = $_SESSION['manager_id'];

if(!isset($manager_id)){
   header('location:index.php');
}

// Handle Delete Product
if(isset($_GET['delete'])){
   $delete_id = $_GET['delete'];
   
   // Delete images first
   $delete_product_image = $conn->prepare("SELECT * FROM `products` WHERE id = ?");
   $delete_product_image->execute([$delete_id]);
   $fetch_delete_image = $delete_product_image->fetch(PDO::FETCH_ASSOC);
   
   if($fetch_delete_image){
      unlink('../uploaded_img/'.$fetch_delete_image['image_01']);
      unlink('../uploaded_img/'.$fetch_delete_image['image_02']);
      unlink('../uploaded_img/'.$fetch_delete_image['image_03']);
   }

   // Delete product
   $delete_product = $conn->prepare("DELETE FROM `products` WHERE id = ?");
   $delete_product->execute([$delete_id]);

   header('location:products.php');
}

// Handle Remove from Category
if(isset($_GET['remove_category'])){
   $product_id = $_GET['remove_category'];
   
   // Update product to remove category association
   $remove_category = $conn->prepare("UPDATE `products` SET category_id = NULL WHERE id = ?");
   $remove_category->execute([$product_id]);

   header('location:products.php');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Keens | Products</title>

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<?php include '../components/product_manager_header.php'; ?>

<section class="products">
    <div class="container-fluid px-4 py-5">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-0">Products</h2>
                <p class="text-muted mb-0">Manage your product catalog</p>
            </div>
            <a href="add_product.php" class="btn btn-primary rounded-3">
                <i class="fas fa-plus me-2"></i>Add New Product
            </a>
        </div>

        <!-- Filters and Search -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <!-- Search -->
                    <div class="col-12 col-md-4">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text" id="searchInput" class="form-control bg-light border-0" 
                                   placeholder="Search products...">
                        </div>
                    </div>
                    
                    <!-- Category Filter -->
                    <div class="col-12 col-md-3">
                        <select id="categoryFilter" class="form-select bg-light border-0">
                            <option value="">All Categories</option>
                            <?php
                                $select_categories = $conn->prepare("SELECT * FROM `category`");
                                $select_categories->execute();
                                while($category = $select_categories->fetch(PDO::FETCH_ASSOC)){
                                    echo "<option value='".$category['category_id']."'>".$category['name']."</option>";
                                }
                            ?>
                        </select>
                    </div>

                    <!-- Stock Status -->
                    <div class="col-12 col-md-3">
                        <select id="stockFilter" class="form-select bg-light border-0">
                            <option value="">All Stock Status</option>
                            <option value="in_stock">In Stock</option>
                            <option value="low_stock">Low Stock</option>
                            <option value="out_of_stock">Out of Stock</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Products List View -->
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Product</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock Status</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                $select_products = $conn->prepare("
                                    SELECT p.*, c.name as category_name, c.category_id,
                                    COALESCE(i.quantity, 0) as stock_quantity
                                    FROM `products` p 
                                    LEFT JOIN `category` c ON p.category_id = c.category_id
                                    LEFT JOIN `inventory` i ON p.id = i.product_id
                                    ORDER BY p.id DESC
                                ");
                                $select_products->execute();
                                
                                if($select_products->rowCount() > 0){
                                    while($fetch_products = $select_products->fetch(PDO::FETCH_ASSOC)){ 
                                        // Stock status based on actual inventory
                                        if($fetch_products['stock_quantity'] <= 0) {
                                            $stock_status = "Out of Stock";
                                            $status_class = "danger";
                                        } elseif($fetch_products['stock_quantity'] < 10) {
                                            $stock_status = "Low Stock";
                                            $status_class = "warning";
                                        } else {
                                            $stock_status = "In Stock";
                                            $status_class = "success";
                                        }
                            ?>
                            <tr class="product-row" data-category="<?= $fetch_products['category_id']; ?>">
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <img src="../uploaded_img/<?= $fetch_products['image_01']; ?>" 
                                             alt="<?= $fetch_products['name']; ?>"
                                             class="rounded me-3"
                                             style="width: 60px; height: 60px; object-fit: cover;">
                                        <div>
                                            <h6 class="mb-1"><?= $fetch_products['name']; ?></h6>
                                            <p class="text-muted small mb-0"><?= substr($fetch_products['details'], 0, 50); ?>...</p>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if(isset($fetch_products['category_name'])): ?>
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-primary-subtle text-primary me-2">
                                                <?= $fetch_products['category_name']; ?>
                                            </span>
                                            <a href="products.php?remove_category=<?= $fetch_products['id']; ?>" 
                                               class="btn btn-sm btn-outline-secondary" 
                                               onclick="return confirm('Remove this product from its category?');">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">No Category</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <h6 class="mb-0">$<?= $fetch_products['price']; ?></h6>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $status_class; ?>-subtle text-<?= $status_class; ?> rounded-pill">
                                        <?= $stock_status; ?>
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="d-flex justify-content-end">
                                        <a href="update_product.php?update=<?= $fetch_products['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary me-2">
                                            <i class="fas fa-edit me-1"></i> Edit
                                        </a>
                                        <a href="products.php?delete=<?= $fetch_products['id']; ?>" 
                                           class="btn btn-sm btn-outline-danger"
                                           onclick="return confirm('Delete this product?');">
                                            <i class="fas fa-trash-alt me-1"></i> Delete
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php
                                    }
                                } else {
                                    echo '<tr><td colspan="5" class="text-center py-4">No products added yet!</td></tr>';
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
/* Table styles */
.table {
    border-collapse: separate;
    border-spacing: 0;
}

.table th {
    font-weight: 600;
    padding: 1rem;
}

.table td {
    padding: 1rem;
    vertical-align: middle;
}

/* Button Styles */
.btn-primary {
    background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
    border: none;
    padding: 0.75rem 1.5rem;
    font-weight: 500;
}

.btn-primary:hover {
    background: linear-gradient(45deg, var(--accent-color), var(--primary-color));
}

/* Custom Form Controls */
.form-control, .form-select {
    padding: 0.75rem 1rem;
    font-size: 0.9rem;
}

.form-control:focus, .form-select:focus {
    box-shadow: none;
    border-color: var(--primary-color);
}

/* Stock Status Badges */
.badge {
    padding: 0.5em 1em;
    font-weight: 500;
}

/* Row hover effect */
.product-row {
    transition: background-color 0.2s ease;
}

.product-row:hover {
    background-color: rgba(0,0,0,0.02);
}

/* Search Input */
.input-group-text {
    border-right: none;
}
</style>

<script>
// Filter functionality
const searchInput = document.getElementById('searchInput');
const categoryFilter = document.getElementById('categoryFilter');
const stockFilter = document.getElementById('stockFilter');

// Add event listeners
searchInput.addEventListener('input', filterProducts);
categoryFilter.addEventListener('change', filterProducts);
stockFilter.addEventListener('change', filterProducts);

function filterProducts() {
    const searchTerm = searchInput.value.toLowerCase();
    const categoryValue = categoryFilter.value;
    const stockValue = stockFilter.value;
    
    // Get all product rows
    const productRows = document.querySelectorAll('.product-row');
    
    productRows.forEach(row => {
        const productName = row.querySelector('h6').textContent.toLowerCase();
        const productDesc = row.querySelector('.text-muted.small').textContent.toLowerCase();
        const stockStatus = row.querySelector('.badge:last-of-type').textContent.trim().toLowerCase();
        const categoryId = row.getAttribute('data-category');
        
        // Check if the product matches all filters
        const matchesSearch = productName.includes(searchTerm) || productDesc.includes(searchTerm);
        const matchesCategory = categoryValue === '' || categoryValue === categoryId;
        
        let matchesStock = true;
        if(stockValue === 'in_stock') {
            matchesStock = stockStatus === 'in stock';
        } else if(stockValue === 'low_stock') {
            matchesStock = stockStatus === 'low stock';
        } else if(stockValue === 'out_of_stock') {
            matchesStock = stockStatus === 'out of stock';
        }
        
        // Show or hide the row
        if(matchesSearch && matchesCategory && matchesStock) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
    
    // Check if there are any visible rows
    const visibleRows = document.querySelectorAll('.product-row[style=""]').length;
    
    // Show a message if no products match the filters
    const tableBody = document.querySelector('tbody');
    const noResultsRow = tableBody.querySelector('.no-results-row');
    
    if(visibleRows === 0) {
        if(!noResultsRow) {
            const newRow = document.createElement('tr');
            newRow.className = 'no-results-row';
            newRow.innerHTML = '<td colspan="5" class="text-center py-4">No products match your filters</td>';
            tableBody.appendChild(newRow);
        }
    } else {
        if(noResultsRow) {
            noResultsRow.remove();
        }
    }
}
</script>

</body>
</html>