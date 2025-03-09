<?php
include '../components/connect.php';

session_start();

$manager_id = $_SESSION['manager_id'];

if(!isset($manager_id)){
   header('location:index.php');
}

// Handle new restock request
if(isset($_POST['add_request'])){
   $product_id = $_POST['product_id'];
   $supplier_id = $_POST['supplier_id'];
   $requested_quantity = $_POST['requested_quantity'];
   $notes = $_POST['notes'];
   $notes = filter_var($notes, FILTER_SANITIZE_STRING);

   // Validate inputs
   if($requested_quantity <= 0){
      $message[] = 'Requested quantity must be greater than zero!';
   } else {
      // Check for ANY recent active requests (both pending and approved)
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
         $insert_request->execute([$supplier_id, $product_id, $requested_quantity, $notes]);
         $message[] = 'Restock request submitted successfully!';
      }
   }
}

// Handle cancel request
if(isset($_GET['cancel'])){
   $request_id = $_GET['cancel'];
   
   $cancel_request = $conn->prepare("UPDATE `restock_requests` SET status = 'rejected', response_notes = 'Cancelled by manager' WHERE request_id = ?");
   $cancel_request->execute([$request_id]);
   
   $message[] = 'Restock request cancelled!';
   header('location: restock_requests.php');
}

// Handle cancel duplicate requests
if(isset($_GET['cancel_duplicates'])){
   $product_id = $_GET['product_id'];
   $supplier_id = $_GET['supplier_id'];
   
   // Check if there's an approved request
   $check_approved = $conn->prepare("SELECT COUNT(*) as count FROM `restock_requests` 
                                   WHERE product_id = ? AND supplier_id = ? AND status = 'approved'");
   $check_approved->execute([$product_id, $supplier_id]);
   
   if($check_approved->fetch(PDO::FETCH_ASSOC)['count'] > 0) {
      // There's an approved request, so cancel all pending ones
      $cancel_pending = $conn->prepare("UPDATE `restock_requests` 
                                      SET status = 'rejected', response_notes = 'Cancelled due to duplicate approved request' 
                                      WHERE product_id = ? AND supplier_id = ? AND status = 'pending'");
      $cancel_pending->execute([$product_id, $supplier_id]);
      $message[] = 'Duplicate requests cancelled!';
   }
   
   header('location: restock_requests.php');
}

// Handle deleting request history
if(isset($_GET['delete_history'])){
   $product_id = $_GET['product_id'];
   $supplier_id = $_GET['supplier_id'];
   
   // Delete only completed (approved/rejected) requests
   $delete_history = $conn->prepare("DELETE FROM `restock_requests` 
                                  WHERE product_id = ? AND supplier_id = ? 
                                  AND status IN ('approved', 'rejected')");
   $delete_history->execute([$product_id, $supplier_id]);
   
   $message[] = 'Request history cleared! You can now send a new request.';
   header('location: restock_requests.php');
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Keens | Restock Requests</title>

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<?php include '../components/product_manager_header.php'; ?>

<section class="restock-requests">
    <div class="container-fluid px-4 py-5">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-0">Restock Requests</h2>
                <p class="text-muted mb-0">Manage inventory restock requests</p>
            </div>
            <div>
                
                <button class="btn btn-primary rounded-3" data-bs-toggle="modal" data-bs-target="#newRequestModal">
                    <i class="fas fa-plus me-2"></i>New Request
                </button>
            </div>
        </div>

        <!-- Filters -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12 col-md-3">
                        <select class="form-select bg-light border-0" id="status-filter">
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-3">
                        <select class="form-select bg-light border-0" id="supplier-filter">
                            <option value="">All Suppliers</option>
                            <?php
                                $suppliers = $conn->prepare("SELECT * FROM `suppliers`");
                                $suppliers->execute();
                                while($supplier = $suppliers->fetch(PDO::FETCH_ASSOC)){
                                    echo "<option value='".$supplier['supplier_id']."'>".$supplier['name']."</option>";
                                }
                            ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text" class="form-control bg-light border-0" 
                                  id="search-input" placeholder="Search products...">
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>

        <!-- Requests Table -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle" id="requests-table">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Request ID</th>
                                <th>Product</th>
                                <th>Supplier</th>
                                <th>Quantity</th>
                                <th>Requested On</th>
                                <th>Status</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                // Group requests by product-supplier combination
                                $product_supplier_groups = [];
                                
                                // Fetch product-supplier combinations that have multiple requests
                                $duplicate_check = $conn->prepare("
                                    SELECT product_id, supplier_id, COUNT(*) as request_count,
                                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count
                                    FROM `restock_requests`
                                    GROUP BY product_id, supplier_id
                                    HAVING request_count > 1 AND approved_count > 0
                                ");
                                $duplicate_check->execute();
                                $duplicates = $duplicate_check->fetchAll(PDO::FETCH_ASSOC);
                                
                                // Create a list of product-supplier combinations with duplicates
                                $duplicate_pairs = [];
                                foreach ($duplicates as $dup) {
                                    $duplicate_pairs[$dup['product_id'] . '-' . $dup['supplier_id']] = true;
                                }
                            
                                $restock_requests = $conn->prepare("
                                    SELECT r.*, p.name as product_name, p.image_01, s.name as supplier_name 
                                    FROM `restock_requests` r 
                                    JOIN `products` p ON r.product_id = p.id
                                    JOIN `suppliers` s ON r.supplier_id = s.supplier_id
                                    ORDER BY r.created_at DESC
                                ");
                                $restock_requests->execute();
                                
                                // Group requests by product-supplier for history deletion feature
                                $grouped_requests = [];
                                
                                if($restock_requests->rowCount() > 0){
                                    while($request = $restock_requests->fetch(PDO::FETCH_ASSOC)){
                                        // Track groups for history deletion
                                        $key = $request['product_id'] . '-' . $request['supplier_id'];
                                        if(!isset($grouped_requests[$key])) {
                                            $grouped_requests[$key] = [
                                                'product_id' => $request['product_id'],
                                                'supplier_id' => $request['supplier_id'],
                                                'product_name' => $request['product_name'],
                                                'supplier_name' => $request['supplier_name'],
                                                'has_pending' => $request['status'] === 'pending',
                                                'has_completed' => $request['status'] === 'approved' || $request['status'] === 'rejected',
                                                'count' => 1
                                            ];
                                        } else {
                                            $grouped_requests[$key]['count']++;
                                            if($request['status'] === 'pending') {
                                                $grouped_requests[$key]['has_pending'] = true;
                                            }
                                            if($request['status'] === 'approved' || $request['status'] === 'rejected') {
                                                $grouped_requests[$key]['has_completed'] = true;
                                            }
                                        }
                                        
                                        // Check if this request is part of a duplicate set
                                        $is_duplicate = isset($duplicate_pairs[$request['product_id'] . '-' . $request['supplier_id']]);
                            ?>
                            <tr class="request-row <?= $is_duplicate ? 'table-warning' : '' ?>" 
                                data-status="<?= $request['status']; ?>" 
                                data-supplier="<?= $request['supplier_id']; ?>"
                                data-product="<?= $request['product_id']; ?>"
                                data-product-supplier="<?= $request['product_id'] . '-' . $request['supplier_id']; ?>">
                                <td class="ps-4">
                                    #<?= $request['request_id']; ?>
                                    <?php if($is_duplicate && $request['status'] == 'pending'): ?>
                                        <span class="badge bg-warning-subtle text-warning">Duplicate</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="../uploaded_img/<?= $request['image_01']; ?>" 
                                             alt="<?= $request['product_name']; ?>"
                                             class="rounded-3 me-3"
                                             style="width: 40px; height: 40px; object-fit: cover;">
                                        <span><?= $request['product_name']; ?></span>
                                    </div>
                                </td>
                                <td><?= $request['supplier_name']; ?></td>
                                <td><?= $request['requested_quantity']; ?> units</td>
                                <td><small class="text-muted"><?= date('M d, Y', strtotime($request['created_at'])); ?></small></td>
                                <td>
                                    <?php 
                                        if($request['status'] == 'pending') {
                                            echo '<span class="badge bg-warning-subtle text-warning">Pending</span>';
                                        } elseif($request['status'] == 'approved') {
                                            echo '<span class="badge bg-success-subtle text-success">Approved</span>';
                                        } else {
                                            echo '<span class="badge bg-danger-subtle text-danger">Rejected</span>';
                                        }
                                    ?>
                                </td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-sm btn-light me-2" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#viewRequestModal<?= $request['request_id']; ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    
                                    <?php if($request['status'] == 'pending'): ?>
                                        <?php if($is_duplicate): ?>
                                        <a href="restock_requests.php?cancel_duplicates=1&product_id=<?= $request['product_id']; ?>&supplier_id=<?= $request['supplier_id']; ?>" 
                                           class="btn btn-sm btn-warning me-2"
                                           title="Cancel duplicates"
                                           onclick="return confirm('Cancel all pending duplicate requests for this product?');">
                                            <i class="fas fa-broom"></i>
                                        </a>
                                        <?php endif; ?>
                                        
                                        <a href="restock_requests.php?cancel=<?= $request['request_id']; ?>" 
                                           class="btn btn-sm btn-light text-danger"
                                           onclick="return confirm('Cancel this restock request?');">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    <?php endif; ?>
                                    
                                </td>
                            </tr>

                            <!-- View Request Modal -->
                            <div class="modal fade" id="viewRequestModal<?= $request['request_id']; ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content border-0">
                                        <div class="modal-header border-0">
                                            <h5 class="modal-title fw-bold">Request Details #<?= $request['request_id']; ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <h6 class="mb-3">Product Information</h6>
                                                    <div class="d-flex mb-3">
                                                        <img src="../uploaded_img/<?= $request['image_01']; ?>" 
                                                             class="rounded-3 me-3"
                                                             style="width: 80px; height: 80px; object-fit: cover;">
                                                        <div>
                                                            <h5><?= $request['product_name']; ?></h5>
                                                            <p class="text-muted mb-0">Product ID: <?= $request['product_id']; ?></p>
                                                        </div>
                                                    </div>
                                                    <p><strong>Requested Quantity:</strong> <?= $request['requested_quantity']; ?> units</p>
                                                    <p><strong>Request Notes:</strong> <?= $request['request_notes'] ?: 'None'; ?></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <h6 class="mb-3">Supplier Information</h6>
                                                    <p><strong>Supplier Name:</strong> <?= $request['supplier_name']; ?></p>
                                                    <p><strong>Request Status:</strong> 
                                                        <?php 
                                                            if($request['status'] == 'pending') {
                                                                echo '<span class="badge bg-warning">Pending</span>';
                                                            } elseif($request['status'] == 'approved') {
                                                                echo '<span class="badge bg-success">Approved</span>';
                                                            } else {
                                                                echo '<span class="badge bg-danger">Rejected</span>';
                                                            }
                                                        ?>
                                                    </p>
                                                    <p><strong>Requested On:</strong> <?= date('F d, Y', strtotime($request['created_at'])); ?></p>
                                                    
                                                    <?php if($request['status'] != 'pending'): ?>
                                                    <p><strong>Response Notes:</strong> <?= $request['response_notes'] ?: 'None'; ?></p>
                                                    <?php endif; ?>
                                                    
                                                    <?php if($is_duplicate): ?>
                                                    <div class="alert alert-warning">
                                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                                        <strong>Warning:</strong> Multiple requests exist for this product-supplier combination.
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer border-0">
                                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                                            
                                            <?php 
                                                $key = $request['product_id'] . '-' . $request['supplier_id'];
                                                if(isset($grouped_requests[$key]) && $grouped_requests[$key]['has_completed']): 
                                            ?>
                                            <a href="restock_requests.php?delete_history=1&product_id=<?= $request['product_id']; ?>&supplier_id=<?= $request['supplier_id']; ?>" 
                                               class="btn btn-info"
                                               onclick="return confirm('Delete request history for this product-supplier? This will allow you to create new requests.');">
                                                <i class="fas fa-trash-alt me-2"></i>Delete History
                                            </a>
                                            <?php endif; ?>
                                            
                                            <?php if($is_duplicate && $request['status'] == 'pending'): ?>
                                            <a href="restock_requests.php?cancel_duplicates=1&product_id=<?= $request['product_id']; ?>&supplier_id=<?= $request['supplier_id']; ?>" 
                                               class="btn btn-warning"
                                               onclick="return confirm('Cancel all pending duplicate requests for this product?');">
                                                Cancel Duplicates
                                            </a>
                                            <?php endif; ?>
                                            
                                            <?php if($request['status'] == 'pending'): ?>
                                            <a href="restock_requests.php?cancel=<?= $request['request_id']; ?>" 
                                               class="btn btn-danger"
                                               onclick="return confirm('Cancel this restock request?');">
                                                Cancel Request
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php
                                    }
                                } else {
                                    echo '<tr><td colspan="7" class="text-center py-4">No restock requests found</td></tr>';
                                }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Request History Section -->
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">Request History by Product & Supplier</h5>
                <p class="text-muted small mb-0">Clear history to send new requests for products with completed requests</p>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach($grouped_requests as $key => $group): 
                        // Only show groups that have completed requests
                        if(!$group['has_completed']) continue;
                        
                        list($product_id, $supplier_id) = explode('-', $key);
                    ?>
                    <div class="col-md-4 mb-3">
                        <div class="card h-100 border">
                            <div class="card-body">
                                <h6 class="card-title"><?= $group['product_name']; ?></h6>
                                <p class="card-text text-muted small">Supplier: <?= $group['supplier_name']; ?></p>
                                <p class="card-text">
                                    <span class="badge bg-secondary"><?= $group['count']; ?> Requests</span>
                                    <?php if($group['has_pending']): ?>
                                    <span class="badge bg-warning">Has Pending</span>
                                    <?php endif; ?>
                                </p>
                                <a href="restock_requests.php?delete_history=1&product_id=<?= $product_id; ?>&supplier_id=<?= $supplier_id; ?>" 
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Delete completed request history for this product-supplier? This will allow you to create new requests.');">
                                    <i class="fas fa-trash-alt me-2"></i>Clear History
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php if(empty($grouped_requests)): ?>
                    <div class="col-12">
                        <p class="text-center text-muted">No product request history available.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- New Request Modal -->
<div class="modal fade" id="newRequestModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0">
            <form method="post" action="">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Create Restock Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select Product</label>
                        <select name="product_id" class="form-select" required id="product-select">
                            <option value="">Select a product</option>
                            <?php
                                $low_stock_products = $conn->prepare("
                                    SELECT p.*, i.quantity 
                                    FROM `products` p 
                                    LEFT JOIN `inventory` i ON p.id = i.product_id
                                    WHERE i.quantity IS NULL OR i.quantity < 10
                                    ORDER BY p.name
                                ");
                                $low_stock_products->execute();
                                while($product = $low_stock_products->fetch(PDO::FETCH_ASSOC)){
                                    echo "<option value='".$product['id']."'>".$product['name']." - Stock: ".($product['quantity'] ?? 0)."</option>";
                                }
                            ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Select Supplier</label>
                        <select name="supplier_id" class="form-select" required id="supplier-select">
                            <option value="">Select a supplier</option>
                            <?php
                                $suppliers = $conn->prepare("SELECT * FROM `suppliers`");
                                $suppliers->execute();
                                while($supplier = $suppliers->fetch(PDO::FETCH_ASSOC)){
                                    echo "<option value='".$supplier['supplier_id']."'>".$supplier['name']."</option>";
                                }
                            ?>
                        </select>
                    </div>
                    
                    <!-- Warning for existing requests -->
                    <div id="existing-request-warning" class="alert alert-warning d-none">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <span>There are existing requests for this product-supplier combination. You may need to clear history first.</span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Quantity to Request</label>
                        <input type="number" name="requested_quantity" required class="form-control" 
                               min="1" placeholder="Enter quantity">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notes (Optional)</label>
                        <textarea name="notes" class="form-control" rows="3" 
                                  placeholder="Additional information for the supplier"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_request" class="btn btn-primary">Submit Request</button>
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
    vertical-align: middle;
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

/* Status Colors */
.bg-warning-subtle {
    background-color: rgba(243, 156, 18, 0.1) !important;
}

.bg-success-subtle {
    background-color: rgba(46, 204, 113, 0.1) !important;
}

.bg-danger-subtle {
    background-color: rgba(220, 53, 69, 0.1) !important;
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
}

.card-header {
    border-bottom: none;
    padding: 1.5rem;
}
</style>

<script>
// Filter functionality
document.addEventListener('DOMContentLoaded', function() {
    const statusFilter = document.getElementById('status-filter');
    const supplierFilter = document.getElementById('supplier-filter');
    const searchInput = document.getElementById('search-input');
    const requestRows = document.querySelectorAll('.request-row');

    // Apply filters
    function applyFilters() {
        const statusValue = statusFilter.value;
        const supplierValue = supplierFilter.value;
        const searchValue = searchInput.value.toLowerCase();

        requestRows.forEach(row => {
            const status = row.getAttribute('data-status');
            const supplier = row.getAttribute('data-supplier');
            const text = row.textContent.toLowerCase();

            const statusMatch = statusValue === '' || status === statusValue;
            const supplierMatch = supplierValue === '' || supplier === supplierValue;
            const searchMatch = searchValue === '' || text.includes(searchValue);

            if (statusMatch && supplierMatch && searchMatch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    // Add event listeners
    statusFilter.addEventListener('change', applyFilters);
    supplierFilter.addEventListener('change', applyFilters);
    searchInput.addEventListener('input', applyFilters);
    
    // Check Duplicates button functionality
    document.getElementById('checkDuplicatesBtn').addEventListener('click', function() {
        // Highlight duplicate rows
        const duplicateRows = document.querySelectorAll('.table-warning');
        
        if (duplicateRows.length > 0) {
            // Scroll to first duplicate
            duplicateRows[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            // Flash effect on duplicates
            duplicateRows.forEach(row => {
                row.style.transition = 'background-color 0.5s';
                row.style.backgroundColor = '#ffe066';
                setTimeout(() => {
                    row.style.backgroundColor = '';
                }, 1000);
            });
            
            // Show alert with count
            alert(`Found ${duplicateRows.length} rows with duplicate requests. Highlighted in yellow.`);
        } else {
            alert('No duplicate requests found!');
        }
    });
    
    // Warning for existing requests in new request modal
    const productSelect = document.getElementById('product-select');
    const supplierSelect = document.getElementById('supplier-select');
    const warningElement = document.getElementById('existing-request-warning');
    
    function checkExistingRequests() {
        const productId = productSelect.value;
        const supplierId = supplierSelect.value;
        
        if(productId && supplierId) {
            const key = productId + '-' + supplierId;
            const rows = document.querySelectorAll(`.request-row[data-product-supplier="${key}"]`);
            
            if(rows.length > 0) {
                warningElement.classList.remove('d-none');
            } else {
                warningElement.classList.add('d-none');
            }
        }
    }
    
    productSelect.addEventListener('change', checkExistingRequests);
    supplierSelect.addEventListener('change', checkExistingRequests);
});
</script>

</body>
</html>