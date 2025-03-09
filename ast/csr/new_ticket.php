<?php
include '../components/connect.php';

session_start();

if(!isset($_SESSION['csr_id'])) {
   header('location:index.php');
   exit();
}

$csr_id = $_SESSION['csr_id'];

// Fetch all users for dropdown
$users_query = $conn->prepare("
    SELECT id, name, email 
    FROM users 
    ORDER BY name
");
$users_query->execute();
$users = $users_query->fetchAll(PDO::FETCH_ASSOC);

// Handle ticket creation
if(isset($_POST['submit'])) {
    try {
        $user_id = $_POST['user_id'];
        $subject = trim($_POST['subject']);
        $description = trim($_POST['description']);
        $priority = $_POST['priority'];
        
        // Validate inputs
        if(empty($user_id) || empty($subject) || empty($description)) {
            $message[] = 'All fields are required';
        } else {
            // Start transaction
            $conn->beginTransaction();

            // Create ticket
            $create_ticket = $conn->prepare("
                INSERT INTO customer_support_tickets (
                    user_id, 
                    csr_id,
                    subject,
                    description,
                    status,
                    priority
                ) VALUES (?, ?, ?, ?, 'pending', ?)
            ");
            $create_ticket->execute([
                $user_id,
                $csr_id,
                $subject,
                $description,
                $priority
            ]);

            $ticket_id = $conn->lastInsertId();

            // Add initial status note
            $add_note = $conn->prepare("
                INSERT INTO ticket_responses (
                    ticket_id,
                    csr_id,
                    response,
                    type
                ) VALUES (?, ?, ?, 'note')
            ");
            $add_note->execute([
                $ticket_id,
                $csr_id,
                'Ticket created with ' . $priority . ' priority'
            ]);

            $conn->commit();
            header('location: view_ticket.php?id=' . $ticket_id);
            exit();
        }
    } catch(PDOException $e) {
        $conn->rollBack();
        $message[] = 'Error creating ticket: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Keens | New Ticket</title>

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
</head>
<body>

<?php include '../components/csr_header.php'; ?>

<section class="new-ticket-section mt-5">
    <div class="container">
        <div class="content-card">
            <div class="card-header mb-4">
                <div class="header-icon">
                    <i class="fas fa-plus-circle"></i>
                </div>
                <h2 class="header-title">Create New Support Ticket</h2>
                <p class="text-muted">Create a new ticket for user support</p>
            </div>

            <form action="" method="POST" class="ticket-form">
                <!-- User Selection -->
                <div class="form-group mb-4">
                    <label class="form-label">Select User</label>
                    <select name="user_id" class="form-select" required>
                        <option value="">-- Select User --</option>
                        <?php foreach($users as $user): ?>
                            <option value="<?= $user['id'] ?>"
                                <?= isset($_POST['user_id']) && $_POST['user_id'] == $user['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['email']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Ticket Subject -->
                <div class="form-group mb-4">
                    <label class="form-label">Subject</label>
                    <input type="text" 
                           name="subject" 
                           class="form-control" 
                           placeholder="Enter ticket subject"
                           maxlength="100"
                           value="<?= isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : '' ?>"
                           required>
                </div>

                <!-- Priority Selection -->
                <div class="form-group mb-4">
                    <label class="form-label">Priority</label>
                    <select name="priority" class="form-select" required>
                        <option value="low" <?= isset($_POST['priority']) && $_POST['priority'] == 'low' ? 'selected' : '' ?>>
                            Low
                        </option>
                        <option value="medium" <?= isset($_POST['priority']) && $_POST['priority'] == 'medium' ? 'selected' : '' ?>>
                            Medium
                        </option>
                        <option value="high" <?= isset($_POST['priority']) && $_POST['priority'] == 'high' ? 'selected' : '' ?>>
                            High
                        </option>
                    </select>
                </div>

                <!-- Ticket Description -->
                <div class="form-group mb-4">
                    <label class="form-label">Description</label>
                    <textarea name="description" 
                              class="form-control" 
                              rows="5" 
                              placeholder="Enter detailed description"
                              required><?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '' ?></textarea>
                </div>

                <!-- Submit Buttons -->
                <div class="form-actions">
                    <button type="submit" name="submit" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Create Ticket
                    </button>
                    <a href="tickets.php" class="btn btn-light">
                        <i class="fas fa-times me-2"></i>Cancel
                    </a>
                </div>
            </form>
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

.header-title {
    text-align: center;
    font-size: 1.5rem;
    font-weight: 700;
    color: #2b3452;
    margin-bottom: 0.5rem;
}

.ticket-form {
    max-width: 800px;
    margin: 0 auto;
}

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
}

.form-label {
    font-weight: 500;
    color: #2b3452;
}

.form-control:focus,
.form-select:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.25);
}

@media (max-width: 768px) {
    .content-card {
        padding: 1rem;
    }

    .form-actions {
        flex-direction: column;
    }

    .form-actions .btn {
        width: 100%;
    }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>