<?php
include '../components/connect.php';

session_start();

$admin_id = $_SESSION['admin_id'];

if(!isset($admin_id)){
   header('location:index.php');
}

// Initialize message array
$message = [];

if(isset($_POST['submit'])){
   // Sanitize and validate inputs
   $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
   $expertise = filter_var($_POST['expertise'], FILTER_SANITIZE_STRING);
   
   // Hash passwords securely
   $password = sha1($_POST['password']); // Note: Consider using password_hash() in production
   $confirm_password = sha1($_POST['confirm_password']);

   // Validate input lengths
   if(strlen($name) > 20){
      $message[] = 'Name must be 20 characters or less!';
   }

   if(strlen($expertise) > 50){
      $message[] = 'Expertise must be 50 characters or less!';
   }

   // Check if marketing manager already exists
   $select_manager = $conn->prepare("SELECT * FROM `marketing_manager` WHERE name = ?");
   $select_manager->execute([$name]);

   if($select_manager->rowCount() > 0){
      $message[] = 'Marketing Manager already exists!';
   }else{
      // Validate password match
      if($password != $confirm_password){
         $message[] = 'Passwords do not match!';
      }else{
         // Validate password strength (optional but recommended)
         if(strlen($_POST['password']) < 8){
            $message[] = 'Password must be at least 8 characters long!';
         }else{
            // Insert new marketing manager
            try {
               $insert_manager = $conn->prepare("INSERT INTO `marketing_manager`(name, expertise, admin_id, password) VALUES(?,?,?,?)");
               $insert_manager->execute([$name, $expertise, $admin_id, $password]);
               $message[] = 'New Marketing Manager added successfully!';
            } catch(PDOException $e) {
               $message[] = 'Error adding Marketing Manager: ' . $e->getMessage();
            }
         }
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
   <title>Keens | Add Marketing Manager</title>

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<section class="add-marketing-manager mt-5">
    <div class="content-card">
        <div class="card-header">
            <div class="header-icon">
                <i class="fas fa-bullhorn"></i>
            </div>
            <h2 class="header-title">Add Marketing Manager</h2>
            <p class="text-muted">Create a new Marketing Manager account</p>
        </div>

        <?php
        // Display messages
        if(!empty($message)){
            foreach($message as $msg){
                $alertType = (
                    strpos($msg, 'successfully') !== false ? 'success' : 
                    (strpos($msg, 'Error') !== false ? 'warning' : 'danger')
                );
                echo '
                <div class="alert alert-'.$alertType.' alert-dismissible fade show" role="alert">
                    '.htmlspecialchars($msg).'
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                ';
            }
        }
        ?>

        <form action="" method="POST" class="form-container needs-validation" novalidate>
            <div class="form-group">
                <label for="name" class="form-label">Name</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" name="name" required class="form-control" 
                           maxlength="20" 
                           placeholder="Enter Marketing Manager name"
                           pattern="[A-Za-z\s]+"
                           title="Name can only contain letters and spaces">
                    <div class="invalid-feedback">
                        Please enter a valid name (letters and spaces only, max 20 characters).
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="expertise" class="form-label">Expertise</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-briefcase"></i></span>
                    <input type="text" name="expertise" required class="form-control" 
                           maxlength="50" 
                           placeholder="Enter marketing expertise"
                           pattern="[A-Za-z\s]+"
                           title="Expertise can only contain letters and spaces">
                    <div class="invalid-feedback">
                        Please enter a valid expertise (letters and spaces only, max 50 characters).
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" name="password" required class="form-control" 
                           minlength="8" 
                           maxlength="20" 
                           placeholder="Enter password"
                           pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}"
                           title="Must contain at least one number, one uppercase and lowercase letter, and be 8-20 characters long">
                    <div class="invalid-feedback">
                        Password must be 8-20 characters long and contain at least one number, one uppercase and one lowercase letter.
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" name="confirm_password" required class="form-control" 
                           minlength="8" 
                           maxlength="20" 
                           placeholder="Confirm password">
                    <div class="invalid-feedback">
                        Passwords must match.
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" name="submit" class="btn-submit">
                    <i class="fas fa-plus"></i> Add Marketing Manager
                </button>
                <a href="manage_marketing_managers.php" class="btn-cancel">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</section>

<script>
// Bootstrap form validation script
(function() {
  'use strict';
  window.addEventListener('load', function() {
    // Fetch all the forms we want to apply custom Bootstrap validation styles to
    var forms = document.getElementsByClassName('needs-validation');
    
    // Loop over them and prevent submission
    var validation = Array.prototype.filter.call(forms, function(form) {
      form.addEventListener('submit', function(event) {
        if (form.checkValidity() === false) {
          event.preventDefault();
          event.stopPropagation();
        }
        form.classList.add('was-validated');
      }, false);
    });
  }, false);
})();

// Custom password match validation
document.addEventListener('DOMContentLoaded', function() {
    const password = document.querySelector('input[name="password"]');
    const confirmPassword = document.querySelector('input[name="confirm_password"]');
    
    function validatePassword() {
        if (password.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity('Passwords do not match');
        } else {
            confirmPassword.setCustomValidity('');
        }
    }
    
    password.addEventListener('change', validatePassword);
    confirmPassword.addEventListener('keyup', validatePassword);
});
</script>

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
    max-width: 800px;
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

/* Form Styles */
.form-container {
    padding: 1rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    font-weight: 500;
    color: #2b3452;
    margin-bottom: 0.5rem;
    display: block;
}

.input-group-text {
    background: #f8f9fa;
    border-right: none;
    color: #6c757d;
}

.form-control {
    border-left: none;
    box-shadow: none;
}

.form-control:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
    outline: none;
}

/* Validation Styles */
.was-validated .form-control:invalid,
.form-control.is-invalid {
    border-color: var(--danger-color);
    padding-right: calc(1.5em + 0.75rem);
    background-image: none;
}

.invalid-feedback {
    display: none;
    width: 100%;
    margin-top: 0.25rem;
    font-size: 80%;
    color: var(--danger-color);
}

.was-validated .form-control:invalid ~ .invalid-feedback,
.form-control.is-invalid ~ .invalid-feedback {
    display: block;
}

/* Button Styles */
.form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
}

.btn-submit {
    padding: 0.75rem 1.5rem;
    background: var(--primary-color);
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
}

.btn-submit:hover {
    background: var(--secondary-color);
}

.btn-cancel {
    padding: 0.75rem 1.5rem;
    background: #f8f9fa;
    color: #6c757d;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
}

.btn-cancel:hover {
    background: #e9ecef;
}

/* Alert Styles */
.alert {
    margin: 1rem 0;
    padding: 0.75rem 1.25rem;
    border-radius: 8px;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border-color: #c3e6cb;
}

.alert-danger {
    background-color: #f8d7da;
    color: #721c24;
    border-color: #f5c6cb;
}

.alert-warning {
    background-color: #fff3cd;
    color: #856404;
    border-color: #ffeeba;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .content-card {
        margin: 1rem;
        padding: 1rem;
    }

    .form-actions {
        flex-direction: column;
    }

    .btn-submit, .btn-cancel {
        width: 100%;
        justify-content: center;
    }
}
</style>

</body>
</html>