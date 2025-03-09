<?php

include 'components/connect.php';

session_start();

if(isset($_SESSION['user_id'])){
   $user_id = $_SESSION['user_id'];
}else{
   $user_id = '';
   header('location:login.php');
};

if(!isset($_GET['id'])){
   header('location:support.php');
}

// Get ticket ID
$ticket_id = $_GET['id'];

// Fetch ticket details
$ticket_query = $conn->prepare("SELECT * FROM `customer_support_tickets` WHERE ticket_id = ? AND user_id = ?");
$ticket_query->execute([$ticket_id, $user_id]);

// Check if ticket exists and belongs to this user
if($ticket_query->rowCount() == 0){
   header('location:support.php');
}

$ticket = $ticket_query->fetch(PDO::FETCH_ASSOC);

// Handle adding response
if(isset($_POST['add_response'])){
   $response = trim($_POST['response']);
   $response = filter_var($response, FILTER_SANITIZE_STRING);
   
   if(empty($response)){
      $message[] = 'Response cannot be empty!';
   } else {
      try {
         // Insert user response
        $response_query = $conn->prepare("INSERT INTO `ticket_responses` (ticket_id, csr_id, response, type) VALUES (?, NULL, ?, 'user')");
        $response_query->execute([$ticket_id, $response]);
         
         // Update ticket status to pending if it was resolved or closed
         if($ticket['status'] == 'resolved' || $ticket['status'] == 'closed'){
            $update_query = $conn->prepare("UPDATE `customer_support_tickets` SET status = 'pending', updated_at = CURRENT_TIMESTAMP WHERE ticket_id = ?");
            $update_query->execute([$ticket_id]);
            
            // Refresh ticket data
            $ticket['status'] = 'pending';
         }
         
         $message[] = 'Response added successfully!';
         
      } catch (PDOException $e) {
         $message[] = 'Error adding response: ' . $e->getMessage();
      }
   }
}

// Fetch responses
$responses_query = $conn->prepare("
   SELECT r.*, 
          CASE
            WHEN r.type = 'user' THEN 'You' 
            WHEN r.type = 'reply' OR r.type = 'note' THEN csr.name
            ELSE 'System'
          END as author_name
   FROM ticket_responses r
   LEFT JOIN customer_sales_representatives csr ON r.csr_id = csr.csr_id
   WHERE r.ticket_id = ? AND (r.type = 'user' OR r.type = 'reply')
   ORDER BY r.created_at ASC
");
$responses_query->execute([$ticket_id]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Keens | View Ticket #<?= $ticket_id ?></title>
   <link rel="icon" type="image/x-icon" href="favicon.png">
   
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <!-- Bootstrap CSS -->
   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
   
<?php include 'components/header.php'; ?>

<section class="ticket-hero py-5" style="background: linear-gradient(135deg, #2b3452 0%, #1a1f2f 100%);">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-8 text-center">
        <span class="badge rounded-pill mb-3" style="background-color: rgba(13,110,253,0.1); color: #0d6efd; font-size: 0.9rem;">
          Support Ticket #<?= $ticket_id ?>
        </span>
        <h1 class="display-5 fw-bold text-white mb-3"><?= strtoupper(htmlspecialchars($ticket['subject'])) ?></h1>
        <p class="lead mb-0" style="color: #a4b5cf;">
          <span class="badge <?php 
            if($ticket['status'] == 'pending') echo 'bg-warning';
            elseif($ticket['status'] == 'in_progress') echo 'bg-info';
            elseif($ticket['status'] == 'resolved') echo 'bg-success';
            else echo 'bg-secondary';
          ?>"><?= ucfirst($ticket['status']); ?> Status</span>
          <span class="badge ms-2 <?php 
            if($ticket['priority'] == 'low') echo 'bg-success';
            elseif($ticket['priority'] == 'medium') echo 'bg-warning';
            else echo 'bg-danger';
          ?>"><?= ucfirst($ticket['priority']); ?> Priority</span>
        </p>
      </div>
    </div>
  </div>
</section>

<section class="view-ticket-section position-relative" style="margin-top: -80px; z-index: 1;">
  <div class="container">
    <div class="card border-0 rounded-4 mb-4" style="background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
      <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <div class="d-flex align-items-center gap-3">
            <div class="p-3 rounded-circle" style="background: rgba(13,110,253,0.1);">
              <i class="fas fa-ticket-alt text-primary"></i>
            </div>
            <h4 class="fw-bold mb-0" style="color: #2b3452;">Ticket Details</h4>
          </div>
          <a href="support.php" class="btn btn-outline-primary rounded-pill px-3">
            <i class="fas fa-arrow-left me-2"></i>Back to Support
          </a>
        </div>
        
        <!-- Ticket Description -->
        <div class="ticket-description p-3 rounded-3 mb-4" style="background-color: #f8f9fa; border: 1px solid #dee2e6;">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="fw-bold text-muted">Initial Request</span>
            <span class="text-muted small">
              <i class="fas fa-calendar-alt me-1"></i> <?= date('M d, Y h:i A', strtotime($ticket['created_at'])); ?>
            </span>
          </div>
          <div class="description-content">
            <?= nl2br(htmlspecialchars($ticket['description'])) ?>
          </div>
        </div>
        
        <!-- Responses -->
        <div class="responses-section mb-4">
          <h5 class="fw-bold mb-3" style="color: #2b3452;">Conversation</h5>
          
          <?php if($responses_query->rowCount() > 0): ?>
            <div class="responses-list">
              <?php while($response = $responses_query->fetch(PDO::FETCH_ASSOC)): ?>
                <div class="response-item p-3 rounded-3 mb-3 <?= $response['type'] == 'user' ? 'user-response' : 'csr-response' ?>"
                     style="border: 1px solid #dee2e6; <?= $response['type'] == 'user' ? 'background-color: rgba(13,110,253,0.05);' : 'background-color: #f8f9fa;' ?>">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="d-flex align-items-center gap-2">
                      <div class="p-2 rounded-circle" style="background-color: <?= $response['type'] == 'user' ? 'rgba(13,110,253,0.1)' : 'rgba(46,204,113,0.1)' ?>;">
                        <i class="<?= $response['type'] == 'user' ? 'fas fa-user' : 'fas fa-headset' ?> <?= $response['type'] == 'user' ? 'text-primary' : 'text-success' ?>"></i>
                      </div>
                      <span class="fw-bold"><?= htmlspecialchars($response['author_name']); ?></span>
                      <span class="badge <?= $response['type'] == 'user' ? 'bg-primary' : 'bg-success' ?>">
                        <?= $response['type'] == 'user' ? 'You' : 'Support Agent' ?>
                      </span>
                    </div>
                    <span class="text-muted small">
                      <?= date('M d, Y h:i A', strtotime($response['created_at'])); ?>
                    </span>
                  </div>
                  <div class="response-content mt-2">
                    <?= nl2br(htmlspecialchars($response['response'])) ?>
                  </div>
                </div>
              <?php endwhile; ?>
            </div>
          <?php else: ?>
            <div class="alert alert-info">
              No responses yet. Our support team will respond to your ticket soon.
            </div>
          <?php endif; ?>
        </div>
        
        <!-- Add Response Form -->
        <div class="response-form">
          <h5 class="fw-bold mb-3" style="color: #2b3452;">Add Response</h5>
          <form action="" method="POST">
            <div class="mb-3">
              <textarea name="response" class="form-control" rows="4" placeholder="Type your response here..." 
                        required style="border-radius: 12px; border: 1px solid #dee2e6;"></textarea>
            </div>
            <div class="text-end">
              <button type="submit" name="add_response" class="btn btn-primary px-4 py-2 rounded-pill" 
                      style="transition: all 0.3s ease;">
                <i class="fas fa-paper-plane me-2"></i>
                Send Response
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
    
    <!-- Ticket Status Information -->
    <div class="card border-0 rounded-4 mb-4" style="background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
      <div class="card-body p-4">
        <div class="d-flex align-items-center gap-3 mb-4">
          <div class="p-3 rounded-circle" style="background: rgba(13,110,253,0.1);">
            <i class="fas fa-info-circle text-primary"></i>
          </div>
          <h4 class="fw-bold mb-0" style="color: #2b3452;">Ticket Status Guide</h4>
        </div>
        
        <div class="row g-4">
          <div class="col-md-3">
            <div class="status-card p-3 rounded-3 text-center" style="border: 1px solid #dee2e6;">
              <div class="p-3 rounded-circle mx-auto mb-3" style="width: 60px; height: 60px; background: rgba(255, 193, 7, 0.1);">
                <i class="fas fa-clock text-warning"></i>
              </div>
              <h6 class="fw-bold">Pending</h6>
              <p class="text-muted small mb-0">Ticket has been received but not yet reviewed by our support team.</p>
            </div>
          </div>
          
          <div class="col-md-3">
            <div class="status-card p-3 rounded-3 text-center" style="border: 1px solid #dee2e6;">
              <div class="p-3 rounded-circle mx-auto mb-3" style="width: 60px; height: 60px; background: rgba(13, 202, 240, 0.1);">
                <i class="fas fa-spinner text-info"></i>
              </div>
              <h6 class="fw-bold">In Progress</h6>
              <p class="text-muted small mb-0">Our team is actively working on resolving your issue.</p>
            </div>
          </div>
          
          <div class="col-md-3">
            <div class="status-card p-3 rounded-3 text-center" style="border: 1px solid #dee2e6;">
              <div class="p-3 rounded-circle mx-auto mb-3" style="width: 60px; height: 60px; background: rgba(25, 135, 84, 0.1);">
                <i class="fas fa-check-circle text-success"></i>
              </div>
              <h6 class="fw-bold">Resolved</h6>
              <p class="text-muted small mb-0">Your issue has been resolved. Please let us know if you need further assistance.</p>
            </div>
          </div>
          
          <div class="col-md-3">
            <div class="status-card p-3 rounded-3 text-center" style="border: 1px solid #dee2e6;">
              <div class="p-3 rounded-circle mx-auto mb-3" style="width: 60px; height: 60px; background: rgba(108, 117, 125, 0.1);">
                <i class="fas fa-archive text-secondary"></i>
              </div>
              <h6 class="fw-bold">Closed</h6>
              <p class="text-muted small mb-0">The ticket has been closed. You can create a new ticket if needed.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<style>
.form-control:focus {
  border-color: #0d6efd40;
  box-shadow: 0 0 0 0.25rem rgba(13,110,253,.15);
}

.btn-primary:hover {
  transform: translateY(-3px);
  box-shadow: 0 5px 15px rgba(13,110,253,0.2);
}

.response-item.user-response {
  border-left: 4px solid #0d6efd !important;
}

.response-item.csr-response {
  border-left: 4px solid #2ecc71 !important;
}

.status-card {
  transition: all 0.3s ease;
}

.status-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 20px rgba(0,0,0,0.05);
}

@media (max-width: 991.98px) {
  .view-ticket-section {
    margin-top: -40px;
  }
  
  .card-body {
    padding: 1.5rem !important;
  }
}

@media (max-width: 767.98px) {
  .status-card {
    margin-bottom: 1rem;
  }
}
</style>

<?php include 'components/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/script.js"></script>

</body>
</html>