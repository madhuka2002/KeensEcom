<?php
include '../components/connect.php';

session_start();

$staff_id = $_SESSION['staff_id'];

if(!isset($staff_id)){
   header('location:index.php');
}

// Handle task status updates
if(isset($_POST['update_task'])) {
    $task_id = $_POST['task_id'];
    $task_id = filter_var($task_id, FILTER_SANITIZE_STRING);
    $status = $_POST['status'];
    $status = filter_var($status, FILTER_SANITIZE_STRING);
    $notes = $_POST['notes'];
    $notes = filter_var($notes, FILTER_SANITIZE_STRING);

    $update_task = $conn->prepare("UPDATE `staff_tasks` SET status = ?, completion_notes = ? WHERE task_id = ? AND staff_id = ?");
    $update_task->execute([$status, $notes, $task_id, $staff_id]);
    $message[] = 'Task status updated successfully!';
}

// Handle task priority updates
if(isset($_POST['update_priority'])) {
    $task_id = $_POST['task_id'];
    $priority = $_POST['priority'];

    $update_priority = $conn->prepare("UPDATE `staff_tasks` SET priority = ? WHERE task_id = ? AND staff_id = ?");
    $update_priority->execute([$priority, $task_id, $staff_id]);
    $message[] = 'Task priority updated!';
}

// Filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$priority_filter = isset($_GET['priority']) ? $_GET['priority'] : '';

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Keens | Staff Tasks</title>

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<?php include '../components/warehouse_staff_header.php'; ?>

<section class="tasks-section">
    <div class="container-fluid px-4 py-5">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h3 mb-0">My Tasks</h2>
            <div class="btn-group">
                <button type="button" class="btn btn-outline-primary" onclick="window.print()">
                    <i class="fas fa-print me-2"></i>Print Tasks
                </button>
            </div>
        </div>

        <!-- Filters -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form action="" method="GET" class="row g-3">
                    <div class="col-md-4">
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="pending" <?= $status_filter == 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="in_progress" <?= $status_filter == 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                            <option value="completed" <?= $status_filter == 'completed' ? 'selected' : '' ?>>Completed</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select name="priority" class="form-select">
                            <option value="">All Priorities</option>
                            <option value="high" <?= $priority_filter == 'high' ? 'selected' : '' ?>>High Priority</option>
                            <option value="medium" <?= $priority_filter == 'medium' ? 'selected' : '' ?>>Medium Priority</option>
                            <option value="low" <?= $priority_filter == 'low' ? 'selected' : '' ?>>Low Priority</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tasks List -->
        <div class="row">
            <div class="col-12">
                <?php
                    $query = "SELECT * FROM `staff_tasks` WHERE staff_id = ?";
                    
                    if(!empty($status_filter)) {
                        $query .= " AND status = ?";
                    }
                    if(!empty($priority_filter)) {
                        $query .= " AND priority = ?";
                    }
                    
                    $query .= " ORDER BY FIELD(priority, 'high', 'medium', 'low'), due_date ASC";
                    
                    $select_tasks = $conn->prepare($query);
                    
                    $params = [$staff_id];
                    if(!empty($status_filter)) {
                        $params[] = $status_filter;
                    }
                    if(!empty($priority_filter)) {
                        $params[] = $priority_filter;
                    }
                    
                    $select_tasks->execute($params);

                    if($select_tasks->rowCount() > 0){
                        while($task = $select_tasks->fetch(PDO::FETCH_ASSOC)){
                ?>
                <!-- Task Card -->
                <div class="card task-card shadow-sm mb-3 <?= $task['priority'] ?>-priority">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h5 class="card-title mb-1"><?= $task['task_name']; ?></h5>
                                <p class="text-muted mb-2"><?= $task['description']; ?></p>
                                <div class="d-flex align-items-center gap-3">
                                    <span class="badge bg-<?= getPriorityColor($task['priority']); ?>">
                                        <?= ucfirst($task['priority']); ?> Priority
                                    </span>
                                    <span class="badge bg-<?= getStatusColor($task['status']); ?>">
                                        <?= ucfirst($task['status']); ?>
                                    </span>
                                    <?php if($task['due_date']): ?>
                                        <span class="text-muted small">
                                            <i class="fas fa-clock me-1"></i>
                                            Due: <?= date('M d, Y', strtotime($task['due_date'])); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <form action="" method="POST" class="d-flex align-items-center gap-2 justify-content-end">
                                    <input type="hidden" name="task_id" value="<?= $task['task_id']; ?>">
                                    <select name="status" class="form-select form-select-sm" style="width: 150px;">
                                        <option value="pending" <?= $task['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="in_progress" <?= $task['status'] == 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                        <option value="completed" <?= $task['status'] == 'completed' ? 'selected' : '' ?>>Completed</option>
                                    </select>
                                    <select name="priority" class="form-select form-select-sm" style="width: 150px;">
                                        <option value="high" <?= $task['priority'] == 'high' ? 'selected' : '' ?>>High Priority</option>
                                        <option value="medium" <?= $task['priority'] == 'medium' ? 'selected' : '' ?>>Medium Priority</option>
                                        <option value="low" <?= $task['priority'] == 'low' ? 'selected' : '' ?>>Low Priority</option>
                                    </select>
                                    <button type="submit" name="update_task" class="btn btn-sm btn-primary">
                                        Update
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php if($task['status'] == 'completed' && $task['completion_notes']): ?>
                        <div class="completion-notes mt-3">
                            <small class="text-muted">Completion Notes:</small>
                            <p class="mb-0"><?= $task['completion_notes']; ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php
                        }
                    } else {
                        echo '<div class="alert alert-info text-center">No tasks found.</div>';
                    }
                ?>
            </div>
        </div>
    </div>
</section>

<style>
/* Custom Styles */
.task-card {
    border: none;
    border-radius: 15px;
    transition: transform 0.2s;
}

.task-card:hover {
    transform: translateY(-2px);
}

.high-priority {
    border-left: 4px solid var(--danger-color);
}

.medium-priority {
    border-left: 4px solid var(--warning-color);
}

.low-priority {
    border-left: 4px solid var(--info-color);
}

.completion-notes {
    background-color: #f8f9fa;
    padding: 1rem;
    border-radius: 8px;
    margin-top: 1rem;
}

.form-select:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.25);
}

.btn-primary {
    background: var(--primary-color);
    border-color: var(--primary-color);
}

.btn-primary:hover {
    background: var(--secondary-color);
    border-color: var(--secondary-color);
}

@media print {
    .navbar, .btn-group, form {
        display: none !important;
    }
    
    .task-card {
        break-inside: avoid;
        border: 1px solid #dee2e6;
    }
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .task-card form {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .task-card .form-select {
        width: 100% !important;
    }
}
</style>

<?php
function getPriorityColor($priority) {
    switch($priority) {
        case 'high': return 'danger';
        case 'medium': return 'warning';
        case 'low': return 'info';
        default: return 'secondary';
    }
}

function getStatusColor($status) {
    switch($status) {
        case 'pending': return 'secondary';
        case 'in_progress': return 'primary';
        case 'completed': return 'success';
        default: return 'secondary';
    }
}
?>

</body>
</html>