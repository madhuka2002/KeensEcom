<?php
include '../components/connect.php';

session_start();

if (!isset($_SESSION['supplier_id'])) {
    header('location:index.php');
    exit();
}

$supplier_id = $_SESSION['supplier_id'];

// Check if product ID is provided
if (!isset($_GET['id'])) {
    header('location:products.php');
    exit();
}

$product_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);

if (!$product_id) {
    header('location:products.php');
    exit();
}

// Fetch product details
$product_query = $conn->prepare("
    SELECT p.*, COALESCE(i.quantity, 0) as inventory_quantity 
    FROM products p
    LEFT JOIN inventory i ON p.id = i.product_id AND i.supplier_id = ?
    WHERE p.id = ?
");
$product_query->execute([$supplier_id, $product_id]);
$product = $product_query->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('location:products.php');
    exit();
}

// Handle product update
if (isset($_POST['update_product'])) {
    // Sanitize inputs
    $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
    $details = filter_var($_POST['details'], FILTER_SANITIZE_STRING);
    $price = filter_var($_POST['price'], FILTER_VALIDATE_FLOAT);
    $inventory_quantity = filter_var($_POST['inventory_quantity'], FILTER_VALIDATE_INT);

    // Validate inputs
    $errors = [];

    if (empty($name) || strlen($name) > 100) {
        $errors[] = "Product name is required and must be less than 100 characters.";
    }

    if (empty($details)) {
        $errors[] = "Product details are required.";
    }

    if ($price === false || $price <= 0) {
        $errors[] = "Invalid price. Please enter a positive number.";
    }

    if ($inventory_quantity === false || $inventory_quantity < 0) {
        $errors[] = "Invalid inventory quantity. Please enter a non-negative number.";
    }

    // Handle file uploads
    $image_files = ['image_01', 'image_02', 'image_03'];
    $uploaded_images = [];

    foreach ($image_files as $image_key) {
        if (isset($_FILES[$image_key]) && $_FILES[$image_key]['error'] == 0) {
            $file = $_FILES[$image_key];
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 5 * 1024 * 1024; // 5MB

            if (!in_array($file['type'], $allowed_types)) {
                $errors[] = "Invalid file type for $image_key. Only JPEG, PNG, and GIF are allowed.";
            }

            if ($file['size'] > $max_size) {
                $errors[] = "$image_key exceeds the maximum file size of 5MB.";
            }

            // Generate unique filename
            $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_filename = uniqid('product_', true) . '.' . $file_ext;
            $upload_path = '../uploads/' . $new_filename;

            if (empty($errors)) {
                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                    $uploaded_images[$image_key] = $new_filename;
                    
                    // Delete old image
                    $old_image_path = '../uploads/' . $product[$image_key];
                    if (file_exists($old_image_path)) {
                        unlink($old_image_path);
                    }
                } else {
                    $errors[] = "Failed to upload $image_key.";
                }
            }
        } else {
            // Keep existing image if no new image is uploaded
            $uploaded_images[$image_key] = $product[$image_key];
        }
    }

    // If no errors, update product and inventory
    if (empty($errors)) {
        try {
            // Start transaction
            $conn->beginTransaction();

            // Update product
            $update_product = $conn->prepare("
                UPDATE products 
                SET name = ?, details = ?, price = ?, 
                    image_01 = ?, image_02 = ?, image_03 = ?
                WHERE id = ?
            ");
            $update_product->execute([
                $name, 
                $details, 
                $price, 
                $uploaded_images['image_01'], 
                $uploaded_images['image_02'], 
                $uploaded_images['image_03'],
                $product_id
            ]);

            // Update or insert inventory
            $inventory_query = $conn->prepare("
                INSERT INTO inventory (product_id, supplier_id, quantity) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE quantity = ?
            ");
            $inventory_query->execute([
                $product_id, 
                $supplier_id, 
                $inventory_quantity,
                $inventory_quantity
            ]);

            // Commit transaction
            $conn->commit();

            // Redirect to products page with success message
            $_SESSION['success_message'] = "Product updated successfully!";
            header('location: products.php');
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
    <title>Keens | Edit Product</title>
    
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
                            <i class="fas fa-edit me-2"></i>Edit Product
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

                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="name" class="form-label">Product Name</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="name" 
                                       name="name" 
                                       required 
                                       maxlength="100"
                                       value="<?= htmlspecialchars($product['name']) ?>"
                                       placeholder="Enter product name">
                            </div>

                            <div class="mb-3">
                                <label for="details" class="form-label">Product Details</label>
                                <textarea 
                                    class="form-control" 
                                    id="details" 
                                    name="details" 
                                    required 
                                    rows="3"
                                    placeholder="Describe your product"
                                ><?= htmlspecialchars($product['details']) ?></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="price" class="form-label">Price ($)</label>
                                    <input type="number" 
                                           class="form-control" 
                                           id="price" 
                                           name="price" 
                                           required 
                                           step="0.01" 
                                           min="0"
                                           value="<?= htmlspecialchars($product['price']) ?>"
                                           placeholder="Enter product price">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="inventory_quantity" class="form-label">Inventory Quantity</label>
                                    <input type="number" 
                                           class="form-control" 
                                           id="inventory_quantity" 
                                           name="inventory_quantity" 
                                           required 
                                           min="0"
                                           value="<?= htmlspecialchars($product['inventory_quantity']) ?>"
                                           placeholder="Enter current stock">
                                </div>
                            </div>

                            <div class="row">
                                <?php foreach (['image_01', 'image_02', 'image_03'] as $image_key): ?>
                                    <div class="col-md-4 mb-3">
                                        <label for="<?= $image_key ?>" class="form-label">
                                            <?= ucfirst(str_replace('_', ' ', $image_key)) ?>
                                        </label>
                                        <div class="d-flex flex-column">
                                            <img src="../uploads/<?= htmlspecialchars($product[$image_key]) ?>" 
                                                 class="img-thumbnail mb-2" 
                                                 style="max-height: 150px; object-fit: cover;">
                                            <input type="file" 
                                                   class="form-control" 
                                                   id="<?= $image_key ?>" 
                                                   name="<?= $image_key ?>" 
                                                   accept="image/jpeg,image/png,image/gif">
                                            <small class="text-muted">Max 5MB (JPEG, PNG, GIF)</small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="products.php" class="btn btn-secondary me-md-2">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                                <button type="submit" name="update_product" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Update Product
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>