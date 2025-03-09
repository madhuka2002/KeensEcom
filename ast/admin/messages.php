<?php

include '../components/connect.php';

session_start();

$admin_id = $_SESSION['admin_id'];

if(!isset($admin_id)){
   header('location:index.php');
}

if(isset($_GET['delete'])){
   $delete_id = $_GET['delete'];
   $delete_message = $conn->prepare("DELETE FROM `messages` WHERE id = ?");
   $delete_message->execute([$delete_id]);
   header('location:messages.php');
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Keens | Messages</title>

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<section class="show-messages mt-5">
    <div class="content-card">
        <div class="card-header">
            <div class="header-icon">
                <i class="fas fa-envelope"></i>
            </div>
            <h2 class="header-title">Messages</h2>
            <p class="text-muted">View and manage customer messages</p>
        </div>

        <div class="table-container">
            <?php
            $select_messages = $conn->prepare("SELECT * FROM `messages`");
            $select_messages->execute();
            if($select_messages->rowCount() > 0){
            ?>
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User Info</th>
                        <th>Contact</th>
                        <th>Message</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $row_number = 1;
                    while($fetch_message = $select_messages->fetch(PDO::FETCH_ASSOC)){
                    ?>
                    <tr>
                        <td>
                            <span class="id-badge">#<?= $row_number++; ?></span>
                        </td>
                        <td>
                            <div class="user-details">
                                <span class="user-name"><?= $fetch_message['name']; ?></span>
                                <span class="user-email">
                                    <i class="fas fa-envelope"></i> <?= $fetch_message['email']; ?>
                                </span>
                            </div>
                        </td>
                        <td>
                            <span class="user-contact">
                                <i class="fas fa-phone"></i> <?= $fetch_message['number']; ?>
                            </span>
                        </td>
                        <td>
                            <div class="message-content">
                                <?= $fetch_message['message']; ?>
                            </div>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="messages.php?delete=<?= $fetch_message['id']; ?>" 
                                   onclick="return confirm('Are you sure you want to delete this message? This action cannot be undone.');" 
                                   class="btn-delete">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
            <?php } else { ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>No messages received yet!</p>
                </div>
            <?php } ?>
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

/* ID Badge */
.id-badge {
    background: #e8f3ff;
    color: #0d6efd;
    padding: 6px 12px;
    border-radius: 6px;
    font-weight: 500;
    font-size: 14px;
}

/* User Details */
.user-details {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.user-name {
    font-weight: 500;
    color: #2b3445;
}

.user-contact, .user-email {
    font-size: 14px;
    color: #6c757d;
}

.user-contact i, .user-email i {
    width: 16px;
    margin-right: 4px;
}

/* Message Content */
.message-content {
    font-size: 14px;
    color: #495057;
    max-width: 300px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
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
    text-decoration: none;
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
@media (max-width: 992px) {
    .content-card {
        padding: 1rem;
        margin: 1rem;
    }

    .modern-table {
        font-size: 14px;
    }

    .message-content {
        max-width: 200px;
    }
}

@media (max-width: 768px) {
    .modern-table th {
        white-space: nowrap;
    }

    .message-content {
        max-width: 150px;
    }
}
</style>

<script src="../js/admin_script.js"></script>

</body>
</html>
