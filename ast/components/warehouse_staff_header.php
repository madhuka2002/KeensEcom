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
        gap: 1rem;
    }

    .profile-image {
        width: 35px;
        height: 35px;
        background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        cursor: pointer;
    }
</style>

<!-- Primary Navigation -->
<nav class="navbar navbar-expand-lg modern-navbar sticky-top">
    <div class="container-fluid">
        <!-- Brand -->
        <a class="navbar-brand" href="../warehouse_staff/warehouse_staff_dashboard.php">
            Keens Warehouse
        </a>

        <!-- Mobile Toggle -->
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
            <i class="fas fa-bars"></i>
        </button>

        <!-- Main Navigation -->
        <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" 
                       href="../warehouse_staff/warehouse_staff_dashboard.php">
                        <i class="fas fa-chart-line me-2"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'inventory.php' ? 'active' : ''; ?>" 
                       href="../warehouse_staff/inventory.php">
                        <i class="fas fa-boxes me-2"></i>Inventory
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>" 
                       href="../warehouse_staff/orders.php">
                        <i class="fas fa-shopping-cart me-2"></i>Orders
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'pick_pack.php' ? 'active' : ''; ?>" 
                       href="../warehouse_staff/pick_pack.php">
                        <i class="fas fa-box me-2"></i>Pick & Pack
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'returns.php' ? 'active' : ''; ?>" 
                       href="../warehouse_staff/returns.php">
                        <i class="fas fa-undo-alt me-2"></i>Returns
                    </a>
                </li>
            </ul>

            <!-- Profile & Actions Section -->
            <?php
                $select_profile = $conn->prepare("SELECT * FROM `warehouse_staff` WHERE staff_id = ?");
                $select_profile->execute([$staff_id]);
                $fetch_profile = $select_profile->fetch(PDO::FETCH_ASSOC);
            ?>
            <div class="profile-section">
                <!-- Staff Quick Actions -->
                <div class="dropdown">
                    <button class="btn btn-light rounded-pill" data-bs-toggle="dropdown">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="../warehouse_staff/tasks.php">
                                <i class="fas fa-tasks me-2"></i>My Tasks
                            </a>
                        </li>
                        
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="../warehouse_staff/profile.php">
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
                            <small class="text-muted"><?= $fetch_profile['shift']; ?> Shift</small>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="../warehouse_staff/profile.php">
                                <i class="fas fa-user-circle me-2"></i>Profile
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="../warehouse_staff/shift_status.php">
                                <i class="fas fa-clock me-2"></i>Shift Status
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Logout Button -->
                <a href="index.php" 
                   style="text-decoration: none; color: red;"
                   onclick="return confirm('Are you sure you want to logout?');">
                    <i class="fas fa-sign-out-alt me-2"></i>
                </a>
            </div>
        </div>
    </div>
</nav>

<!-- Secondary Navigation -->
<nav class="navbar navbar-expand-lg modern-navbar">
    <div class="container-fluid">
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#secondaryNavContent">
            <i class="fas fa-ellipsis-v"></i>
        </button>

        <div class="collapse navbar-collapse" id="secondaryNavContent">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="../warehouse_staff/scan_item.php">
                        <i class="fas fa-barcode me-2"></i>Scan Item
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../warehouse_staff/new_stock.php">
                        <i class="fas fa-plus-circle me-2"></i>New Stock
                    </a>
                </li>
                <li class="nav-item">
                    <div class="nav-link">
                        <span class="badge bg-primary">
                            <?= $fetch_profile['shift']; ?> Shift
                        </span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</nav>