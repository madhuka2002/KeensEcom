<?php
include '../components/connect.php';

session_start();

// Ensure only logged-in financial auditors can access
if(!isset($_SESSION['auditor_id'])){
   header('location:index.php');
   exit();
}

$auditor_id = $_SESSION['auditor_id'];

// Pagination setup
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Filter and search functionality
$filter_type = isset($_GET['type']) ? $_GET['type'] : '';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Base query with flexible filtering
$query_params = [];
$where_clauses = [];

if (!empty($filter_type)) {
    $where_clauses[] = "report_type = ?";
    $query_params[] = $filter_type;
}

if (!empty($search_query)) {
    $where_clauses[] = "(report_id LIKE ? OR total_revenue LIKE ? OR net_profit LIKE ?)";
    $query_params[] = "%$search_query%";
    $query_params[] = "%$search_query%";
    $query_params[] = "%$search_query%";
}

// Construct the WHERE clause
$where_sql = $where_clauses ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Total records query
$total_query = "SELECT COUNT(*) as total FROM `financial_reports` $where_sql";
$total_stmt = $conn->prepare($total_query);
$total_stmt->execute($query_params);
$total_records = $total_stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Calculate total pages
$total_pages = ceil($total_records / $records_per_page);

// Reports query
$reports_query = "SELECT * FROM `financial_reports` 
                  $where_sql 
                  ORDER BY created_at DESC 
                  LIMIT $records_per_page OFFSET $offset";
$reports_stmt = $conn->prepare($reports_query);
$reports_stmt->execute($query_params);
$reports = $reports_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Keens | Financial Reports</title>
   
   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<?php include '../components/financial_auditor_header.php'; ?>

<div class="container-fluid px-4 py-5">
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">
                            <i class="fas fa-file-invoice-dollar me-2 text-primary"></i>Financial Reports
                        </h4>
                        <div class="d-flex gap-2">
                            <a href="generate_report.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus me-1"></i>Generate New Report
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- Filters and Search -->
                    <form method="GET" class="mb-4">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <select name="type" class="form-select">
                                    <option value="">All Report Types</option>
                                    <option value="monthly" <?= $filter_type == 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                                    <option value="quarterly" <?= $filter_type == 'quarterly' ? 'selected' : ''; ?>>Quarterly</option>
                                    <option value="annual" <?= $filter_type == 'annual' ? 'selected' : ''; ?>>Annual</option>
                                    <option value="special" <?= $filter_type == 'special' ? 'selected' : ''; ?>>Special</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <div class="input-group">
                                    <input type="search" name="search" class="form-control" 
                                           placeholder="Search reports..." 
                                           value="<?= htmlspecialchars($search_query); ?>">
                                    <button type="submit" class="btn btn-outline-secondary">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Reports Table -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Report ID</th>
                                    <th>Report Type</th>
                                    <th>Period</th>
                                    <th>Total Revenue</th>
                                    <th>Net Profit</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($reports)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <i class="fas fa-file-csv fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">No financial reports found.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($reports as $report): ?>
                                        <tr>
                                            <td><?= $report['report_id']; ?></td>
                                            <td>
                                                <span class="badge 
                                                    <?php 
                                                    switch($report['report_type']) {
                                                        case 'monthly': echo 'bg-info'; break;
                                                        case 'quarterly': echo 'bg-primary'; break;
                                                        case 'annual': echo 'bg-success'; break;
                                                        default: echo 'bg-secondary';
                                                    }
                                                    ?>
                                                ">
                                                    <?= ucfirst($report['report_type']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?= date('M d, Y', strtotime($report['start_date'])); ?> - 
                                                <?= date('M d, Y', strtotime($report['end_date'])); ?>
                                            </td>
                                            <td>$<?= number_format($report['total_revenue'], 2); ?></td>
                                            <td>$<?= number_format($report['net_profit'], 2); ?></td>
                                            <td>
                                                <span class="badge 
                                                    <?php 
                                                    switch($report['status']) {
                                                        case 'draft': echo 'bg-warning'; break;
                                                        case 'reviewed': echo 'bg-info'; break;
                                                        case 'finalized': echo 'bg-success'; break;
                                                        default: echo 'bg-secondary';
                                                    }
                                                    ?>
                                                ">
                                                    <?= ucfirst($report['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li>
                                                            <a class="dropdown-item" href="view_report.php?id=<?= $report['report_id']; ?>">
                                                                <i class="fas fa-eye me-2"></i>View Details
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item" href="export_report.php?id=<?= $report['report_id']; ?>">
                                                                <i class="fas fa-file-export me-2"></i>Export PDF
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <nav aria-label="Report page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= $page == $i ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?= $i; ?>&type=<?= urlencode($filter_type); ?>&search=<?= urlencode($search_query); ?>">
                                        <?= $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Custom styles can be added here if needed */
    .table-hover tbody tr:hover {
        background-color: rgba(0, 0, 0, 0.05);
    }
</style>

<?php include '../components/footer.php'; ?>
</body>
</html>