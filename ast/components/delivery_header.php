<?php
// Ensure the delivery agent is logged in
if (!isset($_SESSION['delivery_agent_id'])) {
    header('location:../delivery/index.php');
    exit();
}

$delivery_agent_id = $_SESSION['delivery_agent_id'];
?>

<style>
    :root {
        --primary-color: #4361ee;
        --secondary-color: #3f37c9;
        --accent-color: #4895ef;
        --danger-color: #dc3545;
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

    .logout-btn {
        background: linear-gradient(45deg, var(--danger-color), #ff4d6d);
        border: none;
        color: white;
        padding: 0.5rem 1.25rem;
        border-radius: 8px;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .logout-btn:hover {
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
    <div class="container-fluid">
        <!-- Brand -->
        <a class="navbar-brand" href="../delivery/dashboard.php">
            Keens Delivery
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
                       href="../delivery/dashboard.php">
                        <i class="fas fa-chart-line me-2"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'pickup.php' ? 'active' : ''; ?>" 
                       href="../delivery/pickup.php">
                        <i class="fas fa-truck-pickup me-2"></i>Order Pickup
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'delivery-history.php' ? 'active' : ''; ?>" 
                       href="../delivery/history.php">
                        <i class="fas fa-history me-2"></i>Delivery History
                    </a>
                </li>
            </ul>

            <!-- Profile & Actions Section -->
            <?php
                $select_profile = $conn->prepare("SELECT * FROM `delivery_agents` WHERE id = ?");
                $select_profile->execute([$delivery_agent_id]);
                $fetch_profile = $select_profile->fetch(PDO::FETCH_ASSOC);
            ?>
            <div class="profile-section">
                <!-- Profile -->
                <div class="dropdown">
                    <div class="profile-image" role="button" data-bs-toggle="dropdown">
                        <?= strtoupper(substr($fetch_profile['name'], 0, 1)); ?>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li class="dropdown-header">
                            <h6 class="mb-0"><?= $fetch_profile['name']; ?></h6>
                            <small class="text-muted">Delivery Agent</small>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="#">
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

<!-- Logout Script -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const logoutLinks = document.querySelectorAll('.logout-link');
        logoutLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to logout?')) {
                    e.preventDefault();
                }
            });
        });
    });
</script>