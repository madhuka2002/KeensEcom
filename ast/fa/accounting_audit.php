<?php
include '../components/connect.php';

session_start();

// Ensure only logged-in financial auditors can access
if(!isset($_SESSION['auditor_id'])){
   header('location:index.php');
   exit();
}

$auditor_id = $_SESSION['auditor_id'];

// Fetch key accounting metrics
try {
    // Total Revenue
    $revenue_query = $conn->prepare("SELECT 
        SUM(total_price) as total_revenue, 
        COUNT(*) as total_transactions 
        FROM `orders` 
        WHERE payment_status = 'completed'");
    $revenue_query->execute();
    $revenue_data = $revenue_query->fetch(PDO::FETCH_ASSOC);

    // Expense Tracking
    $expense_query = $conn->prepare("SELECT 
        SUM(total_amount) as total_expenses 
        FROM `supply_orders` 
        WHERE status = 'completed'");
    $expense_query->execute();
    $expense_data = $expense_query->fetch(PDO::FETCH_ASSOC);

    // Supplier Compliance
    $compliance_query = $conn->prepare("SELECT 
        COUNT(*) as total_suppliers,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as compliant_suppliers
        FROM `suppliers`");
    $compliance_query->execute();
    $compliance_data = $compliance_query->fetch(PDO::FETCH_ASSOC);

    // Recent Audit Logs
    $audit_logs_query = $conn->prepare("SELECT * FROM `financial_audit_logs` 
        ORDER BY timestamp DESC 
        LIMIT 10");
    $audit_logs_query->execute();
    $audit_logs = $audit_logs_query->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    // Log error and set default values
    error_log("Accounting audit query error: " . $e->getMessage());
    $revenue_data = ['total_revenue' => 0, 'total_transactions' => 0];
    $expense_data = ['total_expenses' => 0];
    $compliance_data = ['total_suppliers' => 0, 'compliant_suppliers' => 0];
    $audit_logs = [];
}

// Calculate profit and financial health indicators
$total_revenue = $revenue_data['total_revenue'] ?? 0;
$total_expenses = $expense_data['total_expenses'] ?? 0;
$net_profit = $total_revenue - $total_expenses;
$profit_margin = $total_revenue > 0 ? ($net_profit / $total_revenue) * 100 : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Keens | Accounting Audit</title>
   
   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<?php include '../components/financial_auditor_header.php'; ?>

<div class="container-fluid px-4 py-5">
    <div class="row">
        <!-- Financial Overview -->
        <div class="col-12 col-xl-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h4 class="card-title mb-0">
                        <i class="fas fa-calculator me-2 text-primary"></i>Accounting Audit Dashboard
                    </h4>
                </div>
                
                <div class="card-body">
                    <!-- Financial Metrics -->
                    <div class="row g-4">
                        <!-- Total Revenue -->
                        <div class="col-md-4">
                            <div class="bg-light p-3 rounded">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Total Revenue</h6>
                                        <h4 class="mb-0">$<?= number_format($total_revenue, 2); ?></h4>
                                    </div>
                                    <i class="fas fa-chart-line text-primary fa-2x opacity-50"></i>
                                </div>
                                <small class="text-muted">
                                    <?= $revenue_data['total_transactions'] ?? 0; ?> Completed Transactions
                                </small>
                            </div>
                        </div>

                        <!-- Total Expenses -->
                        <div class="col-md-4">
                            <div class="bg-light p-3 rounded">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Total Expenses</h6>
                                        <h4 class="mb-0">$<?= number_format($total_expenses, 2); ?></h4>
                                    </div>
                                    <i class="fas fa-wallet text-warning fa-2x opacity-50"></i>
                                </div>
                                <small class="text-muted">Supply Chain Costs</small>
                            </div>
                        </div>

                        <!-- Net Profit -->
                        <div class="col-md-4">
                            <div class="bg-light p-3 rounded">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Net Profit</h6>
                                        <h4 class="mb-0">$<?= number_format($net_profit, 2); ?></h4>
                                    </div>
                                    <i class="fas fa-money-bill-wave text-success fa-2x opacity-50"></i>
                                </div>
                                <small class="text-<?= $profit_margin >= 0 ? 'success' : 'danger'; ?>">
                                    <?= number_format($profit_margin, 2); ?>% Profit Margin
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Compliance Metrics -->
                    <div class="row g-4 mt-3">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <i class="fas fa-clipboard-check me-2 text-info"></i>Supplier Compliance
                                    </h6>
                                    <div class="progress mt-3" style="height: 20px;">
                                        <?php 
                                        $compliance_percentage = $compliance_data['total_suppliers'] > 0 
                                            ? ($compliance_data['compliant_suppliers'] / $compliance_data['total_suppliers']) * 100 
                                            : 0;
                                        ?>
                                        <div class="progress-bar" role="progressbar" 
                                             style="width: <?= $compliance_percentage; ?>%" 
                                             aria-valuenow="<?= $compliance_percentage; ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                            <?= number_format($compliance_percentage, 1); ?>%
                                        </div>
                                    </div>
                                    <div class="mt-2 text-muted">
                                        <?= $compliance_data['compliant_suppliers']; ?> / 
                                        <?= $compliance_data['total_suppliers']; ?> Suppliers Compliant
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <i class="fas fa-list-alt me-2 text-primary"></i>Quick Audit Actions
                                    </h6>
                                    <div class="d-flex flex-wrap gap-2 mt-3">
                                        <a href="generate_report.php" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-file-pdf me-2"></i>Generate Report
                                        </a>
                                        <a href="compliance_reports.php" class="btn btn-outline-info btn-sm">
                                            <i class="fas fa-clipboard-check me-2"></i>Compliance Check
                                        </a>
                                        <a href="risk_assessment.php" class="btn btn-outline-warning btn-sm">
                                            <i class="fas fa-exclamation-triangle me-2"></i>Risk Assessment
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Audit Logs -->
        <div class="col-12 col-xl-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h4 class="card-title mb-0">
                        <i class="fas fa-clipboard-list me-2 text-primary"></i>Recent Audit Logs
                    </h4>
                </div>
                
                <div class="card-body">
                    <?php if (empty($audit_logs)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No recent audit logs.</p>
                        </div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($audit_logs as $log): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <small class="text-muted d-block">
                                            <?= htmlspecialchars($log['log_type']); ?>
                                        </small>
                                        <span><?= htmlspecialchars($log['description']); ?></span>
                                    </div>
                                    <span class="badge bg-<?= 
                                        $log['severity'] == 'high' ? 'danger' : 
                                        ($log['severity'] == 'medium' ? 'warning' : 'secondary')
                                    ?> rounded-pill">
                                        <?= htmlspecialchars($log['severity']); ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="text-center mt-3">
                            <a href="audit_logs.php" class="btn btn-sm btn-outline-primary">
                                View All Logs
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-light {
        background-color: #f8f9fa !important;
    }
</style>

<?php include '../components/footer.php'; ?>
</body>
</html>