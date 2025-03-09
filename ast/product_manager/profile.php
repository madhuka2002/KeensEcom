<?php
include '../components/connect.php';

session_start();

$manager_id = $_SESSION['manager_id'];

if(!isset($manager_id)){
   header('location:index.php');
}

// Handle Profile Update
if(isset($_POST['update_profile'])) {
    $name = $_POST['name'];
    $name = filter_var($name, FILTER_SANITIZE_STRING);
    $expertise = $_POST['expertise'];
    $expertise = filter_var($expertise, FILTER_SANITIZE_STRING);
    
    $update_profile = $conn->prepare("UPDATE `product_manager` SET name = ?, expertise = ? WHERE manager_id = ?");
    $update_profile->execute([$name, $expertise, $manager_id]);
    
    $message[] = 'Profile updated successfully!';
}

// Handle Password Update
if(isset($_POST['update_password'])) {
    $old_pass = sha1($_POST['old_pass']);
    $new_pass = sha1($_POST['new_pass']);
    $confirm_pass = sha1($_POST['confirm_pass']);
    
    $select_pass = $conn->prepare("SELECT password FROM `product_manager` WHERE manager_id = ?");
    $select_pass->execute([$manager_id]);
    $row = $select_pass->fetch(PDO::FETCH_ASSOC);
    
    if($old_pass != $row['password']){
        $message[] = 'Old password not matched!';
    }elseif($new_pass != $confirm_pass){
        $message[] = 'Confirm password not matched!';
    }else{
        $update_pass = $conn->prepare("UPDATE `product_manager` SET password = ? WHERE manager_id = ?");
        $update_pass->execute([$new_pass, $manager_id]);
        $message[] = 'Password updated successfully!';
    }
}

// Get Profile Data
$select_profile = $conn->prepare("SELECT * FROM `product_manager` WHERE manager_id = ?");
$select_profile->execute([$manager_id]);
$fetch_profile = $select_profile->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Keens | Profile Settings</title>

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<?php include '../components/product_manager_header.php'; ?>

<section class="profile-settings">
    <div class="container-fluid px-4 py-5">
        <div class="row justify-content-center">
            <!-- Profile Info -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">Profile Information</h4>
                        </div>

                        <form method="post" class="needs-validation" novalidate>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Name</label>
                                    <input type="text" name="name" required class="form-control" 
                                           value="<?= $fetch_profile['name']; ?>">
                                    <div class="invalid-feedback">Please enter your name.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Expertise</label>
                                    <input type="text" name="expertise" required class="form-control" 
                                           value="<?= $fetch_profile['expertise']; ?>">
                                    <div class="invalid-feedback">Please enter your expertise.</div>
                                </div>

                                <div class="col-12">
                                    <button type="submit" name="update_profile" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Update Profile
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Change Password -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">Change Password</h4>
                        </div>

                        <form method="post" class="needs-validation" novalidate>
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label">Current Password</label>
                                    <div class="input-group">
                                        <input type="password" name="old_pass" required class="form-control" 
                                               placeholder="Enter current password">
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword(this)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="invalid-feedback">Please enter your current password.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">New Password</label>
                                    <div class="input-group">
                                        <input type="password" name="new_pass" required class="form-control" 
                                               placeholder="Enter new password">
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword(this)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="invalid-feedback">Please enter a new password.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Confirm New Password</label>
                                    <div class="input-group">
                                        <input type="password" name="confirm_pass" required class="form-control" 
                                               placeholder="Confirm new password">
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword(this)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="invalid-feedback">Please confirm your new password.</div>
                                </div>

                                <div class="col-12">
                                    <button type="submit" name="update_password" class="btn btn-primary">
                                        <i class="fas fa-key me-2"></i>Change Password
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Account Stats -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <div class="avatar-placeholder mb-3">
                                <?= strtoupper(substr($fetch_profile['name'], 0, 1)); ?>
                            </div>
                            <h5 class="mb-1"><?= $fetch_profile['name']; ?></h5>
                            <p class="text-muted mb-0"><?= $fetch_profile['expertise']; ?></p>
                        </div>

                        <hr>

                        <?php
                            // Get managed products count
                            $product_count = $conn->prepare("SELECT COUNT(*) as count FROM `products`");
                            $product_count->execute();
                            $products = $product_count->fetch(PDO::FETCH_ASSOC)['count'];

                            // Get recent activity count
                            $activity_count = $conn->prepare("
                                SELECT COUNT(*) as count 
                                FROM (
                                    SELECT id FROM `products`
                                    UNION ALL
                                    SELECT alert_id FROM `stock_alert`
                                    LIMIT 30
                                ) as activity
                            ");
                            $activity_count->execute();
                            $activities = $activity_count->fetch(PDO::FETCH_ASSOC)['count'];
                        ?>

                        <div class="d-flex justify-content-between mb-2">
                            <span>Managed Products</span>
                            <span class="fw-bold"><?= $products; ?></span>
                        </div>

                        <div class="d-flex justify-content-between mb-2">
                            <span>Recent Activities</span>
                            <span class="fw-bold"><?= $activities; ?></span>
                        </div>

                        <div class="d-flex justify-content-between">
                            <span>Member Since</span>
                            <span class="fw-bold">
                                <?= date('M Y', strtotime($fetch_profile['created_at'])); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Account Actions -->
                <div class="card border-0 shadow-sm">
                    <div class="list-group list-group-flush rounded-3">
                        <a href="activity_log.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-history me-2"></i>Activity Log
                        </a>
                        <a href="notifications.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-bell me-2"></i>Notifications
                        </a>
                        <a href="../components/manager_logout.php" 
                           class="list-group-item list-group-item-action text-danger"
                           onclick="return confirm('Logout from the account?')">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
:root {
    --primary-color: #4361ee;
    --secondary-color: #3f37c9;
    --accent-color: #4895ef;
    --success-color: #2ecc71;
    --warning-color: #f39c12;
    --danger-color: #dc3545;
}

/* Card Styles */
.card {
    border-radius: 15px;
}

/* Form Controls */
.form-control {
    padding: 0.75rem 1rem;
    border-radius: 8px;
    border: 1px solid #e9ecef;
}

.form-control:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
}

/* Button Styles */
.btn-primary {
    background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
    border: none;
    padding: 0.75rem 1.5rem;
    font-weight: 500;
}

/* Avatar Placeholder */
.avatar-placeholder {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
    color: white;
    font-size: 2rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}

/* List Group */
.list-group-item {
    padding: 1rem 1.5rem;
    border: none;
    color: #495057;
}

.list-group-item:hover {
    background-color: #f8f9fa;
}

.list-group-item i {
    width: 20px;
}

/* Input Group */
.input-group .btn {
    padding: 0.75rem;
    border-color: #e9ecef;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .container-fluid {
        padding: 1rem !important;
    }

    .card-body {
        padding: 1.5rem;
    }

    .avatar-placeholder {
        width: 60px;
        height: 60px;
        font-size: 1.5rem;
    }
}
</style>

<script>
// Form validation
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
})()

// Password visibility toggle
function togglePassword(button) {
    const input = button.previousElementSibling;
    const icon = button.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

// Password strength validation
document.querySelector('input[name="new_pass"]').addEventListener('input', function() {
    const password = this.value;
    const strengthIndicator = this.nextElementSibling;
    
    // Check password strength
    let strength = 0;
    if (password.match(/[a-z]/)) strength++;
    if (password.match(/[A-Z]/)) strength++;
    if (password.match(/[0-9]/)) strength++;
    if (password.match(/[^a-zA-Z0-9]/)) strength++;
    
    // Update UI based on strength
    if (strength < 2) {
        this.setCustomValidity('Password is too weak');
    } else {
        this.setCustomValidity('');
    }
});

// Confirm password validation
document.querySelector('input[name="confirm_pass"]').addEventListener('input', function() {
    const newPass = document.querySelector('input[name="new_pass"]').value;
    if (this.value !== newPass) {
        this.setCustomValidity('Passwords do not match');
    } else {
        this.setCustomValidity('');
    }
});
</script>

</body>
</html>