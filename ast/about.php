<?php

include 'components/connect.php';

session_start();

if(isset($_SESSION['user_id'])){
   $user_id = $_SESSION['user_id'];
}else{
   $user_id = '';
};

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Keens | About</title>
  <link rel="icon" type="image/x-icon" href="favicon.png">

   <link rel="stylesheet" href="https://unpkg.com/swiper@8/swiper-bundle.min.css" />
   
   <!-- font awesome cdn link  -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

</head>
<body>
   
<?php include 'components/header.php'; ?>

<div class="about-page">
  <!-- Hero Section -->
  <section class="hero-section position-relative overflow-hidden" style="background: linear-gradient(135deg, #2b3452 0%, #1a1f2f 100%);">
    <div class="container min-vh-75 d-flex align-items-center py-5" style="min-height: 500px;">
      <div class="row align-items-center g-5">
        <div class="col-lg-6">
          <span class="badge rounded-pill mb-3" style="background-color: rgba(13,110,253,0.1); color: #0d6efd; font-size: 0.9rem;">
            Est. 2024
          </span>
          <h1 class="display-4 fw-bold text-white mb-4" style="line-height: 1.2;">
            Revolutionizing Your Tech Shopping Experience
          </h1>
          <p class="lead mb-4" style="color: #a4b5cf;">
            Welcome to Keens, where innovation meets exceptional service. We're not just another tech store – we're your partner in discovering and implementing cutting-edge technology solutions.
          </p>
          <div class="d-flex gap-3">
            <a href="shop.php" class="btn btn-primary px-4 py-2 rounded-pill">
              Explore Products
            </a>
            <a href="#more" class="btn btn-outline-light px-4 py-2 rounded-pill">
              Learn More
            </a>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="position-relative">
            <div class="position-absolute w-100 h-100 top-0 start-0" 
                 style="background: linear-gradient(45deg, #0d6efd20 0%, #0dcaf020 100%); 
                        transform: translate(20px, 20px); 
                        border-radius: 30px;"></div>
            <img src="images/about.png" 
                 alt="Keens Showcase" 
                 class="img-fluid w-100" 
                 style="border-radius: 30px; 
                        box-shadow: 0 25px 50px rgba(0,0,0,0.2);
                        transition: all 0.3s ease;">
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Stats Section -->
  <section class="stats-section py-5" style="background-color: #fff;">
    <div class="container">
      <div class="row g-4 text-center">
        <div class="col-6 col-md-3">
          <h2 class="display-5 fw-bold mb-2" style="background: linear-gradient(45deg, #2b3452, #0d6efd); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
            10K+
          </h2>
          <p class="text-muted mb-0">Happy Customers</p>
        </div>
        <div class="col-6 col-md-3">
          <h2 class="display-5 fw-bold mb-2" style="background: linear-gradient(45deg, #2b3452, #0d6efd); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
            1K+
          </h2>
          <p class="text-muted mb-0">Products</p>
        </div>
        <div class="col-6 col-md-3">
          <h2 class="display-5 fw-bold mb-2" style="background: linear-gradient(45deg, #2b3452, #0d6efd); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
            24/7
          </h2>
          <p class="text-muted mb-0">Support</p>
        </div>
        <div class="col-6 col-md-3">
          <h2 class="display-5 fw-bold mb-2" style="background: linear-gradient(45deg, #2b3452, #0d6efd); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
            98%
          </h2>
          <p class="text-muted mb-0" id="more">Satisfaction Rate</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Values Section -->
  <section class="values-section py-5" style="background-color: #fff;">
    <div class="container">
      <div class="text-center mb-5">
        <span class="text-primary fw-semibold mb-3 d-block" style="letter-spacing: 1.5px;">WHY CHOOSE US</span>
      </div>
      
      <div class="row g-4">
        <div class="col-md-4">
          <div class="card h-100 border-0 rounded-4 p-4" 
               style="transition: all 0.3s ease;
                      box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
            <div class="card-body">
              <div class="mb-4">
                <div class="d-inline-block p-3 rounded-circle" style="background: linear-gradient(135deg, #0d6efd20 0%, #0dcaf020 100%);">
                  <i class="fas fa-rocket text-primary" style="font-size: 2rem;"></i>
                </div>
              </div>
              <h4 class="fw-bold mb-3" style="color: #2b3452;">Innovation First</h4>
              <p class="text-muted mb-0">
                We're always on the lookout for the latest tech innovations to bring you tomorrow's solutions today.
              </p>
            </div>
          </div>
        </div>

        <div class="col-md-4">
          <div class="card h-100 border-0 rounded-4 p-4" 
               style="transition: all 0.3s ease;
                      box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
            <div class="card-body">
              <div class="mb-4">
                <div class="d-inline-block p-3 rounded-circle" style="background: linear-gradient(135deg, #0d6efd20 0%, #0dcaf020 100%);">
                  <i class="fas fa-star text-primary" style="font-size: 2rem;"></i>
                </div>
              </div>
              <h4 class="fw-bold mb-3" style="color: #2b3452;">Excellence</h4>
              <p class="text-muted mb-0">
                We maintain the highest standards in product quality and customer service.
              </p>
            </div>
          </div>
        </div>

        <div class="col-md-4">
          <div class="card h-100 border-0 rounded-4 p-4" 
               style="transition: all 0.3s ease;
                      box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
            <div class="card-body">
              <div class="mb-4">
                <div class="d-inline-block p-3 rounded-circle" style="background: linear-gradient(135deg, #0d6efd20 0%, #0dcaf020 100%);">
                  <i class="fas fa-users text-primary" style="font-size: 2rem;"></i>
                </div>
              </div>
              <h4 class="fw-bold mb-3" style="color: #2b3452;">Community</h4>
              <p class="text-muted mb-0">
                We build lasting relationships with our customers and the tech community.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>



  <?php include 'components/footer.php'; ?>

</body>
</html>