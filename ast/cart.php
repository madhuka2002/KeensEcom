<?php
include 'components/connect.php';

session_start();

if(isset($_SESSION['user_id'])){
   $user_id = $_SESSION['user_id'];
}else{
   $user_id = '';
};

// Delete single cart item
if(isset($_POST['delete'])){
   $cart_id = $_POST['cart_id'];
   $delete_cart_item = $conn->prepare("DELETE FROM `cart` WHERE id = ?");
   $delete_cart_item->execute([$cart_id]);
   header('location:cart.php');
}

// Delete all cart items
if(isset($_GET['delete_all'])){
   $delete_cart_item = $conn->prepare("DELETE FROM `cart` WHERE user_id = ?");
   $delete_cart_item->execute([$user_id]);
   header('location:cart.php');
}

// Update quantity
if(isset($_POST['update_qty'])){
   $cart_id = $_POST['cart_id'];
   $qty = $_POST['qty'] ?? 1;
   $qty = filter_var($qty, FILTER_SANITIZE_NUMBER_INT);
   $qty = max(1, min(99, $qty));
   
   // Fetch the current price for the item
   $get_price = $conn->prepare("SELECT price FROM `cart` WHERE id = ?");
   $get_price->execute([$cart_id]);
   $item_price = $get_price->fetchColumn();
   
   // Update both quantity and subtotal
   $update_cart = $conn->prepare("UPDATE `cart` SET quantity = ?, subtotal = ? WHERE id = ?");
   $update_cart->execute([$qty, ($item_price * $qty), $cart_id]);
   
   header('location:cart.php');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Keens | Cart</title>
   <link rel="icon" type="image/x-icon" href="favicon.png">
   
   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <!-- Bootstrap CSS -->
   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
   
<?php include 'components/header.php'; ?>

<section class="products shopping-cart py-5" style="background: linear-gradient(135deg, #f4f4f4 0%, #e9ecef 100%);">
   <div class="container">
      <br>
   <div class="text-center mb-5">
      <span class="badge rounded-pill mb-2" style="background-color: rgba(13,110,253,0.1); color: #0d6efd; font-size: 0.9rem;">
        Keens / Cart
      </span>
      <h2 class="fw-bold mb-4" style="color: #2b3452;">Shopping Cart</h2>
    </div>

    
      <div class="card border-0 rounded-4 overflow-hidden" style="box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
         <div class="table-responsive">
            <table class="table table-hover mb-0" style="min-width: 1000px;">
               <thead style="background-color: #f8f9fa;">
                  <tr>
                     <th class="px-4 py-3" style="color: #6c757d; font-weight: 600; font-size: 0.9rem;">Product</th>
                     <th class="px-4 py-3" style="color: #6c757d; font-weight: 600; font-size: 0.9rem;">Image</th>
                     <th class="px-4 py-3" style="color: #6c757d; font-weight: 600; font-size: 0.9rem;">Price</th>
                     <th class="px-4 py-3" style="color: #6c757d; font-weight: 600; font-size: 0.9rem;">Quantity</th>
                     <th class="px-4 py-3" style="color: #6c757d; font-weight: 600; font-size: 0.9rem;">Subtotal</th>
                     <th class="px-4 py-3" style="color: #6c757d; font-weight: 600; font-size: 0.9rem;">Actions</th>
                  </tr>
               </thead>
               <tbody>
                  <?php
                  $grand_total = 0;
                  $select_cart = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ?");
                  $select_cart->execute([$user_id]);
                  if($select_cart->rowCount() > 0){
                     while($fetch_cart = $select_cart->fetch(PDO::FETCH_ASSOC)){
                        $sub_total = $fetch_cart['price'] * $fetch_cart['quantity'];
                        $grand_total += $sub_total;
                  ?>
                  <tr>
                     <td class="px-4 py-3">
                        <a href="view.php?pid=<?= $fetch_cart['pid']; ?>" class="fw-medium" style="color: #2b3452;">
                           <?= htmlspecialchars($fetch_cart['name']); ?>
                        </a>
                     </td>
                     <td class="px-4 py-3">
                        <img src="uploaded_img/<?= htmlspecialchars($fetch_cart['image']); ?>" alt="<?= htmlspecialchars($fetch_cart['name']); ?>" class="img-fluid" style="max-width: 80px; border-radius: 5px;">
                     </td>
                     <td class="px-4 py-3">
                        <span class="fw-medium" style="color: #2b3452;">$<?= htmlspecialchars($fetch_cart['price']); ?></span>
                     </td>
                     <td class="px-4 py-3">
                        <form action="" method="post" class="d-flex align-items-center">
                           <input type="hidden" name="cart_id" value="<?= $fetch_cart['id']; ?>">
                           <input type="number" name="qty" class="form-control text-center" min="1" max="99" onkeypress="if(this.value.length == 2) return false;" value="<?= $fetch_cart['quantity']; ?>" style="width: 60px;">
                           <span><pre> </pre></span><button type="submit" name="update_qty" class="btn btn-outline-primary px-3 py-1 ml-2" style="border-radius: 20px;">Update</button>
                        </form>
                     </td>
                     <td class="px-4 py-3">
                        <span class="fw-medium" style="color: #2b3452;">$<?= htmlspecialchars(number_format($sub_total, 2)); ?></span>
                     </td>
                     <td class="px-4 py-3">
                        <form action="" method="post">
                           <input type="hidden" name="cart_id" value="<?= $fetch_cart['id']; ?>">
                           <button type="submit" name="delete" class="btn btn-outline-danger px-3 py-1" style="border-radius: 20px;" onclick="return confirm('Delete this item?');">Delete</button>
                        </form>
                     </td>
                  </tr>
                  <?php
                     }
                  } else {
                     echo '<tr><td colspan="6" class="text-center text-muted px-4 py-3">Your cart is empty.</td></tr>';
                  }
                  ?>
               </tbody>
            </table>
         </div>
      </div>

      <style>
         .table th, .table td {
            white-space: nowrap;
            vertical-align: middle;
         }
      </style>

      <div class="cart-total text-center mt-4">
      <p class="h5 mt-3"><span class="text-primary">Grand Total: $<?= number_format($grand_total, 2); ?></span><br></p><br>
         <div class="d-flex justify-content-center gap-3 mt-3">
            <a href="shop.php" class="btn btn-outline-primary px-4 py-2" style="border-radius: 20px;">Continue Shopping</a>
            <a href="cart.php?delete_all" class="btn btn-outline-danger px-4 py-2 <?= ($grand_total > 1) ? '' : 'disabled'; ?>" onclick="return confirm('Delete all items?');" style="border-radius: 20px;">Delete All</a>
            <a href="checkout.php" class="btn btn-primary px-4 py-2 <?= ($grand_total > 1) ? '' : 'disabled'; ?>" style="border-radius: 20px;">Proceed to Checkout</a>
         </div>
      </div>
   </div>
</section>

<?php include 'components/footer.php'; ?>

<script src="js/script.js"></script>

</body>
</html>