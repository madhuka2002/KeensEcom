<?php

include 'components/connect.php';

session_start();

if(isset($_SESSION['user_id'])){
   $user_id = $_SESSION['user_id'];
}else{
   $user_id = '';
};

if(isset($_POST['submit'])){

   $name = $_POST['name'];
   $name = filter_var($name, FILTER_SANITIZE_STRING);
   $email = $_POST['email'];
   $email = filter_var($email, FILTER_SANITIZE_STRING);
   $pass = sha1($_POST['pass']);
   $pass = filter_var($pass, FILTER_SANITIZE_STRING);
   $cpass = sha1($_POST['cpass']);
   $cpass = filter_var($cpass, FILTER_SANITIZE_STRING);

   $select_user = $conn->prepare("SELECT * FROM `users` WHERE email = ?");
   $select_user->execute([$email,]);
   $row = $select_user->fetch(PDO::FETCH_ASSOC);

   if($select_user->rowCount() > 0){
      $message[] = ['type' => 'error', 'text' => 'Email already exists!'];
   }else{
      if($pass != $cpass){
         $message[] = ['type' => 'error', 'text' => 'Confirm password not matched!'];
      }else{
         $insert_user = $conn->prepare("INSERT INTO `users`(name, email, password) VALUES(?,?,?)");
         $insert_user->execute([$name, $email, $cpass]);
         $message[] = ['type' => 'success', 'text' => 'Registered successfully, login now please!'];
      }
   }

}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Keens | Register</title>
   <link rel="icon" type="image/x-icon" href="favicon.png">
   
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        /* Message popup styling */
        .message-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            width: 350px;
        }
        
        .message {
            background-color: white;
            padding: 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            animation: slideIn 0.3s ease forwards;
            margin-bottom: 10px;
            border-left: 4px solid #4361ee;
        }
        
        .message-content {
            display: flex;
            align-items: center;
        }
        
        .message span {
            font-size: 0.9rem;
            color: #495057;
        }
        
        .message i.close-btn {
            cursor: pointer;
            color: #6c757d;
            transition: color 0.3s ease;
            font-size: 1rem;
        }
        
        .message i.close-btn:hover {
            color: #dc3545;
        }
        
        @keyframes slideIn {
            0% {
                transform: translateX(100%);
                opacity: 0;
            }
            100% {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes fadeOut {
            0% {
                transform: translateX(0);
                opacity: 1;
            }
            100% {
                transform: translateX(100%);
                opacity: 0;
            }
        }
        
        .message.fade-out {
            animation: fadeOut 0.3s ease forwards;
        }
    </style>
</head>
<body>
   
<?php include 'components/header.php'; ?>

<!-- Message Container -->
<div class="message-container">
    <?php
    if(isset($message)){
       foreach($message as $msg){
          $type = isset($msg['type']) ? $msg['type'] : 'info';
          $text = isset($msg['text']) ? $msg['text'] : $msg;
          $color = '';
          
          switch($type) {
              case 'success': 
                  $color = '#2ecc71'; // Success color
                  $icon = 'fa-check-circle';
                  break;
              case 'error': 
                  $color = '#dc3545'; // Error color
                  $icon = 'fa-exclamation-circle';
                  break;
              case 'warning': 
                  $color = '#f39c12'; // Warning color
                  $icon = 'fa-exclamation-triangle';
                  break;
              default: 
                  $color = '#4361ee'; // Info color
                  $icon = 'fa-info-circle';
          }
          
          echo '
          <div class="message" style="border-left-color: '.$color.'">
             <div class="message-content">
                <i class="fas '.$icon.'" style="color: '.$color.'; margin-right: 10px;"></i>
                <span>'.$text.'</span>
             </div>
             <i class="fas fa-times close-btn" onclick="closeMessage(this)"></i>
          </div>
          ';
       }
    }
    ?>
</div>

<section class="registration-section min-vh-100 d-flex justify-content-center align-items-center py-4" 
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
                                 style="background: linear-gradient(135deg, rgba(13,110,253,0.1) 0%, rgba(13,110,253,0.05) 100%);">
                                <i class="fas fa-user-plus text-primary fs-4"></i>
                            </div>
                            <h3 class="fw-bold mb-1" style="color: #2b3452;">Create Account</h3>
                            <p class="text-muted small">Join our community today</p>
                        </div>

                        <!-- Form -->
                        <form action="" method="post">
                            <!-- Username -->
                            <div class="mb-3">
                                <div class="form-floating">
                                    <input type="text" 
                                           name="name" 
                                           class="form-control bg-light border-0" 
                                           id="name" 
                                           placeholder="Username"
                                           required 
                                           maxlength="20"
                                           style="border-radius: 12px;">
                                    <label class="text-muted">Username</label>
                                </div>
                            </div>

                            <!-- Email -->
                            <div class="mb-3">
                                <div class="form-floating">
                                    <input type="email" 
                                           name="email" 
                                           class="form-control bg-light border-0" 
                                           id="email" 
                                           placeholder="Email"
                                           required 
                                           maxlength="50"
                                           style="border-radius: 12px;"
                                           oninput="this.value = this.value.replace(/\s/g, '')">
                                    <label class="text-muted">Email Address</label>
                                </div>
                            </div>

                            <!-- Password -->
                            <div class="mb-3">
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

                            <!-- Confirm Password -->
                            <div class="mb-4">
                                <div class="form-floating">
                                    <input type="password" 
                                           name="cpass" 
                                           class="form-control bg-light border-0" 
                                           id="cpass" 
                                           placeholder="Confirm Password"
                                           required 
                                           maxlength="20"
                                           style="border-radius: 12px;"
                                           oninput="this.value = this.value.replace(/\s/g, '')">
                                    <label class="text-muted">Confirm Password</label>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="d-grid gap-2 mb-3">
                                <button type="submit" 
                                        name="submit" 
                                        class="btn btn-primary py-3 rounded-3 fw-semibold"
                                        style="background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);">
                                    Create Account
                                </button>
                            </div>

                            <!-- Login Link -->
                            <div class="text-center">
                                <p class="text-muted small mb-0">Already have an account?</p>
                                <a href="login.php" 
                                   class="text-primary fw-semibold"
                                   style="text-decoration: none;">
                                    Sign in here
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'components/footer.php'; ?>

<script>
    // Function to close individual messages
    function closeMessage(element) {
        const messageDiv = element.parentElement;
        messageDiv.classList.add('fade-out');
        setTimeout(() => {
            messageDiv.remove();
        }, 300);
    }
    
    // Auto-close messages after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
        const messages = document.querySelectorAll('.message');
        if (messages.length > 0) {
            messages.forEach(message => {
                setTimeout(() => {
                    message.classList.add('fade-out');
                    setTimeout(() => {
                        message.remove();
                    }, 300);
                }, 5000);
            });
        }
    });
</script>

<script src="js/script.js"></script>

</body>
</html>