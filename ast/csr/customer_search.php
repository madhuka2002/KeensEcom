<?php
include '../components/connect.php';

session_start();

if(!isset($_SESSION['csr_id'])) {
   header('location:index.php');
   exit();
}

$csr_id = $_SESSION['csr_id'];

// Handle search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Base query
$query = "
    SELECT 
        u.*,
        COUNT(DISTINCT t.ticket_id) as total_tickets,
        COUNT(DISTINCT CASE WHEN t.status IN ('pending', 'in_progress') THEN t.ticket_id END) as open_tickets
    FROM users u
    LEFT JOIN customer_support_tickets t ON u.id = t.user_id
";

// Add search conditions
if ($search !== '') {
    $query .= " WHERE (
        u.name LIKE :search 
        OR u.email LIKE :search
    )";
}

// Group by and order
$query .= " GROUP BY u.id ORDER BY u.name";

// Prepare and execute query
$users = $conn->prepare($query);

if ($search !== '') {
    $searchTerm = "%$search%";
    $users->bindParam(':search', $searchTerm);
}

$users->execute();
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Keens | User Search</title>

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
</head>
<body>

<?php include '../components/csr_header.php'; ?>

<section class="user-search-section mt-5">
    <div class="container">
        <div class="content-card">
            <div class="card-header mb-4">
                <div class="header-icon">
                    <i class="fas fa-search"></i>
                </div>
                <h2 class="header-title">User Search</h2>
                <p class="text-muted">Search and manage user information</p>
            </div>

            <!-- Search Form -->
            <form action="" method="GET" class="mb-4">
                <div class="row g-3">
                    <div class="col-md-8">
                        <div class="input-group">
                            <input type="text" 
                                   name="search" 
                                   class="form-control form-control-lg" 
                                   placeholder="Search by name or email..."
                                   value="<?= htmlspecialchars($search) ?>">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i>Search
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <select name="filter" class="form-select form-select-lg" onchange="this.form.submit()">
                            <option value="all" <?= $filter == 'all' ? 'selected' : '' ?>>All Users</option>
                            <option value="active" <?= $filter == 'active' ? 'selected' : '' ?>>Active Tickets</option>
                            <option value="no_tickets" <?= $filter == 'no_tickets' ? 'selected' : '' ?>>No Tickets</option>
                        </select>
                    </div>
                </div>
            </form>

            <!-- Results Table -->
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Contact Information</th>
                            <th>Tickets</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($users->rowCount() > 0): ?>
                            <?php while($user = $users->fetch(PDO::FETCH_ASSOC)): ?>
                                <tr>
                                    <td>
                                        <div class="user-info">
                                            <div class="user-avatar">
                                                <?= strtoupper(substr($user['name'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <div class="user-name">
                                                    <?= htmlspecialchars($user['name']) ?>
                                                </div>
                                                <span class="user-id text-muted">
                                                    ID: #<?= $user['id'] ?>
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="contact-info">
                                            <div><i class="fas fa-envelope me-2"></i><?= htmlspecialchars($user['email']) ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="ticket-stats">
                                            <span class="badge bg-primary">
                                                <?= $user['total_tickets'] ?> Total
                                            </span>
                                            <?php if($user['open_tickets'] > 0): ?>
                                                <span class="badge bg-warning">
                                                    <?= $user['open_tickets'] ?> Open
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="view_user.php?id=<?= $user['id'] ?>" 
                                               class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye me-1"></i>View
                                            </a>
                                            <a href="new_ticket.php?user_id=<?= $user['id'] ?>" 
                                               class="btn btn-sm btn-success">
                                                <i class="fas fa-plus me-1"></i>New Ticket
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-4">
                                    <div class="empty-state">
                                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">No users found</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<style>
:root {
    --primary-color: #4361ee;
    --secondary-color: #3f37c9;
    --accent-color: #4895ef;
}

.content-card {
    background: white;
    border-radius: 20px;
    padding: 2rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.05);
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

.user-info {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: var(--primary-color);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
}

.user-name {
    font-weight: 500;
    margin-bottom: 0.25rem;
}

.contact-info {
    font-size: 0.9rem;
}

.ticket-stats {
    display: flex;
    gap: 0.5rem;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
}

.empty-state {
    padding: 2rem;
    text-align: center;
}

@media (max-width: 768px) {
    .content-card {
        padding: 1rem;
    }

    .action-buttons {
        flex-direction: column;
    }

    .contact-info {
        font-size: 0.8rem;
    }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>