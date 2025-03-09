<?php
include '../components/connect.php';

session_start();

if (!isset($_SESSION['supplier_id'])) {
    header('location:index.php');
    exit();
}

$supplier_id = $_SESSION['supplier_id'];

// Pagination setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Fetch conversation threads
$threads_query = $conn->prepare("
    SELECT 
        sc.*, 
        a.name as admin_name,
        (SELECT message FROM supplier_communications WHERE thread_id = sc.thread_id ORDER BY created_at DESC LIMIT 1) as last_message,
        (SELECT created_at FROM supplier_communications WHERE thread_id = sc.thread_id ORDER BY created_at DESC LIMIT 1) as last_message_time,
        (SELECT COUNT(*) FROM supplier_communications WHERE thread_id = sc.thread_id AND is_read = 0) as unread_count
    FROM supplier_communication_threads sc
    JOIN admins a ON sc.admin_id = a.id
    WHERE sc.supplier_id = ?
    ORDER BY last_message_time DESC
    LIMIT $records_per_page OFFSET $offset
");
$threads_query->execute([$supplier_id]);
$communication_threads = $threads_query->fetchAll(PDO::FETCH_ASSOC);

// Count total threads
$count_query = $conn->prepare("
    SELECT COUNT(*) as total 
    FROM supplier_communication_threads 
    WHERE supplier_id = ?
");
$count_query->execute([$supplier_id]);
$total_threads = $count_query->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_threads / $records_per_page);

// Handle new message thread creation
if (isset($_POST['create_thread'])) {
    $subject = filter_var($_POST['subject'], FILTER_SANITIZE_STRING);
    $initial_message = filter_var($_POST['message'], FILTER_SANITIZE_STRING);

    if (!empty($subject) && !empty($initial_message)) {
        try {
            // Begin transaction
            $conn->beginTransaction();

            // Find an admin to assign the thread
            $admin_query = $conn->prepare("SELECT id FROM admins LIMIT 1");
            $admin_query->execute();
            $admin = $admin_query->fetch(PDO::FETCH_ASSOC);

            // Create new thread
            $thread_insert = $conn->prepare("
                INSERT INTO supplier_communication_threads 
                (supplier_id, admin_id, subject, status) 
                VALUES (?, ?, ?, 'open')
            ");
            $thread_insert->execute([$supplier_id, $admin['id'], $subject]);
            $thread_id = $conn->lastInsertId();

            // Insert initial message
            $message_insert = $conn->prepare("
                INSERT INTO supplier_communications 
                (thread_id, sender, message, sender_type) 
                VALUES (?, ?, ?, 'supplier')
            ");
            $message_insert->execute([$thread_id, $supplier_id, $initial_message]);

            // Commit transaction
            $conn->commit();

            $_SESSION['success_message'] = "New communication thread created successfully!";
            header("Location: communication.php");
            exit();

        } catch (PDOException $e) {
            $conn->rollBack();
            $error_message = "Failed to create communication thread: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keens | Supplier Communications</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
</head>
<body>
    <?php include '../components/supplier_header.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12 d-flex justify-content-between align-items-center mb-4">
                <h1>
                    <i class="fas fa-comments me-2"></i>Communications
                </h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newThreadModal">
                    <i class="fas fa-plus me-2"></i>New Thread
                </button>
            </div>
        </div>

        <!-- Communication Threads -->
        <div class="card">
            <div class="card-body p-0">
                <?php if (empty($communication_threads)): ?>
                    <div class="alert alert-info m-3 mb-0" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        No communication threads found.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Admin</th>
                                    <th>Last Message</th>
                                    <th>Status</th>
                                    <th>Unread</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($communication_threads as $thread): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($thread['subject']) ?></td>
                                        <td><?= htmlspecialchars($thread['admin_name']) ?></td>
                                        <td>
                                            <?= $thread['last_message'] 
                                                ? (strlen($thread['last_message']) > 30 
                                                    ? substr($thread['last_message'], 0, 30) . '...' 
                                                    : $thread['last_message']) 
                                                : 'No messages' ?>
                                            <small class="text-muted d-block">
                                                <?= $thread['last_message_time'] 
                                                    ? date('d M H:i', strtotime($thread['last_message_time'])) 
                                                    : '' ?>
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                switch($thread['status']) {
                                                    case 'open': echo 'success'; break;
                                                    case 'closed': echo 'secondary'; break;
                                                    default: echo 'warning';
                                                }
                                            ?>">
                                                <?= ucfirst($thread['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($thread['unread_count'] > 0): ?>
                                                <span class="badge bg-danger">
                                                    <?= $thread['unread_count'] ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="view_communication.php?thread_id=<?= $thread['thread_id'] ?>" 
                                               class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye me-1"></i>View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav aria-label="Communication Threads Pagination" class="mt-3">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>

    <!-- New Thread Modal -->
    <div class="modal fade" id="newThreadModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Communication Thread</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Subject</label>
                            <input type="text" 
                                   name="subject" 
                                   class="form-control" 
                                   required 
                                   maxlength="100" 
                                   placeholder="Enter thread subject">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Initial Message</label>
                            <textarea 
                                name="message" 
                                class="form-control" 
                                rows="4" 
                                required 
                                placeholder="Write your initial message"
                            ></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Cancel
                        </button>
                        <button type="submit" name="create_thread" class="btn btn-primary">
                            Create Thread
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>