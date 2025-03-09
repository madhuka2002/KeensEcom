<?php
include '../components/connect.php';
session_start();

if(!isset($_SESSION['csr_id'])) {
  header('location:index.php');
  exit();
}

$csr_id = $_SESSION['csr_id'];

if(isset($_POST['update_availability'])) {
   $is_available = $_POST['is_available'] ?? 0;
   
   $update = $conn->prepare("
       UPDATE customer_sales_representatives 
       SET is_available = ? 
       WHERE csr_id = ?
   ");
   $update->execute([$is_available, $csr_id]);
   
   $message[] = 'Availability status updated successfully!';
}

$csr_query = $conn->prepare("SELECT * FROM customer_sales_representatives WHERE csr_id = ?");
$csr_query->execute([$csr_id]);
$csr = $csr_query->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Keens | Set Availability</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
</head>
<body>

<?php include '../components/csr_header.php'; ?>

<section class="availability-section mt-5">
   <div class="container">
       <div class="content-card">
           <div class="text-center mb-4">
               <div class="availability-icon">
                   <i class="fas fa-clock"></i>
               </div>
               <h2>Set Availability</h2>
               <p class="text-muted">Update your current availability status</p>
           </div>

           <div class="status-toggle">
               <form action="" method="POST">
                   <div class="current-status mb-4">
                       <div class="status-badge <?= $csr['is_available'] ? 'available' : 'away' ?>">
                           <i class="fas fa-<?= $csr['is_available'] ? 'check-circle' : 'times-circle' ?>"></i>
                           Currently <?= $csr['is_available'] ? 'Available' : 'Away' ?>
                       </div>
                   </div>

                   <div class="form-check form-switch d-flex justify-content-center align-items-center gap-3 mb-4">
                       <input class="form-check-input" 
                              type="checkbox" 
                              name="is_available" 
                              value="1" 
                              id="availabilitySwitch"
                              <?= $csr['is_available'] ? 'checked' : '' ?>>
                       <label class="form-check-label" for="availabilitySwitch">
                           Toggle Availability
                       </label>
                   </div>

                   <button type="submit" name="update_availability" class="btn btn-primary w-100">
                       Update Status
                   </button>
               </form>
           </div>

           <div class="availability-info mt-4">
               <div class="info-card">
                   <i class="fas fa-info-circle text-primary"></i>
                   <p>When you're available, you can receive new customer inquiries and support tickets.</p>
               </div>
           </div>
       </div>
   </div>
</section>

<style>
:root {
   --primary-color: #4361ee;
   --success-color: #2ecc71;
   --warning-color: #f39c12;
}

.content-card {
   background: white;
   border-radius: 20px;
   padding: 2rem;
   box-shadow: 0 10px 30px rgba(0,0,0,0.05);
   max-width: 500px;
   margin: 0 auto;
}

.availability-icon {
   width: 80px;
   height: 80px;
   border-radius: 50%;
   background: linear-gradient(135deg, rgba(67, 97, 238, 0.1), rgba(72, 149, 239, 0.1));
   color: var(--primary-color);
   display: flex;
   align-items: center;
   justify-content: center;
   font-size: 2rem;
   margin: 0 auto 1rem;
}

.status-badge {
   display: inline-flex;
   align-items: center;
   gap: 0.5rem;
   padding: 0.75rem 1.5rem;
   border-radius: 30px;
   font-weight: 500;
}

.status-badge.available {
   background: rgba(46, 204, 113, 0.1);
   color: var(--success-color);
}

.status-badge.away {
   background: rgba(243, 156, 18, 0.1);
   color: var(--warning-color);
}

.form-check-input {
   width: 3rem;
   height: 1.5rem;
}

.info-card {
   background: #f8f9fa;
   border-radius: 10px;
   padding: 1rem;
   display: flex;
   align-items: center;
   gap: 1rem;
}

.info-card p {
   margin: 0;
   font-size: 0.9rem;
   color: #6c757d;
}

@media (max-width: 768px) {
   .content-card {
       margin: 1rem;
       padding: 1rem;
   }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>