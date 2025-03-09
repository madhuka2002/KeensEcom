<?php
include '../components/connect.php';

session_start();

$admin_id = $_SESSION['admin_id'];

if(!isset($admin_id)){
   header('location:index.php');
   exit();
}

if(isset($_POST['submit'])){
   $name = $_POST['name'];
   $name = filter_var($name, FILTER_SANITIZE_STRING);
   $certification = $_POST['certification'];
   $certification = filter_var($certification, FILTER_SANITIZE_STRING);
   $phone = $_POST['phone'];
   $phone = filter_var($phone, FILTER_SANITIZE_STRING);
   $password = sha1($_POST['password']);
   $password = filter_var($password, FILTER_SANITIZE_STRING);
   $confirm_password = sha1($_POST['confirm_password']);
   $confirm_password = filter_var($confirm_password, FILTER_SANITIZE_STRING);

   $select_auditor = $conn->prepare("SELECT * FROM `financial_auditors` WHERE name = ?");
   $select_auditor->execute([$name]);

   if($select_auditor->rowCount() > 0){
      $message[] = 'Financial Auditor already exists!';
   }else{
      if($password != $confirm_password){
         $message[] = 'Passwords do not match!';
      }else{
         $insert_auditor = $conn->prepare("INSERT INTO `financial_auditors`(name, certification, phone, password, admin_id) VALUES(?,?,?,?,?)");
         $insert_auditor->execute([$name, $certification, $phone, $password, $admin_id]);
         $message[] = 'New Financial Auditor added successfully!';
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
   <title>Keens | Add Financial Auditor</title>

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<?php
if(!empty($message)){
   if (!is_array($message)) {
      $message = [$message];
   }
   foreach($message as $msg){
      echo '
      <div class="alert alert-warning alert-dismissible fade show" role="alert">
         '.$msg.'
         <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
      ';
   }
}
?>

<section class="add-financial-auditor mt-5">
    <div class="content-card">
        <div class="card-header">
            <div class="header-icon">
                <i class="fas fa-calculator"></i>
            </div>
            <h2 class="header-title">Add Financial Auditor</h2>
            <p class="text-muted">Create a new financial auditor account</p>
        </div>

        <form action="" method="POST" class="form-container">
            <div class="form-group">
                <label for="name" class="form-label">Full Name</label>
                <input type="text" name="name" required class="form-control" maxlength="100" placeholder="Enter full name">
            </div>

            <div class="form-group">
                <label for="certification" class="form-label">Certification</label>
                <input type="text" name="certification" required class="form-control" maxlength="50" placeholder="Enter certification details">
            </div>

            <div class="form-group">
                <label for="phone" class="form-label">Phone Number</label>
                <input type="tel" name="phone" required class="form-control" maxlength="15" placeholder="Enter phone number">
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" name="password" required class="form-control" maxlength="20" placeholder="Enter password">
            </div>

            <div class="form-group">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <input type="password" name="confirm_password" required class="form-control" maxlength="20" placeholder="Confirm password">
            </div>

            <div class="form-actions">
                <button type="submit" name="submit" class="btn-submit">
                    <i class="fas fa-plus"></i> Add Financial Auditor
                </button>
                <a href="manage_financial_auditors.php" class="btn-cancel">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</section>

<style>
:root {
    --primary-color: #4361ee;
    --secondary-color: #3f37c9;
    --accent-color: #4895ef;
    --success-color: #2ecc71;
    --danger-color: #dc3545;
    --warning-color: #f39c12;
}

/* Alert Styles */
.alert {
    position: fixed;
    top: 20px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 1050;
    width: 90%;
    max-width: 500px;
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

.form-control {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    transition: all 0.2s;
}

.form-control:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
    outline: none;
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