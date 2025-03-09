<?php
include '../components/connect.php';

session_start();

$admin_id = $_SESSION['admin_id'];

if(!isset($admin_id)){
   header('location:index.php');
}

if(isset($_GET['delete'])){
   $delete_id = $_GET['delete'];
   $delete_admin = $conn->prepare("DELETE FROM `admins` WHERE id = ?");
   $delete_admin->execute([$delete_id]);
   header('location:admin-accounts.php');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Keens | Admin Accounts</title>

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <!-- Font Awesome -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<!-- Admin Accounts Management Section -->
<section class="show-admin-accounts mt-5">
    <div class="content-card">
        <div class="card-header">
            <div class="header-icon">
                <i class="fas fa-user-shield"></i>
            </div>
            <h2 class="header-title">Manage Admin Accounts</h2>
            <p class="text-muted">View and manage administrator accounts</p>
        </div>

        <div class="table-container">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>Admin ID</th>
                        <th>Admin Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                    $select_accounts = $conn->prepare("SELECT * FROM `admins`");
                    $select_accounts->execute();
                    if($select_accounts->rowCount() > 0){
                        while($fetch_accounts = $select_accounts->fetch(PDO::FETCH_ASSOC)){   
                ?>
                    <tr>
                        <td>
                            <span class="id-badge">#<?= $fetch_accounts['id']; ?></span>
                        </td>
                        <td>
                            <div class="user-info">
                                <i class="fas fa-user-shield user-icon"></i>
                                <span class="user-name"><?= $fetch_accounts['name']; ?></span>
                            </div>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="admin-accounts.php?delete=<?= $fetch_accounts['id']; ?>" 
                                   onclick="return confirm('Are you sure you want to delete this admin?')" 
                                   class="btn-delete">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                                <?php
                                if($fetch_accounts['id'] == $admin_id){
                                ?>
                                    <a href="account.php" class="btn-update">
                                        <i class="fas fa-edit"></i> Update
                                    </a>
                                <?php
                                }
                                ?>
                            </div>
                        </td>
                    </tr>
                <?php
                        }
                    } else {
                ?>
                    <tr>
                        <td colspan="3" class="empty-state">
                            <i class="fas fa-user-shield"></i>
                            <p>No admin accounts available!</p>
                        </td>
                    </tr>
                <?php
                    }
                ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<style>
:root {
    --primary-color: #4361ee;
    --secondary-color: #3f37c9;
    --accent-color: #4895ef;
    --success-color: #2ecc71;
    --danger-color: #dc3545;
    --warning-color: #f39c12;
}

/* Card Styles */
.content-card {
    background: white;
    border-radius: 20px;
    padding: 2rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
    margin: 2rem auto;
    max-width: 1200px;
}

.card-header {
    text-align: center;
    margin-bottom: 2rem;
}

.header-icon {
    width: 60px;
    height: 60px;
    border-radius: 15px;
    background: linear-gradient(135deg, rgba(67, 97, 238, 0.1), rgba(72, 149, 239, 0.1));
    color: var(--primary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin: 0 auto 1rem;
}

.header-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #2b3452;
    margin-bottom: 0.5rem;
}

/* Table Styles */
.table-container {
    overflow-x: auto;
    border-radius: 8px;
    background: white;
}

.modern-table {
    width: 100%;
    border-collapse: collapse;
    margin: 0;
    padding: 0;
    background-color: white;
}

.modern-table thead tr {
    background-color: #f8f9fa;
    border-bottom: 2px solid #e9ecef;
}

.modern-table th {
    padding: 16px;
    text-align: left;
    font-weight: 600;
    color: #495057;
    text-transform: uppercase;
    font-size: 13px;
    letter-spacing: 0.5px;
}

.modern-table td {
    padding: 16px;
    vertical-align: middle;
    border-bottom: 1px solid #e9ecef;
}

.modern-table tbody tr:hover {
    background-color: #f8f9fa;
}

/* User ID Badge */
.id-badge {
    background: #e8f3ff;
    color: #0d6efd;
    padding: 6px 12px;
    border-radius: 6px;
    font-weight: 500;
    font-size: 14px;
}

/* User Info */
.user-info {
    display: flex;
    align-items: center;
    gap: 10px;
}

.user-icon {
    width: 32px;
    height: 32px;
    background: #f0f2f5;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6c757d;
}

.user-name {
    font-weight: 500;
    color: #2b3445;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 8px;
}

.btn-delete {
    padding: 8px 16px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #ffeeee;
    color: #dc3545;
    border: 1px solid #ffd5d5;
    transition: all 0.2s;
}

.btn-delete:hover {
    filter: brightness(0.95);
    color: #dc3545;
}

.btn-update {
    padding: 8px 16px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #e8f3ff;
    color: #0d6efd;
    border: 1px solid #d5e8ff;
    transition: all 0.2s;
}

.btn-update:hover {
    filter: brightness(0.95);
    color: #0d6efd;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 48px !important;
    color: #6c757d;
}

.empty-state i {
    font-size: 32px;
    margin-bottom: 16px;
    color: #dee2e6;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .content-card {
        padding: 1rem;
        margin: 1rem;
    }

    .modern-table th {
        white-space: nowrap;
    }

    .action-buttons {
        flex-direction: column;
    }
}
</style>

</body>
</html>