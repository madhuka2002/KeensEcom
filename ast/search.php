<?php
include 'components/connect.php';

session_start();

if(isset($_SESSION['user_id'])){
   $user_id = $_SESSION['user_id'];
}else{
   $user_id = '';
};

include 'components/wishlist_cart.php';

// Function to get review statistics for each product
function getProductReviewStats($conn, $product_id) {
    $review_query = $conn->prepare("
        SELECT 
            AVG(rating) as avg_rating, 
            COUNT(*) as total_reviews 
        FROM reviews 
        WHERE product_id = ?
    ");
    $review_query->execute([$product_id]);
    return $review_query->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AstroShop | Search</title>
    <link rel="icon" type="image/x-icon" href="favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
    .card {
      box-shadow: 0 2px 15px rgba(0,0,0,0.05);
    }
    
    .card:hover {
      transform: translateY(-5px);
      box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    }
    
    .card:hover .position-absolute {
      opacity: 1 !important;
    }
    
    @media (max-width: 575.98px) {
      .position-absolute {
        opacity: 1 !important;
      }
    }
    
    .form-control:focus {
      border-color: #0d6efd40;
      box-shadow: 0 0 0 0.25rem rgba(13,110,253,.15);
    }
    
    input[type="number"]::-webkit-inner-spin-button,
    input[type="number"]::-webkit-outer-spin-button {
      opacity: 1;
    }

    .review-stats {
      color: #6c757d;
      font-size: 0.85rem;
    }

    .review-stars .fas {
      color: #ffc107;
    }

    .review-stars .far {
      color: #dee2e6;
    }
  </style>
</head>

<body> 
   <?php include 'components/header.php'; ?> 
   
   <section class="search-form">
   <br><br>
   <form action="" method="post" class="search-form" style="display: flex; justify-content: center; align-items: center; padding: 20px;">
      <div class="input-group" style="max-width: 600px; width: 100%; border-radius: 30px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
         <input 
            type="text" 
            name="search_box" 
            placeholder="Search here..." 
            maxlength="100" 
            class="form-control" 
            required 
            style="flex: 1; padding: 10px 15px; font-size: 16px; border: none; outline: none;"
         >
         <button 
            type="submit" 
            name="search_btn" 
            class="btn btn-primary" 
            style="background-color: #007bff; color: white; border: none; padding: 10px 20px; cursor: pointer;"
         >
            <i class="fas fa-search" style="font-size: 16px;"></i>
         </button>
      </div>
   </form>
   <br>
</section>

<section class="orders py-8">
   <br><br>

   <div class="container mx-auto px-4">
   <div class="row g-4">
   <?php
     if(isset($_POST['search_box']) OR isset($_POST['search_btn'])){
     $search_box = $_POST['search_box'];
     $select_products = $conn->prepare("SELECT * FROM `products` WHERE name LIKE '%{$search_box}%'"); 
     $select_products->execute();
     if($select_products->rowCount() > 0){
      while($fetch_product = $select_products->fetch(PDO::FETCH_ASSOC)){
        // Get review statistics for this product
        $review_stats = getProductReviewStats($conn, $fetch_product['id']);
        $avg_rating = $review_stats['avg_rating'] ?? 0;
        $total_reviews = $review_stats['total_reviews'] ?? 0;
   ?>
   <div class="col-6 col-md-4 col-lg-3">
        <div class="card h-100 border-0" style="background: white; transition: all 0.25s ease-in-out;">
          <div class="position-relative">
            <a href="view.php?pid=<?= $fetch_product['id']; ?>" class="d-block" style="aspect-ratio: 1;">
              <img src="uploaded_img/<?= $fetch_product['image_01']; ?>" 
                  class="card-img-top h-100 w-100" 
                  alt="<?= $fetch_product['name']; ?>"
                  style="object-fit: cover;">
            </a>
            
            <div class="position-absolute top-0 end-0 p-2 d-flex flex-column gap-2" 
                style="opacity: 0; transition: all 0.2s ease-in-out;">
              <form method="post">
                <input type="hidden" name="pid" value="<?= $fetch_product['id']; ?>">
                <input type="hidden" name="name" value="<?= $fetch_product['name']; ?>">
                <input type="hidden" name="price" value="<?= $fetch_product['price']; ?>">
                <input type="hidden" name="image" value="<?= $fetch_product['image_01']; ?>">
                
                <button type="submit" name="add_to_wishlist" 
                        class="btn btn-light shadow-sm rounded-circle d-flex align-items-center justify-content-center" 
                        style="width: 35px; height: 35px; backdrop-filter: blur(4px); background: rgba(255,255,255,0.9);">
                  <i class="fas fa-heart" style="color: #dc3545; font-size: 0.9rem;"></i>
                </button>
              </form>
              
              <a href="view.php?pid=<?= $fetch_product['id']; ?>" 
                class="btn btn-light shadow-sm rounded-circle d-flex align-items-center justify-content-center" 
                style="width: 35px; height: 35px; backdrop-filter: blur(4px); background: rgba(255,255,255,0.9);">
                <i class="fas fa-eye" style="color: #0d6efd; font-size: 0.9rem;"></i>
              </a>
            </div>
          </div>

          <div class="card-body p-3">
            <h5 class="card-title mb-1" style="font-size: 0.95rem; font-weight: 500; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
              <?= $fetch_product['name']; ?>
            </h5>

            <div class="d-flex align-items-center justify-content-between mb-2">
              <p class="text-primary mb-0" style="font-weight: 600; font-size: 1.1rem;">
                $<?= number_format($fetch_product['price'], 2); ?>
              </p>
              
              <div class="review-stats d-flex align-items-center gap-1">
                <div class="review-stars">
                  <?php 
                  for($i = 1; $i <= 5; $i++){
                    echo $i <= round($avg_rating) 
                      ? '<i class="fas fa-star"></i>' 
                      : '<i class="far fa-star"></i>';
                  }
                  ?>
                </div>
                <span>(<?= $total_reviews ?>)</span>
              </div>
            </div>

            <form method="post" class="d-flex gap-2 align-items-center">
              <input type="hidden" name="pid" value="<?= $fetch_product['id']; ?>">
              <input type="hidden" name="name" value="<?= $fetch_product['name']; ?>">
              <input type="hidden" name="price" value="<?= $fetch_product['price']; ?>">
              <input type="hidden" name="image" value="<?= $fetch_product['image_01']; ?>">

              <input type="number" 
                    name="qty" 
                    class="form-control form-control-sm px-2" 
                    style="width: 65px;" 
                    min="1" 
                    max="99" 
                    value="1" 
                    onkeypress="if(this.value.length == 2) return false;">

              <button type="submit" 
                      name="add_to_cart" 
                      class="btn btn-primary btn-sm flex-grow-1 d-flex align-items-center justify-content-center gap-2">
                <i class="fas fa-shopping-cart"></i>
                <span class="d-none d-sm-inline">Add</span>
              </button>
            </form>
          </div>
        </div>
      </div>
   <?php
      }
   }else{
      echo '<div class="col-12 text-center"><p class="alert alert-info">No products found matching your search!</p></div>';
   }
}
   ?>
</div>
   </div>
</section>

<br><br><br> 
<?php include 'components/footer.php'; ?> 
<script src="js/script.js"></script>
</body>

</html>