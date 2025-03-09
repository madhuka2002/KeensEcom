<?php
include '../components/connect.php';

session_start();

$manager_id = $_SESSION['manager_id'];

if(!isset($manager_id)){
   header('location:index.php');
}

// Update threshold
if(isset($_POST['update_threshold'])) {
    $product_id = $_POST['product_id'];
    $threshold = $_POST['threshold'];
    
    $update_alert = $conn->prepare("INSERT INTO `stock_alert` (product_id, threshold) VALUES (?, ?) 
                                   ON DUPLICATE KEY UPDATE threshold = ?");
    $update_alert->execute([$product_id, $threshold, $threshold]);
    
    $message[] = 'Stock alert threshold updated successfully!';
}

// Mark alert as resolved
if(isset($_POST['resolve_alert'])) {
    $alert_id = $_POST['alert_id'];
    
    $update_alert = $conn->prepare("UPDATE `stock_alert` SET is_resolved = 1 WHERE alert_id = ?");
    $update_alert->execute([$alert_id]);
    
    $message[] = 'Alert marked as resolved!';
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Keens | Stock Alerts</title>

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<?php include '../components/product_manager_header.php'; ?>

<section class="stock-alerts">
    <div class="container-fluid px-4 py-5">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-0">Stock Alerts</h2>
                <p class="text-muted mb-0">Monitor and manage low stock alerts</p>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#setThresholdModal">
                <i class="fas fa-plus me-2"></i>Set New Alert
            </button>
        </div>

        <!-- Alert Stats -->
        <div class="row g-4 mb-4">
            <!-- Low Stock Items -->
            <div class="col-12 col-sm-6 col-xl-3">
                <?php
                    $low_stock = $conn->prepare("
                        SELECT COUNT(*) as count 
                        FROM `inventory` i 
                        JOIN `stock_alert` sa ON i.product_id = sa.product_id 
                        WHERE i.quantity <= sa.threshold AND sa.is_resolved = 0
                    ");
                    $low_stock->execute();
                    $low_stock_count = $low_stock->fetch(PDO::FETCH_ASSOC)['count'];
                ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="icon-wrapper bg-warning bg-opacity-10 text-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div class="ms-3">
                                <h6 class="text-muted mb-0">Low Stock Alerts</h6>
                                <h3 class="mb-0"><?= number_format($low_stock_count); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Out of Stock -->
            <div class="col-12 col-sm-6 col-xl-3">
                <?php
                    $out_stock = $conn->prepare("SELECT COUNT(*) as count FROM `inventory` WHERE quantity = 0");
                    $out_stock->execute();
                    $out_stock_count = $out_stock->fetch(PDO::FETCH_ASSOC)['count'];
                ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="icon-wrapper bg-danger bg-opacity-10 text-danger">
                                <i class="fas fa-times-circle"></i>
                            </div>
                            <div class="ms-3">
                                <h6 class="text-muted mb-0">Out of Stock</h6>
                                <h3 class="mb-0"><?= number_format($out_stock_count); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Alerts Table -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="card-title mb-0">Active Alerts</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Product</th>
                                <th>Current Stock</th>
                                <th>Threshold</th>
                                <th>Status</th>
                                <th>Last Updated</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                $select_alerts = $conn->prepare("
                                    SELECT sa.*, p.name, p.image_01, i.quantity, 
                                           (i.quantity <= sa.threshold) as is_low_stock
                                    FROM `stock_alert` sa
                                    JOIN `products` p ON sa.product_id = p.id
                                    JOIN `inventory` i ON p.id = i.product_id
                                    WHERE sa.is_resolved = 0
                                    ORDER BY is_low_stock DESC, i.quantity ASC
                                ");
                                $select_alerts->execute();
                                if($select_alerts->rowCount() > 0){
                                    while($alert = $select_alerts->fetch(PDO::FETCH_ASSOC)){
                            ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <img src="../uploaded_img/<?= $alert['image_01']; ?>" 
                                             alt="<?= $alert['name']; ?>"
                                             class="rounded"
                                             style="width: 40px; height: 40px; object-fit: cover;">
                                        <div class="ms-3">
                                            <h6 class="mb-0"><?= $alert['name']; ?></h6>
                                            <small class="text-muted">ID: #<?= $alert['product_id']; ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="fw-medium <?= $alert['quantity'] == 0 ? 'text-danger' : ($alert['quantity'] <= $alert['threshold'] ? 'text-warning' : ''); ?>">
                                        <?= $alert['quantity']; ?> units
                                    </span>
                                </td>
                                <td><?= $alert['threshold']; ?> units</td>
                                <td>
                                    <?php if($alert['quantity'] == 0): ?>
                                        <span class="badge bg-danger-subtle text-danger">Out of Stock</span>
                                    <?php elseif($alert['quantity'] <= $alert['threshold']): ?>
                                        <span class="badge bg-warning-subtle text-warning">Low Stock</span>
                                    <?php else: ?>
                                        <span class="badge bg-success-subtle text-success">Stock OK</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?= date('M d, Y H:i', strtotime($alert['updated_at'])); ?>
                                    </small>
                                </td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-sm btn-light me-2" data-bs-toggle="modal" 
                                            data-bs-target="#updateThresholdModal<?= $alert['product_id']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if($alert['quantity'] > $alert['threshold']): ?>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="alert_id" value="<?= $alert['alert_id']; ?>">
                                        <button type="submit" name="resolve_alert" class="btn btn-sm btn-success"
                                                onclick="return confirm('Mark this alert as resolved?')">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>

                            <!-- Update Threshold Modal -->
                            <div class="modal fade" id="updateThresholdModal<?= $alert['product_id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content border-0">
                                        <form method="post">
                                            <div class="modal-header border-0">
                                                <h5 class="modal-title">Update Alert Threshold</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="product_id" value="<?= $alert['product_id']; ?>">
                                                <div class="mb-3">
                                                    <label class="form-label">Product</label>
                                                    <input type="text" class="form-control" value="<?= $alert['name']; ?>" disabled>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Alert Threshold</label>
                                                    <input type="number" name="threshold" class="form-control" 
                                                           value="<?= $alert['threshold']; ?>" required min="1">
                                                    <small class="text-muted">
                                                        Alert will trigger when stock falls below this number
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="modal-footer border-0">
                                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" name="update_threshold" class="btn btn-primary">
                                                    Update Threshold
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php
                                    }
                                } else {
                                    echo '<tr><td colspan="6" class="text-center py-4">No active alerts found</td></tr>';
                                }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Resolved Alerts Section -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="card-title mb-0">Resolved Alerts</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Product</th>
                                <th>Final Stock</th>
                                <th>Threshold</th>
                                <th>Resolved Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                $select_resolved = $conn->prepare("
                                    SELECT sa.*, p.name, p.image_01, i.quantity
                                    FROM `stock_alert` sa
                                    JOIN `products` p ON sa.product_id = p.id
                                    JOIN `inventory` i ON p.id = i.product_id
                                    WHERE sa.is_resolved = 1
                                    ORDER BY sa.updated_at DESC
                                    LIMIT 5
                                ");
                                $select_resolved->execute();
                                if($select_resolved->rowCount() > 0){
                                    while($resolved = $select_resolved->fetch(PDO::FETCH_ASSOC)){
                            ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <img src="../uploaded_img/<?= $resolved['image_01']; ?>" 
                                             alt="<?= $resolved['name']; ?>"
                                             class="rounded"
                                             style="width: 40px; height: 40px; object-fit: cover;">
                                        <div class="ms-3">
                                            <h6 class="mb-0"><?= $resolved['name']; ?></h6>
                                            <small class="text-muted">ID: #<?= $resolved['product_id']; ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?= $resolved['quantity']; ?> units</td>
                                <td><?= $resolved['threshold']; ?> units</td>
                                <td>
                                    <small class="text-muted">
                                        <?= date('M d, Y H:i', strtotime($resolved['updated_at'])); ?>
                                    </small>
                                </td>
                            </tr>
                            <?php
                                    }
                                } else {
                                    echo '<tr><td colspan="4" class="text-center py-4">No resolved alerts found</td></tr>';
                                }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Set New Alert Modal -->
<div class="modal fade" id="setThresholdModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0">
            <form method="post">
                <div class="modal-header border-0">
                    <h5 class="modal-title">Set New Stock Alert</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div
                class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select Product</label>
                        <select name="product_id" class="form-select" required>
                            <option value="">Choose a product</option>
                            <?php
                                $select_products = $conn->prepare("
                                    SELECT p.* FROM `products` p 
                                    LEFT JOIN `stock_alert` sa ON p.id = sa.product_id
                                    WHERE sa.alert_id IS NULL
                                ");
                                $select_products->execute();
                                while($product = $select_products->fetch(PDO::FETCH_ASSOC)){
                                    echo "<option value='".$product['id']."'>".$product['name']."</option>";
                                }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alert Threshold</label>
                        <input type="number" name="threshold" class="form-control" required min="1">
                        <small class="text-muted">
                            Set the minimum stock level before triggering an alert
                        </small>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_threshold" class="btn btn-primary">
                        Set Alert
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
:root {
    --primary-color: #4361ee;
    --secondary-color: #3f37c9;
    --accent-color: #4895ef;
    --success-color: #2ecc71;
    --warning-color: #f39c12;
    --danger-color: #dc3545;
}

/* Card & Table Styles */
.card {
    border-radius: 15px;
    overflow: hidden;
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

/* Icon Wrapper */
.icon-wrapper {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
}

/* Form Controls */
.form-control, .form-select {
    padding: 0.75rem 1rem;
    border-radius: 8px;
    border: 1px solid #e9ecef;
}

.form-control:focus, .form-select:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
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
    border: 1px solid #e9ecef;
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

/* Responsive Adjustments */
@media (max-width: 768px) {
    .container-fluid {
        padding: 1rem !important;
    }

    .card-body {
        padding: 1rem;
    }

    .table-responsive {
        margin: 0 -1rem;
    }

    .btn {
        width: 100%;
        margin-bottom: 0.5rem;
    }

    .icon-wrapper {
        width: 40px;
        height: 40px;
        font-size: 1rem;
    }
}
</style>

<script>
// Initialize tooltips
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl)
});

// Handle form submissions
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function() {
        const btn = this.querySelector('button[type="submit"]');
        if(btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
        }
    });
});

// Threshold input validation
document.querySelectorAll('input[name="threshold"]').forEach(input => {
    input.addEventListener('input', function() {
        if(this.value < 1) {
            this.value = 1;
        }
    });
});

// Product selection validation
document.querySelector('select[name="product_id"]')?.addEventListener('change', function() {
    const submitBtn = this.closest('form').querySelector('button[type="submit"]');
    submitBtn.disabled = !this.value;
});

// Auto-refresh alerts every 5 minutes
setInterval(function() {
    location.reload();
}, 300000);

// Search functionality
const searchInput = document.getElementById('searchAlerts');
if(searchInput) {
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = document.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const productName = row.querySelector('h6').textContent.toLowerCase();
            const productId = row.querySelector('small').textContent.toLowerCase();
            
            if(productName.includes(searchTerm) || productId.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
}

// Show confirmation for resolving alerts
function confirmResolve(alertId) {
    return confirm('Are you sure you want to mark this alert as resolved?');
}

// Update threshold validation
function validateThreshold(input) {
    const currentStock = parseInt(input.getAttribute('data-current-stock'));
    const threshold = parseInt(input.value);
    const submitBtn = input.closest('form').querySelector('button[type="submit"]');
    
    if(threshold >= currentStock) {
        input.classList.add('is-invalid');
        submitBtn.disabled = true;
    } else {
        input.classList.remove('is-invalid');
        submitBtn.disabled = false;
    }
}
</script>

</body>
</html>