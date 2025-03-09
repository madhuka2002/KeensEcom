<?php
// Prevent direct access
define('APP_RUNNING', true);

// Include necessary components
require_once 'components/connect.php';
require_once 'components/functions.php';

// Start session
session_start();

// Check user authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Initialize variables
$user_id = $_SESSION['user_id'];
$message = [];

// Fetch current user profile
try {
    $fetch_profile = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $fetch_profile->execute([$user_id]);
    $user_profile = $fetch_profile->fetch(PDO::FETCH_ASSOC);

    if (!$user_profile) {
        throw new Exception("User profile not found.");
    }
} catch (Exception $e) {
    $message[] = ['type' => 'error', 'text' => 'Error fetching profile: ' . $e->getMessage()];
}

// Handle user profile update
if (isset($_POST['update_profile'])) {
    // Sanitize and validate input
    $name = trim($_POST['name']);
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);

    // Validate inputs
    $profile_errors = [];

    if (empty($name) || strlen($name) < 2 || strlen($name) > 50) {
        $profile_errors[] = "Name must be between 2 and 50 characters.";
    }

    if (!$email) {
        $profile_errors[] = "Invalid email address.";
    }

    // Check if email is already in use by another user
    try {
        $check_email = $conn->prepare("SELECT * FROM users WHERE email = ? AND id != ?");
        $check_email->execute([$email, $user_id]);
        
        if ($check_email->rowCount() > 0) {
            $profile_errors[] = "Email is already in use by another account.";
        }
    } catch (PDOException $e) {
        $profile_errors[] = "Database error: " . $e->getMessage();
    }

    // Proceed with update if no errors
    if (empty($profile_errors)) {
        try {
            $update_profile = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
            $update_result = $update_profile->execute([$name, $email, $user_id]);

            if ($update_result) {
                // Update session name if changed
                $_SESSION['user_name'] = $name;
                $message[] = ['type' => 'success', 'text' => 'Profile updated successfully!'];
                
                // Refresh user profile
                $fetch_profile->execute([$user_id]);
                $user_profile = $fetch_profile->fetch(PDO::FETCH_ASSOC);
            } else {
                $message[] = ['type' => 'error', 'text' => 'Failed to update profile.'];
            }
        } catch (PDOException $e) {
            $message[] = ['type' => 'error', 'text' => 'Database error: ' . $e->getMessage()];
        }
    } else {
        // Add profile update errors
        foreach ($profile_errors as $error) {
            $message[] = ['type' => 'error', 'text' => $error];
        }
    }
}

// Handle password change
if (isset($_POST['change_password'])) {
    // Sanitize inputs
    $current_password = sha1($_POST['current_password']); // Match your existing password hash method
    $new_password = sha1($_POST['new_password']);
    $confirm_password = sha1($_POST['confirm_password']);

    // Validate password inputs
    $password_errors = [];

    // Verify current password
    if ($current_password != $user_profile['password']) {
        $password_errors[] = "Current password is incorrect.";
    }

    // Validate new password match
    if (strlen($_POST['new_password']) < 8) {
        $password_errors[] = "New password must be at least 8 characters long.";
    }

    if ($new_password !== $confirm_password) {
        $password_errors[] = "New passwords do not match.";
    }

    // Proceed with password change if no errors
    if (empty($password_errors)) {
        try {
            // Update password in database
            $update_password = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update_result = $update_password->execute([$new_password, $user_id]);

            if ($update_result) {
                $message[] = ['type' => 'success', 'text' => 'Password changed successfully!'];
            } else {
                $message[] = ['type' => 'error', 'text' => 'Failed to update password.'];
            }
        } catch (PDOException $e) {
            $message[] = ['type' => 'error', 'text' => 'Database error: ' . $e->getMessage()];
        }
    } else {
        // Add password change errors
        foreach ($password_errors as $error) {
            $message[] = ['type' => 'error', 'text' => $error];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | Keens.lk</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="favicon.png">
    
    <!-- Font Awesome -->
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
        
        /* Profile card styling */
        .profile-header {
            background: linear-gradient(135deg, rgba(13,110,253,0.1) 0%, rgba(13,110,253,0.05) 100%);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .profile-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 1rem;
        }
        
        .form-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .form-card-header {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .form-card-header-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: linear-gradient(135deg, rgba(13,110,253,0.1) 0%, rgba(13,110,253,0.05) 100%);
            color: #0d6efd;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            margin-right: 1rem;
        }
        
        .form-card-title {
            margin: 0;
            font-weight: 600;
            color: #2b3452;
        }
        
        .btn-custom {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.2);
            color: white;
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

<div class="container py-5">
    <!-- Profile Header -->
    <div class="profile-header">
        <div class="profile-icon">
            <i class="fas fa-user"></i>
        </div>
        <h2 class="fw-bold"><?= htmlspecialchars($user_profile['name']) ?></h2>
        <p class="text-muted"><?= htmlspecialchars($user_profile['email']) ?></p>
    </div>
    
    <div class="row">
        <!-- User Profile Update Section -->
        <div class="col-md-6">
            <div class="form-card">
                <div class="form-card-header">
                    <div class="form-card-header-icon">
                        <i class="fas fa-user-edit"></i>
                    </div>
                    <h4 class="form-card-title">Update Profile</h4>
                </div>
                <form action="" method="post">
                    <div class="mb-3">
                        <div class="form-floating">
                            <input type="text" 
                                   name="name" 
                                   class="form-control bg-light border-0" 
                                   id="name" 
                                   placeholder="Name"
                                   value="<?= htmlspecialchars($user_profile['name']) ?>" 
                                   required 
                                   maxlength="50"
                                   style="border-radius: 12px;">
                            <label class="text-muted">Your Name</label>
                        </div>
                    </div>
                    <div class="mb-4">
                        <div class="form-floating">
                            <input type="email" 
                                   name="email" 
                                   class="form-control bg-light border-0" 
                                   id="email" 
                                   placeholder="Email"
                                   value="<?= htmlspecialchars($user_profile['email']) ?>" 
                                   required 
                                   maxlength="100"
                                   style="border-radius: 12px;">
                            <label class="text-muted">Email Address</label>
                        </div>
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-custom">
                        <i class="fas fa-save me-2"></i>Update Profile
                    </button>
                </form>
            </div>
        </div>

        <!-- Change Password Section -->
        <div class="col-md-6">
            <div class="form-card">
                <div class="form-card-header">
                    <div class="form-card-header-icon">
                        <i class="fas fa-lock"></i>
                    </div>
                    <h4 class="form-card-title">Change Password</h4>
                </div>
                <form action="" method="post">
                    <div class="mb-3">
                        <div class="form-floating">
                            <input type="password" 
                                   name="current_password" 
                                   class="form-control bg-light border-0" 
                                   id="current_password" 
                                   placeholder="Current Password"
                                   required
                                   style="border-radius: 12px;">
                            <label class="text-muted">Current Password</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-floating">
                            <input type="password" 
                                   name="new_password" 
                                   class="form-control bg-light border-0" 
                                   id="new_password" 
                                   placeholder="New Password"
                                   required 
                                   minlength="8"
                                   style="border-radius: 12px;">
                            <label class="text-muted">New Password</label>
                        </div>
                    </div>
                    <div class="mb-4">
                        <div class="form-floating">
                            <input type="password" 
                                   name="confirm_password" 
                                   class="form-control bg-light border-0" 
                                   id="confirm_password" 
                                   placeholder="Confirm New Password"
                                   required 
                                   minlength="8"
                                   style="border-radius: 12px;">
                            <label class="text-muted">Confirm New Password</label>
                        </div>
                    </div>
                    <button type="submit" name="change_password" class="btn btn-custom">
                        <i class="fas fa-key me-2"></i>Change Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

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

</body>
</html>