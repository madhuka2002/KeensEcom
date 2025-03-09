<?php
include '../components/connect.php';

session_start();

// Ensure only logged-in financial auditors can access
if(!isset($_SESSION['auditor_id'])){
   header('location:index.php');
   exit();
}

$auditor_id = $_SESSION['auditor_id'];

// Fetch suppliers for dropdown
try {
    $suppliers_query = $conn->prepare("SELECT supplier_id, name FROM `suppliers`");
    $suppliers_query->execute();
    $suppliers = $suppliers_query->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Suppliers fetch error: " . $e->getMessage());
    $suppliers = [];
}

// Handle risk assessment creation
if(isset($_POST['create_assessment'])){
    // Validate and sanitize inputs
    $supplier_id = $_POST['supplier_id'] ?? null;
    $risk_category = $_POST['risk_category'] ?? '';
    $risk_score = floatval($_POST['risk_score'] ?? 0);
    $mitigation_strategy = $_POST['mitigation_strategy'] ?? '';
    $status = $_POST['status'] ?? 'identified';
    $assessment_date = $_POST['assessment_date'] ?? date('Y-m-d');

    // Input validation
    $errors = [];
    if(empty($risk_category)){
        $errors[] = "Risk category is required.";
    }
    if($risk_score < 0 || $risk_score > 10){
        $errors[] = "Risk score must be between 0 and 10.";
    }
    if(empty($mitigation_strategy)){
        $errors[] = "Mitigation strategy is required.";
    }

    // If no errors, proceed with risk assessment creation
    if(empty($errors)){
        try {
            // Prepare and execute the insert
            $insert_assessment = $conn->prepare("INSERT INTO `risk_assessments` 
                (auditor_id, supplier_id, risk_category, risk_score, 
                mitigation_strategy, status, assessment_date) 
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            $insert_assessment->execute([
                $auditor_id,
                $supplier_id,
                $risk_category,
                $risk_score,
                $mitigation_strategy,
                $status,
                $assessment_date
            ]);

            $assessment_id = $conn->lastInsertId();

            // Log the risk assessment creation
            $log_query = $conn->prepare("INSERT INTO `financial_audit_logs` 
                (auditor_id, log_type, description, severity) 
                VALUES (?, ?, ?, ?)");
            $log_query->execute([
                $auditor_id, 
                'risk_assessment', 
                "Created new $risk_category risk assessment (ID: $assessment_id)", 
                $risk_score > 7 ? 'high' : ($risk_score > 4 ? 'medium' : 'low')
            ]);

            $success_message = "Risk assessment created successfully. Assessment ID: $assessment_id";

        } catch(PDOException $e) {
            $errors[] = "Risk assessment creation failed: " . $e->getMessage();
            error_log("Risk assessment creation error: " . $e->getMessage());
        }
    }
}

// Fetch recent risk assessments
try {
    $recent_assessments_query = $conn->prepare("
        SELECT ra.*, s.name as supplier_name 
        FROM `risk_assessments` ra
        LEFT JOIN `suppliers` s ON ra.supplier_id = s.supplier_id
        WHERE ra.auditor_id = ? 
        ORDER BY ra.assessment_date DESC 
        LIMIT 5
    ");
    $recent_assessments_query->execute([$auditor_id]);
    $recent_assessments = $recent_assessments_query->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Recent risk assessments fetch error: " . $e->getMessage());
    $recent_assessments = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Keens | Create Risk Assessment</title>
   
   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<?php include '../components/financial_auditor_header.php'; ?>

<div class="container-fluid px-4 py-5">
    <div class="row">
        <!-- Risk Assessment Form -->
        <div class="col-12 col-xl-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h4 class="card-title mb-0">
                        <i class="fas fa-exclamation-triangle me-2 text-primary"></i>Create Risk Assessment
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
                                <label class="form-label">Supplier</label>
                                <select name="supplier_id" class="form-select">
                                    <option value="">Select Supplier (Optional)</option>
                                    <?php foreach($suppliers as $supplier): ?>
                                        <option value="<?= $supplier['supplier_id']; ?>">
                                            <?= htmlspecialchars($supplier['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Risk Category</label>
                                <select name="risk_category" class="form-select" required>
                                    <option value="">Select Risk Category</option>
                                    <option value="financial">Financial</option>
                                    <option value="operational">Operational</option>
                                    <option value="compliance">Compliance</option>
                                    <option value="reputational">Reputational</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Risk Score (0-10)</label>
                                <input type="number" name="risk_score" class="form-control" 
                                       min="0" max="10" step="0.1" required 
                                       placeholder="Enter risk score">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Assessment Date</label>
                                <input type="date" name="assessment_date" class="form-control" 
                                       value="<?= date('Y-m-d'); ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select" required>
                                    <option value="identified">Identified</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="mitigated">Mitigated</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Mitigation Strategy</label>
                                <textarea name="mitigation_strategy" class="form-control" 
                                          rows="4" required 
                                          placeholder="Describe the strategy to mitigate this risk"></textarea>
                            </div>

                            <div class="col-12">
                                <button type="submit" name="create_assessment" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Create Risk Assessment
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Recent Risk Assessments -->
        <div class="col-12 col-xl-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h4 class="card-title mb-0">
                        <i class="fas fa-history me-2 text-primary"></i>Recent Assessments
                    </h4>
                </div>
                
                <div class="card-body">
                    <?php if(empty($recent_assessments)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-chart-line fa-3x mb-3 opacity-50"></i>
                            <p>No recent risk assessments found.</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach($recent_assessments as $assessment): ?>
                                <div class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">
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
                                            Risk Assessment
                                        </h6>
                                        <small class="text-muted">
                                            <?= date('M d, Y', strtotime($assessment['assessment_date'])); ?>
                                        </small>
                                    </div>
                                    <p class="mb-1">
                                        <span class="badge 
                                            <?php 
                                            $score = $assessment['risk_score'];
                                            echo $score < 4 ? 'bg-success' : 
                                                 ($score < 7 ? 'bg-warning' : 'bg-danger');
                                            ?>
                                        ">
                                            Risk Score: <?= number_format($score, 1); ?>
                                        </span>
                                        <?= htmlspecialchars($assessment['supplier_name'] ?? 'No Supplier'); ?>
                                    </p>
                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <small class="text-muted">
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
                                        </small>
                                        <a href="view_risk_assessment.php?id=<?= $assessment['assessment_id']; ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
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
            const riskCategory = form.risk_category.value;
            const riskScore = parseFloat(form.risk_score.value);
            const mitigation_strategy = form.mitigation_strategy.value.trim();

            // Validate risk category
            if (!riskCategory) {
                e.preventDefault();
                alert('Please select a risk category.');
                return;
            }

            // Validate risk score
            if (isNaN(riskScore) || riskScore < 0 || riskScore > 10) {
                e.preventDefault();
                alert('Risk score must be a number between 0 and 10.');
                return;
            }

            // Validate mitigation strategy
            if (mitigation_strategy.length === 0) {
                e.preventDefault();
                alert('Please provide a mitigation strategy.');
                return;
            }
        });
    });
</script>

<?php include '../components/footer.php'; ?>
</body>
</html>