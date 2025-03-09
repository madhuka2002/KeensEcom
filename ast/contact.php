<?php

include 'components/connect.php';

session_start();

if(isset($_SESSION['user_id'])){
   $user_id = $_SESSION['user_id'];
}else{
   $user_id = '';
};

if(isset($_POST['send'])){

   $name = $_POST['name'];
   $name = filter_var($name, FILTER_SANITIZE_STRING);
   $email = $_POST['email'];
   $email = filter_var($email, FILTER_SANITIZE_STRING);
   $number = $_POST['number'];
   $number = filter_var($number, FILTER_SANITIZE_STRING);
   $msg = $_POST['msg'];
   $msg = filter_var($msg, FILTER_SANITIZE_STRING);

   $select_message = $conn->prepare("SELECT * FROM `messages` WHERE name = ? AND email = ? AND number = ? AND message = ?");
   $select_message->execute([$name, $email, $number, $msg]);

   if($select_message->rowCount() > 0){
      $message[] = 'already sent message!';
   }else{

      $insert_message = $conn->prepare("INSERT INTO `messages`(user_id, name, email, number, message) VALUES(?,?,?,?,?)");
      $insert_message->execute([$user_id, $name, $email, $number, $msg]);

      $message[] = 'sent message successfully!';

   }

}


?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Keens | Contact</title>
  <link rel="icon" type="image/x-icon" href="favicon.png">
   
   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <script>

    </script>
</head>
<body>
   
<?php include 'components/header.php'; ?>

<section class="contact-hero py-5" style="background: linear-gradient(135deg, #2b3452 0%, #1a1f2f 100%);">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-8 text-center">
        <span class="badge rounded-pill mb-3" style="background-color: rgba(13,110,253,0.1); color: #0d6efd; font-size: 0.9rem;">
          Keens / Contact
        </span>
        <h1 class="display-5 fw-bold text-white mb-3">GET IN TOUCH</h1>
        <p class="lead mb-0" style="color: #a4b5cf;">
          Have questions? We'd love to hear from you. Send us a message and we'll respond as soon as possible.
          <br><br><br><br>
        </p>
      </div>
    </div>
  </div>
</section>

<section class="contact-section position-relative" style="margin-top: -80px; z-index: 1;">
  <div class="container">
    <div class="row g-4">
      <!-- Contact Info Cards -->
      <div class="col-lg-4">
        <div class="row g-4">
          <div class="col-12">
            <div class="card border-0 rounded-4 p-4" style="background: white; box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
              <div class="d-flex align-items-center gap-3">
                <div class="flex-shrink-0">
                  <div class="p-3 rounded-circle" style="background: rgba(13,110,253,0.1);">
                    <i class="fas fa-map-marker-alt text-primary"></i>
                  </div>
                </div>
                <div>
                  <h5 class="fw-bold mb-1" style="color: #2b3452;">Address</h5>
                  <p class="mb-0 text-muted">Colombo, Sri Lanka</p>
                </div>
              </div>
            </div>
          </div>

          <div class="col-12">
            <div class="card border-0 rounded-4 p-4" style="background: white; box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
              <div class="d-flex align-items-center gap-3">
                <div class="flex-shrink-0">
                  <div class="p-3 rounded-circle" style="background: rgba(13,110,253,0.1);">
                    <i class="fas fa-phone text-primary"></i>
                  </div>
                </div>
                <div>
                  <h5 class="fw-bold mb-1" style="color: #2b3452;">Phone</h5>
                  <p class="mb-0 text-muted">+94 70 555 5555</p>
                </div>
              </div>
            </div>
          </div>

          <div class="col-12">
            <div class="card border-0 rounded-4 p-4" style="background: white; box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
              <div class="d-flex align-items-center gap-3">
                <div class="flex-shrink-0">
                  <div class="p-3 rounded-circle" style="background: rgba(13,110,253,0.1);">
                    <i class="fas fa-envelope text-primary"></i>
                  </div>
                </div>
                <div>
                  <h5 class="fw-bold mb-1" style="color: #2b3452;">Email</h5>
                  <p class="mb-0 text-muted">contact@keens.store</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Contact Form -->
      <div class="col-lg-8">
        <div class="card border-0 rounded-4" style="background: white; box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
          <div class="card-body p-4 p-lg-5">
            <div class="d-flex align-items-center gap-3 mb-4">
              <div class="flex-shrink-0">
                <div class="p-3 rounded-circle" style="background: rgba(13,110,253,0.1);">
                  <i class="fas fa-paper-plane text-primary"></i>
                </div>
              </div>
              <div>
                <h4 class="fw-bold mb-1" style="color: #2b3452;">Send us a Message</h4>
                <p class="text-muted mb-0">Fill out the form below to get in touch with us</p>
              </div>
            </div>

            <form action="" method="post">
              <div class="row g-4">
                <div class="col-md-6">
                  <div class="form-floating">
                    <input type="text" name="name" class="form-control" id="nameInput" placeholder="Your Name" 
                           required maxlength="20" style="border-radius: 12px; border: 1px solid #dee2e6;">
                    <label for="nameInput" class="text-muted">Your Name</label>
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="form-floating">
                    <input type="email" name="email" class="form-control" id="emailInput" placeholder="Your Email" 
                           required maxlength="50" style="border-radius: 12px; border: 1px solid #dee2e6;">
                    <label for="emailInput" class="text-muted">Your Email</label>
                  </div>
                </div>

                <div class="col-12">
                  <div class="form-floating">
                    <input type="tel" name="number" class="form-control" id="phoneInput" placeholder="Your Phone" 
                           required style="border-radius: 12px; border: 1px solid #dee2e6;">
                    <label for="phoneInput" class="text-muted">Your Phone</label>
                  </div>
                </div>

                <div class="col-12">
                  <div class="form-floating">
                    <textarea name="msg" class="form-control" placeholder="Your Message" id="messageInput" 
                              style="height: 150px; border-radius: 12px; border: 1px solid #dee2e6;"></textarea>
                    <label for="messageInput" class="text-muted">Your Message</label>
                  </div>
                </div>

                <div class="col-12">
                  <button type="submit" name="send" class="btn btn-primary px-5 py-3 rounded-pill" 
                          style="transition: all 0.3s ease;">
                    <i class="fas fa-paper-plane me-2"></i>
                    Send Message
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Map Section -->
<section class="map-section py-5">
  <div class="container">
    <div class="card border-0 rounded-4 overflow-hidden" style="box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
      <iframe 
        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d126743.58585989387!2d79.7861641!3d6.9218374!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3ae253d10f7a7003%3A0x320b2e4d32d3838d!2sColombo%2C%20Sri%20Lanka!5e0!3m2!1sen!2sus!4v1674486767305!5m2!1sen!2sus" 
        width="100%" 
        height="400" 
        style="border:0;" 
        allowfullscreen="" 
        loading="lazy">
      </iframe>
    </div>
  </div>
</section>

<style>
  .form-control:focus {
    border-color: #0d6efd40;
    box-shadow: 0 0 0 0.25rem rgba(13,110,253,.15);
  }
  
  .form-floating > .form-control:focus ~ label,
  .form-floating > .form-control:not(:placeholder-shown) ~ label {
    color: #0d6efd;
  }
  
  .btn-primary:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(13,110,253,0.2);
  }
  
  @media (max-width: 991.98px) {
    .contact-section {
      margin-top: -40px;
    }
  }
</style>

<?php include 'components/footer.php'; ?>

</body>
</html>