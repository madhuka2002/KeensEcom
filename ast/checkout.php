<?php

include 'components/connect.php';

session_start();

if(isset($_SESSION['user_id'])){
   $user_id = $_SESSION['user_id'];
}else{
   $user_id = '';
   header('location:login.php');
};

if(isset($_POST['order'])){

   $name = $_POST['name'];
   $name = filter_var($name, FILTER_SANITIZE_STRING);
   $number = $_POST['number'];
   $number = filter_var($number, FILTER_SANITIZE_STRING);
   $email = $_POST['email'];
   $email = filter_var($email, FILTER_SANITIZE_STRING);
   $method = $_POST['method'];
   $method = filter_var($method, FILTER_SANITIZE_STRING);
   $address = 'flat no. '. $_POST['flat'] .', '. $_POST['street'] .', '. $_POST['city'] .', '. $_POST['state'] .', '. $_POST['country'] .' - '. $_POST['pin_code'];
   $address = filter_var($address, FILTER_SANITIZE_STRING);
   $total_products = $_POST['total_products'];
   $total_price = $_POST['total_price'];

   $check_cart = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ?");
   $check_cart->execute([$user_id]);

   if($check_cart->rowCount() > 0){

      $insert_order = $conn->prepare("INSERT INTO `orders`(user_id, name, number, email, method, address, total_products, total_price) VALUES(?,?,?,?,?,?,?,?)");
      $insert_order->execute([$user_id, $name, $number, $email, $method, $address, $total_products, $total_price]);

      $delete_cart = $conn->prepare("DELETE FROM `cart` WHERE user_id = ?");
      $delete_cart->execute([$user_id]);

      $message[] = 'order placed successfully!';
   }else{
      $message[] = 'your cart is empty';
   }

}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Keens | Checkout</title>
  <link rel="icon" type="image/x-icon" href="favicon.png">
   
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

</head>
<body>
   
<?php include 'components/header.php'; ?>

<section class="checkout-hero py-5" style="background: linear-gradient(135deg, #2b3452 0%, #1a1f2f 100%);">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-8 text-center">
        <span class="badge rounded-pill mb-3" style="background-color: rgba(13,110,253,0.1); color: #0d6efd; font-size: 0.9rem;">
          Checkout
        </span>
        <h1 class="display-5 fw-bold text-white mb-3">COMPLETE YOUR ORDER</h1>
        <p class="lead mb-0" style="color: #a4b5cf;">
          Review your items and complete your purchase
        </p>
      </div>
    </div>
  </div>
</section>

<section class="checkout-section position-relative" style="margin-top: -80px; z-index: 1;">
  <div class="container">
    <form action="" method="POST">
      <!-- Order Summary Card -->
      <div class="card border-0 rounded-4 mb-4" style="background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
        <div class="card-body p-4">
          <div class="d-flex align-items-center gap-3 mb-4">
            <div class="p-3 rounded-circle" style="background: rgba(13,110,253,0.1);">
              <i class="fas fa-shopping-cart text-primary"></i>
            </div>
            <h4 class="fw-bold mb-0" style="color: #2b3452;">Order Summary</h4>
          </div>
          
          <div class="display-orders">
            <?php
            $grand_total = 0;
            $cart_items = [];
            $select_cart = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ?");
            $select_cart->execute([$user_id]);
            if($select_cart->rowCount() > 0){
               while($fetch_cart = $select_cart->fetch(PDO::FETCH_ASSOC)){
                  $cart_items[] = $fetch_cart['name'] . ' (' . $fetch_cart['price'] . ' x ' . $fetch_cart['quantity'] . ')';
                  $grand_total += ($fetch_cart['price'] * $fetch_cart['quantity']);
            ?>
            <div class="d-flex justify-content-between align-items-center py-3 border-bottom" 
                 style="transition: all 0.3s ease;">
              <div class="d-flex align-items-center gap-3">
                <div class="p-2 rounded-3" style="background: rgba(13,110,253,0.1);">
                  <i class="fas fa-box text-primary"></i>
                </div>
                <h6 class="fw-bold mb-0"><?= $fetch_cart['name']; ?></h6>
              </div>
              <p class="h6 mb-0 text-primary fw-bold">
                <?= $fetch_cart['price'] . '$ x ' . $fetch_cart['quantity']; ?>
              </p>
            </div>
            <?php
               }
            } else {
               echo '<p class="text-center text-muted">Your cart is empty!</p>';
            }
            ?>
            <input type="hidden" name="total_products" value="<?= implode(', ', $cart_items); ?>">
            <input type="hidden" name="total_price" value="<?= $grand_total; ?>">
            <div class="mt-4 text-end">
              <h4 class="fw-bold">Total: <span class="text-primary">$<?= $grand_total; ?></span></h4>
            </div>
          </div>
        </div>
      </div>

<!-- Shipping Information -->
<div class="card border-0 rounded-4" style="background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
        <div class="card-body p-4">
          <div class="d-flex align-items-center gap-3 mb-4">
            <div class="p-3 rounded-circle" style="background: rgba(13,110,253,0.1);">
              <i class="fas fa-shipping-fast text-primary"></i>
            </div>
            <h4 class="fw-bold mb-0" style="color: #2b3452;">Shipping Details</h4>
          </div>

          <div class="row g-4">
            <div class="col-md-6">
              <div class="form-floating">
                <input type="text" name="name" class="form-control" id="nameInput" placeholder="Your Name" 
                       required maxlength="20" style="border-radius: 12px; border: 1px solid #dee2e6;">
                <label for="nameInput" class="text-muted">Your Name</label>
              </div>
            </div>

            <div class="col-md-6">
              <div class="form-floating">
                <input type="email" name="email" class="form-control" id="emailInput" placeholder="Your Email" 
                       required maxlength="50" style="border-radius: 12px; border: 1px solid #dee2e6;">
                <label for="emailInput" class="text-muted">Your Email</label>
              </div>
            </div>

            <div class="col-md-6">
              <div class="form-floating">
                <input type="number" name="number" class="form-control" id="phoneInput" placeholder="Your Phone" 
                       required maxlength="10" style="border-radius: 12px; border: 1px solid #dee2e6;">
                <label for="phoneInput" class="text-muted">Your Phone</label>
              </div>
            </div>

            <div class="col-md-6">
              <div class="form-floating">
                <select name="method" class="form-select" id="methodInput" 
                        style="border-radius: 12px; border: 1px solid #dee2e6;">
                  <option value="cash on delivery">Cash On Delivery</option>
                  <option value="credit card">Bank Transfer</option>
                  <option value="paytm">Credit / Debit Card</option>
                  <option value="paypal">PayPal</option>
                </select>
                <label for="methodInput" class="text-muted">Payment Method</label>
              </div>
            </div>

            <div class="col-md-6">
              <div class="form-floating">
                <input type="text" name="flat" class="form-control" id="addressInput1" placeholder="Address Line 1" 
                       required maxlength="50" style="border-radius: 12px; border: 1px solid #dee2e6;">
                <label for="addressInput1" class="text-muted">Address Line 1</label>
              </div>
            </div>

            <div class="col-md-6">
              <div class="form-floating">
                <input type="text" name="street" class="form-control" id="addressInput2" placeholder="Address Line 2" 
                       required maxlength="50" style="border-radius: 12px; border: 1px solid #dee2e6;">
                <label for="addressInput2" class="text-muted">Address Line 2</label>
              </div>
            </div>

            <div class="col-md-4">
              <div class="form-floating">
                <input type="text" name="city" class="form-control" id="cityInput" placeholder="City" 
                       required maxlength="50" style="border-radius: 12px; border: 1px solid #dee2e6;">
                <label for="cityInput" class="text-muted">City</label>
              </div>
            </div>

            <div class="col-md-4">
              <div class="form-floating">
                <input type="text" name="state" class="form-control" id="stateInput" placeholder="Province" 
                       required maxlength="50" style="border-radius: 12px; border: 1px solid #dee2e6;">
                <label for="stateInput" class="text-muted">Province</label>
              </div>
            </div>

            <div class="col-md-4">
              <div class="form-floating">
                <input type="text" name="country" class="form-control" id="countryInput" placeholder="Country" 
                       required maxlength="50" style="border-radius: 12px; border: 1px solid #dee2e6;">
                <label for="countryInput" class="text-muted">Country</label>
              </div>
            </div>

            <div class="col-md-12">
              <div class="form-floating">
                <input type="number" name="pin_code" class="form-control" id="zipInput" placeholder="ZIP Code" 
                       required maxlength="6" style="border-radius: 12px; border: 1px solid #dee2e6;">
                <label for="zipInput" class="text-muted">ZIP Code</label>
              </div>
            </div>
          </div>

          <div class="text-center mt-4">
            <button type="submit" name="order" class="btn btn-primary px-5 py-3 rounded-pill <?= ($grand_total > 1) ? '' : 'disabled'; ?>" 
                    style="transition: all 0.3s ease;">
              <i class="fas fa-check-circle me-2"></i>
              Place Order
            </button>
          </div>
        </div>
      </div>


    </form>
  </div>
</section>

<style>
.form-control:focus,
.form-select:focus {
  border-color: #0d6efd40;
  box-shadow: 0 0 0 0.25rem rgba(13,110,253,.15);
}

.form-floating > .form-control:focus ~ label,
.form-floating > .form-control:not(:placeholder-shown) ~ label,
.form-floating > .form-select ~ label {
  color: #0d6efd;
}

.btn-primary:hover {
  transform: translateY(-3px);
  box-shadow: 0 5px 15px rgba(13,110,253,0.2);
}

.display-orders > div:hover {
  background: rgba(13,110,253,0.05);
}

@media (max-width: 991.98px) {
  .checkout-section {
    margin-top: -40px;
  }
  
  .card-body {
    padding: 1.5rem !important;
  }
}

@media (max-width: 767.98px) {
  .display-orders .d-flex {
    flex-direction: column;
    text-align: center;
    gap: 0.5rem;
  }
}
</style>














<?php include 'components/footer.php'; ?>

<script src="js/script.js"></script>




</body>
</html>