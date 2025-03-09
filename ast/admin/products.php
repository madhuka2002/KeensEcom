<?php

include '../components/connect.php';

session_start();

$admin_id = $_SESSION['admin_id'];

if(!isset($admin_id)){
   header('location:index.php');
};

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
      // $message[] = 'product name already exist!';
   }else{

    $category_id = $_POST['category_id'];
    $insert_products = $conn->prepare("INSERT INTO `products`(name, details, price, image_01, image_02, image_03, category_id) VALUES(?,?,?,?,?,?,?)");
    $insert_products->execute([$name, $details, $price, $image_01, $image_02, $image_03, $category_id]);

      if($insert_products){
         if($image_size_01 > 2000000 OR $image_size_02 > 2000000 OR $image_size_03 > 2000000){
            // $message[] = 'image size is too large!';
         }else{
            move_uploaded_file($image_tmp_name_01, $image_folder_01);
            move_uploaded_file($image_tmp_name_02, $image_folder_02);
            move_uploaded_file($image_tmp_name_03, $image_folder_03);
            // $message[] = 'new product added!';
         }

      }

   }  

};

if(isset($_GET['delete'])){

   $delete_id = $_GET['delete'];
   $delete_product_image = $conn->prepare("SELECT * FROM `products` WHERE id = ?");
   $delete_product_image->execute([$delete_id]);
   $fetch_delete_image = $delete_product_image->fetch(PDO::FETCH_ASSOC);
   unlink('../uploaded_img/'.$fetch_delete_image['image_01']);
   unlink('../uploaded_img/'.$fetch_delete_image['image_02']);
   unlink('../uploaded_img/'.$fetch_delete_image['image_03']);
   $delete_product = $conn->prepare("DELETE FROM `products` WHERE id = ?");
   $delete_product->execute([$delete_id]);
   $delete_cart = $conn->prepare("DELETE FROM `cart` WHERE pid = ?");
   $delete_cart->execute([$delete_id]);
   $delete_wishlist = $conn->prepare("DELETE FROM `wishlist` WHERE pid = ?");
   $delete_wishlist->execute([$delete_id]);
   header('location:products.php');
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Keens | Products</title>

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4895ef;
            --success-color: #2ecc71;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
        }

        .content-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        }

        .card-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .header-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            background: linear-gradient(135deg, rgba(67, 97, 238, 0.1), rgba(72, 149, 239, 0.1));
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin: 0 auto 1rem;
        }

        .header-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2b3452;
            margin-bottom: 0.5rem;
        }

        /* Form Styles */
        .custom-input {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 1rem;
            transition: all 0.3s ease;
        }

        .custom-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(67, 97, 238, 0.1);
        }

        .upload-card {
            border: 2px dashed #e9ecef;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
        }

        .upload-card:hover {
            border-color: var(--primary-color);
            background: rgba(67, 97, 238, 0.02);
        }

        .upload-icon {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .upload-label {
            color: #6c757d;
            margin-bottom: 0.5rem;
        }

        .custom-button {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .custom-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(67, 97, 238, 0.2);
        }

        /* Product Card Styles */
        .product-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease;
            height: 100%;
        }

        .product-card:hover {
            transform: translateY(-5px);
        }

        .product-image {
            width: 100%;
            height: 180px;
            border-radius: 12px 12px 0 0;
            overflow: hidden;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .product-card:hover .product-image img {
            transform: scale(1.05);
        }

        .product-details {
            padding: 1rem;
        }

        .product-title {
            font-size: 0.95rem;
            font-weight: 600;
            color: #2b3452;
            margin-bottom: 0.75rem;
            display: -webkit-box;
            -webkit-line-clamp: 1;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.5rem;
        }

        .price-tag {
            background: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn-action {
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .btn-action.edit {
            background: #fff8ee;
            color: #ff9800;
        }

        .btn-action.delete {
            background: #ffeeee;
            color: #dc3545;
        }

        .btn-action:hover {
            opacity: 0.9;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            background: #f8f9fa;
            border-radius: 12px;
        }

        @media (max-width: 768px) {
            .content-card {
                padding: 1rem;
            }
            
            .product-image {
                height: 160px;
            }

            .product-details {
                padding: 0.75rem;
            }

            .btn-action {
                padding: 0.35rem 0.6rem;
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<!-- Add Product Section -->
<section class="add-products">
    <div class="content-card">
        <div class="card-header">
            <div class="header-icon">
                <i class="fas fa-plus-circle"></i>
            </div>
            <h2 class="header-title">Add New Product</h2>
            <p class="text-muted">Fill in the details to add a new product to your store</p>
        </div>

        <form action="" method="post" enctype="multipart/form-data" class="needs-validation">
            <!-- Product Info -->
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="form-floating">
                        <input type="text" 
                               id="name" 
                               name="name" 
                               class="form-control custom-input" 
                               placeholder="Product name"
                               required>
                        <label for="name">Product Name</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating">
                        <input type="number" 
                               id="price" 
                               name="price" 
                               class="form-control custom-input" 
                               placeholder="Price"
                               required>
                        <label for="price">Product Price ($)</label>
                    </div>
                </div>
            </div>

            <!-- Select Category -->
            <div class="col-md-6">
                <div class="form-floating mb-4">
                    <select name="category_id" id="category" class="form-control custom-input" required>
                        <option value="">Select Category</option>
                        <?php
                            $select_categories = $conn->prepare("SELECT * FROM `category`");
                            $select_categories->execute();
                            while($category = $select_categories->fetch(PDO::FETCH_ASSOC)){
                                echo "<option value='".$category['category_id']."'>".$category['name']."</option>";
                            }
                        ?>
                    </select>
                    <label for="category">Product Category</label>
                </div>
            </div>

            <!-- Image Upload Section -->
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="upload-card">
                        <div class="upload-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <p class="upload-label">Main Image</p>
                        <input type="file" 
                               id="image_01" 
                               name="image_01" 
                               class="form-control" 
                               accept="image/jpg, image/jpeg, image/png, image/webp" 
                               required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="upload-card">
                        <div class="upload-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <p class="upload-label">Image 2</p>
                        <input type="file" 
                               id="image_02" 
                               name="image_02" 
                               class="form-control" 
                               accept="image/jpg, image/jpeg, image/png, image/webp" 
                               required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="upload-card">
                        <div class="upload-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <p class="upload-label">Image 3</p>
                        <input type="file" 
                               id="image_03" 
                               name="image_03" 
                               class="form-control" 
                               accept="image/jpg, image/jpeg, image/png, image/webp" 
                               required>
                    </div>
                </div>
            </div>

            <!-- Description -->
            <div class="mb-4">
                <div class="form-floating">
                    <textarea id="details" 
                              name="details" 
                              class="form-control custom-input" 
                              placeholder="Product description"
                              required 
                              maxlength="500" 
                              style="height: 120px"></textarea>
                    <label for="details">Product Description</label>
                </div>
            </div>

            <!-- Submit Button -->
            <button type="submit" name="add_product" class="btn custom-button w-100">
                <i class="fas fa-plus-circle me-2"></i>Add Product
            </button>
        </form>
    </div>
</section>

<!-- Products List Section -->
<section class="show-products mt-4">
    <div class="content-card">
        <div class="card-header">
            <div class="header-icon">
                <i class="fas fa-box"></i>
            </div>
            <h2 class="header-title">Product List</h2>
            <p class="text-muted">Manage your store's products</p>
        </div>

        <div class="row row-cols-2 row-cols-sm-2 row-cols-lg-4 g-3">
        <?php
$select_products = $conn->prepare("SELECT * FROM `products`");
$select_products->execute();
if($select_products->rowCount() > 0){
    while($fetch_products = $select_products->fetch(PDO::FETCH_ASSOC)){ 
?>
<div class="col">
    <div class="product-card">
        <div class="product-image">
            <img src="../uploaded_img/<?= $fetch_products['image_01']; ?>" alt="<?= $fetch_products['name']; ?>">
        </div>
        <div class="product-details">
            <h3 class="product-title"><?= $fetch_products['name']; ?></h3>
            <div class="product-info-row">
                <span class="price-tag">$<?= number_format((float)$fetch_products['price'], 2, '.', ''); ?></span>
                <div class="action-buttons">
                    <a href="update-product.php?update=<?= $fetch_products['id']; ?>" class="btn-action edit">
                        <i class="fas fa-edit"></i>
                    </a>
                    <a href="products.php?delete=<?= $fetch_products['id']; ?>" 
                       onclick="return confirm('Delete this product?');" 
                       class="btn-action delete">
                        <i class="fas fa-trash-alt"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
            <?php
                }
            } else {
            ?>
            <div class="col-12">
                <div class="empty-state">
                    <div class="empty-state-content">
                        <i class="fas fa-box empty-state-icon mb-3"></i>
                        <p class="mb-0">No products added yet!</p>
                    </div>
                </div>
            </div>
            <?php
            }
            ?>
        </div>
    </div>
</section>




<script>
// Bootstrap 5 form validation
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms)
        .forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                form.classList.add('was-validated')
            }, false)
        })
})()
</script>



   
</body>
</html>