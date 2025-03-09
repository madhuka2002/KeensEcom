<?php
include '../components/connect.php';

session_start();

$staff_id = $_SESSION['staff_id'];

if(!isset($staff_id)){
   header('location:index.php');
}

// Handle profile update
if(isset($_POST['update_profile'])) {
    $name = $_POST['name'];
    $name = filter_var($name, FILTER_SANITIZE_STRING);
    $phone = $_POST['phone'];
    $phone = filter_var($phone, FILTER_SANITIZE_STRING);
    $emergency_contact = $_POST['emergency_contact'];
    $emergency_contact = filter_var($emergency_contact, FILTER_SANITIZE_STRING);
    $address = $_POST['address'];
    $address = filter_var($address, FILTER_SANITIZE_STRING);

    // Check if phone number is already in use
    $check_phone = $conn->prepare("SELECT * FROM `warehouse_staff` WHERE phone = ? AND staff_id != ?");
    $check_phone->execute([$phone, $staff_id]);

    if($check_phone->rowCount() > 0) {
        $message[] = 'Phone number already in use!';
    } else {
        // Update profile
        $update_profile = $conn->prepare("
            UPDATE `warehouse_staff` 
            SET name = ?, phone = ?, emergency_contact = ?, address = ? 
            WHERE staff_id = ?
        ");
        $update_profile->execute([$name, $phone, $emergency_contact, $address, $staff_id]);
        $message[] = 'Profile updated successfully!';
    }
}

// Handle password update
if(isset($_POST['update_password'])) {
    $old_pass = sha1($_POST['old_pass']);
    $old_pass = filter_var($old_pass, FILTER_SANITIZE_STRING);
    $new_pass = sha1($_POST['new_pass']);
    $new_pass = filter_var($new_pass, FILTER_SANITIZE_STRING);
    $confirm_pass = sha1($_POST['confirm_pass']);
    $confirm_pass = filter_var($confirm_pass, FILTER_SANITIZE_STRING);

    // Verify old password
    $verify_pass = $conn->prepare("SELECT password FROM `warehouse_staff` WHERE staff_id = ?");
    $verify_pass->execute([$staff_id]);
    $row = $verify_pass->fetch(PDO::FETCH_ASSOC);

    if($old_pass != $row['password']) {
        $message[] = 'Current password is incorrect!';
    } elseif($new_pass != $confirm_pass) {
        $message[] = 'Confirm password does not match!';
    } else {
        // Update password
        $update_pass = $conn->prepare("UPDATE `warehouse_staff` SET password = ? WHERE staff_id = ?");
        $update_pass->execute([$new_pass, $staff_id]);
        $message[] = 'Password updated successfully!';
    }
}

// Get staff details
$select_profile = $conn->prepare("SELECT * FROM `warehouse_staff` WHERE staff_id = ?");
$select_profile->execute([$staff_id]);
$profile = $select_profile->fetch(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Keens | Edit Profile</title>

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<?php include '../components/warehouse_staff_header.php'; ?>

<section class="profile-section py-5">
    <div class="container">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-2">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Edit Profile</li>
                    </ol>
                </nav>
                <h2 class="h3 mb-0">Profile Settings</h2>
            </div>
        </div>

        <div class="row">
            <!-- Profile Details -->
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm">
                    <div class="card-body text-center">
                        <div class="profile-image mb-3">
                            <?= strtoupper(substr($profile['name'], 0, 1)) ?>
                        </div>
                        <h4 class="mb-1"><?= $profile['name'] ?></h4>
                        <p class="text-muted mb-3">Warehouse Staff</p>
                        <div class="d-flex justify-content-center gap-2">
                            <span class="badge bg-primary"><?= ucfirst($profile['shift']) ?> Shift</span>
                            <span class="badge bg-<?= $profile['is_available'] ? 'success' : 'warning' ?>">
                                <?= $profile['is_available'] ? 'Available' : 'Away' ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Profile Form -->
            <div class="col-md-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">Edit Profile</h5>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST" class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="name" class="form-control" 
                                       value="<?= $profile['name'] ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" name="phone" class="form-control" 
                                       value="<?= $profile['phone'] ?>" required 
                                       pattern="[0-9]{10}" maxlength="10">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Emergency Contact</label>
                                <input type="tel" name="emergency_contact" class="form-control" 
                                       value="<?= $profile['emergency_contact'] ?>" 
                                       pattern="[0-9]{10}" maxlength="10">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-control" 
                                          rows="3"><?= $profile['address'] ?></textarea>
                            </div>

                            <div class="col-12">
                                <button type="submit" name="update_profile" class="btn btn-primary">
                                    Update Profile
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Change Password -->
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">Change Password</h5>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST" class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">Current Password</label>
                                <input type="password" name="old_pass" class="form-control" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">New Password</label>
                                <input type="password" name="new_pass" class="form-control" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" name="confirm_pass" class="form-control" required>
                            </div>

                            <div class="col-12">
                                <button type="submit" name="update_password" class="btn btn-primary">
                                    Update Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
/* Custom Styles */
.breadcrumb-item a {
    color: var(--primary-color);
    text-decoration: none;
}

.card {
    border: none;
    border-radius: 15px;
}

.card-header {
    border-bottom: 1px solid rgba(0,0,0,.05);
}

.profile-image {
    width: 100px;
    height: 100px;
    background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    font-weight: 600;
    margin: 0 auto;
}

.form-control:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.25);
}

.btn-primary {
    background: var(--primary-color);
    border-color: var(--primary-color);
}

.btn-primary:hover {
    background: var(--secondary-color);
    border-color: var(--secondary-color);
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .profile-image {
        width: 80px;
        height: 80px;
        font-size: 2rem;
    }
}
</style>

</body>
</html>