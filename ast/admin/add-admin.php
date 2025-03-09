<?php
include '../components/connect.php';

session_start();

$admin_id = $_SESSION['admin_id'];

if(!isset($admin_id)){
   header('location:index.php');
}

// Initialize $message as an empty array
$message = [];

if(isset($_POST['submit'])){
   $name = $_POST['name'];
   $name = filter_var($name, FILTER_SANITIZE_STRING);
   $pass = sha1($_POST['pass']);
   $pass = filter_var($pass, FILTER_SANITIZE_STRING);
   $cpass = sha1($_POST['cpass']);
   $cpass = filter_var($cpass, FILTER_SANITIZE_STRING);

   $select_admin = $conn->prepare("SELECT * FROM `admins` WHERE name = ?");
   $select_admin->execute([$name]);

   if($select_admin->rowCount() > 0){
      $message[] = 'Username already exists!';
   } else {
      if($pass != $cpass){
         $message[] = 'Confirm password does not match!';
      } else {
         $insert_admin = $conn->prepare("INSERT INTO `admins`(name, password) VALUES(?, ?)");
         $insert_admin->execute([$name, $cpass]);
         $message[] = 'New admin registered successfully!';
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
   <title>Keens | Add Admin</title>

   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <!-- Bootstrap CSS -->
   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<section class="add-admin mt-5">
    <div class="content-card">
        <div class="card-header">
            <div class="header-icon">
                <i class="fas fa-user-plus"></i>
            </div>
            <h2 class="header-title">Add New Admin</h2>
            <p class="text-muted">Create a new administrator account</p>
        </div>

        <?php
        if(!empty($message)){
            foreach($message as $msg){
                $alertClass = strpos($msg, 'successfully') !== false ? 'alert-success' : 'alert-danger';
                echo '<div class="alert ' . $alertClass . ' text-center mb-4">' . htmlspecialchars($msg) . '</div>';
            }
        }
        ?>

        <form action="" method="post" class="needs-validation" novalidate>
            <!-- Username Input -->
            <div class="mb-4">
                <div class="form-floating">
                    <input type="text" 
                           id="name" 
                           name="name" 
                           class="form-control custom-input" 
                           placeholder="Admin username"
                           required 
                           maxlength="20"
                           oninput="this.value = this.value.replace(/\s/g, '')">
                    <label for="name">Admin Username</label>
                    <div class="invalid-feedback">
                        Please enter a username (max 20 characters)
                    </div>
                </div>
            </div>

            <!-- Password Input -->
            <div class="mb-4">
                <div class="form-floating">
                    <input type="password" 
                           id="pass" 
                           name="pass" 
                           class="form-control custom-input" 
                           placeholder="Admin password"
                           required 
                           maxlength="20"
                           oninput="this.value = this.value.replace(/\s/g, '')">
                    <label for="pass">Admin Password</label>
                    <div class="invalid-feedback">
                        Please enter a password (max 20 characters)
                    </div>
                </div>
            </div>

            <!-- Confirm Password Input -->
            <div class="mb-4">
                <div class="form-floating">
                    <input type="password" 
                           id="cpass" 
                           name="cpass" 
                           class="form-control custom-input" 
                           placeholder="Confirm password"
                           required 
                           maxlength="20"
                           oninput="this.value = this.value.replace(/\s/g, '')">
                    <label for="cpass">Confirm Password</label>
                    <div class="invalid-feedback">
                        Please confirm your password
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <button type="submit" 
                    name="submit" 
                    class="btn custom-button w-100">
                <i class="fas fa-user-plus me-2"></i>Register New Admin
            </button>
        </form>
    </div>
</section>

<style>
:root {
    --primary-color: #4361ee;
    --secondary-color: #3f37c9;
    --accent-color: #4895ef;
    --success-color: #2ecc71;
    --danger-color: #e74c3c;
    --warning-color: #f39c12;
}

/* Card Styles */
.content-card {
    background: white;
    border-radius: 20px;
    padding: 2rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
    margin: 2rem auto;
    max-width: 500px;
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
.custom-input {
    border: 2px solid #e9ecef;
    border-radius: 12px;
    padding: 1rem;
    transition: all 0.3s ease;
}

.custom-input:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 4px rgba(67, 97, 238, 0.1);
}

.custom-button {
    background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
    color: white;
    border: none;
    padding: 1rem;
    border-radius: 12px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.custom-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(67, 97, 238, 0.2);
}

/* Alert Styles */
.alert {
    border-radius: 12px;
    padding: 1rem;
    font-weight: 500;
}

.alert-success {
    background-color: rgba(46, 204, 113, 0.1);
    color: #2ecc71;
    border: 1px solid rgba(46, 204, 113, 0.2);
}

.alert-danger {
    background-color: rgba(231, 76, 60, 0.1);
    color: #e74c3c;
    border: 1px solid rgba(231, 76, 60, 0.2);
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .content-card {
        padding: 1rem;
        margin: 1rem;
        width: 95%;
    }
}
</style>

<script>
// Bootstrap 5 form validation
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms)
        .forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                form.classList.add('was-validated')
            }, false)
        })
})()
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script src="../js/admin_script.js"></script>

</body>
</html>