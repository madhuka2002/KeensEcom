<?php
include '../components/connect.php';

session_start();

$staff_id = $_SESSION['staff_id'];

if(!isset($staff_id)){
   header('location:index.php');
}

// Handle shift change request
if(isset($_POST['update_shift'])) {
    $new_shift = $_POST['shift'];
    $new_shift = filter_var($new_shift, FILTER_SANITIZE_STRING);
    $effective_date = $_POST['effective_date'];
    $effective_date = filter_var($effective_date, FILTER_SANITIZE_STRING);
    $reason = $_POST['reason'];
    $reason = filter_var($reason, FILTER_SANITIZE_STRING);

    // Insert shift change request
    $insert_request = $conn->prepare("INSERT INTO `shift_change_requests` (staff_id, requested_shift, current_shift, effective_date, reason, status) 
        VALUES (?, ?, (SELECT shift FROM warehouse_staff WHERE staff_id = ?), ?, ?, 'pending')");
    $insert_request->execute([$staff_id, $new_shift, $staff_id, $effective_date, $reason]);
    $message[] = 'Shift change request submitted successfully!';
}

// Handle availability update
if(isset($_POST['update_availability'])) {
    $status = $_POST['status'];
    $status = filter_var($status, FILTER_SANITIZE_STRING);

    $update_availability = $conn->prepare("UPDATE `warehouse_staff` SET is_available = ? WHERE staff_id = ?");
    $update_availability->execute([$status, $staff_id]);
    $message[] = 'Availability status updated successfully!';
}

// Get staff details and shift information
$select_staff = $conn->prepare("SELECT * FROM `warehouse_staff` WHERE staff_id = ?");
$select_staff->execute([$staff_id]);
$staff_data = $select_staff->fetch(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Keens | Shift Status</title>

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<?php include '../components/warehouse_staff_header.php'; ?>

<section class="shift-status">
    <div class="container py-5">
        <div class="row justify-content-center">
            <!-- Current Status Card -->
            <div class="col-md-4 mb-4">
                <div class="card status-card shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="status-icon mb-3">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h4 class="card-title mb-3">Current Status</h4>
                        <div class="current-shift mb-3">
                            <span class="badge bg-primary fs-6 mb-2">
                                <?= ucfirst($staff_data['shift']); ?> Shift
                            </span>
                            <p class="text-muted mb-0">
                                <?php
                                    switch($staff_data['shift']) {
                                        case 'morning':
                                            echo '6:00 AM - 2:00 PM';
                                            break;
                                        case 'afternoon':
                                            echo '2:00 PM - 10:00 PM';
                                            break;
                                        case 'night':
                                            echo '10:00 PM - 6:00 AM';
                                            break;
                                    }
                                ?>
                            </p>
                        </div>
                        
                        <!-- Availability Toggle -->
                        <form action="" method="POST" class="mb-3">
                            <div class="d-flex justify-content-center gap-2">
                                <input type="hidden" name="status" value="<?= $staff_data['is_available'] ? '0' : '1' ?>">
                                <button type="submit" name="update_availability" 
                                        class="btn btn-sm <?= $staff_data['is_available'] ? 'btn-success' : 'btn-warning' ?>">
                                    <i class="fas <?= $staff_data['is_available'] ? 'fa-toggle-on' : 'fa-toggle-off' ?> me-2"></i>
                                    <?= $staff_data['is_available'] ? 'Available' : 'Away' ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Shift Change Request -->
            <div class="col-md-8 mb-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Request Shift Change</h4>
                        <form action="" method="POST">
                            <div class="mb-3">
                                <label class="form-label">New Shift Preference</label>
                                <select name="shift" class="form-select" required>
                                    <option value="">Select shift</option>
                                    <option value="morning">Morning Shift (6:00 AM - 2:00 PM)</option>
                                    <option value="afternoon">Afternoon Shift (2:00 PM - 10:00 PM)</option>
                                    <option value="night">Night Shift (10:00 PM - 6:00 AM)</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Effective From</label>
                                <input type="date" name="effective_date" class="form-control" required 
                                       min="<?= date('Y-m-d', strtotime('+1 day')); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Reason for Change</label>
                                <textarea name="reason" class="form-control" rows="3" required></textarea>
                            </div>
                            <button type="submit" name="update_shift" class="btn btn-primary">
                                Submit Request
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Pending Requests -->
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Shift Change History</h4>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Request Date</th>
                                        <th>Current Shift</th>
                                        <th>Requested Shift</th>
                                        <th>Effective Date</th>
                                        <th>Status</th>
                                        <th>Reason</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                        $select_requests = $conn->prepare("
                                            SELECT * FROM `shift_change_requests` 
                                            WHERE staff_id = ? 
                                            ORDER BY created_at DESC
                                        ");
                                        $select_requests->execute([$staff_id]);
                                        
                                        if($select_requests->rowCount() > 0) {
                                            while($request = $select_requests->fetch(PDO::FETCH_ASSOC)) {
                                    ?>
                                    <tr>
                                        <td><?= date('Y-m-d', strtotime($request['created_at'])); ?></td>
                                        <td><?= ucfirst($request['current_shift']); ?></td>
                                        <td><?= ucfirst($request['requested_shift']); ?></td>
                                        <td><?= date('Y-m-d', strtotime($request['effective_date'])); ?></td>
                                        <td>
                                            <span class="badge bg-<?= getStatusColor($request['status']); ?>">
                                                <?= ucfirst($request['status']); ?>
                                            </span>
                                        </td>
                                        <td><?= $request['reason']; ?></td>
                                    </tr>
                                    <?php
                                            }
                                        } else {
                                            echo '<tr><td colspan="6" class="text-center">No shift change requests found</td></tr>';
                                        }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
/* Custom Styles */
.status-card {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
}

.status-icon {
    width: 64px;
    height: 64px;
    background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin: 0 auto;
}

.current-shift {
    padding: 1rem;
    background: rgba(67, 97, 238, 0.05);
    border-radius: 12px;
}

.form-control:focus, .form-select:focus {
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

.table th {
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.8rem;
    letter-spacing: 0.5px;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .status-icon {
        width: 48px;
        height: 48px;
        font-size: 1.25rem;
    }
}
</style>

<?php
function getStatusColor($status) {
    switch($status) {
        case 'pending': return 'warning';
        case 'approved': return 'success';
        case 'rejected': return 'danger';
        default: return 'secondary';
    }
}
?>

</body>
</html>