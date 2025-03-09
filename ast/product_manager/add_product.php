<?php
include '../components/connect.php';

session_start();

$manager_id = $_SESSION['manager_id'];

if(!isset($manager_id)){
   header('location:index.php');
}

if(isset($_POST['add_product'])){

   $name = $_POST['name'];
   $name = filter_var($name, FILTER_SANITIZE_STRING);
   $price = $_POST['price'];
   $price = filter_var($price, FILTER_SANITIZE_STRING);
   $details = $_POST['details'];
   $details = filter_var($details, FILTER_SANITIZE_STRING);

   $image_01 = $_FILES['image_01']['name'];
   $image_01 = filter_var($image_01, FILTER_SANITIZE_STRING);
   $image_size_01 = $_FILES['image_01']['size'];
   $image_tmp_name_01 = $_FILES['image_01']['tmp_name'];
   $image_folder_01 = '../uploaded_img/'.$image_01;

   $image_02 = $_FILES['image_02']['name'];
   $image_02 = filter_var($image_02, FILTER_SANITIZE_STRING);
   $image_size_02 = $_FILES['image_02']['size'];
   $image_tmp_name_02 = $_FILES['image_02']['tmp_name'];
   $image_folder_02 = '../uploaded_img/'.$image_02;

   $image_03 = $_FILES['image_03']['name'];
   $image_03 = filter_var($image_03, FILTER_SANITIZE_STRING);
   $image_size_03 = $_FILES['image_03']['size'];
   $image_tmp_name_03 = $_FILES['image_03']['tmp_name'];
   $image_folder_03 = '../uploaded_img/'.$image_03;

   $select_products = $conn->prepare("SELECT * FROM `products` WHERE name = ?");
   $select_products->execute([$name]);

   if($select_products->rowCount() > 0){
      $message[] = 'Product name already exists!';
   }else{
      if($image_size_01 > 2000000 OR $image_size_02 > 2000000 OR $image_size_03 > 2000000){
         $message[] = 'Image size is too large!';
      }else{
         move_uploaded_file($image_tmp_name_01, $image_folder_01);
         move_uploaded_file($image_tmp_name_02, $image_folder_02);
         move_uploaded_file($image_tmp_name_03, $image_folder_03);

         $insert_product = $conn->prepare("INSERT INTO `products`(name, details, price, image_01, image_02, image_03) VALUES(?,?,?,?,?,?)");
         $insert_product->execute([$name, $details, $price, $image_01, $image_02, $image_03]);

         // Create inventory entry for the new product
         $product_id = $conn->lastInsertId();
         $insert_inventory = $conn->prepare("INSERT INTO `inventory`(product_id, quantity) VALUES(?,?)");
         $insert_inventory->execute([$product_id, 0]);

         $message[] = 'New product added successfully!';
      }
   }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Keens | Add Product</title>

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<?php include '../components/product_manager_header.php'; ?>

<section class="add-product">
    <div class="container-fluid px-4 py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <!-- Page Header -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h2 class="card-title fw-bold mb-0">Add New Product</h2>
                                <p class="text-muted mb-0">Create a new product listing</p>
                            </div>
                            <a href="products.php" class="btn btn-light">
                                <i class="fas fa-arrow-left me-2"></i>Back to Products
                            </a>
                        </div>

                        <!-- Add Product Form -->
                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="row g-4">
                                <!-- Basic Information -->
                                <div class="col-md-8">
                                    <div class="card bg-light border-0">
                                        <div class="card-body">
                                            <h5 class="card-title mb-3">Basic Information</h5>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Product Name</label>
                                                <input type="text" name="name" required class="form-control" 
                                                       placeholder="Enter product name">
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">Price</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">$</span>
                                                    <input type="number" name="price" required class="form-control" 
                                                           min="0" step="0.01" placeholder="0.00">
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">Description</label>
                                                <textarea name="details" required class="form-control" rows="4" 
                                                          placeholder="Enter product description"></textarea>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">Category</label>
                                                <select class="form-select" name="category_id">
                                                    <option value="">Select Category</option>
                                                    <?php
                                                        $select_categories = $conn->prepare("SELECT * FROM `category`");
                                                        $select_categories->execute();
                                                        while($category = $select_categories->fetch(PDO::FETCH_ASSOC)){
                                                            echo "<option value='".$category['category_id']."'>".$category['name']."</option>";
                                                        }
                                                    ?>
                                                </select>
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
                                                       class="form-control" required>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">Extra Image 1</label>
                                                <input type="file" name="image_02" accept="image/jpg, image/jpeg, image/png, image/webp" 
                                                       class="form-control">
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">Extra Image 2</label>
                                                <input type="file" name="image_03" accept="image/jpg, image/jpeg, image/png, image/webp" 
                                                       class="form-control">
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
                                        <button type="reset" class="btn btn-light">
                                            <i class="fas fa-redo me-2"></i>Reset
                                        </button>
                                        <button type="submit" name="add_product" class="btn btn-primary">
                                            <i class="fas fa-plus me-2"></i>Add Product
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
    font-size: 0.9rem;
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
.btn {
    padding: 0.75rem 1.5rem;
    font-weight: 500;
    border-radius: 8px;
}

.btn-primary {
    background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
    border: none;
}

.btn-light {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
}

/* File Input */
input[type="file"] {
    padding: 0.5rem;
    font-size: 0.85rem;
}

/* Image Note */
.image-note {
    border: 1px dashed #dee2e6;
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
            const img = this.parentElement.querySelector('img');
            if (img) {
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
</script>

</body>
</html>