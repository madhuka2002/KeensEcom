<?php
include '../components/connect.php';

session_start();

if (!isset($_SESSION['supplier_id'])) {
    header('location:index.php');
    exit();
}

$supplier_id = $_SESSION['supplier_id'];

// Prepare list of products for dropdown
$products_query = $conn->prepare("
    SELECT p.id, p.name, COALESCE(i.quantity, 0) as current_quantity 
    FROM products p
    LEFT JOIN inventory i ON p.id = i.product_id AND i.supplier_id = ?
    ORDER BY p.name
");
$products_query->execute([$supplier_id]);
$products = $products_query->fetchAll(PDO::FETCH_ASSOC);

// Check if a specific product is selected for update
$selected_product = null;
if (isset($_GET['id'])) {
    $product_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($product_id) {
        $selected_query = $conn->prepare("
            SELECT p.id, p.name, COALESCE(i.quantity, 0) as current_quantity 
            FROM products p
            LEFT JOIN inventory i ON p.id = i.product_id AND i.supplier_id = ?
            WHERE p.id = ?
        ");
        $selected_query->execute([$supplier_id, $product_id]);
        $selected_product = $selected_query->fetch(PDO::FETCH_ASSOC);
    }
}

// Handle inventory update
if (isset($_POST['update_inventory'])) {
    // Sanitize inputs
    $product_id = filter_var($_POST['product_id'], FILTER_VALIDATE_INT);
    $quantity_change = filter_var($_POST['quantity_change'], FILTER_VALIDATE_INT);
    $change_type = filter_var($_POST['change_type'], FILTER_SANITIZE_STRING);
    $reason = filter_var($_POST['reason'], FILTER_SANITIZE_STRING);

    // Validate inputs
    $errors = [];

    if (!$product_id) {
        $errors[] = "Please select a product.";
    }

    if ($quantity_change === false || $quantity_change <= 0) {
        $errors[] = "Quantity change must be a positive number.";
    }

    if (empty($reason)) {
        $errors[] = "Reason for inventory change is required.";
    }

    // If no errors, update inventory
    if (empty($errors)) {
        try {
            // Start transaction
            $conn->beginTransaction();

            // Fetch current inventory
            $current_inventory_query = $conn->prepare("
                SELECT quantity 
                FROM inventory 
                WHERE product_id = ? AND supplier_id = ?
            ");
            $current_inventory_query->execute([$product_id, $supplier_id]);
            $current_inventory = $current_inventory_query->fetch(PDO::FETCH_ASSOC);

            // Calculate new quantity based on change type
            $new_quantity = $change_type == 'add' 
                ? ($current_inventory['quantity'] + $quantity_change) 
                : max(0, $current_inventory['quantity'] - $quantity_change);

            // Update or insert inventory
            $inventory_query = $conn->prepare("
                INSERT INTO inventory (product_id, supplier_id, quantity) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE quantity = ?
            ");
            $inventory_query->execute([
                $product_id, 
                $supplier_id, 
                $new_quantity,
                $new_quantity
            ]);

            // Log inventory change
            $log_query = $conn->prepare("
                INSERT INTO inventory_log 
                (supplier_id, product_id, previous_quantity, new_quantity, change_type, reason) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $log_query->execute([
                $supplier_id,
                $product_id,
                $current_inventory['quantity'],
                $new_quantity,
                $change_type,
                $reason
            ]);

            // Commit transaction
            $conn->commit();

            // Set success message
            $_SESSION['success_message'] = "Inventory updated successfully!";
            header('location: inventory.php');
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
    <title>Keens | Update Inventory</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
</head>
<body>
    <?php include '../components/supplier_header.php'; ?>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">
                            <i class="fas fa-boxes me-2"></i>Update Inventory
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

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="product_id" class="form-label">Select Product</label>
                                <select 
                                    class="form-select" 
                                    id="product_id" 
                                    name="product_id" 
                                    required
                                    onchange="updateCurrentQuantity(this)"
                                >
                                    <option value="">Choose a Product</option>
                                    <?php foreach ($products as $product): ?>
                                        <option 
                                            value="<?= $product['id'] ?>"
                                            data-current-quantity="<?= $product['current_quantity'] ?>"
                                            <?= $selected_product && $selected_product['id'] == $product['id'] ? 'selected' : '' ?>
                                        >
                                            <?= htmlspecialchars($product['name']) ?> 
                                            (Current: <?= $product['current_quantity'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="current_quantity" class="form-label">Current Quantity</label>
                                    <input 
                                        type="number" 
                                        class="form-control" 
                                        id="current_quantity" 
                                        readonly
                                        value="<?= $selected_product ? $selected_product['current_quantity'] : 0 ?>"
                                    >
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="quantity_change" class="form-label">Quantity Change</label>
                                    <input 
                                        type="number" 
                                        class="form-control" 
                                        id="quantity_change" 
                                        name="quantity_change" 
                                        required 
                                        min="1"
                                        placeholder="Enter quantity to add/remove"
                                    >
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Change Type</label>
                                <div class="d-flex">
                                    <div class="form-check me-3">
                                        <input 
                                            class="form-check-input" 
                                            type="radio" 
                                            name="change_type" 
                                            id="add_inventory" 
                                            value="add" 
                                            required
                                        >
                                        <label class="form-check-label" for="add_inventory">
                                            Add to Inventory
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input 
                                            class="form-check-input" 
                                            type="radio" 
                                            name="change_type" 
                                            id="remove_inventory" 
                                            value="remove" 
                                            required
                                        >
                                        <label class="form-check-label" for="remove_inventory">
                                            Remove from Inventory
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="reason" class="form-label">Reason for Change</label>
                                <textarea 
                                    class="form-control" 
                                    id="reason" 
                                    name="reason" 
                                    rows="3" 
                                    required 
                                    placeholder="Explain the reason for inventory adjustment"
                                ></textarea>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="inventory.php" class="btn btn-secondary me-md-2">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                                <button type="submit" name="update_inventory" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Update Inventory
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function updateCurrentQuantity(selectElement) {
        const currentQuantityInput = document.getElementById('current_quantity');
        const selectedOption = selectElement.options[selectElement.selectedIndex];
        const currentQuantity = selectedOption.getAttribute('data-current-quantity');
        currentQuantityInput.value = currentQuantity;
    }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>