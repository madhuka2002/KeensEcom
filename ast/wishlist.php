<?php

include 'components/connect.php';

session_start();

if(isset($_SESSION['user_id'])){
   $user_id = $_SESSION['user_id'];
}else{
   $user_id = '';
};

include 'components/wishlist_cart.php';

if(isset($_POST['delete'])){
   $wishlist_id = $_POST['wishlist_id'];
   $delete_wishlist_item = $conn->prepare("DELETE FROM `wishlist` WHERE id = ?");
   $delete_wishlist_item->execute([$wishlist_id]);
}

if(isset($_GET['delete_all'])){
   $delete_wishlist_item = $conn->prepare("DELETE FROM `wishlist` WHERE user_id = ?");
   $delete_wishlist_item->execute([$user_id]);
   header('location:wishlist.php');
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Keens | Wishlist</title>
  <link rel="icon" type="image/x-icon" href="favicon.png">
   
   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

</head>
<body>
   
<?php include 'components/header.php'; ?>

<section class="products wishlist py-5" style="background: linear-gradient(135deg, #f9f9f9 0%, #f1f2f6 100%);">
   <div class="container">
      <br>
   <div class="text-center mb-5">
      <span class="badge rounded-pill mb-2" style="background-color: rgba(13,110,253,0.1); color: #0d6efd; font-size: 0.9rem;">
        Keens / Wishlist
      </span>
      <h2 class="fw-bold mb-4" style="color: #2b3452;">Wishlist</h2>
    </div>

      <div class="card border-0 rounded-4 overflow-hidden" style="box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
   <div class="table-responsive">
      <table class="table table-hover mb-0" style="min-width: 1000px;">
         <thead style="background-color: #f8f9fa;">
            <tr>
               <th class="px-4 py-3" style="color: #6c757d; font-weight: 600; font-size: 0.9rem;">Product</th>
               <th class="px-4 py-3" style="color: #6c757d; font-weight: 600; font-size: 0.9rem;">Image</th>
               <th class="px-4 py-3" style="color: #6c757d; font-weight: 600; font-size: 0.9rem;">Price</th>
               <th class="px-4 py-3" style="color: #6c757d; font-weight: 600; font-size: 0.9rem;">Actions</th>
            </tr>
         </thead>
         <tbody>
            <?php
            $grand_total = 0;
            $select_wishlist = $conn->prepare("SELECT * FROM `wishlist` WHERE user_id = ?");
            $select_wishlist->execute([$user_id]);
            if ($select_wishlist->rowCount() > 0) {
               while ($fetch_wishlist = $select_wishlist->fetch(PDO::FETCH_ASSOC)) {
                  $grand_total += $fetch_wishlist['price'];
            ?>
            <tr>
               <td class="px-4 py-3">
                  <a href="view.php?pid=<?= $fetch_wishlist['pid']; ?>" class="fw-medium" style="color: #2b3452;">
                     <?= htmlspecialchars($fetch_wishlist['name']); ?>
                  </a>
               </td>
               <td class="px-4 py-3">
                  <img src="uploaded_img/<?= htmlspecialchars($fetch_wishlist['image']); ?>" alt="<?= htmlspecialchars($fetch_wishlist['name']); ?>" class="img-fluid" style="max-width: 80px; border-radius: 5px;">
               </td>
               <td class="px-4 py-3">
                  <span class="fw-medium" style="color: #2b3452;">$<?= htmlspecialchars($fetch_wishlist['price']); ?></span>
               </td>
               <td class="px-4 py-3">
                  <form action="" method="post" class="d-flex gap-2">
                     <input type="hidden" name="pid" value="<?= $fetch_wishlist['pid']; ?>">
                     <input type="hidden" name="wishlist_id" value="<?= $fetch_wishlist['id']; ?>">
                     <input type="hidden" name="name" value="<?= htmlspecialchars($fetch_wishlist['name']); ?>">
                     <input type="hidden" name="price" value="<?= htmlspecialchars($fetch_wishlist['price']); ?>">
                     <input type="hidden" name="image" value="<?= htmlspecialchars($fetch_wishlist['image']); ?>">
                     <button type="submit" name="add_to_cart" class="btn btn-outline-primary px-3 py-1 ml-2" style="border-radius: 20px;">Add to Cart</button>
                           <button type="submit" name="delete" class="btn btn-outline-danger px-3 py-1" style="border-radius: 20px;" onclick="return confirm('Delete this item?');">Delete</button>
                  </form>
               </td>
            </tr>
            <?php
               }
            } else {
               echo '<tr><td colspan="5" class="text-center text-muted px-4 py-3">Your wishlist is empty.</td></tr>';
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
         <!-- <h4 class="text-primary fw-bold">Total: <?= $grand_total; ?>$</h4> -->
         <div class="d-flex justify-content-center gap-3 mt-3">
            <a href="shop.php" class="btn btn-outline-primary px-4 py-2" style="border-radius: 20px;">Continue Shopping</a>
            <a href="wishlist.php?delete_all" class="btn btn-outline-danger px-4 py-2 <?= ($grand_total > 1) ? '' : 'disabled'; ?>" onclick="return confirm('Delete all items?');" style="border-radius: 20px;">Delete All</a>
         </div>
      </div>
   </div>
</section>



<?php include 'components/footer.php'; ?>

<script src="js/script.js"></script>
</body>
</html>