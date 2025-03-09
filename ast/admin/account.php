<?php

include '../components/connect.php';

session_start();

$admin_id = $_SESSION['admin_id'];

if (!isset($admin_id)) {
    header('location:index.php');
}

if (isset($_POST['submit'])) {
    $name = $_POST['name'];
    $name = filter_var($name, FILTER_SANITIZE_STRING);

    $update_profile_name = $conn->prepare("UPDATE `admins` SET name = ? WHERE id = ?");
    $update_profile_name->execute([$name, $admin_id]);

    $empty_pass = 'da39a3ee5e6b4b0d3255bfef95601890afd80709';
    $prev_pass = $_POST['prev_pass'];
    $old_pass = sha1($_POST['old_pass']);
    $old_pass = filter_var($old_pass, FILTER_SANITIZE_STRING);
    $new_pass = sha1($_POST['new_pass']);
    $new_pass = filter_var($new_pass, FILTER_SANITIZE_STRING);
    $confirm_pass = sha1($_POST['confirm_pass']);
    $confirm_pass = filter_var($confirm_pass, FILTER_SANITIZE_STRING);

    $message = [];

    if ($old_pass == $empty_pass) {
        $message[] = 'Please enter the old password!';
    } elseif ($old_pass != $prev_pass) {
        $message[] = 'Old password not matched!';
    } elseif ($new_pass != $confirm_pass) {
        $message[] = 'Confirm password does not match!';
    } else {
        if ($new_pass != $empty_pass) {
            $update_admin_pass = $conn->prepare("UPDATE `admins` SET password = ? WHERE id = ?");
            $update_admin_pass->execute([$confirm_pass, $admin_id]);
            $message[] = 'Password updated successfully!';
        } else {
            $message[] = 'Please enter a new password!';
        }
    }
}

// Fetch profile data
$select_profile = $conn->prepare("SELECT * FROM `admins` WHERE id = ?");
$select_profile->execute([$admin_id]);
$fetch_profile = $select_profile->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keens | Account</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<section class="update-profile mt-5">
    <div class="content-card">
        <div class="card-header">
            <div class="header-icon">
                <i class="fas fa-user-cog"></i>
            </div>
            <h2 class="header-title">Update Profile</h2>
            <p class="text-muted">Manage your admin account settings</p>
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
            <input type="hidden" name="prev_pass" value="<?= $fetch_profile['password']; ?>">

            <!-- Username Input -->
            <div class="mb-4">
                <div class="form-floating">
                    <input type="text" 
                           id="name" 
                           name="name" 
                           class="form-control custom-input" 
                           placeholder="Username"
                           value="<?= $fetch_profile['name']; ?>"
                           required 
                           maxlength="20"
                           oninput="this.value = this.value.replace(/\s/g, '')">
                    <label for="name">Username</label>
                    <div class="invalid-feedback">
                        Please enter a username (max 20 characters)
                    </div>
                </div>
            </div>

            <!-- Old Password Input -->
            <div class="mb-4">
                <div class="form-floating">
                    <input type="password" 
                           id="old_pass" 
                           name="old_pass" 
                           class="form-control custom-input" 
                           placeholder="Old Password"
                           maxlength="20"
                           oninput="this.value = this.value.replace(/\s/g, '')">
                    <label for="old_pass">Old Password</label>
                    <div class="invalid-feedback">
                        Please enter your old password
                    </div>
                </div>
            </div>

            <!-- New Password Input -->
            <div class="mb-4">
                <div class="form-floating">
                    <input type="password" 
                           id="new_pass" 
                           name="new_pass" 
                           class="form-control custom-input" 
                           placeholder="New Password"
                           maxlength="20"
                           oninput="this.value = this.value.replace(/\s/g, '')">
                    <label for="new_pass">New Password</label>
                    <div class="invalid-feedback">
                        Please enter a new password
                    </div>
                </div>
            </div>

            <!-- Confirm New Password Input -->
            <div class="mb-4">
                <div class="form-floating">
                    <input type="password" 
                           id="confirm_pass" 
                           name="confirm_pass" 
                           class="form-control custom-input" 
                           placeholder="Confirm New Password"
                           maxlength="20"
                           oninput="this.value = this.value.replace(/\s/g, '')">
                    <label for="confirm_pass">Confirm New Password</label>
                    <div class="invalid-feedback">
                        Please confirm your new password
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <button type="submit" 
                    name="submit" 
                    class="btn custom-button w-100">
                <i class="fas fa-sync me-2"></i>Update Profile
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