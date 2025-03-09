<?php
// Ensure session and database connection
if (!isset($_SESSION)) {
    session_start();
}

// Check if marketing manager is logged in
if (!isset($_SESSION['marketing_manager_id'])) {
    header('location:../marketing_manager/index.php');
    exit();
}

$marketing_manager_id = $_SESSION['marketing_manager_id'];
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

    .profile-section {
        display: flex;
        align-items: center;
        gap: 15px;
    }
</style>

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
        <a class="navbar-brand" href="../marketing_manager/marketing-manager-dashboard.php">
            Keens Marketing
        </a>

        <!-- Mobile Toggle -->
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
            <i class="fas fa-bars"></i>
        </button>

        <!-- Main Navigation -->
        <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'marketing_manager-dashboard.php' ? 'active' : ''; ?>" 
                       href="../marketing_manager/marketing-manager-dashboard.php">
                        <i class="fas fa-chart-line me-2"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'campaigns.php' ? 'active' : ''; ?>" 
                       href="../marketing_manager/campaigns.php">
                        <i class="fas fa-bullhorn me-2"></i>Campaigns
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'product-analytics.php' ? 'active' : ''; ?>" 
                       href="../marketing_manager/product-analytics.php">
                        <i class="fas fa-chart-bar me-2"></i>Product Analytics
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'promotions.php' ? 'active' : ''; ?>" 
                       href="../marketing_manager/promotions.php">
                        <i class="fas fa-tags me-2"></i>Promotions
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'customer-insights.php' ? 'active' : ''; ?>" 
                       href="../marketing_manager/customer-insights.php">
                        <i class="fas fa-users me-2"></i>Customer Insights
                    </a>
                </li>
            </ul>

            <!-- Profile & Actions Section -->
            <?php
                $select_profile = $conn->prepare("SELECT * FROM `marketing_manager` WHERE manager_id = ?");
                $select_profile->execute([$marketing_manager_id]);
                $fetch_profile = $select_profile->fetch(PDO::FETCH_ASSOC);
            ?>
            <div class="profile-section">
                <!-- Marketing Manager Quick Actions -->
                <div class="dropdown">
                    <button class="btn btn-light rounded-pill" data-bs-toggle="dropdown">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="../marketing_manager/reports.php">
                                <i class="fas fa-file-alt me-2"></i>Marketing Reports
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="../marketing_manager/loyalty-program.php">
                                <i class="fas fa-gift me-2"></i>Loyalty Program
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="../marketing_manager/profile.php">
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
                            <small class="text-muted">Marketing Manager</small>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="../marketing_manager/profile.php">
                                <i class="fas fa-user-circle me-2"></i>Profile
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="../marketing_manager/expertise.php">
                                <i class="fas fa-briefcase me-2"></i>Expertise
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
        <!-- Secondary Navigation Items -->
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#secondaryNavContent">
            <i class="fas fa-ellipsis-v"></i>
        </button>

        <div class="collapse navbar-collapse" id="secondaryNavContent">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="../marketing_manager/create-campaign.php">
                        <i class="fas fa-plus-circle me-2"></i>Create Campaign
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../marketing_manager/track-campaign-performance.php">
                        <i class="fas fa-chart-pie me-2"></i>Campaign Performance
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../marketing_manager/adjust-loyalty-program.php">
                        <i class="fas fa-cogs me-2"></i>Adjust Loyalty Program
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>