<?php
include '../components/connect.php';

session_start();

if (!isset($_SESSION['supplier_id'])) {
    header('location:index.php');
    exit();
}

$supplier_id = $_SESSION['supplier_id'];

// Handle product deletion
if (isset($_GET['delete'])) {
    $product_id = filter_var($_GET['delete'], FILTER_VALIDATE_INT);
    
    if ($product_id) {
        try {
            // First, remove from inventory
            $delete_inventory = $conn->prepare("DELETE FROM inventory WHERE product_id = ? AND supplier_id = ?");
            $delete_inventory->execute([$product_id, $supplier_id]);

            // Then delete the product
            $delete_product = $conn->prepare("DELETE FROM products WHERE id = ?");
            $delete_product->execute([$product_id]);

            $message[] = "Product deleted successfully!";
        } catch (PDOException $e) {
            $error[] = "Error deleting product: " . $e->getMessage();
        }
    }
}

// Count total products
$count_query = $conn->prepare("
    SELECT COUNT(*) as total_count
    FROM products p
    LEFT JOIN inventory i ON p.id = i.product_id AND i.supplier_id = ?
    WHERE i.supplier_id = ?
");
$count_query->execute([$supplier_id, $supplier_id]);
$total_count = $count_query->fetch(PDO::FETCH_ASSOC)['total_count'];

// Fetch products with inventory information
$products_query = $conn->prepare("
    SELECT p.*, COALESCE(i.quantity, 0) as inventory_quantity 
    FROM products p
    LEFT JOIN inventory i ON p.id = i.product_id AND i.supplier_id = ?
    ORDER BY p.id DESC
");
$products_query->execute([$supplier_id]);
$products = $products_query->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keens | My Products</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4895ef;
            --background-light: #f4f7fe;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --success-color: #198754;
            --text-dark: #2b3452;
            --text-muted: #6c757d;
            --border-color: #e9ecef;
            --card-shadow: 0 8px 20px rgba(67, 97, 238, 0.07);
            --hover-shadow: 0 10px 25px rgba(67, 97, 238, 0.15);
        }
        
        body {
            background-color: var(--background-light);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-dark);
        }
        
        /* Dashboard Layout */
        .dashboard-container {
            display: flex;
            min-height: calc(100vh - 140px);
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            padding: 1.5rem;
        }
        
        /* Page Header */
        .page-header {
            background: white;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .page-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0;
            display: flex;
            align-items: center;
        }
        
        .page-title i {
            margin-right: 0.75rem;
            color: var(--primary-color);
        }
        
        /* Products Grid */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }
        
        /* Product Card */
        .product-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            border: none;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--hover-shadow);
        }
        
        .card-header {
            background: white;
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 1.25rem;
        }
        
        .card-title {
            font-weight: 600;
            margin-bottom: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .product-image-container {
            height: 220px;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem;
        }
        
        .product-image {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            transition: transform 0.3s ease;
        }
        
        .product-card:hover .product-image {
            transform: scale(1.05);
        }
        
        .product-details {
            padding: 0 1.25rem;
            flex-grow: 1;
        }
        
        .product-description {
            color: var(--text-muted);
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            min-height: 4rem;
        }
        
        .product-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
            padding: 0.75rem 1.25rem 1.25rem;
        }
        
        .product-price {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-icon {
            width: 38px;
            height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-icon:hover {
            transform: translateY(-3px);
        }
        
        /* Stock Badge */
        .stock-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            padding: 0.4rem 0.75rem;
            border-radius: 8px;
            font-weight: 600;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            z-index: 1;
        }
        
        /* Empty State */
        .empty-state {
            background: white;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            padding: 3rem 2rem;
            text-align: center;
            margin-top: 2rem;
        }
        
        .empty-icon {
            font-size: 4rem;
            color: var(--text-muted);
            margin-bottom: 1.5rem;
        }
        
        /* Filter Bar */
        .filter-bar {
            background: white;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .search-input {
            width: 280px;
            border-radius: 8px;
        }
        
        .filter-dropdown .dropdown-menu {
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-radius: 8px;
            padding: 0.5rem;
        }
        
        .filter-dropdown .dropdown-item {
            border-radius: 4px;
            padding: 0.5rem 1rem;
        }
        
        .filter-dropdown .dropdown-item:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }
        
        .filter-dropdown .dropdown-item.active {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
            font-weight: 600;
        }
        
        /* Stats */
        .stats-container {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            padding: 1.25rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            flex: 1;
            min-width: 200px;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }
        
        .stat-info h3 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }
        
        .stat-info p {
            color: var(--text-muted);
            margin-bottom: 0;
            font-size: 0.85rem;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .header-actions {
                display: flex;
                width: 100%;
                justify-content: space-between;
            }
            
            .filter-bar {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .search-input {
                width: 100%;
            }
        }
        
        @media (max-width: 768px) {
            .stats-container {
                flex-direction: column;
            }
            
            .products-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include '../components/supplier_header.php'; ?>

    <div class="dashboard-container">
        <div class="main-content">
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">
                    <i class="fas fa-box"></i>My Products
                </h1>
                <div class="header-actions">
                    <a href="inventory.php" class="btn btn-outline-primary rounded-pill me-2">
                        <i class="fas fa-boxes me-1"></i>Inventory
                    </a>
                    <a href="new_product.php" class="btn btn-primary rounded-pill">
                        <i class="fas fa-plus me-1"></i>Add New Product
                    </a>
                </div>
            </div>
            
            <!-- Alerts -->
            <?php if (isset($message)): ?>
                <?php foreach($message as $msg): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($msg) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <?php foreach($error as $err): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($err) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <!-- Stats -->
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= $total_count ?></h3>
                        <p>Total Products</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background-color: rgba(220, 53, 69, 0.1); color: var(--danger-color);">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-info">
                        <?php
                            $low_stock = 0;
                            foreach($products as $product) {
                                if($product['inventory_quantity'] < 10) {
                                    $low_stock++;
                                }
                            }
                        ?>
                        <h3><?= $low_stock ?></h3>
                        <p>Low Stock Items</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background-color: rgba(25, 135, 84, 0.1); color: var(--success-color);">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <?php
                            $in_stock = 0;
                            foreach($products as $product) {
                                if($product['inventory_quantity'] > 0) {
                                    $in_stock++;
                                }
                            }
                        ?>
                        <h3><?= $in_stock ?></h3>
                        <p>In Stock Products</p>
                    </div>
                </div>
            </div>
            
            <!-- Filter Bar -->
            <div class="filter-bar">
                <div class="d-flex align-items-center gap-2">
                    <div class="input-group search-input">
                        <span class="input-group-text bg-transparent border-end-0">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                        <input type="text" class="form-control border-start-0" id="searchProducts" placeholder="Search products...">
                    </div>
                    
                    <div class="dropdown filter-dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-filter me-1"></i>Filter
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item active filter-option" data-filter="all" href="#">All Products</a></li>
                            <li><a class="dropdown-item filter-option" data-filter="in-stock" href="#">In Stock</a></li>
                            <li><a class="dropdown-item filter-option" data-filter="low-stock" href="#">Low Stock</a></li>
                            <li><a class="dropdown-item filter-option" data-filter="out-of-stock" href="#">Out of Stock</a></li>
                        </ul>
                    </div>
                    
                    <div class="dropdown filter-dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-sort me-1"></i>Sort
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item active sort-option" data-sort="newest" href="#">Newest First</a></li>
                            <li><a class="dropdown-item sort-option" data-sort="name-asc" href="#">Name (A-Z)</a></li>
                            <li><a class="dropdown-item sort-option" data-sort="name-desc" href="#">Name (Z-A)</a></li>
                            <li><a class="dropdown-item sort-option" data-sort="price-asc" href="#">Price (Low to High)</a></li>
                            <li><a class="dropdown-item sort-option" data-sort="price-desc" href="#">Price (High to Low)</a></li>
                            <li><a class="dropdown-item sort-option" data-sort="stock-asc" href="#">Stock (Low to High)</a></li>
                            <li><a class="dropdown-item sort-option" data-sort="stock-desc" href="#">Stock (High to Low)</a></li>
                        </ul>
                    </div>
                </div>
                
                <div class="text-muted d-none d-md-block">
                    Showing <span id="shown-count"><?= count($products) ?></span> of <?= count($products) ?> products
                </div>
            </div>

            <?php if (empty($products)): ?>
                <!-- Empty State -->
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-box-open"></i>
                    </div>
                    <h3>No Products Yet</h3>
                    <p class="text-muted mb-4">
                        You haven't added any products to your inventory yet. 
                        Start by adding your first product to manage your inventory effectively.
                    </p>
                    <a href="new_product.php" class="btn btn-primary rounded-pill px-4 py-2">
                        <i class="fas fa-plus me-2"></i>Add Your First Product
                    </a>
                </div>
            <?php else: ?>
                <!-- Products Grid -->
                <div class="products-grid">
                    <?php foreach ($products as $product): ?>
                        <div class="product-card position-relative product-item"
                             data-name="<?= htmlspecialchars(strtolower($product['name'])) ?>"
                             data-price="<?= $product['price'] ?>"
                             data-stock="<?= $product['inventory_quantity'] ?>"
                             data-category="<?= htmlspecialchars($product['category'] ?? '') ?>">
                            
                            <!-- Stock Badge -->
                            <div class="stock-badge badge bg-<?= 
                                $product['inventory_quantity'] < 1 ? 'secondary' : 
                                ($product['inventory_quantity'] < 10 ? 'danger' : 
                                ($product['inventory_quantity'] < 50 ? 'warning' : 'success'))
                            ?>">
                                <i class="fas fa-<?= 
                                    $product['inventory_quantity'] < 1 ? 'ban' : 
                                    ($product['inventory_quantity'] < 10 ? 'exclamation-triangle' : 
                                    ($product['inventory_quantity'] < 50 ? 'exclamation-circle' : 'check-circle'))
                                ?> me-1"></i>
                                <?= $product['inventory_quantity'] < 1 ? 'Out of Stock' : $product['inventory_quantity'].' in stock' ?>
                            </div>
                            
                            <div class="card-header">
                                <h5 class="card-title" title="<?= htmlspecialchars($product['name']) ?>">
                                    <?= htmlspecialchars($product['name']) ?>
                                </h5>
                            </div>
                            

                            
                            <div class="product-details">
                                <p class="product-description">
                                    <?= htmlspecialchars($product['details']) ?>
                                </p>
                                
                                <?php if (!empty($product['category'])): ?>
                                    <span class="badge" style="background-color: rgba(67, 97, 238, 0.1); color: var(--primary-color);">
                                        <?= htmlspecialchars(ucfirst($product['category'])) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="product-meta">
                                <span class="product-price">
                                    $<?= number_format($product['price'], 2) ?>
                                </span>
                                
                                <div class="action-buttons">
                                    <a href="edit_product.php?id=<?= $product['id'] ?>" 
                                       class="btn btn-outline-primary btn-icon" 
                                       data-bs-toggle="tooltip" 
                                       title="Edit Product">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?delete=<?= $product['id'] ?>" 
                                       class="btn btn-outline-danger btn-icon" 
                                       data-bs-toggle="tooltip" 
                                       title="Delete Product"
                                       onclick="return confirm('Are you sure you want to delete this product?');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Initialize tooltips
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
        
        // Search functionality
        const searchInput = document.getElementById('searchProducts');
        const productItems = document.querySelectorAll('.product-item');
        const shownCountEl = document.getElementById('shown-count');
        
        searchInput.addEventListener('keyup', filterProducts);
        
        // Filter options
        const filterOptions = document.querySelectorAll('.filter-option');
        filterOptions.forEach(option => {
            option.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Update active state
                filterOptions.forEach(opt => opt.classList.remove('active'));
                this.classList.add('active');
                
                filterProducts();
            });
        });
        
        // Sort options
        const sortOptions = document.querySelectorAll('.sort-option');
        sortOptions.forEach(option => {
            option.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Update active state
                sortOptions.forEach(opt => opt.classList.remove('active'));
                this.classList.add('active');
                
                sortProducts(this.getAttribute('data-sort'));
            });
        });
        
        function filterProducts() {
            const searchText = searchInput.value.toLowerCase();
            const activeFilter = document.querySelector('.filter-option.active').getAttribute('data-filter');
            
            let visibleCount = 0;
            
            productItems.forEach(item => {
                const name = item.getAttribute('data-name');
                const stock = parseInt(item.getAttribute('data-stock'));
                
                let matchesSearch = name.includes(searchText);
                let matchesFilter = true;
                
                // Apply filter
                if (activeFilter === 'in-stock') {
                    matchesFilter = stock > 0;
                } else if (activeFilter === 'low-stock') {
                    matchesFilter = stock > 0 && stock < 10;
                } else if (activeFilter === 'out-of-stock') {
                    matchesFilter = stock === 0;
                }
                
                if (matchesSearch && matchesFilter) {
                    item.style.display = '';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });
            
            // Update counter
            shownCountEl.textContent = visibleCount;
        }
        
        function sortProducts(sortType) {
            const productsGrid = document.querySelector('.products-grid');
            const productsArray = Array.from(productItems);
            
            productsArray.sort((a, b) => {
                switch(sortType) {
                    case 'name-asc':
                        return a.getAttribute('data-name').localeCompare(b.getAttribute('data-name'));
                    case 'name-desc':
                        return b.getAttribute('data-name').localeCompare(a.getAttribute('data-name'));
                    case 'price-asc':
                        return parseFloat(a.getAttribute('data-price')) - parseFloat(b.getAttribute('data-price'));
                    case 'price-desc':
                        return parseFloat(b.getAttribute('data-price')) - parseFloat(a.getAttribute('data-price'));
                    case 'stock-asc':
                        return parseInt(a.getAttribute('data-stock')) - parseInt(b.getAttribute('data-stock'));
                    case 'stock-desc':
                        return parseInt(b.getAttribute('data-stock')) - parseInt(a.getAttribute('data-stock'));
                    default: // newest first - using original order
                        return 0;
                }
            });
            
            // Reappend in sorted order
            productsArray.forEach(product => {
                productsGrid.appendChild(product);
            });
        }
    </script>
</body>
</html>