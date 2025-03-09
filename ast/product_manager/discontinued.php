<?php
include '../components/connect.php';

session_start();

$manager_id = $_SESSION['manager_id'];

if(!isset($manager_id)){
   header('location:index.php');
}

// Handle Discontinue Product
if(isset($_POST['discontinue_product'])){
    $product_id = $_POST['product_id'];
    $reason = $_POST['reason'];
    
    $update_product = $conn->prepare("UPDATE `products` SET status = 'discontinued', discontinued_reason = ?, discontinued_at = CURRENT_TIMESTAMP WHERE id = ?");
    $update_product->execute([$reason, $product_id]);
    
    $message[] = 'Product marked as discontinued!';
}

// Handle Restore Product
if(isset($_POST['restore_product'])){
    $product_id = $_POST['product_id'];
    
    $update_product = $conn->prepare("UPDATE `products` SET status = 'active', discontinued_reason = NULL, discontinued_at = NULL WHERE id = ?");
    $update_product->execute([$product_id]);
    
    $message[] = 'Product restored successfully!';
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Keens | Discontinued Products</title>

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<?php include '../components/product_manager_header.php'; ?>

<section class="discontinued-products">
    <div class="container-fluid px-4 py-5">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-0">Discontinued Products</h2>
                <p class="text-muted mb-0">Manage discontinued product listings</p>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#discontinueProductModal">
                <i class="fas fa-archive me-2"></i>Discontinue Product
            </button>
        </div>

        <!-- Stats Cards -->
        <div class="row g-4 mb-4">
            <!-- Total Discontinued -->
            <div class="col-12 col-sm-6 col-xl-3">
                <?php
                    $total_discontinued = $conn->prepare("SELECT COUNT(*) as count FROM `products` WHERE status = 'discontinued'");
                    $total_discontinued->execute();
                    $discontinued_count = $total_discontinued->fetch(PDO::FETCH_ASSOC)['count'];
                ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="icon-wrapper bg-danger bg-opacity-10 text-danger">
                                <i class="fas fa-archive"></i>
                            </div>
                            <div class="ms-3">
                                <h6 class="text-muted mb-0">Total Discontinued</h6>
                                <h3 class="mb-0"><?= number_format($discontinued_count); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recently Discontinued -->
            <div class="col-12 col-sm-6 col-xl-3">
                <?php
                    $recent_discontinued = $conn->prepare("SELECT COUNT(*) as count FROM `products` WHERE status = 'discontinued' AND discontinued_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
                    $recent_discontinued->execute();
                    $recent_count = $recent_discontinued->fetch(PDO::FETCH_ASSOC)['count'];
                ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="icon-wrapper bg-warning bg-opacity-10 text-warning">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="ms-3">
                                <h6 class="text-muted mb-0">Recent (30 days)</h6>
                                <h3 class="mb-0"><?= number_format($recent_count); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
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
                                <th>Original Price</th>
                                <th>Discontinued Date</th>
                                <th>Reason</th>
                                <th>Remaining Stock</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                $select_products = $conn->prepare("
                                    SELECT p.*, i.quantity 
                                    FROM `products` p 
                                    LEFT JOIN `inventory` i ON p.id = i.product_id 
                                    WHERE p.status = 'discontinued' 
                                    ORDER BY p.discontinued_at DESC
                                ");
                                $select_products->execute();
                                if($select_products->rowCount() > 0){
                                    while($product = $select_products->fetch(PDO::FETCH_ASSOC)){
                            ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <img src="../uploaded_img/<?= $product['image_01']; ?>" 
                                             alt="<?= $product['name']; ?>"
                                             class="rounded"
                                             style="width: 40px; height: 40px; object-fit: cover;">
                                        <div class="ms-3">
                                            <h6 class="mb-0"><?= $product['name']; ?></h6>
                                            <small class="text-muted">ID: #<?= $product['id']; ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>$<?= number_format($product['price'], 2); ?></td>
                                <td>
                                    <small class="text-muted">
                                        <?= date('M d, Y', strtotime($product['discontinued_at'])); ?>
                                    </small>
                                </td>
                                <td>
                                    <span class="text-truncate d-inline-block" style="max-width: 200px;">
                                        <?= $product['discontinued_reason']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if($product['quantity'] > 0): ?>
                                        <span class="badge bg-warning-subtle text-warning">
                                            <?= $product['quantity']; ?> left
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger">Out of stock</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-sm btn-light me-2" data-bs-toggle="modal" 
                                            data-bs-target="#viewProductModal<?= $product['id']; ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="product_id" value="<?= $product['id']; ?>">
                                        <button type="submit" name="restore_product" class="btn btn-sm btn-success" 
                                                onclick="return confirm('Are you sure you want to restore this product?');">
                                            <i class="fas fa-undo"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>

                            <!-- View Product Modal -->
                            <div class="modal fade" id="viewProductModal<?= $product['id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content border-0">
                                        <div class="modal-header border-0">
                                            <h5 class="modal-title">Product Details</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <img src="../uploaded_img/<?= $product['image_01']; ?>" 
                                                     alt="<?= $product['name']; ?>"
                                                     class="img-fluid rounded mb-3"
                                                     style="max-height: 200px; width: 100%; object-fit: cover;">
                                            </div>
                                            <div class="mb-3">
                                                <label class="fw-bold">Name</label>
                                                <p class="mb-1"><?= $product['name']; ?></p>
                                            </div>
                                            <div class="mb-3">
                                                <label class="fw-bold">Description</label>
                                                <p class="mb-1"><?= $product['details']; ?></p>
                                            </div>
                                            <div class="mb-3">
                                                <label class="fw-bold">Original Price</label>
                                                <p class="mb-1">$<?= number_format($product['price'], 2); ?></p>
                                            </div>
                                            <div class="mb-3">
                                                <label class="fw-bold">Discontinued On</label>
                                                <p class="mb-1"><?= date('F d, Y', strtotime($product['discontinued_at'])); ?></p>
                                            </div>
                                            <div class="mb-3">
                                                <label class="fw-bold">Reason</label>
                                                <p class="mb-1"><?= $product['discontinued_reason']; ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php
                                    }
                                } else {
                                    echo '<tr><td colspan="6" class="text-center py-4">No discontinued products found</td></tr>';
                                }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Discontinue Product Modal -->
<div class="modal fade" id="discontinueProductModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0">
            <form method="post" action="">
                <div class="modal-header border-0">
                    <h5 class="modal-title">Discontinue Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select Product</label>
                        <select name="product_id" class="form-select" required>
                            <option value="">Choose a product</option>
                            <?php
                                $select_active = $conn->prepare("SELECT * FROM `products` WHERE status = 'active'");
                                $select_active->execute();
                                while($active = $select_active->fetch(PDO::FETCH_ASSOC)){
                                    echo "<option value='".$active['id']."'>".$active['name']."</option>";
                                }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason for Discontinuation</label>
                        <textarea name="reason" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="discontinue_product" class="btn btn-danger">
                        <i class="fas fa-archive me-2"></i>Discontinue Product
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

.modal-header, .modal-footer
{
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

// Handle form submission loading state
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function() {
        const btn = this.querySelector('button[type="submit"]');
        if(btn) {
            btn.disabled = true;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
            
            // Reset button after 3 seconds if form hasn't redirected
            setTimeout(() => {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }, 3000);
        }
    });
});

// Product search functionality
document.getElementById('searchProduct')?.addEventListener('input', function() {
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

// Reason validation
document.querySelector('textarea[name="reason"]')?.addEventListener('input', function() {
    const minLength = 10;
    if(this.value.length < minLength) {
        this.classList.add('is-invalid');
        this.nextElementSibling?.remove();
        const feedback = document.createElement('div');
        feedback.className = 'invalid-feedback';
        feedback.textContent = `Please provide at least ${minLength} characters for the reason.`;
        this.parentNode.appendChild(feedback);
    } else {
        this.classList.remove('is-invalid');
        this.nextElementSibling?.remove();
    }
});

// Product selection validation
document.querySelector('select[name="product_id"]')?.addEventListener('change', function() {
    if(!this.value) {
        this.classList.add('is-invalid');
    } else {
        this.classList.remove('is-invalid');
    }
});

// Form validation before submit
document.querySelector('form')?.addEventListener('submit', function(e) {
    const reason = this.querySelector('textarea[name="reason"]');
    const product = this.querySelector('select[name="product_id"]');
    
    if(reason && reason.value.length < 10) {
        e.preventDefault();
        reason.focus();
        return false;
    }
    
    if(product && !product.value) {
        e.preventDefault();
        product.focus();
        return false;
    }
});

// Modal initialization with focus handling
var discontinueModal = document.getElementById('discontinueProductModal')
if(discontinueModal) {
    discontinueModal.addEventListener('shown.bs.modal', function () {
        discontinueModal.querySelector('select').focus()
    });
}

// Enable floating labels if using them
document.querySelectorAll('.form-floating input, .form-floating textarea').forEach(element => {
    element.addEventListener('focus', function() {
        this.parentElement.classList.add('focused');
    });
    
    element.addEventListener('blur', function() {
        if(!this.value) {
            this.parentElement.classList.remove('focused');
        }
    });
});

// Image preview on hover if needed
document.querySelectorAll('.product-image').forEach(img => {
    img.addEventListener('mouseenter', function() {
        const preview = document.createElement('div');
        preview.className = 'image-preview';
        preview.innerHTML = `<img src="${this.src}" alt="${this.alt}">`;
        document.body.appendChild(preview);
    });
    
    img.addEventListener('mouseleave', function() {
        document.querySelector('.image-preview')?.remove();
    });
});
</script>

</body>
</html>