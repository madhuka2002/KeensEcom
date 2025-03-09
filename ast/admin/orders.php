<?php
include '../components/connect.php';

session_start();

$admin_id = $_SESSION['admin_id'];

if(!isset($admin_id)){
   header('location:index.php');
}

// Handle payment status update
if(isset($_POST['update_payment'])){
   $order_id = $_POST['order_id'];
   $payment_status = $_POST['payment_status'];
   $payment_status = filter_var($payment_status, FILTER_SANITIZE_STRING);
   $update_payment = $conn->prepare("UPDATE `orders` SET payment_status = ? WHERE id = ?");
   $update_payment->execute([$payment_status, $order_id]);
   
   // Add success message
   $message[] = 'Payment status updated!';
}

// Handle order deletion
if(isset($_GET['delete'])){
   $delete_id = $_GET['delete'];
   $delete_order = $conn->prepare("DELETE FROM `orders` WHERE id = ?");
   $delete_order->execute([$delete_id]);
   header('location:placed_orders.php');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Keens | Orders</title>
   
   <!-- Bootstrap CSS -->
   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <!-- Font Awesome -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
</head>
<body>

<?php include '../components/admin_header_orders.php'; ?>

<!-- Orders Management Section -->
<section class="show-orders mt-5">
    <div class="content-card">
        <div class="card-header">
            <div class="header-icon">
                <i class="fas fa-shopping-bag"></i>
            </div>
            <h2 class="header-title">Placed Orders</h2>
            <p class="text-muted">View and manage customer orders</p>
        </div>

        <!-- Display success message if exists -->

        <div class="table-container">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>User Info</th>
                        <th>Order Details</th>
                        <th>Payment</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                    // Fetch orders with latest first
                    $select_orders = $conn->prepare("SELECT * FROM `orders` ORDER BY placed_on DESC");
                    $select_orders->execute();
                    if($select_orders->rowCount() > 0){
                        while($fetch_orders = $select_orders->fetch(PDO::FETCH_ASSOC)){   
                ?>
                    <tr>
                        <td>
                            <span class="id-badge">#<?= $fetch_orders['id']; ?></span>
                        </td>
                        <td>
                            <div class="user-details">
                                <span class="user-name"><?= $fetch_orders['name']; ?></span>
                                <span class="user-contact">
                                    <i class="fas fa-phone"></i> <?= $fetch_orders['number']; ?>
                                </span>
                                <span class="user-email">
                                    <i class="fas fa-envelope"></i> <?= $fetch_orders['email']; ?>
                                </span>
                            </div>
                        </td>
                        <td>
                            <div class="order-details">
                                <div class="order-address">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?= $fetch_orders['address']; ?>
                                </div>
                                <div class="order-products">
                                    <span class="total-products">
                                        <i class="fas fa-box"></i> Total Products: <?= $fetch_orders['total_products']; ?>
                                    </span>
                                    <span class="order-price">
                                        <i class="fas fa-dollar-sign"></i> Total Price: $<?= $fetch_orders['total_price']; ?>
                                    </span>
                                </div>
                                <div class="order-date">
                                    <i class="fas fa-calendar"></i> 
                                    <?= date('d M Y', strtotime($fetch_orders['placed_on'])); ?>
                                </div>
                            </div>
                        </td>
                        <td>
                        <form action="" method="post" class="payment-form">
                <input type="hidden" name="order_id" value="<?= $fetch_orders['id']; ?>">
                <div class="d-flex gap-2">
                    <select name="payment_status" class="payment-status <?= $fetch_orders['payment_status'] == 'completed' ? 'status-completed' : 'status-pending' ?>">
                        <option value="pending" <?= $fetch_orders['payment_status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="completed" <?= $fetch_orders['payment_status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                    </select>
                    <button type="submit" name="update_payment" class="btn-update">
                        <i class="fas fa-edit"></i> Update
                    </button>
                </div>
            </form>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="placed_orders.php?delete=<?= $fetch_orders['id']; ?>" 
                                   onclick="return confirm('Are you sure you want to delete this order? This action cannot be undone.');" 
                                   class="btn-delete">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php
                        }
                    } else {
                ?>
                    <tr>
                        <td colspan="5" class="empty-state">
                            <i class="fas fa-shopping-cart"></i>
                            <p>No orders placed yet!</p>
                        </td>
                    </tr>
                <?php
                    }
                ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- Your existing style and script tags here -->
<style>
   :root {
      --primary-color: #4361ee;
      --secondary-color: #3f37c9;
      --accent-color: #4895ef;
      --success-color: #2ecc71;
      --danger-color: #dc3545;
      --warning-color: #f39c12;
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

   .header-title {
      font-size: 1.5rem;
      font-weight: 700;
      color: #2b3452;
      margin-bottom: 0.5rem;
   }

   /* Table Styles */
   .table-container {
      overflow-x: auto;
      border-radius: 8px;
      background: white;
   }

   /* Payment Form */
.payment-form {
    display: flex;
    gap: 8px;
    align-items: center;
}

/* Update Button */
.btn-update {
    padding: 8px 16px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #fff3e0;
    color: #f39c12;
    border: 1px solid #ffe0b2;
    transition: all 0.2s;
    cursor: pointer;
}

.btn-update:hover {
    filter: brightness(0.95);
}

/* Payment Status Select */
.payment-status {
    padding: 8px 12px;
    border-radius: 6px;
    border: 1px solid #e9ecef;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    min-width: 120px;
}

.payment-status:focus {
    outline: none;
    box-shadow: 0 0 0 2px rgba(67, 97, 238, 0.1);
}

   .modern-table {
      width: 100%;
      border-collapse: collapse;
      margin: 0;
      padding: 0;
      background-color: white;
   }

   .modern-table thead tr {
      background-color: #f8f9fa;
      border-bottom: 2px solid #e9ecef;
   }

   .modern-table th {
      padding: 16px;
      text-align: left;
      font-weight: 600;
      color: #495057;
      text-transform: uppercase;
      font-size: 13px;
      letter-spacing: 0.5px;
   }

   .modern-table td {
      padding: 16px;
      vertical-align: middle;
      border-bottom: 1px solid #e9ecef;
   }

   .modern-table tbody tr:hover {
      background-color: #f8f9fa;
   }

   /* ID Badge */
   .id-badge {
      background: #e8f3ff;
      color: #0d6efd;
      padding: 6px 12px;
      border-radius: 6px;
      font-weight: 500;
      font-size: 14px;
   }

   /* User Details */
   .user-details {
      display: flex;
      flex-direction: column;
      gap: 4px;
   }

   .user-name {
      font-weight: 500;
      color: #2b3445;
   }

   .user-contact, .user-email {
      font-size: 14px;
      color: #6c757d;
   }

   .user-contact i, .user-email i {
      width: 16px;
      margin-right: 4px;
   }

   /* Order Details */
   .order-details {
      display: flex;
      flex-direction: column;
      gap: 8px;
   }

   .order-address {
      font-size: 14px;
      color: #495057;
   }

   .order-products {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
   }

   .total-products, .order-price {
      font-size: 14px;
      background: #f8f9fa;
      padding: 4px 8px;
      border-radius: 4px;
      color: #495057;
   }

   .order-date {
      font-size: 13px;
      color: #6c757d;
   }

   /* Payment Status Select */
   .payment-status {
      padding: 8px 12px;
      border-radius: 6px;
      border: 2px solid #e9ecef;
      background: white;
      font-size: 14px;
      color: #495057;
      cursor: pointer;
      transition: all 0.2s;
   }

   .payment-status:focus {
      border-color: var(--primary-color);
      outline: none;
   }

   /* Action Buttons */
   .action-buttons {
      display: flex;
      gap: 8px;
   }

   .btn-delete {
      padding: 8px 16px;
      border-radius: 6px;
      text-decoration: none;
      font-size: 14px;
      font-weight: 500;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: #ffeeee;
      color: #dc3545;
      border: 1px solid #ffd5d5;
      transition: all 0.2s;
   }

   .btn-delete:hover {
      filter: brightness(0.95);
      color: #dc3545;
   }

   /* Empty State */
   .empty-state {
      text-align: center;
      padding: 48px !important;
      color: #6c757d;
   }

   .empty-state i {
      font-size: 32px;
      margin-bottom: 16px;
      color: #dee2e6;
   }

   /* Responsive Adjustments */
   @media (max-width: 992px) {
      .content-card {
         padding: 1rem;
         margin: 1rem;
      }

      .modern-table {
         font-size: 14px;
      }

      .order-products {
         flex-direction: column;
         gap: 4px;
      }

      .user-details, .order-details {
         max-width: 200px;
      }
   }

   @media (max-width: 768px) {
      .modern-table th {
         white-space: nowrap;
      }

      .order-address, .user-email {
         max-width: 150px;
         overflow: hidden;
         text-overflow: ellipsis;
         white-space: nowrap;
      }
   }
</style>

</body>
</html>