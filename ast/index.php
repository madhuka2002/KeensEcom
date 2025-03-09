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
  <link rel="icon" type="image/x-icon" href="favicon.png">
  <title>Keens.lk | Home</title>

  <link rel="stylesheet" href="https://unpkg.com/swiper@8/swiper-bundle.min.css" />

  <!-- font awesome cdn link  -->
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
  </style>
</head>

<body>

  <?php include 'components/header.php'; ?>

  <div class="home-bg">
    <section class="home">
      <div class="swiper home-slider">
      </div>
    </section>
  </div>

<!-- First-Time User Experience Section -->
<section class="home-hero position-relative overflow-hidden" style="background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);">
  <!-- Animated Background Elements -->
  <div class="position-absolute w-100 h-100" style="top: 0; left: 0; overflow: hidden; opacity: 0.1;">
    <div class="position-absolute" style="width: 300px; height: 300px; border-radius: 50%; background: rgba(255,255,255,0.3); top: -100px; left: -100px;"></div>
    <div class="position-absolute" style="width: 200px; height: 200px; border-radius: 50%; background: rgba(255,255,255,0.2); bottom: -50px; right: 10%;"></div>
    <div class="position-absolute" style="width: 150px; height: 150px; border-radius: 50%; background: rgba(255,255,255,0.2); top: 20%; right: -50px;"></div>
  </div>

  <div class="container py-5">
    <div class="row align-items-center">
      <div class="col-lg-6 text-white position-relative" style="z-index: 2;">
        <!-- Animated Badge -->
        <div class="position-relative mb-3">
          <span class="badge bg-warning px-3 py-2" style="font-size: 16px; transform: rotate(-2deg);">
            <i class="fas fa-bolt me-1"></i> Limited Time Offer
          </span>
        </div>

        <!-- Main Title with Highlight -->
        <h1 class="display-4 fw-bold mb-3" style="text-shadow: 0 2px 10px rgba(0,0,0,0.1);">
          Discover Our <span style="background: linear-gradient(90deg, #FFD700, #FFA500); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Premium</span> Collection
        </h1>
        
        <!-- Subtitle with Icon -->
        <p class="mb-4 fs-5">
          <i class="fas fa-check-circle me-2 text-warning"></i> Top quality products at unbeatable prices
        </p>
        <p class="mb-4">
          Explore Astro's latest arrivals and take advantage of our biggest sale of the season. 
          Limited stock available!
        </p>
        
        <!-- CTA Buttons -->
        <div class="d-flex flex-wrap gap-3 mb-4">
          <a href="shop.php" class="btn btn-light px-4 py-2 rounded-pill" 
             style="transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(0,0,0,0.1);" 
             onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 10px 20px rgba(0,0,0,0.2)';" 
             onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(0,0,0,0.1)';">
            <i class="fas fa-shopping-bag me-2"></i> Shop Now
          </a>
          <a href="shop.php" class="btn btn-outline-light px-4 py-2 rounded-pill" 
             style="transition: all 0.3s ease; border-width: 2px;" 
             onmouseover="this.style.transform='translateY(-5px)';" 
             onmouseout="this.style.transform='translateY(0)';">
            Featured Items <i class="fas fa-arrow-right ms-2"></i>
          </a>
        </div>
        
        <!-- Trust Badges -->
        <div class="d-flex flex-wrap gap-4">
          <div class="d-flex align-items-center">
            <i class="fas fa-truck-fast me-2" style="color: #FFD700;"></i>
            <span>Free Shipping</span>
          </div>
          <div class="d-flex align-items-center">
            <i class="fas fa-shield-alt me-2" style="color: #FFD700;"></i>
            <span>Secure Payment</span>
          </div>
          <div class="d-flex align-items-center">
            <i class="fas fa-undo me-2" style="color: #FFD700;"></i>
            <span>Easy Returns</span>
          </div>
        </div>
      </div>
      
      <div class="col-lg-6 position-relative mt-5 mt-lg-0" style="z-index: 1;">
        <!-- Main Product Image with Effects -->
        <div class="position-relative">
          <img src="images/ecom.png" class="img-fluid" 
               style="transform: perspective(1000px) rotate3d(0, 1, 0, 15deg); transition: all 0.5s ease;" 
               alt="Premium Products Collection"
               onmouseover="this.style.transform='perspective(1000px) rotate3d(0, 1, 0, 5deg) translateY(-10px)'"
               onmouseout="this.style.transform='perspective(1000px) rotate3d(0, 1, 0, 15deg)'"
               id="heroImage">
          
          
          <div class="position-absolute top-100 start-100 translate-middle">
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Add this to your CSS styles -->
<style>
  @keyframes float {
    0% { transform: translateY(0px); }
    50% { transform: translateY(-15px); }
    100% { transform: translateY(0px); }
  }
  
  .home-hero {
    position: relative;
  }
  
  .home-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-image: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI1IiBoZWlnaHQ9IjUiPgo8cmVjdCB3aWR0aD0iNSIgaGVpZ2h0PSI1IiBmaWxsPSIjZmZmIiBmaWxsLW9wYWNpdHk9IjAuMDUiPjwvcmVjdD4KPC9zdmc+');
    opacity: 0.3;
  }
</style>

<!-- Add this to your JS script -->
<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Countdown timer functionality
    function updateCountdown() {
      // Set the target date (example: 3 days from now)
      const now = new Date();
      const targetDate = new Date();
      targetDate.setDate(now.getDate() + 3);
      
      const difference = targetDate - now;
      
      const days = Math.floor(difference / (1000 * 60 * 60 * 24));
      const hours = Math.floor((difference % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
      const minutes = Math.floor((difference % (1000 * 60 * 60)) / (1000 * 60));
      const seconds = Math.floor((difference % (1000 * 60)) / 1000);
      
      document.getElementById('days').textContent = days;
      document.getElementById('hours').textContent = hours.toString().padStart(2, '0');
      document.getElementById('minutes').textContent = minutes.toString().padStart(2, '0');
      document.getElementById('seconds').textContent = seconds.toString().padStart(2, '0');
    }
    
    // Update every second
    updateCountdown();
    setInterval(updateCountdown, 1000);
    
    // Image parallax effect on mouse move
    const heroSection = document.querySelector('.home-hero');
    const heroImage = document.getElementById('heroImage');
    
    heroSection.addEventListener('mousemove', (e) => {
      const xAxis = (window.innerWidth / 2 - e.pageX) / 25;
      const yAxis = (window.innerHeight / 2 - e.pageY) / 25;
      heroImage.style.transform = perspective(1000px) rotateY(${xAxis}deg) rotateX(${yAxis}deg) translateZ(10px);
    });
    
    heroSection.addEventListener('mouseleave', () => {
      heroImage.style.transform = 'perspective(1000px) rotate3d(0, 1, 0, 15deg)';
    });
  });
</script>

<!-- Feature Highlights Section -->
<section class="py-5" style="background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);">
  <div class="container">
    <div class="row g-4 text-center text-white">
      <!-- Feature 1 -->
      <div class="col-6 col-md-3">
        <div class="p-3">
          <i class="fas fa-truck-fast mb-3" style="font-size: 32px;"></i>
          <h5 class="fw-bold">Free Shipping</h5>
          <p class="mb-0 opacity-75">On orders over $50</p>
        </div>
      </div>
      
      <!-- Feature 2 -->
      <div class="col-6 col-md-3">
        <div class="p-3">
          <i class="fas fa-rotate-left mb-3" style="font-size: 32px;"></i>
          <h5 class="fw-bold">Easy Returns</h5>
          <p class="mb-0 opacity-75">30-day return policy</p>
        </div>
      </div>
      
      <!-- Feature 3 -->
      <div class="col-6 col-md-3">
        <div class="p-3">
          <i class="fas fa-shield mb-3" style="font-size: 32px;"></i>
          <h5 class="fw-bold">Secure Payment</h5>
          <p class="mb-0 opacity-75">100% secure checkout</p>
        </div>
      </div>
      
      <!-- Feature 4 -->
      <div class="col-6 col-md-3">
        <div class="p-3">
          <i class="fas fa-headset mb-3" style="font-size: 32px;"></i>
          <h5 class="fw-bold">24/7 Support</h5>
          <p class="mb-0 opacity-75">Always here to help</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- New User Special Offer -->


  <section class="py-4 py-lg-5" style="background-color: #f8f9fa;">
    <h1 class="display-6 fw-bold" style="color: #2b3452; text-align: center;">
      <span class="badge rounded-pill mb-2" style="background-color: rgba(13,110,253,0.1); color: #0d6efd; font-size: 24px;">
        Latest Products
      </span>
    </h1>
    <br>
    <div class="container">
      <div class="row g-3 g-lg-4">
        <?php
        $select_products = $conn->prepare("SELECT * FROM products LIMIT 4"); 
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
        } else {
          echo '<div class="col-12"><div class="alert alert-info text-center">No products added yet!</div></div>';
        }
        ?>
      </div>
    </div>
  </section>

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

  <script src="https://unpkg.com/swiper@8/swiper-bundle.min.js"></script>

  <script src="js/script.js"></script>

  <script>
    var swiper = new Swiper(".home-slider", {
       loop:true,
       spaceBetween: 20,
       pagination: {
          el: ".swiper-pagination",
          clickable:true,
        },
    });
    
     var swiper = new Swiper(".category-slider", {
       loop:true,
       spaceBetween: 20,
       pagination: {
          el: ".swiper-pagination",
          clickable:true,
       },
       breakpoints: {
          0: {
             slidesPerView: 2,
           },
          650: {
            slidesPerView: 3,
          },
          768: {
            slidesPerView: 4,
          },
          1024: {
            slidesPerView: 5,
          },
       },
    });
    
    var swiper = new Swiper(".products-slider", {
       loop:true,
       spaceBetween: 20,
       pagination: {
          el: ".swiper-pagination",
          clickable:true,
       },
       breakpoints: {
          550: {
            slidesPerView: 2,
          },
          768: {
            slidesPerView: 2,
          },
          1024: {
            slidesPerView: 3,
          },
       },
    });
  </script>

</body>

</html>