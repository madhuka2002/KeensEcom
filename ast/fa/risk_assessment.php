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

// Filter functionality
$filter_category = isset($_GET['category']) ? $_GET['category'] : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Prepare dynamic query
$query_params = [];
$where_clauses = [];

if (!empty($filter_category)) {
    $where_clauses[] = "risk_category = ?";
    $query_params[] = $filter_category;
}

if (!empty($filter_status)) {
    $where_clauses[] = "status = ?";
    $query_params[] = $filter_status;
}

if (!empty($search_query)) {
    $where_clauses[] = "(assessment_id LIKE ? OR supplier_id LIKE ? OR mitigation_strategy LIKE ?)";
    $query_params[] = "%$search_query%";
    $query_params[] = "%$search_query%";
    $query_params[] = "%$search_query%";
}

// Construct WHERE clause
$where_sql = $where_clauses ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Total records query
$total_query = "SELECT COUNT(*) as total FROM `risk_assessments` $where_sql";
$total_stmt = $conn->prepare($total_query);
$total_stmt->execute($query_params);
$total_records = $total_stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Calculate total pages
$total_pages = ceil($total_records / $records_per_page);

// Risk assessments query
$assessments_query = "SELECT ra.*, s.name as supplier_name 
                      FROM `risk_assessments` ra
                      LEFT JOIN `suppliers` s ON ra.supplier_id = s.supplier_id
                      $where_sql 
                      ORDER BY ra.assessment_date DESC 
                      LIMIT $records_per_page OFFSET $offset";
$assessments_stmt = $conn->prepare($assessments_query);
$assessments_stmt->execute($query_params);
$risk_assessments = $assessments_stmt->fetchAll(PDO::FETCH_ASSOC);

// Aggregate Risk Metrics
try {
    // Risk Category Distribution
    $risk_category_query = $conn->prepare("
        SELECT 
            risk_category, 
            COUNT(*) as count,
            ROUND(AVG(risk_score), 2) as avg_risk_score
        FROM `risk_assessments` 
        GROUP BY risk_category
    ");
    $risk_category_query->execute();
    $risk_category_distribution = $risk_category_query->fetchAll(PDO::FETCH_ASSOC);

    // Overall Risk Summary
    $overall_risk_query = $conn->prepare("
        SELECT 
            COUNT(*) as total_assessments,
            ROUND(AVG(risk_score), 2) as average_risk_score,
            SUM(CASE WHEN risk_score > 7 THEN 1 ELSE 0 END) as high_risk_count,
            SUM(CASE WHEN risk_score BETWEEN 4 AND 7 THEN 1 ELSE 0 END) as medium_risk_count,
            SUM(CASE WHEN risk_score < 4 THEN 1 ELSE 0 END) as low_risk_count
        FROM `risk_assessments`
    ");
    $overall_risk_query->execute();
    $overall_risk = $overall_risk_query->fetch(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    // Log error and set default values
    error_log("Risk assessment metrics error: " . $e->getMessage());
    $risk_category_distribution = [];
    $overall_risk = [
        'total_assessments' => 0,
        'average_risk_score' => 0,
        'high_risk_count' => 0,
        'medium_risk_count' => 0,
        'low_risk_count' => 0
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Keens | Risk Assessment</title>
   
   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
   <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<?php include '../components/financial_auditor_header.php'; ?>

<div class="container-fluid px-4 py-5">
    <div class="row">
        <!-- Risk Overview -->
        <div class="col-12 col-xl-4 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h4 class="card-title mb-0">
                        <i class="fas fa-exclamation-triangle me-2 text-primary"></i>Risk Overview
                    </h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6 mb-3">
                            <small class="text-muted d-block">Total Assessments</small>
                            <h5><?= number_format($overall_risk['total_assessments']); ?></h5>
                        </div>
                        <div class="col-6 mb-3">
                            <small class="text-muted d-block">Avg Risk Score</small>
                            <h5><?= number_format($overall_risk['average_risk_score'], 2); ?></h5>
                        </div>
                    </div>

                    <!-- Risk Level Distribution -->
                    <div class="risk-distribution mt-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-success">Low Risk</span>
                            <span class="text-warning">Medium Risk</span>
                            <span class="text-danger">High Risk</span>
                        </div>
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar bg-success" role="progressbar" 
                                 style="width: <?= ($overall_risk['low_risk_count'] / max(1, $overall_risk['total_assessments'])) * 100; ?>%" 
                                 aria-valuenow="<?= $overall_risk['low_risk_count']; ?>">
                                <?= $overall_risk['low_risk_count']; ?>
                            </div>
                            <div class="progress-bar bg-warning" role="progressbar" 
                                 style="width: <?= ($overall_risk['medium_risk_count'] / max(1, $overall_risk['total_assessments'])) * 100; ?>%" 
                                 aria-valuenow="<?= $overall_risk['medium_risk_count']; ?>">
                                <?= $overall_risk['medium_risk_count']; ?>
                            </div>
                            <div class="progress-bar bg-danger" role="progressbar" 
                                 style="width: <?= ($overall_risk['high_risk_count'] / max(1, $overall_risk['total_assessments'])) * 100; ?>%" 
                                 aria-valuenow="<?= $overall_risk['high_risk_count']; ?>">
                                <?= $overall_risk['high_risk_count']; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Risk Category Chart -->
                    <div class="mt-4">
                        <canvas id="riskCategoryChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Risk Assessments -->
        <div class="col-12 col-xl-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">
                            <i class="fas fa-chart-line me-2 text-primary"></i>Risk Assessments
                        </h4>
                        <div class="d-flex gap-2">
                            <a href="create_risk_assessment.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus me-1"></i>New Assessment
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- Filters -->
                    <form method="GET" class="mb-4">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <select name="category" class="form-select">
                                    <option value="">All Categories</option>
                                    <option value="financial" <?= $filter_category == 'financial' ? 'selected' : ''; ?>>Financial</option>
                                    <option value="operational" <?= $filter_category == 'operational' ? 'selected' : ''; ?>>Operational</option>
                                    <option value="compliance" <?= $filter_category == 'compliance' ? 'selected' : ''; ?>>Compliance</option>
                                    <option value="reputational" <?= $filter_category == 'reputational' ? 'selected' : ''; ?>>Reputational</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select name="status" class="form-select">
                                    <option value="">All Statuses</option>
                                    <option value="identified" <?= $filter_status == 'identified' ? 'selected' : ''; ?>>Identified</option>
                                    <option value="in_progress" <?= $filter_status == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                    <option value="mitigated" <?= $filter_status == 'mitigated' ? 'selected' : ''; ?>>Mitigated</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <div class="input-group">
                                    <input type="search" name="search" class="form-control" 
                                           placeholder="Search assessments..." 
                                           value="<?= htmlspecialchars($search_query); ?>">
                                    <button type="submit" class="btn btn-outline-secondary">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Risk Assessments Table -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Assessment ID</th>
                                    <th>Supplier</th>
                                    <th>Risk Category</th>
                                    <th>Risk Score</th>
                                    <th>Status</th>
                                    <th>Assessment Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($risk_assessments)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">No risk assessments found.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($risk_assessments as $assessment): ?>
                                        <tr>
                                            <td><?= $assessment['assessment_id']; ?></td>
                                            <td><?= htmlspecialchars($assessment['supplier_name'] ?? 'N/A'); ?></td>
                                            <td>
                                                <span class="badge 
                                                    <?php 
                                                    switch($assessment['risk_category']) {
                                                        case 'financial': echo 'bg-primary'; break;
                                                        case 'operational': echo 'bg-info'; break;
                                                        case 'compliance': echo 'bg-warning'; break;
                                                        case 'reputational': echo 'bg-danger'; break;
                                                        default: echo 'bg-secondary';
                                                    }
                                                    ?>
                                                ">
                                                    <?= ucfirst($assessment['risk_category']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge 
                                                    <?php 
                                                    $score = $assessment['risk_score'];
                                                    echo $score < 4 ? 'bg-success' : 
                                                         ($score < 7 ? 'bg-warning' : 'bg-danger');
                                                    ?>
                                                ">
                                                    <?= number_format($score, 2); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge 
                                                    <?php 
                                                    switch($assessment['status']) {
                                                        case 'identified': echo 'bg-secondary'; break;
                                                        case 'in_progress': echo 'bg-warning'; break;
                                                        case 'mitigated': echo 'bg-success'; break;
                                                        default: echo 'bg-secondary';
                                                    }
                                                    ?>
                                                ">
                                                    <?= ucfirst($assessment['status']); ?>
                                                </span>
                                            </td>
                                            <td><?= date('M d, Y', strtotime($assessment['assessment_date'])); ?></td>
                                            <td>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li>
                                                            <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#riskAssessmentModal
                                                            <?= $assessment['assessment_id']; ?>">
                                                                <i class="fas fa-eye me-2"></i>View Details
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item" href="edit_risk_assessment.php?id=<?= $assessment['assessment_id']; ?>">
                                                                <i class="fas fa-edit me-2"></i>Edit Assessment
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
                    <nav aria-label="Risk assessments page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= $page == $i ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?= $i; ?>&category=<?= urlencode($filter_category); ?>&status=<?= urlencode($filter_status); ?>&search=<?= urlencode($search_query); ?>">
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

<!-- Risk Assessment Detail Modals -->
<?php if (!empty($risk_assessments)): ?>
    <?php foreach ($risk_assessments as $assessment): ?>
        <div class="modal fade" id="riskAssessmentModal<?= $assessment['assessment_id']; ?>" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Risk Assessment Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Assessment Information</h6>
                                <table class="table table-borderless">
                                    <tr>
                                        <th>Assessment ID:</th>
                                        <td><?= $assessment['assessment_id']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Supplier:</th>
                                        <td><?= htmlspecialchars($assessment['supplier_name'] ?? 'N/A'); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Risk Category:</th>
                                        <td>
                                            <span class="badge 
                                                <?php 
                                                switch($assessment['risk_category']) {
                                                    case 'financial': echo 'bg-primary'; break;
                                                    case 'operational': echo 'bg-info'; break;
                                                    case 'compliance': echo 'bg-warning'; break;
                                                    case 'reputational': echo 'bg-danger'; break;
                                                    default: echo 'bg-secondary';
                                                }
                                                ?>
                                            ">
                                                <?= ucfirst($assessment['risk_category']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6>Risk Details</h6>
                                <table class="table table-borderless">
                                    <tr>
                                        <th>Risk Score:</th>
                                        <td>
                                            <span class="badge 
                                                <?php 
                                                $score = $assessment['risk_score'];
                                                echo $score < 4 ? 'bg-success' : 
                                                     ($score < 7 ? 'bg-warning' : 'bg-danger');
                                                ?>
                                            ">
                                                <?= number_format($score, 2); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Status:</th>
                                        <td>
                                            <span class="badge 
                                                <?php 
                                                switch($assessment['status']) {
                                                    case 'identified': echo 'bg-secondary'; break;
                                                    case 'in_progress': echo 'bg-warning'; break;
                                                    case 'mitigated': echo 'bg-success'; break;
                                                    default: echo 'bg-secondary';
                                                }
                                                ?>
                                            ">
                                                <?= ucfirst($assessment['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Assessment Date:</th>
                                        <td><?= date('M d, Y', strtotime($assessment['assessment_date'])); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <h6>Mitigation Strategy</h6>
                            <p><?= htmlspecialchars($assessment['mitigation_strategy'] ?? 'No mitigation strategy provided'); ?></p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <a href="edit_risk_assessment.php?id=<?= $assessment['assessment_id']; ?>" class="btn btn-primary">
                            <i class="fas fa-edit me-2"></i>Edit Assessment
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Export Modal -->
<div class="modal fade" id="exportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Export Risk Assessments</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="exportRiskAssessmentsForm">
                    <div class="mb-3">
                        <label class="form-label">Export Format</label>
                        <select class="form-select" name="export_format">
                            <option value="pdf">PDF</option>
                            <option value="csv">CSV</option>
                            <option value="excel">Excel</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date Range</label>
                        <div class="input-group">
                            <input type="date" class="form-control" name="start_date">
                            <span class="input-group-text">to</span>
                            <input type="date" class="form-control" name="end_date">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Filter by Risk Category</label>
                        <select class="form-select" name="risk_category">
                            <option value="">All Categories</option>
                            <option value="financial">Financial</option>
                            <option value="operational">Operational</option>
                            <option value="compliance">Compliance</option>
                            <option value="reputational">Reputational</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-file-export me-2"></i>Export Assessments
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Risk Category Chart
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('riskCategoryChart').getContext('2d');
        
        // Prepare data from PHP
        const categoryData = <?= json_encode(array_column($risk_category_distribution, 'count', 'risk_category')); ?>;
        const categoryAvgScores = <?= json_encode(array_column($risk_category_distribution, 'avg_risk_score', 'risk_category')); ?>;
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: Object.keys(categoryData),
                datasets: [{
                    label: 'Number of Assessments',
                    data: Object.values(categoryData),
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.7)',   // Financial (Blue)
                        'rgba(255, 206, 86, 0.7)',   // Operational (Yellow)
                        'rgba(75, 192, 192, 0.7)',   // Compliance (Green)
                        'rgba(255, 99, 132, 0.7)'    // Reputational (Red)
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Risk Assessments by Category'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const category = context.label;
                                const count = context.parsed.y;
                                const avgScore = categoryAvgScores[category];
                                return `Assessments: ${count} (Avg Score: ${avgScore.toFixed(2)})`;
                            }
                        }
                    }
                }
            }
        });
    });

    // Export Form Handling
    document.getElementById('exportRiskAssessmentsForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Basic form validation
        const startDate = this.start_date.value;
        const endDate = this.end_date.value;
        
        if (startDate && endDate && new Date(startDate) > new Date(endDate)) {
            alert('Start date must be before end date');
            return;
        }
        
        // Prepare form data
        const formData = new FormData(this);
        
        // AJAX request to export endpoint
        fetch('export_risk_assessments.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Export failed');
            }
            return response.blob();
        })
        .then(blob => {
            // Create download link
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.style.display = 'none';
            a.href = url;
            
            // Determine file extension
            const format = formData.get('export_format');
            a.download = `risk_assessments.${format}`;
            
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
        })
        .catch(error => {
            console.error('Export error:', error);
            alert('Failed to export risk assessments');
        });
    });
</script>

<style>
    .table-borderless th {
        width: 40%;
        font-weight: 500;
    }

    .progress {
        background-color: #e9ecef;
    }
</style>

<?php include '../components/footer.php'; ?>
</body>
</html>