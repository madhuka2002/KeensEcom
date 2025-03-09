<?php
include '../components/connect.php';

session_start();

// Ensure only logged-in financial auditors can access
if(!isset($_SESSION['auditor_id'])){
   header('location:index.php');
   exit();
}

$auditor_id = $_SESSION['auditor_id'];

// Handle report generation
if(isset($_POST['generate_report'])){
    // Validate and sanitize inputs
    $report_type = $_POST['report_type'] ?? '';
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $report_title = $_POST['report_title'] ?? '';

    // Input validation
    $errors = [];
    if(empty($report_type)){
        $errors[] = "Report type is required.";
    }
    if(empty($start_date)){
        $errors[] = "Start date is required.";
    }
    if(empty($end_date)){
        $errors[] = "End date is required.";
    }
    if(strtotime($start_date) > strtotime($end_date)){
        $errors[] = "Start date must be before end date.";
    }

    // If no errors, proceed with report generation
    if(empty($errors)){
        try {
            // Comprehensive financial metrics calculation
            $metrics_query = $conn->prepare("
                SELECT 
                    COUNT(DISTINCT o.id) as total_orders,
                    SUM(o.total_price) as total_revenue,
                    AVG(o.total_price) as average_order_value,
                    SUM(CASE WHEN o.payment_status = 'completed' THEN o.total_price ELSE 0 END) as completed_revenue,
                    SUM(CASE WHEN o.payment_status = 'pending' THEN o.total_price ELSE 0 END) as pending_revenue,
                    (SELECT COUNT(*) FROM `products`) as total_products,
                    (SELECT COUNT(*) FROM `users`) as total_customers
                FROM `orders` o
                WHERE o.placed_on BETWEEN ? AND ?
            ");
            $metrics_query->execute([$start_date, $end_date]);
            $financial_metrics = $metrics_query->fetch(PDO::FETCH_ASSOC);

            // Prepare report data for insertion
            $insert_report = $conn->prepare("INSERT INTO `financial_reports` 
                (auditor_id, report_type, title, start_date, end_date, 
                total_revenue, total_expenses, net_profit, summary, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            // Calculate expenses (using supply orders as a proxy for expenses)
            $expenses_query = $conn->prepare("
                SELECT SUM(total_amount) as total_expenses 
                FROM `supply_orders` 
                WHERE order_date BETWEEN ? AND ?
            ");
            $expenses_query->execute([$start_date, $end_date]);
            $expenses = $expenses_query->fetch(PDO::FETCH_ASSOC);

            // Calculate net profit
            $total_revenue = $financial_metrics['total_revenue'] ?? 0;
            $total_expenses = $expenses['total_expenses'] ?? 0;
            $net_profit = $total_revenue - $total_expenses;

            // Generate summary
            $summary = sprintf(
                "Financial report for period %s to %s. Total orders: %d. Total revenue: $%0.2f. " .
                "Total expenses: $%0.2f. Net profit: $%0.2f. Average order value: $%0.2f. " .
                "Total products: %d. Total customers: %d.",
                $start_date, 
                $end_date, 
                $financial_metrics['total_orders'],
                $total_revenue,
                $total_expenses,
                $net_profit,
                $financial_metrics['average_order_value'],
                $financial_metrics['total_products'],
                $financial_metrics['total_customers']
            );

            // Insert the report
            $insert_report->execute([
                $auditor_id,
                $report_type,
                $report_title ?: "Financial Report - " . date('M Y', strtotime($start_date)),
                $start_date,
                $end_date,
                $total_revenue,
                $total_expenses,
                $net_profit,
                $summary,
                'draft'
            ]);

            $report_id = $conn->lastInsertId();
            $success_message = "Report generated successfully. Report ID: $report_id";

            // Log the report generation
            $log_query = $conn->prepare("INSERT INTO `financial_audit_logs` 
                (auditor_id, log_type, description, severity) 
                VALUES (?, ?, ?, ?)");
            $log_query->execute([
                $auditor_id, 
                'report_generation', 
                "Generated $report_type financial report for period $start_date to $end_date", 
                'low'
            ]);

        } catch(PDOException $e) {
            $errors[] = "Report generation failed: " . $e->getMessage();
            error_log("Report generation error: " . $e->getMessage());
        }
    }
}

// Fetch recent reports for display
try {
    $recent_reports_query = $conn->prepare("
        SELECT report_id, title, report_type, start_date, end_date, status, total_revenue, net_profit 
        FROM `financial_reports` 
        WHERE auditor_id = ? 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $recent_reports_query->execute([$auditor_id]);
    $recent_reports = $recent_reports_query->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Recent reports fetch error: " . $e->getMessage());
    $recent_reports = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Keens | Generate Financial Report</title>
   
   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<?php include '../components/financial_auditor_header.php'; ?>

<div class="container-fluid px-4 py-5">
    <div class="row">
        <!-- Report Generation Form -->
        <div class="col-12 col-xl-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h4 class="card-title mb-0">
                        <i class="fas fa-file-pdf me-2 text-primary"></i>Generate Financial Report
                    </h4>
                </div>
                
                <div class="card-body">
                    <?php 
                    // Display success or error messages
                    if(!empty($success_message)){
                        echo '<div class="alert alert-success">' . htmlspecialchars($success_message) . '</div>';
                    }
                    if(!empty($errors)){
                        echo '<div class="alert alert-danger">';
                        foreach($errors as $error){
                            echo htmlspecialchars($error) . '<br>';
                        }
                        echo '</div>';
                    }
                    ?>

                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Report Type</label>
                                <select name="report_type" class="form-select" required>
                                    <option value="">Select Report Type</option>
                                    <option value="monthly">Monthly</option>
                                    <option value="quarterly">Quarterly</option>
                                    <option value="annual">Annual</option>
                                    <option value="special">Special</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Report Title (Optional)</label>
                                <input type="text" name="report_title" class="form-control" 
                                       placeholder="Enter custom report title" 
                                       maxlength="255">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Start Date</label>
                                <input type="date" name="start_date" class="form-control" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">End Date</label>
                                <input type="date" name="end_date" class="form-control" required>
                            </div>

                            <div class="col-12">
                                <button type="submit" name="generate_report" class="btn btn-primary">
                                    <i class="fas fa-file-download me-2"></i>Generate Report
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Recent Reports -->
        <div class="col-12 col-xl-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h4 class="card-title mb-0">
                        <i class="fas fa-history me-2 text-primary"></i>Recent Reports
                    </h4>
                </div>
                
                <div class="card-body">
                    <?php if(empty($recent_reports)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-file-alt fa-3x mb-3 opacity-50"></i>
                            <p>No recent reports found.</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach($recent_reports as $report): ?>
                                <div class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">
                                            <span class="badge 
                                                <?php 
                                                switch($report['status']) {
                                                    case 'draft': echo 'bg-secondary'; break;
                                                    case 'reviewed': echo 'bg-info'; break;
                                                    case 'finalized': echo 'bg-success'; break;
                                                    default: echo 'bg-secondary';
                                                }
                                                ?>
                                            ">
                                                <?= ucfirst($report['status']); ?>
                                            </span>
                                            <?= htmlspecialchars($report['title']); ?>
                                        </h6>
                                        <small class="text-muted">
                                            <?= date('M d, Y', strtotime($report['start_date'])); ?> - 
                                            <?= date('M d, Y', strtotime($report['end_date'])); ?>
                                        </small>
                                    </div>
                                    <p class="mb-1">
                                        Revenue: $<?= number_format($report['total_revenue'], 2); ?> | 
                                        Net Profit: $<?= number_format($report['net_profit'], 2); ?>
                                    </p>
                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <small class="text-muted"><?= ucfirst($report['report_type']); ?> Report</small>
                                        <div class="btn-group btn-group-sm">
                                            <a href="view_report.php?id=<?= $report['report_id']; ?>" class="btn btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="export_report.php?id=<?= $report['report_id']; ?>" class="btn btn-outline-success">
                                                <i class="fas fa-file-export"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .list-group-item {
        transition: all 0.3s ease;
    }

    .list-group-item:hover {
        background-color: rgba(0,0,0,0.025);
        transform: translateY(-2px);
    }
</style>

<script>
    // Client-side form validation
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        form.addEventListener('submit', function(e) {
            const reportType = form.report_type.value;
            const startDate = form.start_date.value;
            const endDate = form.end_date.value;

            // Validate report type
            if (!reportType) {
                e.preventDefault();
                alert('Please select a report type.');
                return;
            }

            // Validate dates
            if (!startDate || !endDate) {
                e.preventDefault();
                alert('Please select both start and end dates.');
                return;
            }

            // Ensure start date is before end date
            if (new Date(startDate) > new Date(endDate)) {
                e.preventDefault();
                alert('Start date must be before end date.');
                return;
            }
        });
    });
</script>

<?php include '../components/footer.php'; ?>
</body>
</html>