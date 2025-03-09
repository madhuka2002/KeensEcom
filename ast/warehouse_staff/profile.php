<?php
include '../components/connect.php';

session_start();

$staff_id = $_SESSION['staff_id'];

if(!isset($staff_id)){
   header('location:index.php');
}

// Handle profile updates
if(isset($_POST['update_profile'])) {
    $name = $_POST['name'];
    $name = filter_var($name, FILTER_SANITIZE_STRING);
    $phone = $_POST['phone'];
    $phone = filter_var($phone, FILTER_SANITIZE_STRING);

    // Check if phone number is already used
    $check_phone = $conn->prepare("SELECT * FROM `warehouse_staff` WHERE phone = ? AND staff_id != ?");
    $check_phone->execute([$phone, $staff_id]);

    if($check_phone->rowCount() > 0) {
        $message[] = 'Phone number already in use!';
    } else {
        $update_profile = $conn->prepare("UPDATE `warehouse_staff` SET name = ?, phone = ? WHERE staff_id = ?");
        $update_profile->execute([$name, $phone, $staff_id]);
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

    $select_pass = $conn->prepare("SELECT password FROM `warehouse_staff` WHERE staff_id = ?");
    $select_pass->execute([$staff_id]);
    $row = $select_pass->fetch(PDO::FETCH_ASSOC);

    if($old_pass != $row['password']) {
        $message[] = 'Old password not matched!';
    } elseif($new_pass != $confirm_pass) {
        $message[] = 'Confirm password not matched!';
    } else {
        $update_pass = $conn->prepare("UPDATE `warehouse_staff` SET password = ? WHERE staff_id = ?");
        $update_pass->execute([$new_pass, $staff_id]);
        $message[] = 'Password updated successfully!';
    }
}

// Get staff details
$select_profile = $conn->prepare("SELECT * FROM `warehouse_staff` WHERE staff_id = ?");
$select_profile->execute([$staff_id]);
$fetch_profile = $select_profile->fetch(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Keens | Profile</title>

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<?php include '../components/warehouse_staff_header.php'; ?>

<section class="profile-section">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <!-- Profile Card -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <div class="profile-image">
                                <?= strtoupper(substr($fetch_profile['name'], 0, 1)); ?>
                            </div>
                            <h4 class="mt-3 mb-1"><?= $fetch_profile['name']; ?></h4>
                            <p class="text-muted mb-3">Warehouse Staff</p>
                            <span class="badge bg-primary"><?= $fetch_profile['shift']; ?> Shift</span>
                        </div>

                        <form action="" method="POST">
                            <div class="mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="name" class="form-control" 
                                       value="<?= $fetch_profile['name']; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Phone Number</label>
                                <input type="text" name="phone" class="form-control" 
                                       value="<?= $fetch_profile['phone']; ?>" required>
                            </div>
                            <button type="submit" name="update_profile" class="btn btn-primary w-100">
                                Update Profile
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Password Card -->
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Change Password</h5>
                        <form action="" method="POST">
                            <div class="mb-3">
                                <label class="form-label">Current Password</label>
                                <input type="password" name="old_pass" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <input type="password" name="new_pass" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" name="confirm_pass" class="form-control" required>
                            </div>
                            <button type="submit" name="update_password" class="btn btn-primary w-100">
                                Update Password
                            </button>
                        </form>
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

.card {
    border: none;
    border-radius: 15px;
}

.form-control {
    border-radius: 8px;
    padding: 0.75rem 1rem;
}

.form-control:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.25);
}

.btn-primary {
    background: var(--primary-color);
    border-color: var(--primary-color);
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 500;
}

.btn-primary:hover {
    background: var(--secondary-color);
    border-color: var(--secondary-color);
}

.form-label {
    font-weight: 500;
    color: #2b3452;
    margin-bottom: 0.5rem;
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