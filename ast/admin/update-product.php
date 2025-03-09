<?php

include '../components/connect.php';

session_start();

$admin_id = $_SESSION['admin_id'];

if(!isset($admin_id)){
   header('location:index.php');
}

if(isset($_POST['update'])){

   $pid = $_POST['pid'];
   $name = $_POST['name'];
   $name = filter_var($name, FILTER_SANITIZE_STRING);
   $price = $_POST['price'];
   $price = filter_var($price, FILTER_SANITIZE_STRING);
   $details = $_POST['details'];
   $details = filter_var($details, FILTER_SANITIZE_STRING);

   $update_product = $conn->prepare("UPDATE products SET name = ?, price = ?, details = ? WHERE id = ?");
   $update_product->execute([$name, $price, $details, $pid]);

   $message[] = 'product updated successfully!';

   $old_image_01 = $_POST['old_image_01'];
   $image_01 = $_FILES['image_01']['name'];
   $image_01 = filter_var($image_01, FILTER_SANITIZE_STRING);
   $image_size_01 = $_FILES['image_01']['size'];
   $image_tmp_name_01 = $_FILES['image_01']['tmp_name'];
   $image_folder_01 = '../uploaded_img/'.$image_01;

   if(!empty($image_01)){
      if($image_size_01 > 2000000){
         $message[] = 'image size is too large!';
      }else{
         $update_image_01 = $conn->prepare("UPDATE products SET image_01 = ? WHERE id = ?");
         $update_image_01->execute([$image_01, $pid]);
         move_uploaded_file($image_tmp_name_01, $image_folder_01);
         unlink('../uploaded_img/'.$old_image_01);
         $message[] = 'image 01 updated successfully!';
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
         $message[] = 'image size is too large!';
      }else{
         $update_image_02 = $conn->prepare("UPDATE products SET image_02 = ? WHERE id = ?");
         $update_image_02->execute([$image_02, $pid]);
         move_uploaded_file($image_tmp_name_02, $image_folder_02);
         unlink('../uploaded_img/'.$old_image_02);
         $message[] = 'image 02 updated successfully!';
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
         $message[] = 'image size is too large!';
      }else{
         $update_image_03 = $conn->prepare("UPDATE products SET image_03 = ? WHERE id = ?");
         $update_image_03->execute([$image_03, $pid]);
         move_uploaded_file($image_tmp_name_03, $image_folder_03);
         unlink('../uploaded_img/'.$old_image_03);
         $message[] = 'image 03 updated successfully!';
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
   <title>Keens | Update Product</title>

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</head>
<body>

<?php include '../components/admin_header.php'; ?>

<?php
   // Check if the update request is made
   if (isset($_GET['update'])) {
      $update_id = $_GET['update'];
      // Fetch product details from the database
      $select_products = $conn->prepare("SELECT * FROM products WHERE id = ?");
      $select_products->execute([$update_id]);

      if ($select_products->rowCount() > 0) {
         $fetch_products = $select_products->fetch(PDO::FETCH_ASSOC);
         
         // Handle form submission for updating product
         if (isset($_POST['add_product'])) {
            $name = $_POST['name'];
            $price = $_POST['price'];
            $details = $_POST['details'];

            // Handle image uploads
            $image_01 = $_FILES['image_01']['name'];
            $image_02 = $_FILES['image_02']['name'];
            $image_03 = $_FILES['image_03']['name'];

            $upload_dir = "../uploaded_img/";

            // For each image, check if a new file is uploaded, if so, move it to the upload directory
            if ($image_01) {
                move_uploaded_file($_FILES['image_01']['tmp_name'], $upload_dir . $image_01);
            } else {
                $image_01 = $fetch_products['image_01'];
            }

            if ($image_02) {
                move_uploaded_file($_FILES['image_02']['tmp_name'], $upload_dir . $image_02);
            } else {
                $image_02 = $fetch_products['image_02'];
            }

            if ($image_03) {
                move_uploaded_file($_FILES['image_03']['tmp_name'], $upload_dir . $image_03);
            } else {
                $image_03 = $fetch_products['image_03'];
            }

            // Update the product in the database
            $update_product = $conn->prepare("UPDATE products SET name = ?, price = ?, details = ?, image_01 = ?, image_02 = ?, image_03 = ? WHERE id = ?");
            $update_product->execute([$name, $price, $details, $image_01, $image_02, $image_03, $update_id]);

            // Check if the product was successfully updated
            if ($update_product) {
               echo "<p class='success'>Product updated successfully!</p>";
            } else {
               echo "<p class='error'>Failed to update product. Please try again.</p>";
            }
         }
      } else {
         echo '<p class="empty">No product found!</p>';
      }
   }
?>

<!-- Update Product Section -->
<!-- Update Product Section -->
<section class="update-products">
    <div class="content-card">
        <div class="card-header">
            <div class="header-icon">
                <i class="fas fa-edit"></i>
            </div>
            <h2 class="header-title">Update Product</h2>
            <p class="text-muted">Make changes to your product details</p>
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
                               value="<?= $fetch_products['name']; ?>"
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
                               value="<?= $fetch_products['price']; ?>"
                               required>
                        <label for="price">Product Price ($)</label>
                    </div>
                </div>
            </div>

            <!-- Image Upload Section -->
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="upload-card">
                        <div class="upload-preview">
                            <img src="../uploaded_img/<?= $fetch_products['image_01']; ?>" alt="" class="preview-image">
                        </div>
                        <div class="upload-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <p class="upload-label">Update Main Image</p>
                        <input type="file" 
                               id="image_01" 
                               name="image_01" 
                               class="form-control" 
                               accept="image/jpg, image/jpeg, image/png, image/webp">
                        <input type="hidden" name="old_image_01" value="<?= $fetch_products['image_01']; ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="upload-card">
                        <div class="upload-preview">
                            <img src="../uploaded_img/<?= $fetch_products['image_02']; ?>" alt="" class="preview-image">
                        </div>
                        <div class="upload-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <p class="upload-label">Update Image 2</p>
                        <input type="file" 
                               id="image_02" 
                               name="image_02" 
                               class="form-control" 
                               accept="image/jpg, image/jpeg, image/png, image/webp">
                        <input type="hidden" name="old_image_02" value="<?= $fetch_products['image_02']; ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="upload-card">
                        <div class="upload-preview">
                            <img src="../uploaded_img/<?= $fetch_products['image_03']; ?>" alt="" class="preview-image">
                        </div>
                        <div class="upload-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <p class="upload-label">Update Image 3</p>
                        <input type="file" 
                               id="image_03" 
                               name="image_03" 
                               class="form-control" 
                               accept="image/jpg, image/jpeg, image/png, image/webp">
                        <input type="hidden" name="old_image_03" value="<?= $fetch_products['image_03']; ?>">
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
                              style="height: 120px"><?= $fetch_products['details']; ?></textarea>
                    <label for="details">Product Description</label>
                </div>
            </div>

            <input type="hidden" name="pid" value="<?= $fetch_products['id']; ?>">

            <!-- Submit Button -->
            <button type="submit" name="update" class="btn custom-button w-100">
                <i class="fas fa-edit me-2"></i>Update Product
            </button>
        </form>
    </div>
</section>

<?php
// Ensure $update_id is set and valid
if (!isset($update_id) || empty($update_id)) {
    echo "<p class='alert alert-danger'>Invalid product ID</p>";
    exit;
}

// Initialize reviews array and stats
$reviews = [];
$total_reviews = 0;
$average_rating = 0;

try {
    // Fetch reviews for this product with user details
    $select_reviews = $conn->prepare("
        SELECT r.*, u.name as user_name 
        FROM reviews r 
        JOIN users u ON r.user_id = u.id 
        WHERE r.product_id = ? 
        ORDER BY r.created_at DESC
    ");
    $select_reviews->execute([$update_id]);
    
    // Fetch all reviews, or use an empty array if no reviews exist
    $reviews = $select_reviews->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // Calculate review statistics
    $review_stats = $conn->prepare("
        SELECT 
            COUNT(*) as total_reviews, 
            AVG(rating) as average_rating 
        FROM reviews 
        WHERE product_id = ?
    ");
    $review_stats->execute([$update_id]);
    $stats = $review_stats->fetch(PDO::FETCH_ASSOC);
    
    $total_reviews = $stats['total_reviews'] ?? 0;
    $average_rating = round($stats['average_rating'] ?? 0, 1);

} catch (PDOException $e) {
    // Log the error 
    error_log("Error fetching reviews: " . $e->getMessage());
    // You might want to display a user-friendly error message
    echo "<p class='alert alert-danger'>Unable to load reviews. Please try again later.</p>";
}
?>

<section class="review-management mt-5">
    <div class="review-container">
        <div class="">
        <div class="card-header">
            <div class="header-icon">
                <i class="fas fa-comments"></i>
            </div>
            <h2 class="header-title">Product Reviews</h2>
            <p class="text-muted">Manage and monitor customer feedback</p>
        </div>
            <div class="review-summary">
                <div class="review-stats justify-center">
                    <div class="stat-item">
                        <span class="stat-number"><?= htmlspecialchars($total_reviews) ?></span>
                        <span class="stat-label">Total Reviews</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?= htmlspecialchars($average_rating) ?></span>
                        <span class="stat-label">Average Rating</span>
                    </div>
                </div>
            </div>
        </div>
        <p><br></p>

        <div class="review-actions">
            <div class="action-buttons">
                <button type="button" class="btn btn-danger delete-selected w-100-mobile">
                    <i class="fas fa-trash me-2"></i>Delete Selected
                </button>
                <div class="filter-controls">
                    <select class="form-select rating-filter w-100-mobile">
                        <option value="">All Ratings</option>
                        <option value="5">5 Stars</option>
                        <option value="4">4 Stars</option>
                        <option value="3">3 Stars</option>
                        <option value="2">2 Stars</option>
                        <option value="1">1 Star</option>
                    </select>
                    <div class="search-container w-100-mobile">
                        <input type="text" class="form-control review-search" placeholder="Search">
                        <i class="fas fa-search search-icon"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="review-list">
            <?php if (!empty($reviews)): ?>
                <?php foreach($reviews as $review): ?>
                <div class="review-card" data-rating="<?= htmlspecialchars($review['rating']) ?>">
                    <div class="review-card-header">
                        <div class="review-user-info">
                            <div class="user-avatar">
                                <?= htmlspecialchars(strtoupper(substr($review['user_name'], 0, 1))) ?>
                            </div>
                            <div class="user-details">
                                <h5 class="user-name"><?= htmlspecialchars($review['user_name']) ?></h5>
                                <div class="review-rating">
                                    <?php 
                                    for($i = 1; $i <= 5; $i++){
                                        echo $i <= $review['rating'] 
                                            ? '<i class="fas fa-star text-warning"></i>' 
                                            : '<i class="far fa-star text-muted"></i>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                        <div class="review-actions-inline">
                            <input type="checkbox" name="selected_reviews[]" 
                                   value="<?= htmlspecialchars($review['id']) ?>" class="review-checkbox">
                        </div>
                    </div>
                    <div class="review-content">
                        <p><?= htmlspecialchars($review['review_text']) ?></p>
                    </div>
                    <div class="review-footer">
                        <span class="review-date">
                            <i class="far fa-clock me-2"></i>
                            <?= htmlspecialchars(date('M d, Y', strtotime($review['created_at']))) ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-reviews-placeholder">
                    <i class="fas fa-comment-slash"></i>
                    <p>No reviews for this product yet</p>
                </div>
            <?php endif; ?>
        </div>

        <?php if(count($reviews) > 10): ?>
        <div class="pagination-container">
            <nav aria-label="Review pagination">
                <ul class="pagination justify-content-center">
                    <li class="page-item disabled">
                        <a class="page-link" href="#" tabindex="-1">Previous</a>
                    </li>
                    <li class="page-item active"><a class="page-link" href="#">1</a></li>
                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                    <li class="page-item">
                        <a class="page-link" href="#">Next</a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</section>

<style>
:root {
    --primary-color: #4361EE;
    --secondary-color: #4895EF;
    --light-bg: #F8F9FA;
    --text-muted: #6C757D;
}

.review-container {
    background: white;
    border-radius: 16px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
    padding: 2rem;
}

.review-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    border-bottom: 1px solid var(--light-bg);
    padding-bottom: 1.5rem;
}

.review-header-content {
    display: flex;
    align-items: center;
}

.review-icon-wrapper {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, rgba(67, 97, 238, 0.1), rgba(72, 149, 239, 0.1));
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
}

.review-icon-wrapper i {
    color: var(--primary-color);
    font-size: 1.5rem;
}

.review-header-text h2 {
    margin-bottom: 0.25rem;
}

.review-summary {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 100%;
}

.review-stats.justify-center {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 2rem;
    text-align: center;
}

.stat-item {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.stat-number {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--primary-color);
}

.stat-label {
    color: var(--text-muted);
    font-size: 0.875rem;
}

.review-actions {
    margin-bottom: 1.5rem;
}

.action-buttons {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.filter-controls {
    display: flex;
    gap: 1rem;
}

.search-container {
    position: relative;
}

.search-icon {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-muted);
}

.review-list {
    display: grid;
    gap: 1rem;
}

.review-card {
    background: var(--light-bg);
    border-radius: 12px;
    padding: 1.25rem;
    transition: all 0.3s ease;
}

.review-card:hover {
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    transform: translateY(-2px);
}

.review-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.review-user-info {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.user-avatar {
    width: 40px;
    height: 40px;
    background: var(--primary-color);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

.user-name {
    margin-bottom: 0.25rem;
}

.review-rating i {
    margin-right: 2px;
}

.review-content {
    margin-bottom: 1rem;
}

.review-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: var(--text-muted);
    font-size: 0.875rem;
}

.no-reviews-placeholder {
    text-align: center;
    padding: 3rem;
    color: var(--text-muted);
}

.no-reviews-placeholder i {
    font-size: 3rem;
    margin-bottom: 1rem;
    color: var(--primary-color);
}

.pagination-container {
    margin-top: 2rem;
}

@media (max-width: 768px) {
    .review-header {
        flex-direction: column;
        text-align: center;
    }

    .review-stats {
        margin-top: 1rem;
        gap: 1rem;
    }

    .action-buttons {
        flex-direction: column;
        gap: 1rem;
    }

    .filter-controls {
        flex-direction: column;
        width: 100%;
    }
}

/* Card Styles */
.content-card {
    background: white;
    border-radius: 20px;
    padding: 2rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
    margin: 2rem auto;
    max-width: 1200px;
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
    position: relative;
}

.upload-card:hover {
    border-color: var(--primary-color);
    background: rgba(67, 97, 238, 0.02);
}

.upload-preview {
    margin: -0.5rem -0.5rem 1rem;
    border-radius: 8px;
    overflow: hidden;
    background: #f8f9fa;
    height: 150px;
}

.preview-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.upload-preview:hover .preview-image {
    transform: scale(1.05);
}

.upload-icon {
    font-size: 1.5rem;
    color: var(--primary-color);
    margin: 1rem 0;
}

.upload-label {
    color: #6c757d;
    margin-bottom: 1rem;
    font-weight: 500;
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

/* Form Floating Labels */
.form-floating > label {
    padding: 1rem;
}

.form-floating > .form-control {
    padding: 1rem;
    height: calc(3.5rem + 2px);
}

.form-floating > .form-control:focus ~ label,
.form-floating > .form-control:not(:placeholder-shown) ~ label {
    transform: scale(.85) translateY(-1rem) translateX(.15rem);
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .content-card {
        padding: 1.5rem;
        margin: 1rem;
    }

    .upload-preview {
        height: 120px;
    }
}

</style>

<script>
// Form validation
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Select all checkbox functionality
    const selectAllCheckbox = document.getElementById('select-all');
    const reviewCheckboxes = document.querySelectorAll('.review-checkbox');

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            reviewCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    }

    // Rating filter
    const ratingFilter = document.querySelector('.rating-filter');
    const reviewCards = document.querySelectorAll('.review-card');

    ratingFilter.addEventListener('change', function() {
        const selectedRating = this.value;

        reviewCards.forEach(card => {
            const cardRating = card.dataset.rating;
            
            if (selectedRating === '' || cardRating === selectedRating) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    });

    // Search functionality
    const searchInput = document.querySelector('.review-search');
    
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();

        reviewCards.forEach(card => {
            const userName = card.querySelector('.user-name').textContent.toLowerCase();
            const reviewText = card.querySelector('.review-content p').textContent.toLowerCase();

            if (userName.includes(searchTerm) || reviewText.includes(searchTerm)) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const deleteSelectedButton = document.querySelector('.delete-selected');
    const reviewCheckboxes = document.querySelectorAll('.review-checkbox');
    const reviewList = document.querySelector('.review-list');
    const totalReviewsEl = document.querySelector('.stat-item:first-child .stat-number');
    const averageRatingEl = document.querySelector('.stat-item:last-child .stat-number');

    if (deleteSelectedButton) {
        deleteSelectedButton.addEventListener('click', function() {
            // Collect selected review IDs
            const selectedReviews = Array.from(reviewCheckboxes)
                .filter(checkbox => checkbox.checked)
                .map(checkbox => checkbox.value);

            // Check if any reviews are selected
            if (selectedReviews.length === 0) {
                alert('Please select reviews to delete.');
                return;
            }

            // Confirm deletion
            if (confirm(`Are you sure you want to delete ${selectedReviews.length} review(s)?`)) {
                // Prepare form data
                const formData = new FormData();
                formData.append('delete_reviews', 'true');
                formData.append('product_id', '<?= $update_id ?>');
                selectedReviews.forEach(reviewId => {
                    formData.append('selected_reviews[]', reviewId);
                });

                // Send AJAX request
                fetch('delete-reviews.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove deleted reviews from DOM
                        selectedReviews.forEach(reviewId => {
                            const reviewCard = document.querySelector(
                                `.review-card input[value="${reviewId}"]`
                            ).closest('.review-card');
                            if (reviewCard) {
                                reviewCard.remove();
                            }
                        });

                        // Update review statistics
                        if (totalReviewsEl) {
                            const currentTotal = parseInt(totalReviewsEl.textContent);
                            totalReviewsEl.textContent = currentTotal - selectedReviews.length;
                        }

                        // Update average rating if needed (optional)
                        alert('Reviews deleted successfully!');

                        // Refresh page or update UI as needed
                        if (reviewList.children.length === 0) {
                            reviewList.innerHTML = `
                                <div class="no-reviews-placeholder">
                                    <i class="fas fa-comment-slash"></i>
                                    <p>No reviews for this product yet</p>
                                </div>
                            `;
                        }
                    } else {
                        alert(data.message || 'Failed to delete reviews.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting reviews.');
                });
            }
        });
    }
});
</script>
   
</body>
</html>