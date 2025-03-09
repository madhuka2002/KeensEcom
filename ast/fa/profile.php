<?php
include '../components/connect.php';

session_start();

// Ensure only logged-in financial auditors can access
if(!isset($_SESSION['auditor_id'])){
   header('location:../index.php');
   exit();
}

$auditor_id = $_SESSION['auditor_id'];

// Handle profile update
if(isset($_POST['update_profile'])){
    $name = $_POST['name'];
    
    $phone = $_POST['phone'];
    $certification = $_POST['certification'];

    try {
        // Validate inputs
        if(empty($name)){
            $error_message = "Name is required.";
        } else {
            // Prepare update statement
            $update_query = $conn->prepare("UPDATE `financial_auditors` 
                SET 
                    name = ?, 
                    
                    phone = ?, 
                    certification = ?,
                    updated_at = CURRENT_TIMESTAMP 
                WHERE auditor_id = ?");
            
            $update_query->execute([
                $name, 
                 
                $phone, 
                $certification, 
                $auditor_id
            ]);

            $success_message = "Profile updated successfully!";
        }
    } catch(PDOException $e) {
        $error_message = "Update failed: " . $e->getMessage();
        error_log("Profile update error: " . $e->getMessage());
    }
}

// Handle password change
if(isset($_POST['change_password'])){
    $current_password = sha1($_POST['current_password']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    try {
        // Verify current password
        $verify_query = $conn->prepare("SELECT * FROM `financial_auditors` WHERE auditor_id = ? AND password = ?");
        $verify_query->execute([$auditor_id, $current_password]);

        if($verify_query->rowCount() > 0){
            // Validate new password
            if($new_password !== $confirm_password){
                $password_error = "New passwords do not match.";
            } elseif(strlen($new_password) < 6){
                $password_error = "Password must be at least 6 characters long.";
            } else {
                // Update password
                $new_hashed_password = sha1($new_password);
                $update_password_query = $conn->prepare("UPDATE `financial_auditors` 
                    SET password = ?, 
                    updated_at = CURRENT_TIMESTAMP 
                    WHERE auditor_id = ?");
                $update_password_query->execute([$new_hashed_password, $auditor_id]);

                $password_success = "Password changed successfully!";
            }
        } else {
            $password_error = "Current password is incorrect.";
        }
    } catch(PDOException $e) {
        $password_error = "Password change failed: " . $e->getMessage();
        error_log("Password change error: " . $e->getMessage());
    }
}

// Fetch current profile information
try {
    $profile_query = $conn->prepare("SELECT * FROM `financial_auditors` WHERE auditor_id = ?");
    $profile_query->execute([$auditor_id]);
    $profile = $profile_query->fetch(PDO::FETCH_ASSOC);

    if(!$profile){
        throw new Exception("Profile not found");
    }
} catch(Exception $e) {
    error_log("Profile fetch error: " . $e->getMessage());
    header('location:index.php');
    exit();
}

// Fetch audit activity
try {
    $audit_logs_query = $conn->prepare("
        SELECT * FROM `financial_audit_logs` 
        WHERE auditor_id = ? 
        ORDER BY timestamp DESC 
        LIMIT 10
    ");
    $audit_logs_query->execute([$auditor_id]);
    $audit_logs = $audit_logs_query->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Audit logs fetch error: " . $e->getMessage());
    $audit_logs = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Keens | Financial Auditor Profile</title>
   
   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<?php include '../components/financial_auditor_header.php'; ?>

<div class="container-fluid px-4 py-5">
    <div class="row">
        <!-- Profile Information -->
        <div class="col-12 col-xl-4 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h4 class="card-title mb-0">
                        <i class="fas fa-user-circle me-2 text-primary"></i>Profile Information
                    </h4>
                </div>
                <div class="card-body text-center">
                    <!-- Profile Avatar -->
                    <div class="profile-avatar mx-auto mb-3">
                        <div class="avatar-container">
                            <?= strtoupper(substr($profile['name'], 0, 1)); ?>
                        </div>
                    </div>

                    <!-- Profile Details -->
                    <h5 class="mb-1"><?= htmlspecialchars($profile['name']); ?></h5>
                    <p class="text-muted mb-3">Financial Auditor</p>

                    <!-- Certification Badge -->
                    <div class="mb-3">
                        <span class="badge bg-info">
                            <i class="fas fa-award me-2"></i>
                            <?= htmlspecialchars($profile['certification']); ?>
                        </span>
                    </div>

                    <!-- Profile Stats -->
                    <div class="row mt-4">
                        
                        <div class="col-4">
                            <small class="text-muted d-block">Phone</small>
                            <strong><?= htmlspecialchars($profile['phone'] ?? 'N/A'); ?></strong>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>

        <!-- Profile Edit and Password Change -->
        <div class="col-12 col-xl-8">
            <div class="row">
                <!-- Edit Profile -->
                <div class="col-12 col-md-6 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-3">
                            <h4 class="card-title mb-0">
                                <i class="fas fa-user-edit me-2 text-primary"></i>Edit Profile
                            </h4>
                        </div>
                        <div class="card-body">
                            <?php if(isset($success_message)): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <?= $success_message; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>

                            <?php if(isset($error_message)): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <?= $error_message; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>

                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" name="name" class="form-control" 
                                           value="<?= htmlspecialchars($profile['name']); ?>" 
                                           required maxlength="100">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Phone Number</label>
                                    <input type="tel" name="phone" class="form-control" 
                                           value="<?= htmlspecialchars($profile['phone'] ?? ''); ?>" 
                                           maxlength="15">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Certification</label>
                                    <input type="text" name="certification" class="form-control" 
                                           value="<?= htmlspecialchars($profile['certification']); ?>" 
                                           required maxlength="50">
                                </div>
                                <button type="submit" name="update_profile" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Update Profile
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Change Password -->
                <div class="col-12 col-md-6 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-3">
                            <h4 class="card-title mb-0">
                                <i class="fas fa-lock me-2 text-primary"></i>Change Password
                            </h4>
                        </div>
                        <div class="card-body">
                            <?php if(isset($password_success)): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <?= $password_success; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>

                            <?php if(isset($password_error)): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <?= $password_error; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>

                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Current Password</label>
                                    <input type="password" name="current_password" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">New Password</label>
                                    <input type="password" name="new_password" class="form-control" 
                                           required minlength="6" maxlength="20">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Confirm New Password</label>
                                    <input type="password" name="confirm_password" class="form-control" 
                                           required minlength="6" maxlength="20">
                                </div>
                                <button type="submit" name="change_password" class="btn btn-warning">
                                    <i class="fas fa-key me-2"></i>Change Password
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3">
                            <h4 class="card-title mb-0">
                                <i class="fas fa-history me-2 text-primary"></i>Recent Activity
                            </h4>
                        </div>
                        <div class="card-body">
                            <?php if(empty($audit_logs)): ?>
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-clipboard-list fa-3x mb-3 opacity-50"></i>
                                    <p>No recent activity found.</p>
                                </div>
                            <?php else: ?>
                                <div class="list-group">
                                    <?php foreach($audit_logs as $log): ?>
                                        <div class="list-group-item list-group-item-action">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h6 class="mb-1">
                                                    <span class="badge 
                                                        <?php 
                                                        switch($log['severity']) {
                                                            case 'high': echo 'bg-danger'; break;
                                                            case 'medium': echo 'bg-warning'; break;
                                                            default: echo 'bg-secondary';
                                                        }
                                                        ?>
                                                    ">
                                                        <?= ucfirst($log['severity']); ?>
                                                    </span>
                                                    <?= htmlspecialchars($log['log_type']); ?>
                                                </h6>
                                                <small class="text-muted">
                                                    <?= date('M d, H:i', strtotime($log['timestamp'])); ?>
                                                </small>
                                            </div>
                                            <p class="mb-1"><?= htmlspecialchars($log['description']); ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="text-center mt-3">
                                    <a href="audit_logs.php" class="btn btn-sm btn-outline-primary">
                                        View All Logs
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .profile-avatar .avatar-container {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background-color: #4361ee;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        font-weight: bold;
        margin: 0 auto;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }

    .list-group-item {
        transition: all 0.3s ease;
    }

    .list-group-item:hover {
        background-color: rgba(0,0,0,0.025);
        transform: translateX(5px);
    }

    /* Form Input Styles */
    .form-control:focus {
        border-color: #4361ee;
        box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.25);
    }

    /* Responsive Adjustments */
    @media (max-width: 768px) {
        .profile-avatar .avatar-container {
            width: 100px;
            height: 100px;
            font-size: 2.5rem;
        }
    }
</style>

<script>
    // Client-side form validation
    document.addEventListener('DOMContentLoaded', function() {
        // Profile Update Form Validation
        const profileForm = document.querySelector('form[name="update_profile"]');
        if (profileForm) {
            profileForm.addEventListener('submit', function(e) {
                const name = this.name.value.trim();
                

                // Basic validation
                if (name.length === 0) {
                    e.preventDefault();
                    alert('Name cannot be empty');
                    return;
                }
            });
        }

        // Password Change Form Validation
        const passwordForm = document.querySelector('form[name="change_password"]');
        if (passwordForm) {
            passwordForm.addEventListener('submit', function(e) {
                const currentPassword = this.current_password.value;
                const newPassword = this.new_password.value;
                const confirmPassword = this.confirm_password.value;

                // Current password check
                if (currentPassword.length === 0) {
                    e.preventDefault();
                    alert('Current password is required');
                    return;
                }

                // New password validation
                if (newPassword.length < 6) {
                    e.preventDefault();
                    alert('New password must be at least 6 characters long');
                    return;
                }

                // Password match check
                if (newPassword !== confirmPassword) {
                    e.preventDefault();
                    alert('New passwords do not match');
                    return;
                }

                // Prevent submitting same password
                if (currentPassword === newPassword) {
                    e.preventDefault();
                    alert('New password must be different from current password');
                    return;
                }
            });
        }
    });
</script>

<?php include '../components/footer.php'; ?>
</body>
</html>