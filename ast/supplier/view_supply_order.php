<?php
include '../components/connect.php';

session_start();

if (!isset($_SESSION['supplier_id'])) {
    header('location:index.php');
    exit();
}

$supplier_id = $_SESSION['supplier_id'];

// Check if order ID is provided
if (!isset($_GET['id'])) {
    header('location:supply_orders.php');
    exit();
}

$order_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);

if (!$order_id) {
    header('location:supply_orders.php');
    exit();
}

// Fetch supply order details
$order_query = $conn->prepare("
    SELECT so.*, 
           COUNT(soi.supply_order_item_id) as total_items
    FROM supply_orders so
    LEFT JOIN supply_order_items soi ON so.supply_order_id = soi.supply_order_id
    WHERE so.supply_order_id = ? AND so.supplier_id = ?
");
$order_query->execute([$order_id, $supplier_id]);
$order = $order_query->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('location:supply_orders.php');
    exit();
}

// Fetch order items with product details
$items_query = $conn->prepare("
    SELECT 
        soi.*, 
        p.name as product_name, 
        p.image_01
    FROM supply_order_items soi
    JOIN products p ON soi.product_id = p.id
    WHERE soi.supply_order_id = ?
");
$items_query->execute([$order_id]);
$order_items = $items_query->fetchAll(PDO::FETCH_ASSOC);

// Handle status update
if (isset($_POST['update_status'])) {
    $new_status = filter_var($_POST['status'], FILTER_SANITIZE_STRING);
    
    // Validate status
    $allowed_statuses = ['pending', 'in_progress', 'completed', 'cancelled'];
    if (in_array($new_status, $allowed_statuses)) {
        $update_query = $conn->prepare("
            UPDATE supply_orders 
            SET status = ? 
            WHERE supply_order_id = ? AND supplier_id = ?
        ");
        
        try {
            $update_query->execute([$new_status, $order_id, $supplier_id]);
            $_SESSION['success_message'] = "Order status updated successfully!";
            header("location: view_supply_order.php?id=$order_id");
            exit();
        } catch (PDOException $e) {
            $error_message = "Failed to update status: " . $e->getMessage();
        }
    }
}

// Calculate metrics
$total_quantity = 0;
$highest_cost_item = ['name' => '', 'cost' => 0];
$lowest_cost_item = ['name' => '', 'cost' => PHP_INT_MAX];

foreach ($order_items as $item) {
    $total_quantity += $item['quantity'];
    
    if ($item['unit_cost'] > $highest_cost_item['cost']) {
        $highest_cost_item = ['name' => $item['product_name'], 'cost' => $item['unit_cost']];
    }
    
    if ($item['unit_cost'] < $lowest_cost_item['cost']) {
        $lowest_cost_item = ['name' => $item['product_name'], 'cost' => $item['unit_cost']];
    }
}

// Get average cost per item
$avg_cost = count($order_items) > 0 ? $order['total_amount'] / $total_quantity : 0;

// Get order status class and icon
function getStatusInfo($status) {
    switch($status) {
        case 'pending':
            return ['class' => 'warning', 'icon' => 'clock', 'text' => 'Pending'];
        case 'in_progress':
            return ['class' => 'info', 'icon' => 'spinner fa-spin', 'text' => 'In Progress'];
        case 'completed':
            return ['class' => 'success', 'icon' => 'check-circle', 'text' => 'Completed'];
        case 'cancelled':
            return ['class' => 'danger', 'icon' => 'times-circle', 'text' => 'Cancelled'];
        default:
            return ['class' => 'secondary', 'icon' => 'question-circle', 'text' => 'Unknown'];
    }
}

$status_info = getStatusInfo($order['status']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keens | Supply Order #<?= $order['supply_order_id'] ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --success-color: #2ecc71;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --info-color: #3498db;
        }
        
        body {
            background-color: #f8f9fa;
            color: #333;
        }
        
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: none;
            margin-bottom: 20px;
        }
        
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            padding: 15px 20px;
            border-radius: 10px 10px 0 0 !important;
        }
        
        .table-responsive {
            padding: 0;
        }
        
        .status-badge {
            font-size: 1rem;
            padding: 8px 15px;
            border-radius: 50px;
        }
        
        .action-btn {
            border-radius: 50px;
            padding: 8px 20px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        
        .product-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .metric-card {
            transition: all 0.3s;
            cursor: default;
        }
        
        .metric-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }
        
        .table th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }
        
        .floating-action-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--primary-color);
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            color: white;
            font-size: 24px;
            z-index: 100;
            transition: all 0.3s;
        }
        
        .floating-action-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.25);
            background: var(--secondary-color);
            color: white;
        }
        
        .breadcrumb {
            background: transparent;
            padding: 0;
        }
        
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            top: 0;
            left: 15px;
            height: 100%;
            width: 2px;
            background-color: #dee2e6;
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 20px;
        }
        
        .timeline-marker {
            position: absolute;
            left: -30px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background-color: var(--primary-color);
            border: 3px solid white;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }
        
        .timeline-content {
            padding-left: 10px;
        }
        
        .btn-pdf {
            background-color: #e74c3c;
            color: white;
        }
        
        .btn-pdf:hover {
            background-color: #c0392b;
            color: white;
        }
    </style>
</head>
<body>
    <?php include '../components/supplier_header.php'; ?>
    
    <!-- Success Message Toast -->
    <?php if (isset($_SESSION['success_message'])): ?>
    <div class="position-fixed top-0 end-0 p-3" style="z-index: 1100">
        <div class="toast show bg-success text-white" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-success text-white">
                <i class="fas fa-check-circle me-2"></i>
                <strong class="me-auto">Success</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">
                <?= $_SESSION['success_message'] ?>
            </div>
        </div>
    </div>
    <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <div class="container-fluid py-4">
        <!-- Breadcrumbs -->
        <div class="row mb-3">
            <div class="col-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="supplier_dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="supply_orders.php">Supply Orders</a></li>
                        <li class="breadcrumb-item active">Order #<?= $order['supply_order_id'] ?></li>
                    </ol>
                </nav>
            </div>
        </div>
        
        <!-- Header Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h1 class="mb-1">
                                    <i class="fas fa-truck-loading me-2 text-primary"></i>
                                    Supply Order #<?= $order['supply_order_id'] ?>
                                </h1>
                                <p class="text-muted mb-0">
                                    <i class="fas fa-calendar-alt me-2"></i>
                                    Created on <?= date('d M Y H:i', strtotime($order['order_date'])) ?>
                                </p>
                            </div>
                            <div class="d-flex align-items-center">
                                <span class="badge bg-<?= $status_info['class'] ?> status-badge me-3">
                                    <i class="fas fa-<?= $status_info['icon'] ?> me-2"></i>
                                    <?= $status_info['text'] ?>
                                </span>
                                <div class="btn-group">
                                    <?php if ($order['status'] != 'completed' && $order['status'] != 'cancelled'): ?>
                                        <button type="button" class="btn btn-primary action-btn" data-bs-toggle="modal" data-bs-target="#updateStatusModal">
                                            <i class="fas fa-edit me-2"></i>Update Status
                                        </button>
                                    <?php endif; ?>
                                    
                                    <a href="vso_report.php?id=<?= $order_id ?>" class="btn btn-pdf action-btn ms-2" target="_blank">
                                        <i class="fas fa-file-pdf me-2"></i>View PDF
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Metrics Row -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card metric-card bg-primary text-white">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">Total Amount</h6>
                                <h2 class="mb-0">$<?= number_format($order['total_amount'], 2) ?></h2>
                            </div>
                            <div class="rounded-circle bg-white p-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                <i class="fas fa-dollar-sign fa-2x text-primary"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card metric-card bg-success text-white">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">Total Items</h6>
                                <h2 class="mb-0"><?= $total_quantity ?></h2>
                            </div>
                            <div class="rounded-circle bg-white p-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                <i class="fas fa-box fa-2x text-success"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card metric-card bg-info text-white">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">Products</h6>
                                <h2 class="mb-0"><?= count($order_items) ?></h2>
                            </div>
                            <div class="rounded-circle bg-white p-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                <i class="fas fa-tags fa-2x text-info"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card metric-card bg-warning text-white">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">Avg. Unit Cost</h6>
                                <h2 class="mb-0">$<?= number_format($avg_cost, 2) ?></h2>
                            </div>
                            <div class="rounded-circle bg-white p-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                <i class="fas fa-calculator fa-2x text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Order Details -->
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Order Items</h5>
                        <span class="badge bg-<?= $status_info['class'] ?> rounded-pill">
                            <?= $order['total_items'] ?> Products
                        </span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Product</th>
                                        <th class="text-center">Quantity</th>
                                        <th class="text-end">Unit Cost</th>
                                        <th class="text-end">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($order_items as $item): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div>
                                                        <h6 class="mb-0"><?= htmlspecialchars($item['product_name']) ?></h6>
                                                        <small class="text-muted">ID: <?= $item['product_id'] ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center align-middle">
                                                <span class="badge bg-primary rounded-pill"><?= $item['quantity'] ?></span>
                                            </td>
                                            <td class="text-end align-middle">$<?= number_format($item['unit_cost'], 2) ?></td>
                                            <td class="text-end align-middle">
                                                <strong>$<?= number_format($item['subtotal'], 2) ?></strong>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="table-light">
                                        <td colspan="3" class="text-end fw-bold">Total:</td>
                                        <td class="text-end fw-bold">$<?= number_format($order['total_amount'], 2) ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Additional Cards -->
                <?php if (!empty($order['notes'])): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-sticky-note me-2 text-warning"></i>Order Notes
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="p-3 bg-light rounded">
                            <?= nl2br(htmlspecialchars($order['notes'])) ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Order Information -->
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-info-circle me-2 text-primary"></i>Order Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <div class="timeline-item">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h6 class="mb-0">Order Date</h6>
                                    <p class="mb-0 text-muted">
                                        <i class="fas fa-calendar me-2"></i>
                                        <?= date('d M Y H:i', strtotime($order['order_date'])) ?>
                                    </p>
                                </div>
                            </div>
                            <div class="timeline-item">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h6 class="mb-0">Expected Delivery</h6>
                                    <p class="mb-0 text-muted">
                                        <i class="fas fa-truck me-2"></i>
                                        <?= $order['expected_delivery'] 
                                            ? date('d M Y', strtotime($order['expected_delivery'])) 
                                            : 'Not specified' 
                                        ?>
                                    </p>
                                </div>
                            </div>
                            <div class="timeline-item">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h6 class="mb-0">Order Status</h6>
                                    <p class="mb-0">
                                        <span class="badge bg-<?= $status_info['class'] ?>">
                                            <i class="fas fa-<?= $status_info['icon'] ?> me-1"></i>
                                            <?= $status_info['text'] ?>
                                        </span>
                                    </p>
                                </div>
                            </div>
                            <div class="timeline-item">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h6 class="mb-0">Total Products</h6>
                                    <p class="mb-0 text-muted">
                                        <i class="fas fa-box me-2"></i>
                                        <?= count($order_items) ?> Product(s) / <?= $total_quantity ?> Item(s)
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            
            </div>
        </div>
    </div>

    <!-- Floating Action Button -->
    <a href="supply_orders.php" class="floating-action-btn text-decoration-none">
        <i class="fas fa-arrow-left"></i>
    </a>

    <!-- Status Update Modal -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Update Order Status
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Select New Status</label>
                            <select name="status" class="form-select form-select-lg" required>
                                <option value="pending" <?= $order['status'] == 'pending' ? 'selected' : '' ?>>
                                    <i class="fas fa-clock"></i> Pending
                                </option>
                                <option value="in_progress" <?= $order['status'] == 'in_progress' ? 'selected' : '' ?>>
                                    <i class="fas fa-spinner"></i> In Progress
                                </option>
                                <option value="completed" <?= $order['status'] == 'completed' ? 'selected' : '' ?>>
                                    <i class="fas fa-check-circle"></i> Completed
                                </option>
                                <option value="cancelled" <?= $order['status'] == 'cancelled' ? 'selected' : '' ?>>
                                    <i class="fas fa-times-circle"></i> Cancelled
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancel
                        </button>
                        <button type="submit" name="update_status" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Status
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap Toast Initialization -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize toasts
        document.addEventListener('DOMContentLoaded', function() {
            var toastElList = [].slice.call(document.querySelectorAll('.toast'));
            var toastList = toastElList.map(function(toastEl) {
                return new bootstrap.Toast(toastEl, {
                    autohide: true,
                    delay: 5000
                });
            });
            
            // Auto-hide toast after 5 seconds
            setTimeout(function() {
                toastList.forEach(toast => toast.hide());
            }, 5000);
        });
    </script>
</body>
</html>