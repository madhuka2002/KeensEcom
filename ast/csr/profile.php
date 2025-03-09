<?php
include '../components/connect.php';

session_start();

if(!isset($_SESSION['csr_id'])) {
   header('location:index.php');
   exit();
}

$csr_id = $_SESSION['csr_id'];

// Fetch CSR details with only existing columns
$csr_query = $conn->prepare("
    SELECT csr_id, name, password, expertise, is_available 
    FROM customer_sales_representatives 
    WHERE csr_id = ?
");
$csr_query->execute([$csr_id]);
$csr = $csr_query->fetch(PDO::FETCH_ASSOC);

// Set default values if not present
$csr['expertise'] = $csr['expertise'] ?? 'General Support';
$csr['is_available'] = $csr['is_available'] ?? 1;

// Handle profile update
if(isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $expertise = trim($_POST['expertise']);
    $current_password = sha1($_POST['current_password']);
    
    // Verify current password
    if($current_password != $csr['password']) {
        $message[] = 'Current password is incorrect!';
    } else {
        try {
            $conn->beginTransaction();
            
            // Update basic info
            $update_profile = $conn->prepare("
                UPDATE customer_sales_representatives 
                SET name = ?, expertise = ?
                WHERE csr_id = ?
            ");
            $update_profile->execute([$name, $expertise, $csr_id]);

            // Update password if provided
            if(!empty($_POST['new_password'])) {
                $new_password = sha1($_POST['new_password']);
                $update_password = $conn->prepare("
                    UPDATE customer_sales_representatives 
                    SET password = ? 
                    WHERE csr_id = ?
                ");
                $update_password->execute([$new_password, $csr_id]);
            }

            $conn->commit();
            $message[] = 'Profile updated successfully!';
            
            // Refresh CSR data
            $csr_query->execute([$csr_id]);
            $csr = $csr_query->fetch(PDO::FETCH_ASSOC);
            
        } catch(PDOException $e) {
            $conn->rollBack();
            $message[] = 'Error updating profile: ' . $e->getMessage();
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
    <title>Keens | CSR Profile</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
</head>
<body>

<?php include '../components/csr_header.php'; ?>

<section class="profile-section mt-5">
    <div class="container">
        <!-- Alert Messages -->
        <?php
        if(isset($message)){
            foreach($message as $msg){
                echo '
                <div class="alert alert-info alert-dismissible fade show mb-4" role="alert">
                    <i class="fas fa-info-circle me-2"></i>'.$msg.'
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                ';
            }
        }
        ?>

        <div class="row">
            <!-- Profile Card -->
            <div class="col-lg-4 mb-4">
                <div class="content-card profile-card">
                    <div class="profile-header text-center">
                        <div class="profile-avatar">
                            <?= strtoupper(substr($csr['name'] ?? 'CSR', 0, 1)) ?>
                        </div>
                        <h3 class="profile-name"><?= htmlspecialchars($csr['name'] ?? 'CSR Name') ?></h3>
                        <p class="profile-role">Customer Service Representative</p>
                        <div class="availability-badge <?= $csr['is_available'] ? 'available' : 'away' ?>">
                            <?= $csr['is_available'] ? 'Available' : 'Away' ?>
                        </div>
                    </div>
                    <div class="profile-info">
                        <div class="info-item">
                            <i class="fas fa-star"></i>
                            <span><?= htmlspecialchars($csr['expertise']) ?></span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-id-badge"></i>
                            <span>CSR ID: <?= htmlspecialchars($csr['csr_id']) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Profile Form -->
            <div class="col-lg-8">
                <div class="content-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-user-edit me-2"></i>Edit Profile
                        </h3>
                    </div>
                    
                    <form action="" method="POST" class="profile-form">
                        <!-- Basic Information -->
                        <div class="form-section">
                            <h4>Basic Information</h4>
                            
                            <div class="mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" 
                                       name="name" 
                                       class="form-control"
                                       value="<?= htmlspecialchars($csr['name'] ?? '') ?>"
                                       required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Expertise</label>
                                <input type="text" 
                                       name="expertise" 
                                       class="form-control"
                                       value="<?= htmlspecialchars($csr['expertise']) ?>"
                                       required>
                            </div>
                        </div>

                        <!-- Change Password -->
                        <div class="form-section">
                            <h4>Change Password</h4>
                            
                            <div class="mb-3">
                                <label class="form-label">Current Password</label>
                                <input type="password" 
                                       name="current_password" 
                                       class="form-control"
                                       required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <input type="password" 
                                       name="new_password" 
                                       class="form-control">
                                <small class="text-muted">Leave blank to keep current password</small>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" name="update_profile" class="btn-submit">
                                <i class="fas fa-save me-2"></i>Save Changes
                            </button>
                            <a href="dashboard.php" class="btn-cancel">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                        </div>
                    </form>
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
}

/* Content Card Styles */
.content-card {
    background: white;
    border-radius: 20px;
    padding: 2rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.05);
    margin-bottom: 2rem;
}

.card-header {
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e9ecef;
}

.card-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #2b3452;
    margin: 0;
}

/* Profile Card Styles */
.profile-card {
    text-align: center;
}

.profile-avatar {
    width: 100px;
    height: 100px;
    border-radius: 20px;
    background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
    color: white;
    font-size: 2.5rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
}

.profile-name {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.profile-role {
    color: #6c757d;
    margin-bottom: 1rem;
}

.availability-badge {
    display: inline-block;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 500;
    margin-bottom: 1.5rem;
}

.availability-badge.available {
    background: rgba(46, 204, 113, 0.1);
    color: #2ecc71;
}

.availability-badge.away {
    background: rgba(243, 156, 18, 0.1);
    color: #f39c12;
}

/* Profile Info Styles */
.profile-info {
    text-align: left;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    border-bottom: 1px solid #e9ecef;
}

.info-item:last-child {
    border-bottom: none;
}

.info-item i {
    width: 20px;
    color: var(--primary-color);
}

/* Form Styles */
.form-section {
    margin-bottom: 2rem;
    padding-bottom: 2rem;
    border-bottom: 1px solid #e9ecef;
}

.form-section:last-child {
    border-bottom: none;
    margin-bottom: 1rem;
}

.form-section h4 {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    color: #2b3452;
}

.form-control {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 0.75rem 1rem;
}

.form-control:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
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
    transition: all 0.3s ease;
    text-decoration: none;
}

.btn-submit:hover {
    background: var(--secondary-color);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(67, 97, 238, 0.2);
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
    transition: all 0.3s ease;
    text-decoration: none;
}

.btn-cancel:hover {
    background: #e9ecef;
    transform: translateY(-2px);
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .content-card {
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>