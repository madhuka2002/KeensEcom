<footer class="footer py-5" style="background-color: #1a1a1a; color: #ffffff;">
   <div class="container">
      <!-- Footer Top Section with Logo and Description -->
      <div class="row mb-5">
         <div class="col-lg-4 mb-4 mb-lg-0">
            <h2 class="mb-4" style="font-weight: 700; letter-spacing: -0.5px;">
            <a href="index.php" style="
                font-size: 1.75rem;
                font-weight: 800;
                color: #333;
                text-decoration: none;
                background: linear-gradient(45deg, #6a11cb 0%, #2575fc 100%);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
            ">
                Astro<span style="color: #2575fc;">Shop</span>
            </a>
            </h2>
            <p class="pe-lg-4" style="color: #999999; line-height: 1.8;">
               Your one-stop destination for quality products. We provide the best shopping experience with carefully curated items just for you.
            </p>
            <div class="d-flex gap-3 mt-4">
               <a href="https://facebook.com" target="_blank" class="btn p-2" style="background-color: rgba(255,255,255,0.1); width: 40px; height: 40px; border-radius: 8px; transition: all 0.3s ease;">
                  <i class="fab fa-facebook-f" style="color: #0d6efd;"></i>
               </a>
               <a href="https://instagram.com" target="_blank" class="btn p-2" style="background-color: rgba(255,255,255,0.1); width: 40px; height: 40px; border-radius: 8px; transition: all 0.3s ease;">
                  <i class="fab fa-instagram" style="color: #0d6efd;"></i>
               </a>
               <a href="https://twitter.com" target="_blank" class="btn p-2" style="background-color: rgba(255,255,255,0.1); width: 40px; height: 40px; border-radius: 8px; transition: all 0.3s ease;">
                  <i class="fab fa-twitter" style="color: #0d6efd;"></i>
               </a>
            </div>
         </div>

         <!-- Quick Links Section -->
         <div class="col-6 col-lg-2 mb-4 mb-lg-0">
            <h5 class="mb-4" style="font-weight: 600; letter-spacing: 0.5px; color: #ffffff;">Quick Links</h5>
            <ul class="list-unstyled" style="margin: 0; padding: 0;">
               <li class="mb-3">
                  <a href="home.php" class="text-decoration-none d-block" style="color: #999999; transition: all 0.3s ease;">Home</a>
               </li>
               <li class="mb-3">
                  <a href="about.php" class="text-decoration-none d-block" style="color: #999999; transition: all 0.3s ease;">About</a>
               </li>
               <li class="mb-3">
                  <a href="shop.php" class="text-decoration-none d-block" style="color: #999999; transition: all 0.3s ease;">Shop</a>
               </li>
               <li class="mb-3">
                  <a href="contact.php" class="text-decoration-none d-block" style="color: #999999; transition: all 0.3s ease;">Contact</a>
               </li>
            </ul>
         </div>

         <!-- Account Links Section -->
         <div class="col-6 col-lg-2 mb-4 mb-lg-0">
            <h5 class="mb-4" style="font-weight: 600; letter-spacing: 0.5px; color: #ffffff;">Account</h5>
            <ul class="list-unstyled" style="margin: 0; padding: 0;">
               <li class="mb-3">
                  <a href="login.php" class="text-decoration-none d-block" style="color: #999999; transition: all 0.3s ease;">Login</a>
               </li>
               <li class="mb-3">
                  <a href="register.php" class="text-decoration-none d-block" style="color: #999999; transition: all 0.3s ease;">Register</a>
               </li>
               <li class="mb-3">
                  <a href="cart.php" class="text-decoration-none d-block" style="color: #999999; transition: all 0.3s ease;">Cart</a>
               </li>
               <li class="mb-3">
                  <a href="orders.php" class="text-decoration-none d-block" style="color: #999999; transition: all 0.3s ease;">Orders</a>
               </li>
            </ul>
         </div>

         <!-- Contact Information Section -->
         <div class="col-lg-4">
            <h5 class="mb-4" style="font-weight: 600; letter-spacing: 0.5px; color: #ffffff;">Contact Us</h5>
            <div class="d-flex flex-column gap-3">
               <div class="d-flex align-items-center gap-3">
                  <div style="width: 40px; height: 40px; background-color: rgba(255,255,255,0.1); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                     <i class="fas fa-map-marker-alt" style="color: #0d6efd;"></i>
                  </div>
                  <div style="color: #999999;">
                     123 Main Street,<br>Colombo, Sri Lanka
                  </div>
               </div>
               <div class="d-flex align-items-center gap-3">
                  <div style="width: 40px; height: 40px; background-color: rgba(255,255,255,0.1); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                     <i class="fas fa-phone" style="color: #0d6efd;"></i>
                  </div>
                  <div style="color: #999999;">
                     +94 70 555 5555<br>+94 70 555 5556
                  </div>
               </div>
               <div class="d-flex align-items-center gap-3">
                  <div style="width: 40px; height: 40px; background-color: rgba(255,255,255,0.1); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                     <i class="fas fa-envelope" style="color: #0d6efd;"></i>
                  </div>
                  <div style="color: #999999;">
                     contact@astro.store
                  </div>
               </div>
            </div>
         </div>
      </div>


      <!-- Copyright Section -->
      <div class="row align-items-center">
         <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
            <p class="mb-0" style="color: #999999;">
               Copyright © <span style="color: #0d6efd;"><?= date('Y'); ?></span> Keens.lk All rights reserved.
            </p>
         </div>
         <div class="col-md-6 text-center text-md-end">
            <div class="d-flex gap-3 justify-content-center justify-content-md-end">
               <!-- <a href="#" class="text-decoration-none" style="color: #999999; transition: all 0.3s ease;">Privacy Policy</a>
               <a href="#" class="text-decoration-none" style="color: #999999; transition: all 0.3s ease;">Terms of Service</a> -->
            </div>
         </div>
      </div>
   </div>
</footer>