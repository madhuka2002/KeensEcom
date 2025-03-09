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

    .nav-link {
        position: relative;
        padding: 0.5rem 1rem !important;
        margin: 0 0.2rem;
        border-radius: 8px;
        transition: all 0.3s ease;
    }

    .nav-link:hover {
        background: rgba(67, 97, 238, 0.05);
        transform: translateY(-1px);
    }

    .nav-link.active {
        background: rgba(67, 97, 238, 0.1);
        color: var(--primary-color) !important;
        font-weight: 600;
    }

    .nav-link.active::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 4px;
        height: 4px;
        border-radius: 50%;
        background-color: var(--primary-color);
    }

    .profile-section {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .profile-image {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
    }

    .logout-btnn {
        background: linear-gradient(45deg, var(--danger-color), #ff4d6d);
        border: none;
        color: white;
        padding: 0.5rem 1.25rem;
        border-radius: 8px;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .logout-btnn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(239, 35, 60, 0.2);
    }

    .alert-message {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 1050;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        animation: slideIn 0.3s ease forwards;
    }

    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }   
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
    <script src="adminHeader.js"></script>
    <div class="container-fluid">
        <!-- Brand -->
        <a class="navbar-brand" href="../admin/dashboard.php">
            Keens
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
                       href="../admin/dashboard.php">
                        <i class="fas fa-chart-line me-2"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>" 
                       href="../admin/products.php">
                        <i class="fas fa-box me-2"></i>Products
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>" 
                       href="../admin/orders.php">
                        <i class="fas fa-shopping-cart me-2"></i>Orders
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>" 
                       href="../admin/users.php">
                        <i class="fas fa-users me-2"></i>Users
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'messages.php' ? 'active' : ''; ?>" 
                       href="../admin/messages.php">
                        <i class="fas fa-envelope me-2"></i>Messages
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>" 
                       href="../admin/categories.php">
                        <i class="fas fa-envelope me-2"></i>Categories
                    </a>
                </li>
            </ul>

            <!-- Profile & Actions Section -->
            <?php
                $select_profile = $conn->prepare("SELECT * FROM `admins` WHERE id = ?");
                $select_profile->execute([$admin_id]);
                $fetch_profile = $select_profile->fetch(PDO::FETCH_ASSOC);
            ?>
            <div class="profile-section">
                <!-- Admin Quick Actions -->
                <div class="dropdown">
                    <button class="btn btn-light rounded-pill" data-bs-toggle="dropdown">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="../admin/add-admin.php">
                                <i class="fas fa-user-plus me-2"></i>Add Admin
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="../admin/admin-accounts.php">
                                <i class="fas fa-user-shield me-2"></i>Admin List
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="../admin/account.php">
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
                            <small class="text-muted">Administrator</small>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="../admin/account.php">
                                <i class="fas fa-user-circle me-2"></i>Profile
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="#">
                                <i class="fas fa-cog me-2"></i>Settings
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Logout Button -->
                <a href="../components/admin_logout.php" 
                   class=""
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
        <!-- Page Title -->
        <span class="navbar-text">
            <!-- <?= ucfirst(str_replace('.php', '', basename($_SERVER['PHP_SELF']))); ?> -->
        </span>

        <!-- Secondary Navigation Items -->
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#secondaryNavContent">
            <i class="fas fa-ellipsis-v"></i>
        </button>

        <div class="collapse navbar-collapse" id="secondaryNavContent">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="fas fa-search me-2"></i>Search
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="fas fa-filter me-2"></i>Filter
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../admin/salesreport.php">
                        <i class="fas fa-plus me-2"></i>New Report
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<style>
.modern-navbar {
    background-color: #fff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.profile-section {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.profile-image {
    width: 40px;
    height: 40px;
    background-color: #0d6efd;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    cursor: pointer;
}

.logout-btn {
    text-decoration: none;
    color: #dc3545;
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    transition: background-color 0.3s;
    display: flex;
    align-items: center;
}

.logout-btn:hover {
    background-color: rgba(220, 53, 69, 0.1);
    color: #dc3545;
}

.nav-link {
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    transition: all 0.3s;
}

.nav-link:hover, .nav-link.active {
    background-color: rgba(13, 110, 253, 0.1);
    color: #0d6efd;
}

.dropdown-menu {
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border-radius: 0.75rem;
}

.dropdown-item {
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    margin: 0.25rem;
}

.dropdown-item:hover {
    background-color: rgba(13, 110, 253, 0.1);
}
</style>