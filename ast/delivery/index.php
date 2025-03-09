<?php

include '../components/connect.php';

session_start();

if(isset($_POST['submit'])){

   $phone = $_POST['phone'];
   $phone = filter_var($phone, FILTER_SANITIZE_STRING);
   $pass = sha1($_POST['pass']);
   $pass = filter_var($pass, FILTER_SANITIZE_STRING);

   $select_agent = $conn->prepare("SELECT * FROM `delivery_agents` WHERE phone = ? AND password = ?");
   $select_agent->execute([$phone, $pass]);
   $row = $select_agent->fetch(PDO::FETCH_ASSOC);

   if($select_agent->rowCount() > 0){
      $_SESSION['delivery_agent_id'] = $row['id'];
      header('location:dashboard.php');
   }else{
      $message[] = 'incorrect phone number or password!';
   }

}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Keens | Delivery Agent Login</title>

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
</head>
<body>

<?php
   if(isset($message)){
      foreach($message as $message){
         echo '
         <div class="message">
            <span>'.$message.'</span>
            <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
         </div>
         ';
      }
   }
?>

<section class="login-section min-vh-100 d-flex justify-content-center align-items-center py-4" 
         style="background-color: #ffffff;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6 col-xl-5">
                <!-- Card with Glassmorphism -->
                <div class="card border-0 rounded-4 overflow-hidden" 
                     style="background: rgba(255, 255, 255, 0.95);
                            backdrop-filter: blur(10px);
                            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);">
                    
                    <div class="card-body p-4 p-md-5">
                        <!-- Header -->
                        <div class="text-center mb-4">
                            <div class="d-inline-block p-3 rounded-circle mb-3" 
                                 style="background: linear-gradient(135deg, rgba(67, 97, 238, 0.1) 0%, rgba(67, 97, 238, 0.05) 100%);">
                                <i class="fas fa-truck text-primary fs-4"></i>
                            </div>
                            <h3 class="fw-bold mb-1" style="color: #2b3452;">Delivery Agent Login</h3>
                            <p class="text-muted small">Enter your phone number and password to login</p>
                        </div>

                        <!-- Form -->
                        <form action="" method="post">
                            <!-- Phone Number -->
                            <div class="mb-3">
                                <div class="form-floating">
                                    <input type="tel" 
                                           name="phone" 
                                           class="form-control bg-light border-0" 
                                           id="phone" 
                                           placeholder="Phone Number"
                                           required 
                                           pattern="[0-9]{10}"
                                           maxlength="15"
                                           style="border-radius: 12px;"
                                           oninput="this.value = this.value.replace(/\D/g, '')">
                                    <label class="text-muted">Phone Number</label>
                                </div>
                            </div>

                            <!-- Password -->
                            <div class="mb-4">
                                <div class="form-floating">
                                    <input type="password" 
                                           name="pass" 
                                           class="form-control bg-light border-0" 
                                           id="pass" 
                                           placeholder="Password"
                                           required 
                                           maxlength="20"
                                           style="border-radius: 12px;"
                                           oninput="this.value = this.value.replace(/\s/g, '')">
                                    <label class="text-muted">Password</label>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="d-grid gap-2">
                                <button type="submit" 
                                        name="submit" 
                                        class="btn btn-primary py-3 rounded-3 fw-semibold"
                                        style="background: linear-gradient(135deg, #4361ee 0%, #3f37c9 100%);">
                                    Login
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
