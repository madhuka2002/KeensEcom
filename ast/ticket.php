<?php

include 'components/connect.php';

session_start();

if(isset($_SESSION['user_id'])){
   $user_id = $_SESSION['user_id'];
}else{
   $user_id = '';
   header('location:login.php');
};

if(isset($_POST['submit_ticket'])){
   $subject = $_POST['subject'];
   $subject = filter_var($subject, FILTER_SANITIZE_STRING);
   $description = $_POST['description'];
   $description = filter_var($description, FILTER_SANITIZE_STRING);
   $priority = $_POST['priority'];
   $priority = filter_var($priority, FILTER_SANITIZE_STRING);
   
   // Insert the ticket
   $insert_ticket = $conn->prepare("INSERT INTO `customer_support_tickets`(user_id, subject, description, priority, status) VALUES(?,?,?,?,'pending')");
   $insert_ticket->execute([$user_id, $subject, $description, $priority]);
   
   $message[] = 'Support ticket submitted successfully!';
}

// Get user tickets
$select_tickets = $conn->prepare("SELECT * FROM `customer_support_tickets` WHERE user_id = ? ORDER BY created_at DESC");
$select_tickets->execute([$user_id]);

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Keens | Support</title>
   <link rel="icon" type="image/x-icon" href="favicon.png">
   
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <!-- Bootstrap CSS -->
   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
   
<?php include 'components/header.php'; ?>

<section class="support-hero py-5" style="background: linear-gradient(135deg, #2b3452 0%, #1a1f2f 100%);">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-8 text-center">
        <span class="badge rounded-pill mb-3" style="background-color: rgba(13,110,253,0.1); color: #0d6efd; font-size: 0.9rem;">
          Customer Support
        </span>
        <h1 class="display-5 fw-bold text-white mb-3">HOW CAN WE HELP YOU?</h1>
        <p class="lead mb-0" style="color: #a4b5cf;">
          Submit a support ticket and our team will assist you shortly
        </p>
      </div>
    </div>
  </div>
</section>

<section class="support-section position-relative" style="margin-top: -80px; z-index: 1;">
  <div class="container">
    <div class="row">
      <!-- Create Ticket Card -->
      <div class="col-lg-6 mb-4">
        <div class="card border-0 rounded-4 h-100" style="background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
          <div class="card-body p-4">
            <div class="d-flex align-items-center gap-3 mb-4">
              <div class="p-3 rounded-circle" style="background: rgba(13,110,253,0.1);">
                <i class="fas fa-ticket-alt text-primary"></i>
              </div>
              <h4 class="fw-bold mb-0" style="color: #2b3452;">Create New Ticket</h4>
            </div>
            
            <form action="" method="POST">
              <div class="mb-3">
                <div class="form-floating">
                  <input type="text" name="subject" class="form-control" id="subjectInput" placeholder="Subject" 
                         required maxlength="100" style="border-radius: 12px; border: 1px solid #dee2e6;">
                  <label for="subjectInput" class="text-muted">Subject</label>
                </div>
              </div>
              
              <div class="mb-3">
                <div class="form-floating">
                  <select name="priority" class="form-select" id="priorityInput" 
                          style="border-radius: 12px; border: 1px solid #dee2e6;">
                    <option value="low">Low Priority</option>
                    <option value="medium" selected>Medium Priority</option>
                    <option value="high">High Priority</option>
                  </select>
                  <label for="priorityInput" class="text-muted">Priority Level</label>
                </div>
              </div>
              
              <div class="mb-4">
                <div class="form-floating">
                  <textarea name="description" class="form-control" id="descriptionInput" placeholder="Description" 
                            required style="border-radius: 12px; border: 1px solid #dee2e6; height: 150px;"></textarea>
                  <label for="descriptionInput" class="text-muted">Detailed Description</label>
                </div>
              </div>
              
              <div class="text-center">
                <button type="submit" name="submit_ticket" class="btn btn-primary px-5 py-3 rounded-pill" 
                        style="transition: all 0.3s ease;">
                  <i class="fas fa-paper-plane me-2"></i>
                  Submit Ticket
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
      
      <!-- Existing Tickets Card -->
      <div class="col-lg-6 mb-4">
        <div class="card border-0 rounded-4 h-100" style="background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
          <div class="card-body p-4">
            <div class="d-flex align-items-center gap-3 mb-4">
              <div class="p-3 rounded-circle" style="background: rgba(13,110,253,0.1);">
                <i class="fas fa-history text-primary"></i>
              </div>
              <h4 class="fw-bold mb-0" style="color: #2b3452;">Your Tickets</h4>
            </div>
            
            <div class="tickets-list">
              <?php if($select_tickets->rowCount() > 0): ?>
                <?php while($ticket = $select_tickets->fetch(PDO::FETCH_ASSOC)): ?>
                  <div class="ticket-item p-3 mb-3 rounded-3" 
                       style="border: 1px solid #dee2e6; transition: all 0.3s ease;">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                      <h6 class="fw-bold mb-0"><?= htmlspecialchars($ticket['subject']); ?></h6>
                      <span class="badge <?php 
                        if($ticket['status'] == 'pending') echo 'bg-warning';
                        elseif($ticket['status'] == 'in_progress') echo 'bg-info';
                        elseif($ticket['status'] == 'resolved') echo 'bg-success';
                        else echo 'bg-secondary';
                      ?>"><?= ucfirst($ticket['status']); ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                      <small class="text-muted">
                        <i class="fas fa-calendar-alt me-1"></i> <?= date('M d, Y', strtotime($ticket['created_at'])); ?>
                        <span class="ms-2 badge <?php 
                          if($ticket['priority'] == 'low') echo 'bg-success';
                          elseif($ticket['priority'] == 'medium') echo 'bg-warning';
                          else echo 'bg-danger';
                        ?>"><?= ucfirst($ticket['priority']); ?> Priority</span>
                      </small>
                      <a href="view_ticket.php?id=<?= $ticket['ticket_id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill">
                        <i class="fas fa-eye me-1"></i> View
                      </a>
                    </div>
                  </div>
                <?php endwhile; ?>
              <?php else: ?>
                <div class="text-center py-5">
                  <i class="fas fa-ticket-alt fa-3x text-muted mb-3"></i>
                  <p class="text-muted">You haven't created any support tickets yet.</p>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Support FAQs -->
    <div class="row mt-2">
      <div class="col-12">
        <div class="card border-0 rounded-4" style="background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
          <div class="card-body p-4">
            <div class="d-flex align-items-center gap-3 mb-4">
              <div class="p-3 rounded-circle" style="background: rgba(13,110,253,0.1);">
                <i class="fas fa-question-circle text-primary"></i>
              </div>
              <h4 class="fw-bold mb-0" style="color: #2b3452;">Frequently Asked Questions</h4>
            </div>
            
            <div class="accordion" id="supportFAQ">
              <div class="accordion-item border-0 mb-3 rounded-3">
                <h2 class="accordion-header">
                  <button class="accordion-button collapsed rounded-3" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                    How long does it take to get a response?
                  </button>
                </h2>
                <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#supportFAQ">
                  <div class="accordion-body">
                    We typically respond to all support tickets within 24 hours. High priority tickets are usually addressed within 4-6 hours during business days.
                  </div>
                </div>
              </div>
              
              <div class="accordion-item border-0 mb-3 rounded-3">
                <h2 class="accordion-header">
                  <button class="accordion-button collapsed rounded-3" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                    What information should I include in my ticket?
                  </button>
                </h2>
                <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#supportFAQ">
                  <div class="accordion-body">
                    Please include as much detail as possible: any error messages you've received, steps to reproduce the issue, and relevant order numbers or product names will help us assist you faster.
                  </div>
                </div>
              </div>
              
              <div class="accordion-item border-0 mb-3 rounded-3">
                <h2 class="accordion-header">
                  <button class="accordion-button collapsed rounded-3" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                    Can I update my ticket after submission?
                  </button>
                </h2>
                <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#supportFAQ">
                  <div class="accordion-body">
                    Yes, you can add additional information to your ticket by viewing it and adding a response. Our team will be notified of your update.
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<style>
.form-control:focus,
.form-select:focus {
  border-color: #0d6efd40;
  box-shadow: 0 0 0 0.25rem rgba(13,110,253,.15);
}

.form-floating > .form-control:focus ~ label,
.form-floating > .form-control:not(:placeholder-shown) ~ label,
.form-floating > .form-select ~ label {
  color: #0d6efd;
}

.btn-primary:hover {
  transform: translateY(-3px);
  box-shadow: 0 5px 15px rgba(13,110,253,0.2);
}

.ticket-item:hover {
  background: rgba(13,110,253,0.05);
  border-color: rgba(13,110,253,0.2) !important;
}

.accordion-button {
  box-shadow: none !important;
  background-color: #f8f9fa;
}

.accordion-button:not(.collapsed) {
  color: #0d6efd;
  background-color: rgba(13,110,253,0.1);
}

@media (max-width: 991.98px) {
  .support-section {
    margin-top: -40px;
  }
  
  .card-body {
    padding: 1.5rem !important;
  }
}
</style>

<?php include 'components/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/script.js"></script>

</body>
</html>