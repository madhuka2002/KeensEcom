<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Keens.lk Staff Portal</title>
  <link rel="icon" type="image/x-icon" href="favicon.png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <style>
    body {
      background-color: #f8f9fa;
      background-image: radial-gradient(circle at 30% 20%, rgba(67, 97, 238, 0.05) 0%, transparent 50%),
                        radial-gradient(circle at 70% 80%, rgba(58, 12, 163, 0.05) 0%, transparent 50%),
                        radial-gradient(circle at 90% 10%, rgba(138, 43, 226, 0.05) 0%, transparent 40%);
      min-height: 100vh;
      overflow-x: hidden;
    }
    
    /* Unified glassmorphism card style */
    .portal-card {
      background: rgba(255, 255, 255, 0.7);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.2);
      border-radius: 15px;
      overflow: hidden;
      transition: all 0.3s ease;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
      height: 100%;
    }
    
    .portal-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.4);
    }
    
    .portal-icon {
      width: 60px;
      height: 60px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.8rem;
      margin-bottom: 1rem;
      background: rgba(255, 255, 255, 0.3);
      backdrop-filter: blur(5px);
      border: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .portal-link {
      text-decoration: none;
      color: inherit;
    }
    
    .header {
      background: linear-gradient(135deg, rgba(67, 97, 238, 0.8) 0%, rgba(58, 12, 163, 0.8) 100%);
      backdrop-filter: blur(10px);
      color: white;
      border-radius: 15px;
      margin-bottom: 2rem;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
      position: relative;
      overflow: hidden;
      border: 1px solid rgba(255, 255, 255, 0.2);
    }
    
    .header:before {
      content: '';
      position: absolute;
      top: -10%;
      left: -10%;
      width: 120%;
      height: 120%;
      background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, transparent 100%);
      pointer-events: none;
    }
    
    .header-icon {
      width: 70px;
      height: 70px;
      border-radius: 15px;
      background: rgba(255, 255, 255, 0.2);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2rem;
      margin-right: 1.5rem;
      border: 1px solid rgba(255, 255, 255, 0.3);
    }
    
    /* Unified grid layout */
    .role-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 24px;
    }
    
    /* Consistent button style */
    .portal-btn {
      background: rgba(255, 255, 255, 0.2);
      backdrop-filter: blur(5px);
      border: 1px solid rgba(255, 255, 255, 0.1);
      color: #4361ee;
      font-weight: 500;
      padding: 8px 16px;
      border-radius: 10px;
      transition: all 0.3s ease;
    }
    
    .portal-btn:hover {
      background: rgba(67, 97, 238, 0.1);
      border: 1px solid rgba(67, 97, 238, 0.2);
      transform: translateY(-2px);
    }
    
    @media (max-width: 768px) {
      .role-grid {
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      }
    }
  </style>
</head>
<body>
  <div class="container py-5">
    <!-- Header -->
    <div class="header p-4 mb-5">
      <div class="d-flex align-items-center">
        <div class="header-icon">
          <i class="fas fa-user-lock"></i>
        </div>
        <div>
          <h1 class="mb-0">Keens.lk Staff Portal</h1>
          <p class="mb-0">Select your role to access the system</p>
        </div>
      </div>
    </div>
    
    <div class="role-grid">
      <!-- Administrator -->
      <div class="grid-item">
        <a href="admin/index.php" class="portal-link">
          <div class="card portal-card">
            <div class="card-body p-4 text-center">
              <div class="portal-icon text-primary mx-auto">
                <i class="fas fa-user-shield"></i>
              </div>
              <h4 class="card-title">Administrator</h4>
              <p class="card-text text-muted">System management, user roles, and settings</p>
              <button class="btn portal-btn mt-3">
                <i class="fas fa-sign-in-alt me-2"></i>Access Portal
              </button>
            </div>
          </div>
        </a>
      </div>
      
      <!-- CSR -->
      <div class="grid-item">
        <a href="csr/index.php" class="portal-link">
          <div class="card portal-card">
            <div class="card-body p-4 text-center">
              <div class="portal-icon text-success mx-auto">
                <i class="fas fa-headset"></i>
              </div>
              <h4 class="card-title">Customer Service</h4>
              <p class="card-text text-muted">Customer inquiries, orders, and support</p>
              <button class="btn portal-btn mt-3">
                <i class="fas fa-sign-in-alt me-2"></i>Access Portal
              </button>
            </div>
          </div>
        </a>
      </div>
      
      <!-- Delivery Agent -->
      <div class="grid-item">
        <a href="delivery/index.php" class="portal-link">
          <div class="card portal-card">
            <div class="card-body p-4 text-center">
              <div class="portal-icon text-info mx-auto">
                <i class="fas fa-truck"></i>
              </div>
              <h4 class="card-title">Delivery Agent</h4>
              <p class="card-text text-muted">Deliveries & tracking</p>
              <button class="btn portal-btn mt-3">
                <i class="fas fa-sign-in-alt me-2"></i>Access Portal
              </button>
            </div>
          </div>
        </a>
      </div>
      
      <!-- Financial Auditor -->
      <div class="grid-item">
        <a href="fa/index.php" class="portal-link">
          <div class="card portal-card">
            <div class="card-body p-4 text-center">
              <div class="portal-icon text-purple mx-auto" style="color: blueviolet;">
                <i class="fas fa-balance-scale"></i>
              </div>
              <h4 class="card-title">Financial Auditor</h4>
              <p class="card-text text-muted">Financial analysis, audits, and reporting</p>
              <button class="btn portal-btn mt-3">
                <i class="fas fa-sign-in-alt me-2"></i>Access Portal
              </button>
            </div>
          </div>
        </a>
      </div>
      
      <!-- Product Manager -->
      <div class="grid-item">
        <a href="product_manager/index.php" class="portal-link">
          <div class="card portal-card">
            <div class="card-body p-4 text-center">
              <div class="portal-icon text-warning mx-auto">
                <i class="fas fa-box-open"></i>
              </div>
              <h4 class="card-title">Product Manager</h4>
              <p class="card-text text-muted">Product catalog & inventory</p>
              <button class="btn portal-btn mt-3">
                <i class="fas fa-sign-in-alt me-2"></i>Access Portal
              </button>
            </div>
          </div>
        </a>
      </div>
      
      <!-- Warehouse Staff -->
      <div class="grid-item">
        <a href="warehouse_staff/index.php" class="portal-link">
          <div class="card portal-card">
            <div class="card-body p-4 text-center">
              <div class="portal-icon text-danger mx-auto">
                <i class="fas fa-warehouse"></i>
              </div>
              <h4 class="card-title">Warehouse Staff</h4>
              <p class="card-text text-muted">Inventory management and order processing</p>
              <button class="btn portal-btn mt-3">
                <i class="fas fa-sign-in-alt me-2"></i>Access Portal
              </button>
            </div>
          </div>
        </a>
      </div>
      
      <!-- Supplier -->
      <div class="grid-item">
        <a href="supplier/index.php" class="portal-link">
          <div class="card portal-card">
            <div class="card-body p-4 text-center">
              <div class="portal-icon text-secondary mx-auto">
                <i class="fas fa-dolly"></i>
              </div>
              <h4 class="card-title">Supplier</h4>
              <p class="card-text text-muted">Supply chain, orders, and deliveries</p>
              <button class="btn portal-btn mt-3">
                <i class="fas fa-sign-in-alt me-2"></i>Access Portal
              </button>
            </div>
          </div>
        </a>
      </div>
    </div>
    
    <!-- Back to Main Website -->
    <div class="text-center mt-5">
      <a href="index.php" class="btn portal-btn">
        <i class="fas fa-arrow-left me-2"></i>Back to Main Website
      </a>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>