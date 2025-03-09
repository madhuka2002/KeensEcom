<?php
   if(!isset($auditor_id)){
      header('location:../fa/index.php');
      exit();
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

    .profile-image {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: var(--primary-color);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        cursor: pointer;
    }

    .alert-message {
        position: fixed;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 1050;
        width: 90%;
        max-width: 500px;
    }
</style>

<!-- Message Alerts -->
<?php
   if(isset($message)){
      if (!is_array($message)) {
         $message = [$message];
      }
      foreach($message as $msg){
         echo '
         <div class="alert alert-info alert-dismissible fade show alert-message" role="alert">
            <i class="fas fa-info-circle me-2"></i>'.$msg.'
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
        <a class="navbar-brand" href="../fa/dashboard.php">
            Keens Financial Auditor
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
                       href="../fa/dashboard.php">
                        <i class="fas fa-chart-line me-2"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'transaction_logs.php' ? 'active' : ''; ?>" 
                       href="../fa/transaction_logs.php">
                        <i class="fas fa-receipt me-2"></i>Transaction Logs
                    </a>
                </li>
            </ul>

            <!-- Profile & Actions Section -->
            <?php
                $select_profile = $conn->prepare("SELECT * FROM `financial_auditors` WHERE auditor_id = ?");
                $select_profile->execute([$auditor_id]);
                $fetch_profile = $select_profile->fetch(PDO::FETCH_ASSOC);
            ?>
            <div class="profile-section d-flex align-items-center">
                <!-- Quick Actions -->
                <div class="dropdown me-3">
                    <button class="btn btn-light rounded-pill" data-bs-toggle="dropdown">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="../fa/profile.php">
                                <i class="fas fa-user-edit me-2"></i>Update Profile
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Profile -->
                <div class="dropdown me-3">
                    <div class="profile-image" role="button" data-bs-toggle="dropdown">
                        <?= strtoupper(substr($fetch_profile['name'], 0, 1)); ?>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li class="dropdown-header">
                            <h6 class="mb-0"><?= $fetch_profile['name']; ?></h6>
                            <small class="text-muted">Financial Auditor</small>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="../fa/profile.php">
                                <i class="fas fa-user-circle me-2"></i>Profile
                            </a>
                        </li>
            
                    </ul>
                </div>

                <!-- Logout Button -->
                <a href="index.php" 
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
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#secondaryNavContent">
            <i class="fas fa-ellipsis-v"></i>
        </button>

        <div class="collapse navbar-collapse" id="secondaryNavContent">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="performance-report.php">
                        <i class="fas fa-plus-circle me-2"></i>Performance Report
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="order-audit-report.php">
                        <i class="fas fa-plus-circle me-2"></i>Order Audit Report
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>