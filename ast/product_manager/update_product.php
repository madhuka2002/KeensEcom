<?php
include '../components/connect.php';

session_start();

$manager_id = $_SESSION['manager_id'];

if(!isset($manager_id)){
   header('location:index.php');
}

// Get product ID from URL
$product_id = isset($_GET['update']) ? $_GET['update'] : null;

if(!$product_id) {
    header('location:products.php');
}

// Handle product update
if(isset($_POST['update_product'])) {
    $name = $_POST['name'];
    $name = filter_var($name, FILTER_SANITIZE_STRING);
    $price = $_POST['price'];
    $price = filter_var($price, FILTER_SANITIZE_STRING);
    $details = $_POST['details'];
    $details = filter_var($details, FILTER_SANITIZE_STRING);
    $category_id = $_POST['category_id'];
    $category_id = filter_var($category_id, FILTER_SANITIZE_STRING);

    // Update basic product info
    $update_product = $conn->prepare("UPDATE `products` SET name = ?, price = ?, details = ?, category_id = ? WHERE id = ?");
    $update_product->execute([$name, $price, $details, $category_id, $product_id]);

    $message[] = 'Product updated successfully!';

    // Handle image updates
    $old_image_01 = $_POST['old_image_01'];
    $image_01 = $_FILES['image_01']['name'];
    $image_01 = filter_var($image_01, FILTER_SANITIZE_STRING);
    $image_size_01 = $_FILES['image_01']['size'];
    $image_tmp_name_01 = $_FILES['image_01']['tmp_name'];
    $image_folder_01 = '../uploaded_img/'.$image_01;

    if(!empty($image_01)){
        if($image_size_01 > 2000000){
            $message[] = 'Image size is too large!';
        }else{
            $update_image_01 = $conn->prepare("UPDATE `products` SET image_01 = ? WHERE id = ?");
            $update_image_01->execute([$image_01, $product_id]);
            move_uploaded_file($image_tmp_name_01, $image_folder_01);
            unlink('../uploaded_img/'.$old_image_01);
            $message[] = 'Image 01 updated successfully!';
        }
    }

    $old_image_02 = $_POST['old_image_02'];
    $image_02 = $_FILES['image_02']['name'];
    $image_02 = filter_var($image_02, FILTER_SANITIZE_STRING);
    $image_size_02 = $_FILES['image_02']['size'];
    $image_tmp_name_02 = $_FILES['image_02']['tmp_name'];
    $image_folder_02 = '../uploaded_img/'.$image_02;

    if(!empty($image_02)){
        if($image_size_02 > 2000000){
            $message[] = 'Image size is too large!';
        }else{
            $update_image_02 = $conn->prepare("UPDATE `products` SET image_02 = ? WHERE id = ?");
            $update_image_02->execute([$image_02, $product_id]);
            move_uploaded_file($image_tmp_name_02, $image_folder_02);
            unlink('../uploaded_img/'.$old_image_02);
            $message[] = 'Image 02 updated successfully!';
        }
    }

    $old_image_03 = $_POST['old_image_03'];
    $image_03 = $_FILES['image_03']['name'];
    $image_03 = filter_var($image_03, FILTER_SANITIZE_STRING);
    $image_size_03 = $_FILES['image_03']['size'];
    $image_tmp_name_03 = $_FILES['image_03']['tmp_name'];
    $image_folder_03 = '../uploaded_img/'.$image_03;

    if(!empty($image_03)){
        if($image_size_03 > 2000000){
            $message[] = 'Image size is too large!';
        }else{
            $update_image_03 = $conn->prepare("UPDATE `products` SET image_03 = ? WHERE id = ?");
            $update_image_03->execute([$image_03, $product_id]);
            move_uploaded_file($image_tmp_name_03, $image_folder_03);
            unlink('../uploaded_img/'.$old_image_03);
            $message[] = 'Image 03 updated successfully!';
        }
    }
}

// Get current product data
$select_products = $conn->prepare("SELECT * FROM `products` WHERE id = ?");
$select_products->execute([$product_id]);
$fetch_products = $select_products->fetch(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Keens | Update Product</title>

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<?php include '../components/product_manager_header.php'; ?>

<section class="update-product">
    <div class="container-fluid px-4 py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <!-- Page Header -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h2 class="card-title fw-bold mb-0">Update Product</h2>
                                <p class="text-muted mb-0">Update product information and images</p>
                            </div>
                            <a href="products.php" class="btn btn-light">
                                <i class="fas fa-arrow-left me-2"></i>Back to Products
                            </a>
                        </div>

                        <form action="" method="post" enctype="multipart/form-data">
                            <input type="hidden" name="old_image_01" value="<?= $fetch_products['image_01']; ?>">
                            <input type="hidden" name="old_image_02" value="<?= $fetch_products['image_02']; ?>">
                            <input type="hidden" name="old_image_03" value="<?= $fetch_products['image_03']; ?>">

                            <div class="row g-4">
                                <!-- Basic Information -->
                                <div class="col-md-8">
                                    <div class="card bg-light border-0">
                                        <div class="card-body">
                                            <h5 class="card-title mb-3">Basic Information</h5>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Product Name</label>
                                                <input type="text" name="name" required class="form-control" 
                                                       value="<?= $fetch_products['name']; ?>">
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">Price</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">$</span>
                                                    <input type="number" name="price" required class="form-control" 
                                                           min="0" step="0.01" value="<?= $fetch_products['price']; ?>">
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">Category</label>
                                                <select name="category_id" class="form-select" required>
                                                    <?php
                                                        $select_categories = $conn->prepare("SELECT * FROM `category`");
                                                        $select_categories->execute();
                                                        while($category = $select_categories->fetch(PDO::FETCH_ASSOC)){
                                                            $selected = ($category['category_id'] == $fetch_products['category_id']) ? 'selected' : '';
                                                            echo "<option value='".$category['category_id']."' ".$selected.">".$category['name']."</option>";
                                                        }
                                                    ?>
                                                </select>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">Description</label>
                                                <textarea name="details" required class="form-control" 
                                                          rows="4"><?= $fetch_products['details']; ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Product Images -->
                                <div class="col-md-4">
                                    <div class="card bg-light border-0">
                                        <div class="card-body">
                                            <h5 class="card-title mb-3">Product Images</h5>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Main Image</label>
                                                <input type="file" name="image_01" accept="image/jpg, image/jpeg, image/png, image/webp" 
                                                       class="form-control">
                                                <img src="../uploaded_img/<?= $fetch_products['image_01']; ?>" 
                                                     class="img-thumbnail mt-2" alt="Main image">
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">Extra Image 1</label>
                                                <input type="file" name="image_02" accept="image/jpg, image/jpeg, image/png, image/webp" 
                                                       class="form-control">
                                                <?php if($fetch_products['image_02']): ?>
                                                    <img src="../uploaded_img/<?= $fetch_products['image_02']; ?>" 
                                                         class="img-thumbnail mt-2" alt="Extra image 1">
                                                <?php endif; ?>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">Extra Image 2</label>
                                                <input type="file" name="image_03" accept="image/jpg, image/jpeg, image/png, image/webp" 
                                                       class="form-control">
                                                <?php if($fetch_products['image_03']): ?>
                                                    <img src="../uploaded_img/<?= $fetch_products['image_03']; ?>" 
                                                         class="img-thumbnail mt-2" alt="Extra image 2">
                                                <?php endif; ?>
                                            </div>

                                            <div class="image-note bg-white rounded p-3 mb-3">
                                                <small class="text-muted">
                                                    <i class="fas fa-info-circle me-1"></i>
                                                    Recommended image size: 800x800px
                                                    <br>Maximum file size: 2MB
                                                    <br>Supported formats: JPG, JPEG, PNG, WEBP
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Form Actions -->
                                <div class="col-12">
                                    <hr class="my-4">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="products.php" class="btn btn-light">
                                            <i class="fas fa-times me-2"></i>Cancel
                                        </a>
                                        <button type="submit" name="update_product" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Update Product
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

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

/* Input Group */
.input-group-text {
    background-color: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px 0 0 8px;
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

/* Image Preview */
.img-thumbnail {
    max-height: 150px;
    width: auto;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .container-fluid {
        padding: 1rem !important;
    }

    .card-body {
        padding: 1.5rem;
    }

    .btn {
        width: 100%;
        margin-bottom: 0.5rem;
    }
}
</style>

<script>
// Preview images before upload
document.querySelectorAll('input[type="file"]').forEach(input => {
    input.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const img = this.nextElementSibling;
            if (img && img.tagName === 'IMG') {
                img.src = URL.createObjectURL(this.files[0]);
            }
        }
    });
});

// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const price = document.querySelector('input[name="price"]').value;
    if (price <= 0) {
        e.preventDefault();
        alert('Price must be greater than 0');
    }
});

// Image size validation
document.querySelectorAll('input[type="file"]').forEach(input => {
    input.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            if (this.files[0].size > 2000000) {
                alert('Image size must be less than 2MB');
                this.value = '';
                return;
            }

            // Validate image dimensions
            const img = new Image();
            img.src = URL.createObjectURL(this.files[0]);
            img.onload = function() {
                if (this.width < 800 || this.height < 800) {
                    alert('Image dimensions should be at least 800x800 pixels');
                    input.value = '';
                }
            };
        }
    });
});

// Handle form submission loading state
document.querySelector('button[type="submit"]').addEventListener('click', function() {
    const form = this.closest('form');
    if(form.checkValidity()) {
        this.disabled = true;
        const originalText = this.innerHTML;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Updating...';
        
        // Reset button after 10 seconds (in case of error)
        setTimeout(() => {
            this.disabled = false;
            this.innerHTML = originalText;
        }, 10000);
    }
});

// Auto-expand textarea
document.querySelector('textarea[name="details"]').addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = (this.scrollHeight + 2) + 'px';
});

// Confirm navigation when form is dirty
let formChanged = false;
document.querySelector('form').addEventListener('change', function() {
    formChanged = true;
});

window.addEventListener('beforeunload', function(e) {
    if (formChanged) {
        e.preventDefault();
        e.returnValue = '';
    }
});

// Clear form change flag when submitting
document.querySelector('form').addEventListener('submit', function() {
    formChanged = false;
});

// Handle cancel button
document.querySelector('.btn-light').addEventListener('click', function(e) {
    if (formChanged) {
        if (!confirm('You have unsaved changes. Are you sure you want to leave?')) {
            e.preventDefault();
        }
    }
});

// Image format validation
const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
document.querySelectorAll('input[type="file"]').forEach(input => {
    input.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            if (!allowedTypes.includes(this.files[0].type)) {
                alert('Only JPG, JPEG, PNG, and WEBP files are allowed');
                this.value = '';
            }
        }
    });
});

// Initialize tooltips
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl)
});
</script>

</body>
</html>