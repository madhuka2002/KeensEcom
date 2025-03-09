<?php
include '../components/connect.php';

session_start();

$manager_id = $_SESSION['manager_id'];

if(!isset($manager_id)){
   header('location:index.php');
}

// First, let's check if the manager_settings table exists, and if not, create it
try {
    $check_table = $conn->query("SHOW TABLES LIKE 'manager_settings'");
    if($check_table->rowCount() == 0) {
        // Table doesn't exist, create it
        $create_table = $conn->prepare("
            CREATE TABLE `manager_settings` (
              `settings_id` int(11) NOT NULL AUTO_INCREMENT,
              `manager_id` int(11) NOT NULL,
              `low_stock_alerts` tinyint(1) NOT NULL DEFAULT 1,
              `new_order_alerts` tinyint(1) NOT NULL DEFAULT 1,
              `review_alerts` tinyint(1) NOT NULL DEFAULT 1,
              `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
              `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
              PRIMARY KEY (`settings_id`),
              UNIQUE KEY `manager_id` (`manager_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ");
        $create_table->execute();
    }
} catch (PDOException $e) {
    // Handle error silently to avoid interrupting the user experience
    $message[] = 'System setup error. Please contact administrator.';
    // Optionally log the error
    error_log('DB Setup Error: ' . $e->getMessage());
}

// Handle Email Notification Settings
if(isset($_POST['update_notifications'])) {
    $low_stock = isset($_POST['low_stock_alerts']) ? 1 : 0;
    $new_orders = isset($_POST['new_order_alerts']) ? 1 : 0;
    $product_reviews = isset($_POST['review_alerts']) ? 1 : 0;
    
    try {
        $update_settings = $conn->prepare("
            UPDATE `manager_settings` 
            SET low_stock_alerts = ?, new_order_alerts = ?, review_alerts = ? 
            WHERE manager_id = ?
        ");
        $update_settings->execute([$low_stock, $new_orders, $product_reviews, $manager_id]);
        
        $message[] = 'Notification settings updated successfully!';
    } catch (PDOException $e) {
        $message[] = 'Error updating settings. Please try again.';
        error_log('Settings Update Error: ' . $e->getMessage());
    }
}

// Initialize default settings
$settings = [
    'low_stock_alerts' => 1,
    'new_order_alerts' => 1,
    'review_alerts' => 1
];

// Get Current Settings
try {
    $select_settings = $conn->prepare("SELECT * FROM `manager_settings` WHERE manager_id = ?");
    $select_settings->execute([$manager_id]);
    $fetched_settings = $select_settings->fetch(PDO::FETCH_ASSOC);

    // If settings exist, use them
    if($fetched_settings) {
        $settings = $fetched_settings;
    } else {
        // If no settings exist, create default settings
        $create_settings = $conn->prepare("
            INSERT INTO `manager_settings` 
            (manager_id, low_stock_alerts, new_order_alerts, review_alerts) 
            VALUES (?, 1, 1, 1)
        ");
        $create_settings->execute([$manager_id]);
    }
} catch (PDOException $e) {
    // Handle error silently - we'll use default settings
    error_log('Settings Fetch Error: ' . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Keens | Settings</title>

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<?php include '../components/product_manager_header.php'; ?>

<section class="settings">
    <div class="container-fluid px-4 py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- Page Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="fw-bold mb-0">Settings</h2>
                        <p class="text-muted mb-0">Manage your preferences and configurations</p>
                    </div>
                </div>

                <?php
                // Display messages if any
                if(isset($message) && !empty($message)){
                    foreach($message as $msg){
                        echo '<div class="alert alert-info alert-dismissible fade show" role="alert">
                                ' . $msg . '
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                              </div>';
                    }
                }
                ?>

                <!-- Notification Settings -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h5 class="card-title mb-4">Notification Preferences</h5>
                        <form method="post">
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input" id="lowStockAlerts" 
                                           name="low_stock_alerts" <?= $settings['low_stock_alerts'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="lowStockAlerts">Low Stock Alerts</label>
                                    <small class="d-block text-muted">Get notified when product stock falls below threshold</small>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input" id="newOrderAlerts" 
                                           name="new_order_alerts" <?= $settings['new_order_alerts'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="newOrderAlerts">New Order Notifications</label>
                                    <small class="d-block text-muted">Receive alerts for new orders</small>
                                </div>
                            </div>

                            <div class="mb-4">
                                <div class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input" id="reviewAlerts" 
                                           name="review_alerts" <?= $settings['review_alerts'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="reviewAlerts">Product Review Alerts</label>
                                    <small class="d-block text-muted">Get notified when products receive new reviews</small>
                                </div>
                            </div>

                            <button type="submit" name="update_notifications" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Save Notification Settings
                            </button>
                        </form>
                    </div>
                </div>

                <!-- System Preferences -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h5 class="card-title mb-4">System Preferences</h5>
                        <div class="mb-3">
                            <label class="form-label">Display Language</label>
                            <select class="form-select">
                                <option value="en">English</option>
                                <option value="es">Spanish</option>
                                <option value="fr">French</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Date Format</label>
                            <select class="form-select">
                                <option value="MM/DD/YYYY">MM/DD/YYYY</option>
                                <option value="DD/MM/YYYY">DD/MM/YYYY</option>
                                <option value="YYYY-MM-DD">YYYY-MM-DD</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Currency Display</label>
                            <select class="form-select">
                                <option value="USD">USD ($)</option>
                                <option value="EUR">EUR (€)</option>
                                <option value="GBP">GBP (£)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Data Management -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h5 class="card-title mb-4">Data Management</h5>
                        <div class="mb-4">
                            <label class="form-label">Export Reports</label>
                            <div class="d-flex gap-2">
                                <button class="btn btn-outline-primary">
                                    <i class="fas fa-file-excel me-2"></i>Export to Excel
                                </button>
                                <button class="btn btn-outline-primary">
                                    <i class="fas fa-file-pdf me-2"></i>Export to PDF
                                </button>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Cache Management</label>
                            <button class="btn btn-light" onclick="clearCache()">
                                <i class="fas fa-broom me-2"></i>Clear Cache
                            </button>
                        </div>

                        <div>
                            <label class="form-label text-danger">Danger Zone</label>
                            <button class="btn btn-outline-danger" onclick="return confirm('Are you sure? This cannot be undone.')">
                                <i class="fas fa-trash-alt me-2"></i>Delete Account
                            </button>
                        </div>
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
.form-control, .form-select {
    padding: 0.75rem 1rem;
    border-radius: 8px;
    border: 1px solid #e9ecef;
}

.form-control:focus, .form-select:focus {
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

.btn-outline-primary {
    border-color: var(--primary-color);
    color: var(--primary-color);
}

.btn-outline-primary:hover {
    background: var(--primary-color);
    border-color: var(--primary-color);
}

/* Switch Style */
.form-check-input:checked {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .container-fluid {
        padding: 1rem !important;
    }

    .card-body {
        padding: 1.5rem;
    }

    .btn {
        width: 100%;
        margin-bottom: 0.5rem;
    }
}
</style>

<script>
// Handle form submission loading state
document.querySelector('form').addEventListener('submit', function() {
    const btn = this.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
});

// Cache clearing simulation
function clearCache() {
    const btn = event.target;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Clearing...';
    
    setTimeout(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-broom me-2"></i>Clear Cache';
        alert('Cache cleared successfully!');
    }, 1500);
}

// Save settings confirmation
window.onbeforeunload = function() {
    if(document.querySelector('form').classList.contains('was-validated')) {
        return 'You have unsaved changes. Are you sure you want to leave?';
    }
}
</script>

</body>
</html>