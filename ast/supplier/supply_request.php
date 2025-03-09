<?php
include '../components/connect.php';

session_start();

if (!isset($_SESSION['supplier_id'])) {
    header('location:index.php');
    exit();
}

$supplier_id = $_SESSION['supplier_id'];

// Fetch existing products for dropdown
$products_query = $conn->prepare("SELECT id, name FROM products");
$products_query->execute();
$products = $products_query->fetchAll(PDO::FETCH_ASSOC);

// Handle supply request submission
if (isset($_POST['submit_request'])) {
    // Validate and sanitize inputs
    $errors = [];

    // Prepare product details array
    $request_products = [];
    $total_amount = 0;

    // Validate product inputs
    if (isset($_POST['products']) && is_array($_POST['products'])) {
        foreach ($_POST['products'] as $index => $product_data) {
            $product_id = filter_var($product_data['product_id'], FILTER_VALIDATE_INT);
            $quantity = filter_var($product_data['quantity'], FILTER_VALIDATE_INT);
            $unit_cost = filter_var($product_data['unit_cost'], FILTER_VALIDATE_FLOAT);

            // Validate each product entry
            if (!$product_id) {
                $errors[] = "Invalid product selection";
                continue;
            }

            if ($quantity <= 0) {
                $errors[] = "Quantity must be a positive number";
                continue;
            }

            if ($unit_cost <= 0) {
                $errors[] = "Unit cost must be a positive number";
                continue;
            }

            // Calculate subtotal
            $subtotal = $quantity * $unit_cost;
            $total_amount += $subtotal;

            // Add to request products
            $request_products[] = [
                'product_id' => $product_id,
                'quantity' => $quantity,
                'unit_cost' => $unit_cost,
                'subtotal' => $subtotal
            ];
        }
    } else {
        $errors[] = "Please add at least one product to the supply request";
    }

    // Additional validation
    $notes = filter_var($_POST['notes'] ?? '', FILTER_SANITIZE_STRING);
    $expected_delivery = filter_var($_POST['expected_delivery'], FILTER_SANITIZE_STRING);

    if (empty($request_products)) {
        $errors[] = "No valid products in the request";
    }

    // If no errors, process the supply request
    if (empty($errors)) {
        try {
            // Start transaction
            $conn->beginTransaction();

            // Insert supply order
            $insert_order = $conn->prepare("
                INSERT INTO supply_orders 
                (supplier_id, total_amount, status, order_date, expected_delivery, notes) 
                VALUES (?, ?, 'pending', NOW(), ?, ?)
            ");
            $insert_order->execute([
                $supplier_id, 
                $total_amount, 
                $expected_delivery,
                $notes
            ]);

            // Get the last inserted order ID
            $order_id = $conn->lastInsertId();

            // Prepare order items insert
            $insert_item = $conn->prepare("
                INSERT INTO supply_order_items 
                (supply_order_id, product_id, quantity, unit_cost, subtotal) 
                VALUES (?, ?, ?, ?, ?)
            ");

            // Insert each product in the order
            foreach ($request_products as $product) {
                $insert_item->execute([
                    $order_id,
                    $product['product_id'],
                    $product['quantity'],
                    $product['unit_cost'],
                    $product['subtotal']
                ]);
            }

            // Commit transaction
            $conn->commit();

            // Set success message
            $_SESSION['success_message'] = "Supply request submitted successfully!";
            header('location: supply_orders.php');
            exit();

        } catch (PDOException $e) {
            // Rollback transaction
            $conn->rollBack();
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keens | Create Supply Request</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
</head>
<body>
    <?php include '../components/supplier_header.php'; ?>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">
                            <i class="fas fa-truck-loading me-2"></i>Create Supply Request
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <?php foreach ($errors as $error): ?>
                                    <p class="mb-0">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <?= htmlspecialchars($error) ?>
                                    </p>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <form id="supplyRequestForm" method="POST">
                            <div id="productContainer">
                                <!-- Initial Product Row -->
                                <div class="product-row mb-3 p-3 border rounded">
                                    <div class="row">
                                        <div class="col-md-4 mb-2">
                                            <label class="form-label">Product</label>
                                            <select name="products[0][product_id]" class="form-select product-select" required>
                                                <option value="">Select Product</option>
                                                <?php foreach ($products as $product): ?>
                                                    <option value="<?= $product['id'] ?>">
                                                        <?= htmlspecialchars($product['name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <label class="form-label">Quantity</label>
                                            <input type="number" 
                                                   name="products[0][quantity]" 
                                                   class="form-control quantity-input" 
                                                   min="1" 
                                                   required 
                                                   placeholder="Enter quantity">
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <label class="form-label">Unit Cost ($)</label>
                                            <input type="number" 
                                                   name="products[0][unit_cost]" 
                                                   class="form-control unit-cost-input" 
                                                   step="0.01" 
                                                   min="0.01" 
                                                   required 
                                                   placeholder="Enter unit cost">
                                        </div>
                                        <div class="col-md-2 mb-2 d-flex align-items-end">
                                            <button type="button" class="btn btn-danger remove-product">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="row mt-2">
                                        <div class="col-12">
                                            <small class="subtotal-display text-muted">Subtotal: $0.00</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-center mb-3">
                                <button type="button" id="addProductBtn" class="btn btn-outline-primary">
                                    <i class="fas fa-plus me-2"></i>Add Another Product
                                </button>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Expected Delivery Date</label>
                                    <input type="date" 
                                           name="expected_delivery" 
                                           class="form-control" 
                                           required 
                                           min="<?= date('Y-m-d') ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Total Request Amount</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="text" 
                                               id="totalAmount" 
                                               class="form-control" 
                                               readonly 
                                               value="0.00">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Additional Notes</label>
                                <textarea 
                                    name="notes" 
                                    class="form-control" 
                                    rows="3" 
                                    placeholder="Any additional information about the supply request"
                                ></textarea>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="supply_orders.php" class="btn btn-secondary me-md-2">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                                <button type="submit" name="submit_request" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-2"></i>Submit Request
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const productContainer = document.getElementById('productContainer');
        const addProductBtn = document.getElementById('addProductBtn');
        const totalAmountInput = document.getElementById('totalAmount');
        let productIndex = 1;

        // Function to calculate total amount
        function calculateTotalAmount() {
            let total = 0;
            document.querySelectorAll('.product-row').forEach(row => {
                const quantity = row.querySelector('.quantity-input').value || 0;
                const unitCost = row.querySelector('.unit-cost-input').value || 0;
                const subtotal = quantity * unitCost;
                
                row.querySelector('.subtotal-display').textContent = `Subtotal: $${subtotal.toFixed(2)}`;
                total += subtotal;
            });
            totalAmountInput.value = total.toFixed(2);
        }

        // Add product row
        addProductBtn.addEventListener('click', function() {
            const newRow = productContainer.querySelector('.product-row').cloneNode(true);
            
            // Reset inputs
            newRow.querySelectorAll('input').forEach(input => input.value = '');
            newRow.querySelector('.subtotal-display').textContent = 'Subtotal: $0.00';
            
            // Update name attributes
            newRow.querySelectorAll('[name]').forEach(el => {
                el.name = el.name.replace(/\[\d+\]/, `[${productIndex}]`);
            });

            // Add remove functionality
            newRow.querySelector('.remove-product').addEventListener('click', function() {
                newRow.remove();
                calculateTotalAmount();
            });

            // Add to container
            productContainer.appendChild(newRow);
            productIndex++;
        });

        // Remove product row
        productContainer.addEventListener('click', function(e) {
            if (e.target.closest('.remove-product')) {
                if (document.querySelectorAll('.product-row').length > 1) {
                    e.target.closest('.product-row').remove();
                    calculateTotalAmount();
                }
            }
        });

        // Calculate total on input change
        productContainer.addEventListener('input', function(e) {
            if (e.target.classList.contains('quantity-input') || 
                e.target.classList.contains('unit-cost-input')) {
                calculateTotalAmount();
            }
        });
    });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>