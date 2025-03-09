<?php
include '../components/connect.php';

session_start();

if (!isset($_SESSION['supplier_id'])) {
    header('location:index.php');
    exit();
}

$supplier_id = $_SESSION['supplier_id'];

// Handle request approval
if (isset($_POST['approve_request'])) {
    $request_id = $_POST['request_id'];
    $response_notes = $_POST['response_notes'];
    
    // Update request status
    $update_request = $conn->prepare("UPDATE `restock_requests` SET status = 'approved', response_notes = ? WHERE request_id = ? AND supplier_id = ?");
    $update_request->execute([$response_notes, $request_id, $supplier_id]);
    
    // Get request details for inventory update
    $get_request = $conn->prepare("SELECT * FROM `restock_requests` WHERE request_id = ?");
    $get_request->execute([$request_id]);
    $request = $get_request->fetch(PDO::FETCH_ASSOC);
    
    if ($request) {
        // Get current inventory
        $get_inventory = $conn->prepare("SELECT * FROM `inventory` WHERE product_id = ?");
        $get_inventory->execute([$request['product_id']]);
        $inventory = $get_inventory->fetch(PDO::FETCH_ASSOC);
        
        $previous_quantity = $inventory ? $inventory['quantity'] : 0;
        $new_quantity = $previous_quantity + $request['requested_quantity'];
        
        // Update inventory
        if ($inventory) {
            $update_inventory = $conn->prepare("UPDATE `inventory` SET quantity = ? WHERE product_id = ?");
            $update_inventory->execute([$new_quantity, $request['product_id']]);
        } else {
            $insert_inventory = $conn->prepare("INSERT INTO `inventory` (product_id, supplier_id, quantity) VALUES (?, ?, ?)");
            $insert_inventory->execute([$request['product_id'], $supplier_id, $request['requested_quantity']]);
        }
        
        // Log inventory change
        $log_inventory = $conn->prepare("INSERT INTO `inventory_log` (supplier_id, product_id, previous_quantity, new_quantity, change_type, reason, notes) VALUES (?, ?, ?, ?, 'add', 'Restock request approved', ?)");
        $log_inventory->execute([$supplier_id, $request['product_id'], $previous_quantity, $new_quantity, $response_notes]);
        
        $message[] = 'Request approved and inventory updated!';
    }
}

// Handle request rejection
if (isset($_POST['reject_request'])) {
    $request_id = $_POST['request_id'];
    $response_notes = $_POST['response_notes'];
    
    $update_request = $conn->prepare("UPDATE `restock_requests` SET status = 'rejected', response_notes = ? WHERE request_id = ? AND supplier_id = ?");
    $update_request->execute([$response_notes, $request_id, $supplier_id]);
    
    $message[] = 'Request rejected!';
}

// Pagination setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Filter options
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Base query for restock requests
$query = "
    FROM restock_requests r
    JOIN products p ON r.product_id = p.id
    WHERE r.supplier_id = ?
";
$params = [$supplier_id];

// Apply filters
if (!empty($status_filter)) {
    $query .= " AND r.status = ?";
    $params[] = $status_filter;
}

if (!empty($date_from)) {
    $query .= " AND r.created_at >= ?";
    $params[] = $date_from . ' 00:00:00';
}

if (!empty($date_to)) {
    $query .= " AND r.created_at <= ?";
    $params[] = $date_to . ' 23:59:59';
}

// Count total records
$count_query = $conn->prepare("SELECT COUNT(*) as total $query");
$count_query->execute($params);
$total_records = $count_query->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_records / $records_per_page);

// Fetch restock requests
$requests_query = $conn->prepare("
    SELECT r.*, p.name as product_name, p.image_01 $query 
    ORDER BY r.created_at DESC 
    LIMIT $records_per_page OFFSET $offset
");
$requests_query->execute($params);
$restock_requests = $requests_query->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keens | Restock Requests</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
</head>
<body>
    <?php include '../components/supplier_header.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12 d-flex justify-content-between align-items-center mb-4">
                <h1>
                    <i class="fas fa-truck-loading me-2"></i>Restock Requests
                </h1>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All Statuses</option>
                                <option value="pending" <?= $status_filter == 'pending' ? 'selected' : '' ?>>
                                    Pending
                                </option>
                                <option value="approved" <?= $status_filter == 'approved' ? 'selected' : '' ?>>
                                    Approved
                                </option>
                                <option value="rejected" <?= $status_filter == 'rejected' ? 'selected' : '' ?>>
                                    Rejected
                                </option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">From Date</label>
                            <input type="date" 
                                   name="date_from" 
                                   class="form-control" 
                                   value="<?= htmlspecialchars($date_from) ?>">
                        </div>
                        <div class="col-md-3">
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
                            <a href="restock_requests.php" class="btn btn-secondary">
                                <i class="fas fa-refresh me-2"></i>Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Restock Requests Table -->
        <div class="card">
            <div class="card-body p-0">
                <?php if (empty($restock_requests)): ?>
                    <div class="alert alert-info m-3 mb-0" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        No restock requests found.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Request ID</th>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Requested On</th>
                                    <th>Status</th>
                                    <th>Notes</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($restock_requests as $request): ?>
                                    <tr>
                                        <td>#<?= $request['request_id'] ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="../uploaded_img/<?= $request['image_01'] ?>" 
                                                     alt="<?= $request['product_name'] ?>"
                                                     class="rounded me-2"
                                                     style="width: 40px; height: 40px; object-fit: cover;">
                                                <span><?= htmlspecialchars($request['product_name']) ?></span>
                                            </div>
                                        </td>
                                        <td><?= $request['requested_quantity'] ?> units</td>
                                        <td><?= date('d M Y', strtotime($request['created_at'])) ?></td>
                                        <td>
                                            <?php 
                                                if ($request['status'] == 'pending') {
                                                    echo '<span class="badge bg-warning">Pending</span>';
                                                } elseif ($request['status'] == 'approved') {
                                                    echo '<span class="badge bg-success">Approved</span>';
                                                } else {
                                                    echo '<span class="badge bg-danger">Rejected</span>';
                                                }
                                            ?>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?= htmlspecialchars($request['request_notes'] ?: 'No notes') ?>
                                            </small>
                                        </td>
                                        <td>
                                            <?php if ($request['status'] == 'pending'): ?>
                                                <button class="btn btn-sm btn-success me-1" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#approveModal<?= $request['request_id'] ?>">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#rejectModal<?= $request['request_id'] ?>">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-info" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#viewModal<?= $request['request_id'] ?>">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>

                                    <!-- Approve Modal -->
                                    <div class="modal fade" id="approveModal<?= $request['request_id'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="post">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Approve Restock Request</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <p><strong>Product:</strong> <?= htmlspecialchars($request['product_name']) ?></p>
                                                            <p><strong>Quantity:</strong> <?= $request['requested_quantity'] ?> units</p>
                                                            <p><strong>Request Notes:</strong> <?= htmlspecialchars($request['request_notes'] ?: 'No notes') ?></p>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Response Notes (Optional)</label>
                                                            <textarea name="response_notes" class="form-control" rows="3"></textarea>
                                                        </div>
                                                        <input type="hidden" name="request_id" value="<?= $request['request_id'] ?>">
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" name="approve_request" class="btn btn-success">
                                                            Approve Request
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Reject Modal -->
                                    <div class="modal fade" id="rejectModal<?= $request['request_id'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="post">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Reject Restock Request</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <p><strong>Product:</strong> <?= htmlspecialchars($request['product_name']) ?></p>
                                                            <p><strong>Quantity:</strong> <?= $request['requested_quantity'] ?> units</p>
                                                            <p><strong>Request Notes:</strong> <?= htmlspecialchars($request['request_notes'] ?: 'No notes') ?></p>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Reason for Rejection (Required)</label>
                                                            <textarea name="response_notes" class="form-control" rows="3" required></textarea>
                                                        </div>
                                                        <input type="hidden" name="request_id" value="<?= $request['request_id'] ?>">
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" name="reject_request" class="btn btn-danger">
                                                            Reject Request
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- View Modal -->
                                    <div class="modal fade" id="viewModal<?= $request['request_id'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Request Details</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <p><strong>Product:</strong> <?= htmlspecialchars($request['product_name']) ?></p>
                                                        <p><strong>Quantity:</strong> <?= $request['requested_quantity'] ?> units</p>
                                                        <p><strong>Status:</strong> 
                                                            <?php 
                                                                if ($request['status'] == 'pending') {
                                                                    echo '<span class="badge bg-warning">Pending</span>';
                                                                } elseif ($request['status'] == 'approved') {
                                                                    echo '<span class="badge bg-success">Approved</span>';
                                                                } else {
                                                                    echo '<span class="badge bg-danger">Rejected</span>';
                                                                }
                                                            ?>
                                                        </p>
                                                        <p><strong>Request Date:</strong> <?= date('d M Y H:i', strtotime($request['created_at'])) ?></p>
                                                        <p><strong>Request Notes:</strong> <?= htmlspecialchars($request['request_notes'] ?: 'No notes') ?></p>
                                                        <p><strong>Response Notes:</strong> <?= htmlspecialchars($request['response_notes'] ?: 'No response notes') ?></p>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav aria-label="Restock Requests Pagination" class="mt-3">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&status=<?= $status_filter ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>

    <style>
        /* Card Styles */
        .card {
            border-radius: 15px;
            overflow: hidden;
            border: none;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
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
            vertical-align: middle;
        }

        /* Form Controls */
        .form-control, .form-select {
            padding: 0.75rem 1rem;
            font-size: 0.9rem;
            border-radius: 8px;
            border: 1px solid #eee;
        }

        .form-control:focus, .form-select:focus {
            box-shadow: none;
            border-color: #4361ee;
        }

        /* Button Styles */
        .btn-primary {
            background: linear-gradient(45deg, #4361ee, #4895ef);
            border: none;
            font-weight: 500;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .container-fluid {
                padding: 1rem !important;
            }
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>