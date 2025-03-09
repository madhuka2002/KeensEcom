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

// Function to get all categories
function getAllCategories($conn) {
    $category_query = $conn->prepare("SELECT * FROM category");
    $category_query->execute();
    return $category_query->fetchAll(PDO::FETCH_ASSOC);
}

// Get selected category from GET parameter
$selected_category = isset($_GET['category']) ? intval($_GET['category']) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>AstroShop | Product Catalogue</title>
   <link rel="icon" type="image/x-icon" href="favicon.png">
   
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <!-- Bootstrap CSS -->
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

    .category-filter .list-group-item {
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .category-filter .list-group-item:hover,
    .category-filter .list-group-item.active {
      background-color: rgba(13,110,253,0.1);
      color: #0d6efd;
    }
  </style>
</head>
<body>
   
<?php include 'components/header.php'; ?>

<section class="orders py-8">
   <br>
   <h1 class="display-6 fw-bold" style="color: #2b3452; text-align: center;">
      <span class="badge rounded-pill mb-2" style="background-color: rgba(13,110,253,0.1); color: #0d6efd; font-size: 24px;">
        Product Catalogue
      </span>
   </h1>
   
   <!-- Search and Filter Bar -->
   <div class="container-fluid mx-auto px-4 mb-4">
     <div class="card border-0 shadow-sm">
       <div class="card-body p-4">
         <form action="shop.php" method="get" class="row g-3 align-items-end">
           <!-- Search Input -->
           <div class="col-md-6">
             <label for="search" class="form-label">Search Products</label>
             <div class="input-group">
               <input type="text" class="form-control" id="search" name="search" placeholder="What are you looking for?"
                      value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
               <button class="btn btn-primary" type="submit">
                 <i class="fas fa-search me-1"></i> Search
               </button>
             </div>
           </div>
           
           <!-- Category Dropdown -->
           <div class="col-md-3">
             <label for="category" class="form-label">Category</label>
             <select class="form-select" id="category" name="category" onchange="this.form.submit()">
               <option value="0">All Categories</option>
               <?php 
               $categories = getAllCategories($conn);
               foreach($categories as $category): 
               ?>
                 <option value="<?= $category['category_id']; ?>" <?= $selected_category == $category['category_id'] ? 'selected' : ''; ?>>
                   <?= htmlspecialchars($category['name']); ?>
                 </option>
               <?php endforeach; ?>
             </select>
           </div>
           
           <!-- Sort Dropdown -->
           <div class="col-md-3">
             <label for="sort" class="form-label">Sort By</label>
             <select class="form-select" id="sort" name="sort" onchange="this.form.submit()">
               <option value="latest" <?= (isset($_GET['sort']) && $_GET['sort'] == 'latest') ? 'selected' : ''; ?>>Latest</option>
               <option value="price_low" <?= (isset($_GET['sort']) && $_GET['sort'] == 'price_low') ? 'selected' : ''; ?>>Price: Low to High</option>
               <option value="price_high" <?= (isset($_GET['sort']) && $_GET['sort'] == 'price_high') ? 'selected' : ''; ?>>Price: High to Low</option>
             </select>
           </div>
         </form>
       </div>
     </div>
   </div>
    
    <div class="container-fluid mx-auto px-4">
      <div class="row g-4">
        <!-- Category Filter Column -->
        <div class="col-md-3 col-lg-2">
          <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
              <div class="category-filter p-3">
                <h5 class="fw-bold mb-3">Shop by Category</h5>
                <div class="list-group list-group-flush border-top pt-2">
                  <a href="shop.php<?= isset($_GET['search']) ? '?search='.urlencode($_GET['search']) : ''; ?>" 
                     class="list-group-item list-group-item-action border-0 rounded py-2 px-3 <?= $selected_category == 0 ? 'active fw-medium' : ''; ?>">
                    <i class="fas fa-tags me-2"></i> All Products
                    <span class="badge rounded-pill bg-light text-dark float-end">
                      <?php 
                      $count_query = $conn->prepare("SELECT COUNT(*) as count FROM products");
                      $count_query->execute();
                      echo $count_query->fetch(PDO::FETCH_ASSOC)['count'];
                      ?>
                    </span>
                  </a>
                  
                  <?php foreach($categories as $category): 
                    // Get count of products in this category
                    $count_query = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ?");
                    $count_query->execute([$category['category_id']]);
                    $product_count = $count_query->fetch(PDO::FETCH_ASSOC)['count'];
                  ?>
                    <a href="shop.php?category=<?= $category['category_id']; ?><?= isset($_GET['search']) ? '&search='.urlencode($_GET['search']) : ''; ?>" 
                       class="list-group-item list-group-item-action border-0 rounded py-2 px-3 <?= $selected_category == $category['category_id'] ? 'active fw-medium' : ''; ?>">
                      <i class="<?= !empty($category['icon']) ? $category['icon'] : 'fas fa-folder'; ?> me-2"></i>
                      <?= htmlspecialchars($category['name']); ?>
                      <span class="badge rounded-pill bg-light text-dark float-end"><?= $product_count; ?></span>
                    </a>
                  <?php endforeach; ?>
                </div>
              </div>
              
            </div>
          </div>
        </div>

        <!-- Products Column -->
        <div class="col-md-9 col-lg-10">
          <?php
          // Get search term from GET parameter
          $search = isset($_GET['search']) ? trim($_GET['search']) : '';
          if(!empty($search)): 
          ?>
          <div class="mb-4">
            <h5 class="fw-normal">
              Search results for: <span class="fw-bold">"<?= htmlspecialchars($search); ?>"</span>
              <a href="shop.php<?= $selected_category > 0 ? '?category='.$selected_category : ''; ?>" class="btn btn-sm btn-outline-secondary ms-2">
                <i class="fas fa-times"></i> Clear Search
              </a>
            </h5>
          </div>
          <?php endif; ?>
          
          <div class="row g-4">
            <?php
            // Prepare the product query with optional category filtering and search
            $query = "SELECT p.* FROM products p";
            $where_clauses = [];
            $params = [];

            if($selected_category > 0) {
              $where_clauses[] = "p.category_id = ?";
              $params[] = $selected_category;
            }

            if(!empty($search)) {
              $where_clauses[] = "(p.name LIKE ? OR p.details LIKE ?)";
              $params[] = "%{$search}%";
              $params[] = "%{$search}%";
            }
            
            // Add price filter
            if(isset($_GET['min_price']) && $_GET['min_price'] !== '') {
              $where_clauses[] = "p.price >= ?";
              $params[] = $_GET['min_price'];
            }
            
            if(isset($_GET['max_price']) && $_GET['max_price'] !== '') {
              $where_clauses[] = "p.price <= ?";
              $params[] = $_GET['max_price'];
            }
            
            // Add rating filter
            if(isset($_GET['rating']) && $_GET['rating'] !== '') {
              $query = "SELECT p.* FROM products p 
                        LEFT JOIN (
                          SELECT product_id, AVG(rating) as avg_rating 
                          FROM reviews 
                          GROUP BY product_id
                        ) r ON p.id = r.product_id";
              $where_clauses[] = "(r.avg_rating >= ? OR r.avg_rating IS NULL)";
              $params[] = $_GET['rating'];
            }

            if(!empty($where_clauses)) {
              $query .= " WHERE " . implode(" AND ", $where_clauses);
            }
            
            // Add sorting
            if(isset($_GET['sort'])) {
              switch($_GET['sort']) {
                case 'price_low':
                  $query .= " ORDER BY p.price ASC";
                  break;
                case 'price_high':
                  $query .= " ORDER BY p.price DESC";
                  break;
                case 'popular':
                  $query .= " ORDER BY p.sales_count DESC";
                  break;
                case 'rating':
                  $query = str_replace("SELECT p.", "SELECT p., COALESCE(r.avg_rating, 0) as sort_rating", $query);
                  $query .= " ORDER BY sort_rating DESC";
                  break;
                default:
                  $query .= " ORDER BY p.id DESC"; // Latest by default
              }
            } else {
              $query .= " ORDER BY p.id DESC"; // Latest by default
            }

            $select_products = $conn->prepare($query); 
            $select_products->execute($params);

            if($select_products->rowCount() > 0){
              while($fetch_product = $select_products->fetch(PDO::FETCH_ASSOC)){
                // Get review statistics for this product
                $review_stats = getProductReviewStats($conn, $fetch_product['id']);
                $avg_rating = $review_stats['avg_rating'] ?? 0;
                $total_reviews = $review_stats['total_reviews'] ?? 0;
            ?>
               <div class="col-6 col-md-4 col-xl-3">
                  <div class="card h-100 border-0" style="background: white; transition: all 0.25s ease-in-out;">
                    <div class="position-relative">
                      <a href="view.php?pid=<?= $fetch_product['id']; ?>" class="d-block" style="aspect-ratio: 1;">
                        <img src="uploaded_img/<?= $fetch_product['image_01']; ?>" 
                             class="card-img-top h-100 w-100" 
                             alt="<?= $fetch_product['name']; ?>"
                             style="object-fit: cover;">
                      </a>
                      
                      <?php if(!empty($fetch_product['discount_price']) && $fetch_product['discount_price'] < $fetch_product['price']): ?>
                      <div class="position-absolute top-0 start-0 m-2">
                        <span class="badge bg-danger">
                          <?= round((($fetch_product['price'] - $fetch_product['discount_price']) / $fetch_product['price']) * 100) ?>% OFF
                        </span>
                      </div>
                      <?php endif; ?>
                      
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
                        <div>
                          <?php if(!empty($fetch_product['discount_price']) && $fetch_product['discount_price'] < $fetch_product['price']): ?>
                            <p class="mb-0">
                              <span class="text-primary fw-semibold" style="font-size: 1.1rem;">$<?= number_format($fetch_product['discount_price'], 2); ?></span>
                              <span class="text-decoration-line-through text-muted ms-1" style="font-size: 0.85rem;">$<?= number_format($fetch_product['price'], 2); ?></span>
                            </p>
                          <?php else: ?>
                            <p class="text-primary mb-0" style="font-weight: 600; font-size: 1.1rem;">
                              $<?= number_format($fetch_product['price'], 2); ?>
                            </p>
                          <?php endif; ?>
                        </div>
                        
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
            } else {
              echo '<div class="col-12 text-center p-5">
                      <div class="py-5">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <h4>No products found</h4>
                        <p class="text-muted">Try different search terms or browse categories</p>
                        <a href="shop.php" class="btn btn-primary mt-2">View All Products</a>
                      </div>
                    </div>';
            }
            ?>
          </div>
        </div>
      </div>
   </div>
</section>

<br>
<br>

<script>
    document.addEventListener('DOMContentLoaded', function() {
       const productCards = document.querySelectorAll('.product-card');
       productCards.forEach(card => {
          card.addEventListener('mouseover', function() {
             this.style.transform = 'translateY(-10px)';
          });
          card.addEventListener('mouseout', function() {
             this.style.transform = 'translateY(0)';
          });
       });
    });
</script>

<?php include 'components/footer.php'; ?>

<script src="js/script.js"></script>

</body>
</html>