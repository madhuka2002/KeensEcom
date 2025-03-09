<?php
include '../components/connect.php';

session_start();

// Ensure only logged-in financial auditors can access
if(!isset($_SESSION['auditor_id'])){
   header('location:index.php');
   exit();
}

$auditor_id = $_SESSION['auditor_id'];

// Validate and sanitize transaction ID
$transaction_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if($transaction_id <= 0){
    header('location:transaction_logs.php');
    exit();
}

try {
    // Fetch transaction details with related information
    $transaction_query = $conn->prepare("
        SELECT 
            o.*,
            u.name as user_name,
            u.email as user_email,
            (SELECT COUNT(*) FROM `order_items` WHERE order_id = o.id) as total_items
        FROM `orders` o
        LEFT JOIN `users` u ON o.user_id = u.id
        WHERE o.id = ?
    ");
    $transaction_query->execute([$transaction_id]);
    $transaction = $transaction_query->fetch(PDO::FETCH_ASSOC);

    if(!$transaction){
        throw new Exception("Transaction not found");
    }

    // Fetch order items
    $items_query = $conn->prepare("
        SELECT 
            oi.*, 
            p.name as product_name, 
            p.details as product_details
        FROM `order_items` oi
        JOIN `products` p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ");
    $items_query->execute([$transaction_id]);
    $order_items = $items_query->fetchAll(PDO::FETCH_ASSOC);

    // Fetch payment information
    $payment_query = $conn->prepare("
        SELECT * FROM `payment_gateway_logs`
        WHERE transaction_id = ?
        ORDER BY timestamp DESC
        LIMIT 1
    ");
    $payment_query->execute([$transaction_id]);
    $payment_info = $payment_query->fetch(PDO::FETCH_ASSOC);

    // Fetch transaction audit trail
    $audit_trail_query = $conn->prepare("
        SELECT * FROM `transaction_audit_trails`
        WHERE order_id = ?
        ORDER BY timestamp
    ");
    $audit_trail_query->execute([$transaction_id]);
    $audit_trail = $audit_trail_query->fetchAll(PDO::FETCH_ASSOC);

} catch(Exception $e) {
    error_log("Transaction details error: " . $e->getMessage());
    header('location:transaction_logs.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Keens | Transaction Details #<?= $transaction_id; ?></title>
   
   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<?php include '../components/financial_auditor_header.php'; ?>

<div class="container-fluid px-4 py-5">
    <div class="row">
        <!-- Transaction Overview -->
        <div class="col-12 col-xl-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">
                        <i class="fas fa-receipt me-2 text-primary"></i>
                        Transaction Details #<?= $transaction_id; ?>
                    </h4>
                    <span class="badge 
                        <?php 
                        switch($transaction['payment_status']) {
                            case 'completed': echo 'bg-success'; break;
                            case 'pending': echo 'bg-warning'; break;
                            case 'cancelled': echo 'bg-danger'; break;
                            default: echo 'bg-secondary';
                        }
                        ?>
                    ">
                        <?= ucfirst($transaction['payment_status']); ?>
                    </span>
                </div>
                
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted">Customer Information</h6>
                            <p class="mb-1">
                                <strong>Name:</strong> 
                                <?= htmlspecialchars($transaction['user_name'] ?? 'N/A'); ?>
                            </p>
                            <p class="mb-1">
                                <strong>Email:</strong> 
                                <?= htmlspecialchars($transaction['user_email'] ?? 'N/A'); ?>
                            </p>
                            <p class="mb-1">
                                <strong>Phone:</strong> 
                                <?= htmlspecialchars($transaction['number'] ?? 'N/A'); ?>
                            </p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <h6 class="text-muted">Transaction Summary</h6>
                            <p class="mb-1">
                                <strong>Total Items:</strong> 
                                <?= $transaction['total_items']; ?>
                            </p>
                            <p class="mb-1">
                                <strong>Total Price:</strong> 
                                $<?= number_format($transaction['total_price'], 2); ?>
                            </p>
                            <p class="mb-1">
                                <strong>Payment Method:</strong> 
                                <?= htmlspecialchars($transaction['method']); ?>
                            </p>
                            <p class="mb-1">
                                <strong>Order Date:</strong> 
                                <?= date('M d, Y H:i', strtotime($transaction['placed_on'])); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Items -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h4 class="card-title mb-0">
                        <i class="fas fa-shopping-cart me-2 text-primary"></i>
                        Order Items
                    </h4>
                </div>
                
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Unit Price</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($order_items)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">
                                            No items found in this order
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach($order_items as $item): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($item['product_name']); ?></strong>
                                                <small class="d-block text-muted">
                                                    <?= htmlspecialchars($item['product_details'] ?? ''); ?>
                                                </small>
                                            </td>
                                            <td><?= $item['quantity']; ?></td>
                                            <td>$<?= number_format($item['unit_price'], 2); ?></td>
                                            <td>$<?= number_format($item['subtotal'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment and Audit Information -->
        <div class="col-12 col-xl-4">
            <!-- Payment Gateway Logs -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h4 class="card-title mb-0">
                        <i class="fas fa-credit-card me-2 text-primary"></i>
                        Payment Information
                    </h4>
                </div>
                
                <div class="card-body">
                    <?php if(empty($payment_info)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-credit-card fa-3x mb-3 opacity-50"></i>
                            <p>No payment gateway information available</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group">
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">Transaction Details</h6>
                                    <span class="badge 
                                        <?php 
                                        switch($payment_info['status']) {
                                            case 'success': echo 'bg-success'; break;
                                            case 'failed': echo 'bg-danger'; break;
                                            case 'pending': echo 'bg-warning'; break;
                                            default: echo 'bg-secondary';
                                        }
                                        ?>
                                    ">
                                        <?= ucfirst($payment_info['status']); ?>
                                    </span>
                                </div>
                                <p class="mb-1">
                                    <strong>Gateway:</strong> 
                                    <?= htmlspecialchars($payment_info['gateway_name'] ?? 'N/A'); ?>
                                </p>
                                <p class="mb-1">
                                    <strong>Amount:</strong> 
                                    $<?= number_format($payment_info['amount'] ?? 0, 2); ?>
                                </p>
                                <?php if(!empty($payment_info['error_code'])): ?>
                                    <p class="mb-1 text-danger">
                                        <strong>Error Code:</strong> 
                                        <?= htmlspecialchars($payment_info['error_code']); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Audit Trail -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h4 class="card-title mb-0">
                        <i class="fas fa-history me-2 text-primary"></i>
                        Transaction Audit Trail
                    </h4>
                </div>
                
                <div class="card-body">
                    <?php if(empty($audit_trail)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-clipboard-list fa-3x mb-3 opacity-50"></i>
                            <p>No audit trail found</p>
                        </div>
                    <?php else: ?>
                        <div class="timeline">
                            <?php foreach($audit_trail as $trail): ?>
                                <div class="timeline-item">
                                    <div class="timeline-icon 
                                        <?php 
                                        switch($trail['transaction_stage']) {
                                            case 'initiated': echo 'bg-secondary'; break;
                                            case 'processed': echo 'bg-info'; break;
                                            case 'verified': echo 'bg-success'; break;
                                            case 'flagged': echo 'bg-warning'; break;
                                            case 'completed': echo 'bg-primary'; break;
                                            default: echo 'bg-secondary';
                                        }
                                        ?>
                                    ">
                                        <i class="fas 
                                            <?php 
                                            switch($trail['transaction_stage']) {
                                                case 'initiated': echo 'fa-play'; break;
                                                case 'processed': echo 'fa-cog'; break;
                                                case 'verified': echo 'fa-check'; break;
                                                case 'flagged': echo 'fa-exclamation-triangle'; break;
                                                case 'completed': echo 'fa-flag-checkered'; break;
                                                default: echo 'fa-dot-circle';
                                            }
                                            ?>
                                        "></i>
                                    </div>
                                    <div class="timeline-content">
                                        <h6 class="timeline-title">
                                            <?= ucfirst($trail['transaction_stage']); ?>
                                        </h6>
                                        <p class="timeline-description">
                                            <?= htmlspecialchars($trail['notes']); ?>
                                        </p>
                                        <small class="text-muted">
                                            <?= date('M d, Y H:i:s', strtotime($trail['timestamp'])); ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Timeline Styles */
    .timeline {
        position: relative;
        padding-left: 30px;
        margin-top: 20px;
    }

    .timeline::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        width: 2px;
        height: 100%;
        background-color: #e9ecef;
    }

    .timeline-item {
        position: relative;
        margin-bottom: 20px;
    }

    .timeline-icon {
        position: absolute;
        left: -30px;
        top: 0;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }

    .timeline-content {
        padding-left: 20px;
        border-left: 2px solid transparent;
    }

    .timeline-title {
        margin-bottom: 5px;
        color: #2b3452;
    }

    .timeline-description {
        color: #6c757d;
        margin-bottom: 5px;
    }
</style>

<?php include '../components/footer.php'; ?>
</body>
</html>