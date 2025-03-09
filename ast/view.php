<?php
include 'components/connect.php';

session_start();

if(isset($_SESSION['user_id'])){
   $user_id = $_SESSION['user_id'];
}else{
   $user_id = '';
};

include 'components/wishlist_cart.php';

function getUserReview($conn, $pid, $user_id) {
   $review_query = $conn->prepare("SELECT r.*, u.name FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.product_id = ? AND r.user_id = ?");
   $review_query->execute([$pid, $user_id]);
   return $review_query->fetch(PDO::FETCH_ASSOC);
}


// Handle review deletion
if(isset($_POST['delete_review'])){
   if($user_id == ''){
      $message[] = 'Please login first';
   } else {
      $review_id = $_POST['review_id'];
      $delete_review = $conn->prepare("DELETE FROM reviews WHERE id = ? AND user_id = ?");
      $delete_review->execute([$review_id, $user_id]);
      if($delete_review->rowCount() > 0){
         $message[] = 'Review deleted successfully!';
      }
   }
}



// Handle review submission/update
if(isset($_POST['submit_review'])){
   if($user_id == ''){
      $message[] = 'Please login to submit a review';
   } else {
      $pid = $_GET['pid'];
      $rating = $_POST['rating'];
      $review_text = $_POST['review_text'];

      // Check for existing review
      $check_review = $conn->prepare("SELECT * FROM reviews WHERE product_id = ? AND user_id = ?");
      $check_review->execute([$pid, $user_id]);
      
      // Validate inputs
      if(empty($rating) || empty($review_text)){
         $message[] = 'Please provide both a rating and review text';
      } else {
         if($check_review->rowCount() > 0){
            // Update existing review
            $update_review = $conn->prepare("UPDATE reviews SET rating = ?, review_text = ?, updated_at = CURRENT_TIMESTAMP WHERE product_id = ? AND user_id = ?");
            $update_review->execute([$rating, $review_text, $pid, $user_id]);
            
            if($update_review){
               $message[] = 'Review updated successfully!';
            } else {
               $message[] = 'Failed to update review';
            }
         } else {
            // Insert new review
            $insert_review = $conn->prepare("INSERT INTO reviews (product_id, user_id, rating, review_text) VALUES (?, ?, ?, ?)");
            $insert_review->execute([$pid, $user_id, $rating, $review_text]);
            
            if($insert_review){
               $message[] = 'Review submitted successfully!';
            } else {
               $message[] = 'Failed to submit review';
            }
         }
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
   <title>Keens | Quick View</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <link rel="icon" type="image/x-icon" href="favicon.png">
   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <style>
      /* General Styles */
      body {
         background-color: #f8f9fa;
      }

      .section-header {
         margin-bottom: 2rem;
         text-align: center;
      }

      .badge-soft {
         background-color: rgba(13,110,253,0.1);
         color: #0d6efd;
         font-size: 0.9rem;
         padding: 0.5rem 1rem;
      }

      /* Card Styles */
      .custom-card {
         background: white;
         border: none;
         border-radius: 1rem;
         box-shadow: 0 10px 30px rgba(0,0,0,0.05);
         transition: all 0.3s ease;
         padding: 2rem;
      }

      .custom-card:hover {
         transform: translateY(-5px);
         box-shadow: 0 15px 40px rgba(0,0,0,0.1);
      }

      /* Image Gallery */
      .main-image {
         margin-bottom: 1.5rem;
         border-radius: 1rem;
         overflow: hidden;
         box-shadow: 0 5px 15px rgba(0,0,0,0.08);
      }

      .main-image img {
         width: 100%;
         height: auto;
         transition: transform 0.3s ease;
      }

      .main-image:hover img {
         transform: scale(1.02);
      }

      .sub-images {
         display: flex;
         gap: 1rem;
         justify-content: center;
      }

      .sub-image-wrapper {
         width: 80px;
         height: 80px;
         border-radius: 0.75rem;
         overflow: hidden;
         cursor: pointer;
         border: 2px solid #dee2e6;
         transition: all 0.3s ease;
      }

      .sub-image-wrapper img {
         width: 100%;
         height: 100%;
         object-fit: cover;
      }

      .sub-image-wrapper:hover {
         transform: translateY(-3px);
         border-color: #0d6efd;
         box-shadow: 0 5px 15px rgba(0,0,0,0.1);
      }

      /* Product Details */
      .product-title {
         color: #2b3452;
         font-weight: bold;
         margin-bottom: 1.5rem;
         font-size: 2.5rem;
      }

      .product-info-section {
         padding: 2rem;
         background: rgba(13,110,253,0.02);
         border-radius: 1rem;
         margin-bottom: 2rem;
      }

      .price-badge {
         background-color: rgba(13,110,253,0.1);
         color: #0d6efd;
         padding: 1rem 1.5rem;
         border-radius: 1rem;
         display: inline-flex;
         align-items: center;
         margin-bottom: 1.5rem;
         transition: all 0.3s ease;
      }

      .price-badge:hover {
         transform: translateY(-2px);
         box-shadow: 0 5px 15px rgba(13,110,253,0.2);
      }

      .product-meta {
         display: grid;
         grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
         gap: 1.5rem;
         margin-bottom: 2rem;
      }

      .meta-item {
         display: flex;
         align-items: center;
         gap: 1rem;
         padding: 1rem;
         background: white;
         border-radius: 0.75rem;
         box-shadow: 0 2px 8px rgba(0,0,0,0.05);
      }

      .meta-icon {
         width: 40px;
         height: 40px;
         display: flex;
         align-items: center;
         justify-content: center;
         background: rgba(13,110,253,0.1);
         color: #0d6efd;
         border-radius: 0.5rem;
         font-size: 1.2rem;
      }

      /* Enhanced Review Actions */
      .review-actions {
         position: absolute;
         top: 1rem;
         right: 1rem;
         display: flex;
         gap: 0.5rem;
         opacity: 0;
         transition: all 0.3s ease;
      }

      .review-item:hover .review-actions {
         opacity: 1;
      }

      .action-btn {
         width: 35px;
         height: 35px;
         display: flex;
         align-items: center;
         justify-content: center;
         background: white;
         border-radius: 50%;
         box-shadow: 0 2px 8px rgba(0,0,0,0.1);
         transition: all 0.3s ease;
      }

      .action-btn:hover {
         transform: translateY(-2px);
      }

      .edit-btn { 
         color: #0d6efd;
         background: rgba(13,110,253,0.1);
      }
      
      .delete-btn { 
         color: #dc3545;
         background: rgba(220,53,69,0.1);
      }

      .quantity-input {
         width: 150px;
         border-radius: 12px;
         border: 1px solid #dee2e6;
         padding: 0.75rem;
      }

      .btn {
         padding: 0.75rem 1.5rem;
         border-radius: 50rem;
         transition: all 0.3s ease;
      }

      .btn:hover {
         transform: translateY(-3px);
         box-shadow: 0 5px 15px rgba(13,110,253,0.2);
      }

      /* Review Styles */
      .star-rating {
         display: flex;
         flex-direction: row-reverse;
         justify-content: center;
         gap: 0.25rem;
      }

      .star-rating input {
         display: none;
      }

      .star-rating label {
         font-size: 2rem;
         color: #ddd;
         cursor: pointer;
         transition: color 0.3s ease;
      }

      .star-rating input:checked ~ label,
      .star-rating input:hover ~ label {
         color: #ffc107;
      }

      .review-item {
         background: white;
         border-radius: 1rem;
         padding: 1.5rem;
         margin-bottom: 1.5rem;
         box-shadow: 0 5px 15px rgba(0,0,0,0.05);
         transition: all 0.3s ease;
      }

      .review-item:hover {
         transform: translateY(-3px);
         box-shadow: 0 10px 30px rgba(0,0,0,0.1);
      }

      .review-actions {
         position: absolute;
         top: 1rem;
         right: 1rem;
         display: flex;
         gap: 0.5rem;
      }

      .action-btn {
         background: none;
         border: none;
         padding: 0.5rem;
         cursor: pointer;
         transition: all 0.3s ease;
         border-radius: 50%;
      }

      .action-btn:hover {
         transform: scale(1.1);
         background-color: rgba(13,110,253,0.1);
      }

      .edit-btn { color: #0d6efd; }
      .delete-btn { color: #dc3545; }

      .user-avatar {
         width: 50px;
         height: 50px;
         background-color: rgba(13,110,253,0.1);
         color: #0d6efd;
         display: flex;
         align-items: center;
         justify-content: center;
         font-weight: bold;
         border-radius: 50%;
      }

      .form-control {
         border-radius: 12px;
         border: 1px solid #dee2e6;
         padding: 1rem;
      }

      .form-control:focus {
         border-color: #0d6efd40;
         box-shadow: 0 0 0 0.25rem rgba(13,110,253,.15);
      }

      /* Rating Display */
      .rating-stars {
         color: #ffc107;
         display: flex;
         gap: 0.25rem;
      }

      .rating-text {
         color: #6c757d;
         font-size: 0.9rem;
         margin-left: 0.5rem;
      }

      /* Responsive Adjustments */
      @media (max-width: 1199.98px) {
         .product-title {
            font-size: 2rem;
         }
         
         .product-meta {
            grid-template-columns: repeat(2, 1fr);
         }
      }

      @media (max-width: 991.98px) {
         .custom-card {
            padding: 1.5rem;
         }
         
         .product-info-section {
            padding: 1.5rem;
         }
         
         .price-badge {
            padding: 0.75rem 1rem;
         }
         
         .product-meta {
            gap: 1rem;
         }
      }

      @media (max-width: 768px) {
         .sub-image-wrapper {
            width: 60px;
            height: 60px;
         }
         
         .product-title {
            font-size: 1.75rem;
            margin-top: 1.5rem;
         }
         
         .product-meta {
            grid-template-columns: 1fr;
         }
         
         .review-item {
            padding: 1.25rem;
         }
         
         .review-actions {
            opacity: 1;
            position: relative;
            top: auto;
            right: auto;
            justify-content: flex-end;
            margin-top: 1rem;
         }
         
         .main-image {
            margin: -1.5rem -1.5rem 1.5rem -1.5rem;
            border-radius: 1rem 1rem 0 0;
         }
      }

      @media (max-width: 575.98px) {
         .product-info-section {
            padding: 1rem;
         }
         
         .d-flex.flex-wrap {
            flex-direction: column;
            align-items: flex-start !important;
         }
         
         .rating-display {
            margin-left: 0 !important;
            width: 100%;
         }
         
         .btn {
            width: 100%;
            margin-bottom: 0.5rem;
         }
         
         .buttons-container {
            flex-direction: column;
            width: 100%;
         }
      }
   </style>
</head>
<body>

<?php include 'components/header.php'; ?>

<section class="quick-view py-5" style="margin-top: -80px;">
   <div class="container">
      <!-- Section Header -->
      <div class="row justify-content-center mb-4">
         <div class="col-lg-8 text-center">
            <br><br>
            <h1 class="display-6 fw-bold" style="color: #2b3452;">
               <span class="badge rounded-pill mb-2" style="background-color: rgba(13,110,253,0.1); color: #0d6efd; font-size: 0.9rem;">
                  Product Information
               </span>
            </h1>
         </div>
      </div>


      <?php
         $pid = $_GET['pid'];
         $select_products = $conn->prepare("SELECT p.*, 
            (SELECT AVG(rating) FROM reviews WHERE product_id = p.id) as avg_rating,
            (SELECT COUNT(*) FROM reviews WHERE product_id = p.id) as review_count
            FROM `products` p WHERE p.id = ?"); 
         $select_products->execute([$pid]);
         if($select_products->rowCount() > 0){
            while($fetch_product = $select_products->fetch(PDO::FETCH_ASSOC)){
      ?>
      <form action="" method="post">
         <input type="hidden" name="pid" value="<?= $fetch_product['id']; ?>">
         <input type="hidden" name="name" value="<?= $fetch_product['name']; ?>">
         <input type="hidden" name="price" value="<?= $fetch_product['price']; ?>">
         <input type="hidden" name="image" value="<?= $fetch_product['image_01']; ?>">

         <div class="custom-card">
            <div class="row g-4">
               <!-- Image Section -->
               <div class="col-lg-6">
                  <div class="main-image">
                     <img src="uploaded_img/<?= $fetch_product['image_01']; ?>" alt="Product Image">
                  </div>
                  <div class="sub-images">
                     <?php foreach(['image_01', 'image_02', 'image_03'] as $image): ?>
                        <div class="sub-image-wrapper">
                           <img src="uploaded_img/<?= $fetch_product[$image]; ?>" alt="Product thumbnail">
                        </div>
                     <?php endforeach; ?>
                  </div>
               </div>

               <!-- Product Details -->
               <div class="col-lg-6">
                  <h2 class="product-title"><?= $fetch_product['name']; ?></h2>
                  
                  <div class="product-info-section">
                     <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
                        <div class="price-badge">
                           <i class="fas fa-tag me-2"></i>
                           <span class="h3 fw-bold mb-0">$<?= number_format($fetch_product['price'], 2); ?></span>
                        </div>
                        
                        <div class="rating-display ms-auto">
                           <div class="rating-stars">
                              <?php 
                              $avg_rating = round($fetch_product['avg_rating'] ?? 0);
                              for($i = 1; $i <= 5; $i++){
                                 echo $i <= $avg_rating 
                                    ? '<i class="fas fa-star"></i>' 
                                    : '<i class="far fa-star"></i>';
                              }
                              ?>
                              <span class="rating-text">
                                 (<?= number_format($fetch_product['review_count'] ?? 0) ?> reviews)
                              </span>
                           </div>
                        </div>
                     </div>

                     <div class="product-meta">
                        <div class="meta-item">
                           <div class="meta-icon">
                              <i class="fas fa-shipping-fast"></i>
                           </div>
                           <div>
                              <h6 class="mb-0 fw-bold">Free Shipping</h6>
                              <small class="text-muted">On orders over $100</small>
                           </div>
                        </div>
                        <div class="meta-item">
                           <div class="meta-icon">
                              <i class="fas fa-undo"></i>
                           </div>
                           <div>
                              <h6 class="mb-0 fw-bold">Easy Returns</h6>
                              <small class="text-muted">30-day return policy</small>
                           </div>
                        </div>
                     </div>

                  <div class="form-floating mb-4">
                     <input type="number" class="form-control quantity-input" 
                            id="quantity" name="qty" 
                            min="1" max="99" value="1" required>
                     <label for="quantity">Quantity</label>
                  </div>

                  <p class="lead mb-4" style="color: #6c757d;">
                     <?= $fetch_product['details']; ?>
                  </p>

                  <div class="d-flex gap-3">
                     <button type="submit" name="add_to_cart" class="btn btn-primary">
                        <i class="fas fa-shopping-cart me-2"></i>Add to Cart
                     </button>
                     <button type="submit" name="add_to_wishlist" class="btn btn-outline-primary">
                        <i class="fas fa-heart me-2"></i>Add to Wishlist
                     </button>
                  </div>
               </div>
            </div>
         </div>
      </form>
      <?php
            }
         }
      ?>

      <!-- Reviews Section -->
      <div class="reviews-section mt-5">
         <div class="row justify-content-center">
            <div class="col-lg-8">
               <div class="section-header">
                  <span class="badge rounded-pill badge-soft mb-3">Customer Reviews</span>
               </div>

               <?php if($user_id):
                  $user_review = getUserReview($conn, $pid, $user_id);
                  if($user_review):
               ?>
                  <!-- User's Own Review -->
                  <div class="review-item">
                     <div class="review-actions">
                        <button type="button" class="action-btn edit-btn" 
                                onclick="editReview(this, '<?= $user_review['id'] ?>')">
                           <i class="fas fa-pencil-alt"></i>
                        </button>
                        <form method="post" class="d-inline" 
                              onsubmit="return confirm('Delete this review?')">
                           <input type="hidden" name="review_id" value="<?= $user_review['id'] ?>">
                           <button type="submit" name="delete_review" class="action-btn delete-btn">
                              <i class="fas fa-trash-alt"></i>
                           </button>
                        </form>
                     </div>

                     <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="user-avatar">
                           <?= strtoupper(substr($user_review['name'], 0, 1)); ?>
                        </div>
                        <div>
                           <h5 class="mb-1 fw-bold" style="color: #2b3452;">
                              <?= htmlspecialchars($user_review['name']); ?>
                              <span class="badge rounded-pill mb-3" style="background-color: rgba(13,110,253,0.1); color: #0d6efd; font-size: 0.9rem;">
               Your Review
            </span>
                           </h5>
                           <div class="rating-stars">
                              <?php 
                              for($i = 1; $i <= 5; $i++){
                                 echo $i <= $user_review['rating'] 
                                    ? '<i class="fas fa-star"></i>' 
                                    : '<i class="far fa-star"></i>';
                              }
                              ?>
                           </div>
                        </div>
                     </div>

                     <div class="review-content" id="review-<?= $user_review['id'] ?>">
                        <p class="lead mb-0" style="color: #6c757d;">
                           <?= htmlspecialchars($user_review['review_text']); ?>
                        </p>
                     </div>

                     <!-- Hidden Edit Form -->
                     <div class="edit-form mt-4" style="display: none;">
                        <form method="post" action="">
                           <div class="mb-3">
                              <label class="form-label text-center w-100">
                                 <span class="badge rounded-pill badge-soft">Your Rating</span>
                              </label>
                              <div class="star-rating">
                                 <?php for($i = 5; $i >= 1; $i--): ?>
                                    <input type="radio" id="edit-star<?= $i ?>" 
                                          name="rating" value="<?= $i ?>" 
                                          <?= ($user_review['rating'] == $i) ? 'checked' : '' ?>>
                                    <label for="edit-star<?= $i ?>" title="<?= $i ?> stars">&#9733;</label>
                                 <?php endfor; ?>
                              </div>
                           </div>
                           
                           <div class="form-floating mb-4">
                              <textarea class="form-control" 
                                 name="review_text" 
                                 id="edit-review-text"
                                 placeholder="Share your experience"
                                 style="height: 120px"
                                 required><?= htmlspecialchars($user_review['review_text']); ?></textarea>
                              <label for="edit-review-text">Share your experience</label>
                           </div>

                           <div class="d-flex gap-3">
                              <button type="submit" name="submit_review" 
                                      class="btn btn-primary">
                                 <i class="fas fa-check me-2"></i>Update Review
                              </button>
                              <button type="button" class="btn btn-light" 
                                      onclick="cancelEdit(this)">
                                 <i class="fas fa-times me-2"></i>Cancel
                              </button>
                           </div>
                        </form>
                     </div>
                  </div>
               <?php endif; endif; ?>

               <!-- Other Reviews -->
               <?php
               $select_reviews = $conn->prepare("
                  SELECT r.*, u.name 
                  FROM reviews r 
                  JOIN users u ON r.user_id = u.id 
                  WHERE r.product_id = ? AND r.user_id != ?
                  ORDER BY r.created_at DESC
               ");
               $select_reviews->execute([$pid, $user_id ?? 0]);
               
               if($select_reviews->rowCount() > 0):
                  while($review = $select_reviews->fetch(PDO::FETCH_ASSOC)):
               ?>
                  <div class="review-item">
                     <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="user-avatar">
                           <?= strtoupper(substr($review['name'], 0, 1)); ?>
                        </div>
                        <div>
                           <h5 class="mb-1 fw-bold" style="color: #2b3452;">
                              <?= htmlspecialchars($review['name']); ?>
                           </h5>
                           <div class="d-flex align-items-center gap-2">
                              <div class="rating-stars">
                                 <?php 
                                 for($i = 1; $i <= 5; $i++){
                                    echo $i <= $review['rating'] 
                                       ? '<i class="fas fa-star"></i>' 
                                       : '<i class="far fa-star"></i>';
                                 }
                                 ?>
                              </div>
                              <small class="text-muted">
                                 <?= date('F j, Y', strtotime($review['created_at'])); ?>
                                 <?php if(isset($review['updated_at']) && $review['updated_at'] !== $review['created_at']): ?>
                                    <span class="text-muted">(edited)</span>
                                 <?php endif; ?>
                              </small>
                           </div>
                        </div>
                     </div>
                     <p class="lead mb-0" style="color: #6c757d;">
                        <?= htmlspecialchars($review['review_text']); ?>
                     </p>
                  </div>
               <?php 
                  endwhile;
               else:
               ?>
                  <div class="text-center py-5">
                     <i class="fas fa-comment-slash text-muted" style="font-size: 3rem;"></i>
                     <p class="lead mt-3 text-muted">No reviews yet for this product</p>
                  </div>
               <?php endif; ?>

               <!-- Review Form for New Review -->
               <?php if($user_id && !$user_review): ?>
                  <div class="custom-card mt-5">
                     <div class="text-center mb-4">
                        <h4 class="fw-bold" style="color: #2b3452;">Write a Review</h4>
                        <p class="text-muted">Share your experience with this product</p>
                     </div>
                     
                     <form method="post" action="">
                        <div class="mb-4">
                           <label class="form-label text-center w-100">
                              <span class="badge rounded-pill badge-soft">Your Rating</span>
                           </label>
                           <div class="star-rating">
                              <?php for($i = 5; $i >= 1; $i--): ?>
                                 <input type="radio" id="star<?= $i ?>" 
                                    name="rating" value="<?= $i ?>" required>
                                 <label for="star<?= $i ?>" title="<?= $i ?> stars">&#9733;</label>
                              <?php endfor; ?>
                           </div>
                        </div>
                        
                        <div class="form-floating mb-4">
                           <textarea class="form-control" 
                              name="review_text" 
                              id="review-text"
                              placeholder="Share your experience"
                              style="height: 120px"
                              required></textarea>
                           <label for="review-text">Share your experience</label>
                        </div>
                        
                        <div class="text-center">
                           <button type="submit" name="submit_review" 
                                   class="btn btn-primary px-5">
                              <i class="fas fa-paper-plane me-2"></i>Submit Review
                           </button>
                        </div>
                     </form>
                  </div>
               <?php elseif(!$user_id): ?>
                  <!-- Not Logged In State -->
                  <div class="custom-card mt-5 text-center py-5">
                     <div class="mb-4">
                        <i class="fas fa-user-lock text-primary" style="font-size: 3rem;"></i>
                     </div>
                     <h4 class="fw-bold mb-3" style="color: #2b3452;">
                        Please Login to Write a Review
                     </h4>
                     <p class="text-muted mb-4">
                        Share your experience and help others by logging in to your account.
                     </p>
                     <a href="login.php" class="btn btn-primary px-5">
                        <i class="fas fa-sign-in-alt me-2"></i>Login
                     </a>
                  </div>
               <?php endif; ?>
            </div>
         </div>
      </div>
   </div>
</section>

<?php include 'components/footer.php'; ?>

<script>
function editReview(button, reviewId) {
   const reviewItem = button.closest('.review-item');
   const reviewContent = reviewItem.querySelector('.review-content');
   const editForm = reviewItem.querySelector('.edit-form');
   
   reviewContent.style.display = 'none';
   editForm.style.display = 'block';
}

function cancelEdit(button) {
   const reviewItem = button.closest('.review-item');
   const reviewContent = reviewItem.querySelector('.review-content');
   const editForm = reviewItem.querySelector('.edit-form');
   
   reviewContent.style.display = 'block';
   editForm.style.display = 'none';
}

// Image gallery functionality
document.addEventListener('DOMContentLoaded', function() {
   const mainImage = document.querySelector('.main-image img');
   const subImages = document.querySelectorAll('.sub-image-wrapper img');
   
   subImages.forEach(img => {
      img.addEventListener('click', function() {
         mainImage.src = this.src;
         
         // Remove active border from all thumbnails
         subImages.forEach(thumb => {
            thumb.parentElement.style.borderColor = '#dee2e6';
         });
         
         // Add active border to clicked thumbnail
         this.parentElement.style.borderColor = '#0d6efd';
      });
   });

   // Set initial active thumbnail
   if (subImages.length > 0) {
      subImages[0].parentElement.style.borderColor = '#0d6efd';
   }
});
</script>

</body>
</html>