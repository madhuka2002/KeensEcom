<style>
    :root {
        --primary-color: #4361ee;
        --secondary-color: #3f37c9;
        --accent-color: #4895ef;
    }

    .modern-navbar {
        background: white;
        padding: 1rem;
        box-shadow: 0 2px 15px rgba(0,0,0,0.05);
    }

    .navbar-brand {
        font-size: 1.5rem;
        font-weight: 700;
        background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        letter-spacing: -0.5px;
    }
    
    .profile-section {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .profile-image {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        cursor: pointer;
    }

    /* Rest of the base styles remain the same as your reference */
</style>

<!-- Message Alerts -->
<?php
   if(isset($message)){
      foreach($message as $message){
         echo '
         <div class="alert alert-info alert-dismissible fade show alert-message" role="alert">
            <i class="fas fa-info-circle me-2"></i>'.$message.'
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
         </div>
         ';
      }
   }
?>

<!-- Primary Navigation -->
<nav class="navbar navbar-expand-lg modern-navbar sticky-top">
    <div class="container-fluid">
        <!-- Brand -->
        <a class="navbar-brand" href="../supplier/supplier_dashboard.php">
            <i class="fas fa-store me-2"></i>Keens
        </a>

        <!-- Mobile Toggle -->
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
            <i class="fas fa-bars"></i>
        </button>

        <!-- Main Navigation -->
        <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'supplier_dashboard.php' ? 'active' : ''; ?>" 
                       href="../supplier/supplier_dashboard.php">
                        <i class="fas fa-chart-line me-2"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'inventory.php' ? 'active' : ''; ?>" 
                       href="../supplier/inventory.php">
                        <i class="fas fa-boxes me-2"></i>Inventory
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>" 
                       href="../supplier/products.php">
                        <i class="fas fa-box me-2"></i>Products
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'supply_orders.php' ? 'active' : ''; ?>" 
                       href="../supplier/supply_orders.php">
                        <i class="fas fa-truck-loading me-2"></i>Orders
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'stock_updates.php' ? 'active' : ''; ?>" 
                       href="../supplier/stock_updates.php">
                        <i class="fas fa-sync me-2"></i>Updates
                    </a>
                </li>
            </ul>

            <!-- Profile & Actions Section -->
            <?php
                $select_profile = $conn->prepare("SELECT * FROM `suppliers` WHERE supplier_id = ?");
                $select_profile->execute([$supplier_id]);
                $fetch_profile = $select_profile->fetch(PDO::FETCH_ASSOC);
            ?>
            <div class="profile-section">
                <!-- Supplier Quick Actions -->
                <div class="dropdown">
                    <button class="btn btn-light rounded-pill" data-bs-toggle="dropdown">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="../supplier/restock_requests.php">
                                <i class="fas fa-plus-circle me-2"></i>Restock Requests
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="../supplier/analytics.php">
                                <i class="fas fa-chart-bar me-2"></i>Analytics
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="../supplier/profile.php">
                                <i class="fas fa-user-edit me-2"></i>Update Profile
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Profile -->
                <div class="dropdown">
                    <div class="profile-image" role="button" data-bs-toggle="dropdown">
                        <?= strtoupper(substr($fetch_profile['name'], 0, 1)); ?>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li class="dropdown-header">
                            <h6 class="mb-0"><?= $fetch_profile['name']; ?></h6>
                            <small class="text-muted">Supplier</small>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="../supplier/profile.php">
                                <i class="fas fa-user-circle me-2"></i>Profile
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="../supplier/communication.php">
                                <i class="fas fa-comments me-2"></i>Communications
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center text-danger" href="../supplier/index.php" 
                               onclick="return confirm('Are you sure you want to logout?');">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>

<!-- Secondary Navigation -->
<nav class="navbar navbar-expand-lg modern-navbar">
    <div class="container-fluid">
        <!-- Secondary Navigation Items -->
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#secondaryNavContent">
            <i class="fas fa-ellipsis-v"></i>
        </button>

        <div class="collapse navbar-collapse" id="secondaryNavContent">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="../supplier/new_product.php">
                        <i class="fas fa-plus-circle me-2"></i>Add Product
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../supplier/inventory_alert.php">
                        <i class="fas fa-bell me-2"></i>Inventory Alerts
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>