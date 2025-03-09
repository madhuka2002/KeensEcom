<?php
include '../components/connect.php';

session_start();

if (!isset($_SESSION['supplier_id'])) {
    header('location:index.php');
    exit();
}

$supplier_id = $_SESSION['supplier_id'];

// Fetch current supplier details
$profile_query = $conn->prepare("SELECT * FROM suppliers WHERE supplier_id = ?");
$profile_query->execute([$supplier_id]);
$supplier = $profile_query->fetch(PDO::FETCH_ASSOC);

// Handle profile update
if (isset($_POST['update_profile'])) {
    // Sanitize and validate inputs
    $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $phone = filter_var($_POST['phone'], FILTER_SANITIZE_NUMBER_INT);
    $address = filter_var($_POST['address'], FILTER_SANITIZE_STRING);

    // Validate inputs
    $errors = [];

    if (empty($name) || strlen($name) > 18) {
        $errors[] = "Name is required and must be less than 18 characters.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email address.";
    }

    if (strlen($phone) != 10) {
        $errors[] = "Phone number must be 10 digits.";
    }

    if (empty($address)) {
        $errors[] = "Address is required.";
    }

    // Check if email or phone already exists (excluding current supplier)
    $check_unique = $conn->prepare("
        SELECT * FROM suppliers 
        WHERE (email = ? OR phone = ?) AND supplier_id != ?
    ");
    $check_unique->execute([$email, $phone, $supplier_id]);

    if ($check_unique->rowCount() > 0) {
        $errors[] = "Email or phone number is already in use.";
    }

    // If no errors, update profile
    if (empty($errors)) {
        $update_query = $conn->prepare("
            UPDATE suppliers 
            SET name = ?, email = ?, phone = ?, address = ? 
            WHERE supplier_id = ?
        ");

        try {
            $update_query->execute([$name, $email, $phone, $address, $supplier_id]);
            $success_message = "Profile updated successfully!";
            
            // Refresh supplier data
            $profile_query->execute([$supplier_id]);
            $supplier = $profile_query->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keens | Update Supplier Profile</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
</head>
<body>
    <?php include '../components/supplier_header.php'; ?>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center">
                            <i class="fas fa-user-edit me-2"></i>Update Profile
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php 
                        // Display success message
                        if (isset($success_message)) {
                            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="fas fa-check-circle me-2"></i>' . $success_message . '
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                  </div>';
                        }

                        // Display error messages
                        if (!empty($errors)) {
                            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
                            foreach ($errors as $error) {
                                echo '<p class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>' . $error . '</p>';
                            }
                            echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                  </div>';
                        }
                        ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="name" class="form-label">
                                    <i class="fas fa-user me-2"></i>Full Name
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="name" 
                                       name="name" 
                                       value="<?= htmlspecialchars($supplier['name']) ?>" 
                                       required 
                                       maxlength="18"
                                       placeholder="Enter your full name">
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope me-2"></i>Email Address
                                </label>
                                <input type="email" 
                                       class="form-control" 
                                       id="email" 
                                       name="email" 
                                       value="<?= htmlspecialchars($supplier['email']) ?>" 
                                       required 
                                       maxlength="40"
                                       placeholder="Enter your email">
                            </div>

                            <div class="mb-3">
                                <label for="phone" class="form-label">
                                    <i class="fas fa-phone me-2"></i>Phone Number
                                </label>
                                <input type="tel" 
                                       class="form-control" 
                                       id="phone" 
                                       name="phone" 
                                       value="<?= htmlspecialchars($supplier['phone']) ?>" 
                                       required 
                                       pattern="[0-9]{10}"
                                       maxlength="10"
                                       placeholder="Enter 10-digit phone number">
                            </div>

                            <div class="mb-3">
                                <label for="address" class="form-label">
                                    <i class="fas fa-map-marker-alt me-2"></i>Address
                                </label>
                                <textarea 
                                    class="form-control" 
                                    id="address" 
                                    name="address" 
                                    required 
                                    maxlength="100"
                                    rows="3"
                                    placeholder="Enter your full address"
                                ><?= htmlspecialchars($supplier['address']) ?></textarea>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="profile.php" class="btn btn-secondary me-md-2">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                                <button type="submit" name="update_profile" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Update Profile
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .card {
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .card-header {
            background-color: #f8f9fa;
            border-bottom: none;
        }

        .form-label i {
            color: #4361ee;
            margin-right: 10px;
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>