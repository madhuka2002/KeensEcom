<?php
include '../components/connect.php';

session_start();

if (!isset($_SESSION['supplier_id'])) {
    header('location:index.php');
    exit();
}

$supplier_id = $_SESSION['supplier_id'];

// Pagination setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Filter options
$product_filter = isset($_GET['product']) ? $_GET['product'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$change_type = isset($_GET['change_type']) ? $_GET['change_type'] : '';

// Base query for stock updates
$query = "
    FROM inventory_log il
    JOIN products p ON il.product_id = p.id
    WHERE il.supplier_id = ?
";
$params = [$supplier_id];

// Apply filters
if (!empty($product_filter)) {
    $query .= " AND p.name LIKE ?";
    $params[] = "%$product_filter%";
}

if (!empty($date_from)) {
    $query .= " AND il.logged_at >= ?";
    $params[] = $date_from . ' 00:00:00';
}

if (!empty($date_to)) {
    $query .= " AND il.logged_at <= ?";
    $params[] = $date_to . ' 23:59:59';
}

if (!empty($change_type)) {
    $query .= " AND il.change_type = ?";
    $params[] = $change_type;
}

// Count total records
$count_query = $conn->prepare("SELECT COUNT(*) as total $query");
$count_query->execute($params);
$total_records = $count_query->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_records / $records_per_page);

// Fetch stock updates
$updates_query = $conn->prepare("
    SELECT il.*, p.name as product_name $query 
    ORDER BY il.logged_at DESC 
    LIMIT $records_per_page OFFSET $offset
");
$updates_query->execute($params);
$stock_updates = $updates_query->fetchAll(PDO::FETCH_ASSOC);

// Fetch products for filter dropdown
$products_query = $conn->prepare("
    SELECT DISTINCT p.id, p.name 
    FROM products p
    JOIN inventory i ON p.id = i.product_id
    WHERE i.supplier_id = ?
    ORDER BY p.name
");
$products_query->execute([$supplier_id]);
$available_products = $products_query->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keens | Stock Updates</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
</head>
<body>
    <?php include '../components/supplier_header.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12 d-flex justify-content-between align-items-center mb-4">
                <h1>
                    <i class="fas fa-sync me-2"></i>Stock Updates
                </h1>
                <a href="inventory_update.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Update Inventory
                </a>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Product</label>
                            <select name="product" class="form-select">
                                <option value="">All Products</option>
                                <?php foreach ($available_products as $product): ?>
                                    <option value="<?= $product['id'] ?>" 
                                        <?= $product_filter == $product['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($product['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Change Type</label>
                            <select name="change_type" class="form-select">
                                <option value="">All Changes</option>
                                <option value="add" <?= $change_type == 'add' ? 'selected' : '' ?>>
                                    Added
                                </option>
                                <option value="remove" <?= $change_type == 'remove' ? 'selected' : '' ?>>
                                    Removed
                                </option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">From Date</label>
                            <input type="date" 
                                   name="date_from" 
                                   class="form-control" 
                                   value="<?= htmlspecialchars($date_from) ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">To Date</label>
                            <input type="date" 
                                   name="date_to" 
                                   class="form-control" 
                                   value="<?= htmlspecialchars($date_to) ?>">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-filter me-2"></i>Apply Filters
                            </button>
                            <a href="stock_updates.php" class="btn btn-secondary">
                                <i class="fas fa-refresh me-2"></i>Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Stock Updates Table -->
        <div class="card">
            <div class="card-body p-0">
                <?php if (empty($stock_updates)): ?>
                    <div class="alert alert-info m-3 mb-0" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        No stock updates found.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Previous Quantity</th>
                                    <th>Change</th>
                                    <th>New Quantity</th>
                                    <th>Reason</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stock_updates as $update): ?>
                                    <tr>
                                        <td>
                                            <?= htmlspecialchars($update['product_name']) ?>
                                        </td>
                                        <td><?= $update['previous_quantity'] ?></td>
                                        <td>
                                            <span class="badge <?= 
                                                $update['change_type'] == 'add' 
                                                    ? 'bg-success' 
                                                    : 'bg-danger'
                                            ?>">
                                                <?= $update['change_type'] == 'add' ? '+' : '-' ?>
                                                <?= abs($update['new_quantity'] - $update['previous_quantity']) ?>
                                            </span>
                                        </td>
                                        <td><?= $update['new_quantity'] ?></td>
                                        <td>
                                            <small class="text-muted">
                                                <?= htmlspecialchars($update['reason']) ?>
                                            </small>
                                        </td>
                                        <td>
                                            <?= date('d M Y H:i', strtotime($update['logged_at'])) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav aria-label="Stock Updates Pagination" class="mt-3">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&product=<?= $product_filter ?>&change_type=<?= $change_type ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>

    <style>
        .table-responsive {
            max-height: 600px;
            overflow-y: auto;
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>