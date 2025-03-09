<?php
include '../components/connect.php';

session_start();

if (!isset($_SESSION['supplier_id'])) {
    header('location:index.php');
    exit();
}

$supplier_id = $_SESSION['supplier_id'];

// Fetch existing products for dropdown
$products_query = $conn->prepare("
    SELECT p.id, p.name, p.price, 
           COALESCE(i.quantity, 0) as current_inventory
    FROM products p
    LEFT JOIN inventory i ON p.id = i.product_id AND i.supplier_id = ?
");
$products_query->execute([$supplier_id]);
$products = $products_query->fetchAll(PDO::FETCH_ASSOC);

// Handle supply order creation
if (isset($_POST['create_supply_order'])) {
    // Validate and sanitize inputs
    $errors = [];

    // Prepare product details array
    $order_products = [];
    $total_amount = 0;

    // Validate product inputs
    if (isset($_POST['products']) && is_array($_POST['products'])) {
        foreach ($_POST['products'] as $index => $product_data) {
            $product_id = filter_var($product_data['product_id'], FILTER_VALIDATE_INT);
            $quantity = filter_var($product_data['quantity'], FILTER_VALIDATE_INT);
            $unit_cost = filter_var($product_data['unit_cost'], FILTER_VALIDATE_FLOAT);

            // Validate each product entry
            if (!$product_id) {
                $errors[] = "Invalid product selection at item " . ($index + 1);
                continue;
            }

            if ($quantity <= 0) {
                $errors[] = "Quantity must be a positive number for item " . ($index + 1);
                continue;
            }

            if ($unit_cost <= 0) {
                $errors[] = "Unit cost must be a positive number for item " . ($index + 1);
                continue;
            }

            // Calculate subtotal
            $subtotal = $quantity * $unit_cost;
            $total_amount += $subtotal;

            // Add to order products
            $order_products[] = [
                'product_id' => $product_id,
                'quantity' => $quantity,
                'unit_cost' => $unit_cost,
                'subtotal' => $subtotal
            ];
        }
    } else {
        $errors[] = "Please add at least one product to the supply order";
    }

    // Additional validations
    $expected_delivery = filter_var($_POST['expected_delivery'], FILTER_SANITIZE_STRING);
    $notes = filter_var($_POST['notes'] ?? '', FILTER_SANITIZE_STRING);
    $payment_method = filter_var($_POST['payment_method'], FILTER_SANITIZE_STRING);

    // Validate payment method
    $allowed_payment_methods = ['bank_transfer', 'credit_card', 'cash', 'check'];
    if (!in_array($payment_method, $allowed_payment_methods)) {
        $errors[] = "Invalid payment method selected";
    }

    // Validate expected delivery date
    if (empty($expected_delivery) || strtotime($expected_delivery) < strtotime('today')) {
        $errors[] = "Invalid or past expected delivery date";
    }

    // If no errors, process the supply order
    if (empty($errors)) {
        try {
            // Start transaction
            $conn->beginTransaction();

            // Insert supply order
            $insert_order = $conn->prepare("
                INSERT INTO supply_orders 
                (supplier_id, total_amount, status, order_date, 
                 expected_delivery, notes, payment_method) 
                VALUES (?, ?, 'pending', NOW(), ?, ?, ?)
            ");
            $insert_order->execute([
                $supplier_id, 
                $total_amount, 
                $expected_delivery,
                $notes,
                $payment_method
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
            foreach ($order_products as $product) {
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
            $_SESSION['success_message'] = "Supply order created successfully!";
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
    <title>Keens | Create Supply Order</title>
    
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
                            <i class="fas fa-cart-plus me-2"></i>Create Supply Order
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

                        <form id="supplyOrderForm" method="POST">
                            <div id="productContainer">
                                <!-- Initial Product Row -->
                                <div class="product-row mb-3 p-3 border rounded">
                                    <div class="row">
                                        <div class="col-md-4 mb-2">
                                            <label class="form-label">Product</label>
                                            <select name="products[0][product_id]" 
                                                    class="form-select product-select" 
                                                    required
                                                    data-current-inventory-display="current-inventory-0">
                                                <option value="">Select Product</option>
                                                <?php foreach ($products as $product): ?>
                                                    <option value="<?= $product['id'] ?>"
                                                            data-price="<?= $product['price'] ?>"
                                                            data-current-inventory="<?= $product['current_inventory'] ?>">
                                                        <?= htmlspecialchars($product['name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <small id="current-inventory-0" class="form-text text-muted">
                                                Current Inventory: 0
                                            </small>
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <label class="form-label">Quantity</label>
                                            <input type="number" 
                                                   name="products[0][quantity]" 
                                                   class="form-control quantity-input" 
                                                   min="1" 
                                                   required 
                                                   placeholder="Qty">
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <label class="form-label">Unit Cost ($)</label>
                                            <input type="number" 
                                                   name="products[0][unit_cost]" 
                                                   class="form-control unit-cost-input" 
                                                   step="0.01" 
                                                   min="0.01" 
                                                   required 
                                                   placeholder="Cost">
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <label class="form-label">Subtotal</label>
                                            <input type="text" 
                                                   class="form-control subtotal-display" 
                                                   readonly 
                                                   value="$0.00">
                                        </div>
                                        <div class="col-md-2 mb-2 d-flex align-items-end">
                                            <button type="button" class="btn btn-danger remove-product">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-center mb-3">
                                <button type="button" id="addProductBtn" class="btn btn-outline-primary">
                                    <i class="fas fa-plus me-2"></i>Add Product
                                </button>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Payment Method</label>
                                    <select name="payment_method" class="form-select" required>
                                        <option value="">Select Payment Method</option>
                                        <option value="bank_transfer">Bank Transfer</option>
                                        <option value="credit_card">Credit Card</option>
                                        <option value="cash">Cash</option>
                                        <option value="check">Check</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Expected Delivery Date</label>
                                    <input type="date" 
                                           name="expected_delivery" 
                                           class="form-control" 
                                           required 
                                           min="<?= date('Y-m-d') ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Total Order Amount</label>
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
                                    placeholder="Any additional information about the supply order"
                                ></textarea>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="supply_orders.php" class="btn btn-secondary me-md-2">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                                <button type="submit" name="create_supply_order" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Create Supply Order
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

        // Function to calculate total amount and update subtotals
        function calculateTotals() {
            let total = 0;
            document.querySelectorAll('.product-row').forEach(row => {
                const quantity = row.querySelector('.quantity-input').value || 0;
                const unitCost = row.querySelector('.unit-cost-input').value || 0;
                const subtotal = quantity * unitCost;
                
                row.querySelector('.subtotal-display').value = `$${subtotal.toFixed(2)}`;
                total += subtotal;
            });
            totalAmountInput.value = total.toFixed(2);
        }

        // Update current inventory and suggested unit cost when product is selected
        productContainer.addEventListener('change', function(e) {
            if (e.target.classList.contains('product-select')) {
                const selectedOption = e.target.selectedOptions[0];
                const currentInventoryDisplay = document.getElementById(
                    e.target.getAttribute('data-current-inventory-display')
                );
                const currentInventory = selectedOption.getAttribute('data-current-inventory');
                const suggestedPrice = selectedOption.getAttribute('data-price');

                // Update current inventory display
                currentInventoryDisplay.textContent = `Current Inventory: ${currentInventory}`;

                // Auto-fill unit cost with product price
                const unitCostInput = e.target.closest('.product-row').querySelector('.unit-cost-input');
                unitCostInput.value = suggestedPrice;
            }
        });

        // Add product row
        addProductBtn.addEventListener('click', function() {
            const newRow = productContainer.querySelector('.product-row').cloneNode(true);
            
            // Reset and update inputs
            newRow.querySelectorAll('input').forEach(input => input.value = '');
            
            // Update name and ID attributes
            newRow.querySelectorAll('[name]').forEach(el => {
                el.name = el.name.replace(/\[\d+\]/, `[${productIndex}]`);
            });
            newRow.querySelector('.product-select').setAttribute(
                'data-current-inventory-display', 
                `current-inventory-${productIndex}`
            );

            // Add inventory display element
            const inventoryDisplay = document.createElement('small');
            inventoryDisplay.id = `current-inventory-${productIndex}`;
            inventoryDisplay.className = 'form-text text-muted';
            inventoryDisplay.textContent = 'Current Inventory: 0';
            newRow.querySelector('.product-select').after(inventoryDisplay);

            // Add remove functionality
            newRow.querySelector('.remove-product').addEventListener('click', function() {
                newRow.remove();
                calculateTotals();
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
                    calculateTotals();
                }
            }
        });

        // Calculate totals on input change
        productContainer.addEventListener('input', function(e) {
            if (e.target.classList.contains('quantity-input') || 
                e.target.classList.contains('unit-cost-input')) {
                    calculateTotals();
            }
        });

        // Initial setup
        calculateTotals();
    });
    </script>

    <style>
        .product-row {
            position: relative;
            transition: all 0.3s ease;
        }

        .remove-product {
            transition: all 0.2s ease;
        }

        .remove-product:hover {
            background-color: #dc3545;
            color: white;
        }

        .subtotal-display {
            background-color: #f8f9fa;
            font-weight: bold;
        }

        #totalAmount {
            font-size: 1.2rem;
            font-weight: bold;
            background-color: #e9ecef;
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>