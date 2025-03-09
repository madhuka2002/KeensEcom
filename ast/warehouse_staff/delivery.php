<?php
include '../components/connect.php';

session_start();

$staff_id = $_SESSION['staff_id'];

if(!isset($staff_id)){
   header('location:warehouse_staff_login.php');
}

// Handle assigning delivery agent
if(isset($_POST['assign_delivery'])) {
    $order_id = $_POST['order_id'];
    $order_id = filter_var($order_id, FILTER_SANITIZE_STRING);
    $agent_id = $_POST['agent_id'];
    $agent_id = filter_var($agent_id, FILTER_SANITIZE_STRING);

    // Check if delivery assignment already exists
    $check_assignment = $conn->prepare("SELECT * FROM `delivery_assignments` WHERE order_id = ?");
    $check_assignment->execute([$order_id]);
    
    if($check_assignment->rowCount() > 0) {
        // Update existing assignment
        $update_assignment = $conn->prepare("
            UPDATE `delivery_assignments` 
            SET delivery_agent_id = ?, status = 'pending' 
            WHERE order_id = ?
        ");
        $update_assignment->execute([$agent_id, $order_id]);
    } else {
        // Create new assignment
        $insert_assignment = $conn->prepare("
            INSERT INTO `delivery_assignments` 
            (order_id, delivery_agent_id, status, assignment_date) 
            VALUES (?, ?, 'pending', NOW())
        ");
        $insert_assignment->execute([$order_id, $agent_id]);
    }

    // Update order status
    $update_order = $conn->prepare("UPDATE `orders` SET order_status = 'shipped' WHERE id = ?");
    $update_order->execute([$order_id]);

    $message[] = 'Delivery agent assigned successfully!';
}

// Get filter parameters
$status = isset($_GET['status']) ? $_GET['status'] : '';
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$agent = isset($_GET['agent']) ? $_GET['agent'] : '';

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>AstroShop | Delivery Management</title>

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<?php include '../components/warehouse_staff_header.php'; ?>

<section class="delivery-management py-5">
    <div class="container">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-2">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Delivery Management</li>
                    </ol>
                </nav>
                <h2 class="h3 mb-0">Delivery Management</h2>
            </div>
             
        </div>

        <!-- Filters -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form action="" method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Delivery Date</label>
                        <input type="date" name="date" class="form-control" value="<?= $date ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="pending" <?= $status == 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="picked_up" <?= $status == 'picked_up' ? 'selected' : '' ?>>Picked Up</option>
                            <option value="delivered" <?= $status == 'delivered' ? 'selected' : '' ?>>Delivered</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Delivery Agent</label>
                        <select name="agent" class="form-select">
                            <option value="">All Agents</option>
                            <?php
                                $select_agents = $conn->prepare("SELECT * FROM `delivery_agents`");
                                $select_agents->execute();
                                while($agent_row = $select_agents->fetch(PDO::FETCH_ASSOC)){
                                    $selected = ($agent_row['id'] == $agent) ? 'selected' : '';
                                    echo "<option value='{$agent_row['id']}' $selected>{$agent_row['name']}</option>";
                                }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Ready for Delivery Orders -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0">
                    <i class="fas fa-box-open me-2 text-primary"></i>Ready for Delivery
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Items</th>
                                <th>Address</th>
                                <th>Date</th>
                                <th>Assign</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                $query = "
                                    SELECT o.*, u.name as customer_name 
                                    FROM `orders` o 
                                    JOIN `users` u ON o.user_id = u.id 
                                    WHERE o.order_status = 'picked_up' 
                                    AND o.id NOT IN (SELECT order_id FROM `delivery_assignments`)
                                ";
                                
                                if(!empty($date)) {
                                    $query .= " AND DATE(o.placed_on) = ?";
                                }
                                
                                $query .= " ORDER BY o.placed_on DESC";
                                
                                $select_orders = $conn->prepare($query);
                                
                                if(!empty($date)) {
                                    $select_orders->execute([$date]);
                                } else {
                                    $select_orders->execute();
                                }
                                
                                if($select_orders->rowCount() > 0){
                                    while($order = $select_orders->fetch(PDO::FETCH_ASSOC)){
                            ?>
                            <tr>
                                <td>
                                    <a href="order_details.php?id=<?= $order['id'] ?>" class="text-decoration-none">
                                        #<?= $order['id'] ?>
                                    </a>
                                </td>
                                <td><?= $order['customer_name'] ?></td>
                                <td>
                                    <?php
                                        $items = explode(', ', $order['total_products']);
                                        echo count($items) . ' items';
                                    ?>
                                </td>
                                <td class="text-truncate" style="max-width: 200px;">
                                    <?= $order['address'] ?>
                                </td>
                                <td><?= date('M d, Y', strtotime($order['placed_on'])) ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-primary" 
                                            data-bs-toggle="modal" data-bs-target="#assignModal<?= $order['id'] ?>">
                                        Assign Agent
                                    </button>
                                    
                                    <!-- Assign Modal -->
                                    <div class="modal fade" id="assignModal<?= $order['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Assign Delivery Agent</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <form action="" method="POST">
                                                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label">Select Delivery Agent</label>
                                                            <select name="agent_id" class="form-select" required>
                                                                <option value="">Choose agent...</option>
                                                                <?php
                                                                    $select_available = $conn->prepare("SELECT * FROM `delivery_agents`");
                                                                    $select_available->execute();
                                                                    while($agent = $select_available->fetch(PDO::FETCH_ASSOC)){
                                                                        echo "<option value='{$agent['id']}'>{$agent['name']} ({$agent['phone']})</option>";
                                                                    }
                                                                ?>
                                                            </select>
                                                        </div>
                                                        
                                                        <div class="d-flex justify-content-end">
                                                            <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">
                                                                Cancel
                                                            </button>
                                                            <button type="submit" name="assign_delivery" class="btn btn-primary">
                                                                Assign
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php
                                    }
                                } else {
                                    echo '<tr><td colspan="6" class="text-center py-4">No orders ready for delivery</td></tr>';
                                }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>


    </div>
</section>

<style>
/* Custom Styles */
.breadcrumb-item a {
    color: var(--primary-color);
    text-decoration: none;
}

.card {
    border: none;
    border-radius: 15px;
    margin-bottom: 1.5rem;
}

.card-header {
    border-bottom: 1px solid rgba(0,0,0,.05);
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
</style>

<?php
function getStatusColor($status) {
    switch($status) {
        case 'pending': return 'warning';
        case 'picked_up': return 'info';
        case 'delivered': return 'success';
        default: return 'secondary';
    }
}
?>

</body>
</html>