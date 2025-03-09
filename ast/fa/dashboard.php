<?php
include '../components/connect.php';

session_start();

$auditor_id = $_SESSION['auditor_id'];

if(!isset($auditor_id)){
   header('location:index.php');
   exit();
}

// Fetch auditor profile
$select_profile = $conn->prepare("SELECT * FROM `financial_auditors` WHERE auditor_id = ?");
$select_profile->execute([$auditor_id]);
$fetch_profile = $select_profile->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Keens | Financial Auditor Dashboard</title>
   
   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<?php include '../components/financial_auditor_header.php'; ?>

<section class="dashboard">
<div class="container-fluid">
    <div class="row">
        <!-- Main Content -->
        <main class="col px-4 py-5">
            <!-- Welcome Banner -->
            <div class="welcome-banner mb-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="fw-bold mb-2">Welcome back, <?= $fetch_profile['name']; ?>! 👋</h2>
                        <p class="text-muted mb-0">Here's an overview of your financial audit dashboard.</p>
                    </div>
                    <a href="profile.php" class="btn btn-glass">
                        <i class="fas fa-user-edit me-2"></i>Update Profile
                    </a>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row g-4 mb-4">
                <!-- Total Revenue -->
                <div class="col-12 col-sm-6 col-xl-3">
                    <?php
                        $total_revenue = 0;
                        $select_revenue = $conn->prepare("SELECT SUM(total_price) as total FROM `orders` WHERE payment_status = ?");
                        $select_revenue->execute(['completed']);
                        $revenue_result = $select_revenue->fetch(PDO::FETCH_ASSOC);
                        $total_revenue = $revenue_result['total'] ?? 0;
                    ?>
                    <div class="stat-card revenue">
                        <div class="card-content">
                            <div class="icon-wrapper">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                            <div class="stat-details">
                                <p class="stat-label">Total Revenue</p>
                                <h3 class="stat-value">$<?= number_format($total_revenue, 2); ?></h3>
                            </div>
                            <a href="financial_reports.php" class="stat-link">
                                Detailed Report
                                <i class="fas fa-arrow-right ms-2"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Pending Transactions -->
                <div class="col-12 col-sm-6 col-xl-3">
                    <?php
                        $pending_transactions = 0;
                        $select_pending = $conn->prepare("SELECT COUNT(*) as count FROM `orders` WHERE payment_status = ?");
                        $select_pending->execute(['pending']);
                        $pending_result = $select_pending->fetch(PDO::FETCH_ASSOC);
                        $pending_transactions = $pending_result['count'];
                    ?>
                    <div class="stat-card pending">
                        <div class="card-content">
                            <div class="icon-wrapper">
                                <i class="fas fa-hourglass-half"></i>
                            </div>
                            <div class="stat-details">
                                <p class="stat-label">Pending Transactions</p>
                                <h3 class="stat-value"><?= number_format($pending_transactions); ?></h3>
                            </div>
                            <a href="transaction_logs.php" class="stat-link">
                                View Transactions
                                <i class="fas fa-arrow-right ms-2"></i>
                            </a>
                        </div>
                    </div>
                </div>

                
            </div>

            <!-- Quick Actions -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Quick Actions</h5>
                            <div class="d-flex flex-wrap gap-3">
                                
                            
                                <a href="payment_gateway.php" class="btn btn-outline-info">
                                    <i class="fas fa-credit-card me-2"></i>Payment Gateway
                                </a>
                                <a href="transaction_logs.php" class="btn btn-outline-warning">
                                    <i class="fas fa-receipt me-2"></i>View Transaction Logs
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
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
    --info-color: #3498db;
    --pending-color: #e67e22;
    --risk-color: #e74c3c;
}

/* Welcome Banner */
.welcome-banner {
    background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
    padding: 2rem;
    border-radius: 20px;
    color: white;
    box-shadow: 0 10px 30px rgba(67, 97, 238, 0.15);
}

.btn-glass {
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 12px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-glass:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: translateY(-2px);
    color: white;
}

/* Stat Cards */
.stat-card {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    transition: all 0.3s ease;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
}

.card-content {
    padding: 1.5rem;
    position: relative;
}

.icon-wrapper {
    width: 50px;
    height: 50px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-bottom: 1rem;
}

.stat-details {
    margin-bottom: 1rem;
}

.stat-label {
    color: #6c757d;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
}

.stat-value {
    font-size: 1.8rem;
    font-weight: 700;
    margin: 0;
}

.stat-link {
    display: inline-flex;
    align-items: center;
    text-decoration: none;
    font-weight: 500;
    font-size: 0.9rem;
    transition: transform 0.3s ease;
}

.stat-link:hover {
    transform: translateX(5px);
}

/* Card Variants */
.stat-card.revenue .icon-wrapper {
    background: rgba(46, 204, 113, 0.1);
    color: var(--success-color);
}

.stat-card.revenue .stat-link {
    color: var(--success-color);
}

.stat-card.pending .icon-wrapper {
    background: rgba(230, 126, 34, 0.1);
    color: var(--pending-color);
}

.stat-card.pending .stat-link {
    color: var(--pending-color);
}

.stat-card.compliance .icon-wrapper {
    background: rgba(52, 152, 219, 0.1);
    color: var(--info-color);
}

.stat-card.compliance .stat-link {
    color: var(--info-color);
}

.stat-card.risk .icon-wrapper {
    background: rgba(231, 76, 60, 0.1);
    color: var(--risk-color);
}

.stat-card.risk .stat-link {
    color: var(--risk-color);
}

/* Quick Actions */
.btn-outline-primary {
    color: var(--primary-color);
    border-color: var(--primary-color);
}

.btn-outline-success {
    color: var(--success-color);
    border-color: var(--success-color);
}

.btn-outline-info {
    color: var(--info-color);
    border-color: var(--info-color);
}

.btn-outline-warning {
    color: var(--warning-color);
    border-color: var(--warning-color);
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .welcome-banner {
        padding: 1.5rem;
    }

    .welcome-banner h2 {
        font-size: 1.5rem;
    }

    .stat-card {
        margin-bottom: 1rem;
    }

    .stat-value {
        font-size: 1.5rem;
    }

    .icon-wrapper {
        width: 40px;
        height: 40px;
        font-size: 1.2rem;
    }
}
</style>

<script src="../js/financial_auditor_script.js"></script>
</body>
</html>