<?php
include '../components/connect.php';

session_start();

$manager_id = $_SESSION['manager_id'];

if(!isset($manager_id)){
   header('location:index.php');
}

// Handle Add Category
if(isset($_POST['add_category'])){
   $name = $_POST['name'];
   $name = filter_var($name, FILTER_SANITIZE_STRING);
   $description = $_POST['description'];
   $description = filter_var($description, FILTER_SANITIZE_STRING);

   $select_category = $conn->prepare("SELECT * FROM `category` WHERE name = ?");
   $select_category->execute([$name]);

   if($select_category->rowCount() > 0){
      $message[] = 'Category already exists!';
   }else{
      $insert_category = $conn->prepare("INSERT INTO `category`(name, description) VALUES(?,?)");
      $insert_category->execute([$name, $description]);
      $message[] = 'New category added successfully!';
   }
}

// Handle Edit Category
if(isset($_POST['edit_category'])){
   $category_id = $_POST['category_id'];
   $name = $_POST['name'];
   $name = filter_var($name, FILTER_SANITIZE_STRING);
   $description = $_POST['description'];
   $description = filter_var($description, FILTER_SANITIZE_STRING);

   // Check if another category with the same name exists (excluding current one)
   $select_category = $conn->prepare("SELECT * FROM `category` WHERE name = ? AND category_id != ?");
   $select_category->execute([$name, $category_id]);

   if($select_category->rowCount() > 0){
      $message[] = 'Category name already exists!';
   }else{
      $update_category = $conn->prepare("UPDATE `category` SET name = ?, description = ? WHERE category_id = ?");
      $update_category->execute([$name, $description, $category_id]);
      $message[] = 'Category updated successfully!';
   }
}

// Handle Delete Category
if(isset($_GET['delete'])){
   $delete_id = $_GET['delete'];
   
   // Check if category has products before deleting
   $check_products = $conn->prepare("SELECT * FROM `products` WHERE category_id = ?");
   $check_products->execute([$delete_id]);
   
   if($check_products->rowCount() > 0){
      $message[] = 'Cannot delete category with existing products!';
   }else{
      $delete_category = $conn->prepare("DELETE FROM `category` WHERE category_id = ?");
      $delete_category->execute([$delete_id]);
      header('location:categories.php');
   }
}

// Get category data for edit modal
$category_data = null;
if(isset($_GET['edit'])){
   $edit_id = $_GET['edit'];
   $select_category = $conn->prepare("SELECT * FROM `category` WHERE category_id = ?");
   $select_category->execute([$edit_id]);
   if($select_category->rowCount() > 0){
      $category_data = $select_category->fetch(PDO::FETCH_ASSOC);
   }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Keens | Categories</title>

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<?php include '../components/product_manager_header.php'; ?>

<section class="categories">
    <div class="container-fluid px-4 py-5">
        <div class="row">
            <!-- Category List -->
            <div class="col-lg-8">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="fw-bold mb-0">Categories</h2>
                        <p class="text-muted mb-0">Manage your product categories</p>
                    </div>
                    <button class="btn btn-primary rounded-3" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                        <i class="fas fa-plus me-2"></i>Add Category
                    </button>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4">Category Name</th>
                                        <th>Description</th>
                                        <th>Products</th>
                                        <th class="text-end pe-4">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                        $show_categories = $conn->prepare("SELECT c.*, COUNT(p.id) as product_count 
                                                                         FROM `category` c 
                                                                         LEFT JOIN `products` p ON c.category_id = p.category_id 
                                                                         GROUP BY c.category_id");
                                        $show_categories->execute();
                                        if($show_categories->rowCount() > 0){
                                            while($fetch_category = $show_categories->fetch(PDO::FETCH_ASSOC)){
                                    ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center">
                                                <div class="category-icon me-3">
                                                    <i class="fas fa-folder text-primary"></i>
                                                </div>
                                                <span class="fw-medium"><?= $fetch_category['name']; ?></span>
                                            </div>
                                        </td>
                                        <td><?= $fetch_category['description']; ?></td>
                                        <td>
                                            <span class="badge bg-primary-subtle text-primary rounded-pill">
                                                <?= $fetch_category['product_count']; ?> products
                                            </span>
                                        </td>
                                        <td class="text-end pe-4">
                                            <a href="categories.php?edit=<?= $fetch_category['category_id']; ?>" 
                                               class="btn btn-sm btn-light me-2">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="categories.php?delete=<?= $fetch_category['category_id']; ?>" 
                                               class="btn btn-sm btn-light text-danger"
                                               onclick="return confirm('Delete this category?');">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php
                                            }
                                        } else {
                                            echo '<tr><td colspan="4" class="text-center py-4">No categories found</td></tr>';
                                        }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Category Statistics -->
            <div class="col-lg-4 mt-4 mt-lg-0">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="fw-bold mb-4">Category Statistics</h5>
                        <?php
                            $total_categories = $conn->prepare("SELECT COUNT(*) as count FROM `category`");
                            $total_categories->execute();
                            $category_count = $total_categories->fetch(PDO::FETCH_ASSOC)['count'];

                            $empty_categories = $conn->prepare("SELECT COUNT(c.category_id) as count 
                                                             FROM `category` c 
                                                             LEFT JOIN `products` p ON c.category_id = p.category_id 
                                                             WHERE p.id IS NULL");
                            $empty_categories->execute();
                            $empty_count = $empty_categories->fetch(PDO::FETCH_ASSOC)['count'];
                        ?>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span>Total Categories</span>
                            <span class="fw-bold"><?= $category_count; ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Empty Categories</span>
                            <span class="fw-bold text-warning"><?= $empty_count; ?></span>
                        </div>
                    </div>
                </div>

                <!-- Quick Tips -->
                <div class="card border-0 shadow-sm bg-primary bg-gradient text-white">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3"><i class="fas fa-lightbulb me-2"></i>Quick Tips</h5>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2"><i class="fas fa-check-circle me-2"></i>Keep category names clear and concise</li>
                            <li class="mb-2"><i class="fas fa-check-circle me-2"></i>Use descriptive category descriptions</li>
                            <li><i class="fas fa-check-circle me-2"></i>Regularly review empty categories</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0">
            <form method="post" action="">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Add New Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Category Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_category" class="btn btn-primary">Add Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0">
            <form method="post" action="">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Edit Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="category_id" id="edit_category_id">
                    <div class="mb-3">
                        <label class="form-label">Category Name</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="edit_description" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="edit_category" class="btn btn-primary">Update Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Table Styles */
.table {
    margin-bottom: 0;
}

.table th {
    font-weight: 600;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.table td {
    font-size: 0.9rem;
}

/* Card Styles */
.card {
    border-radius: 15px;
    overflow: hidden;
}

/* Badge Styles */
.badge {
    font-weight: 500;
    padding: 0.5em 1em;
}

/* Button Styles */
.btn-primary {
    background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
    border: none;
    padding: 0.75rem 1.5rem;
    font-weight: 500;
}

.btn-light {
    background: #f8f9fa;
    border: 1px solid #eee;
}

/* Modal Styles */
.modal-content {
    border-radius: 15px;
}

.modal-header, .modal-footer {
    padding: 1.25rem 1.5rem;
}

.modal-body {
    padding: 1.5rem;
}

/* Form Controls */
.form-control {
    padding: 0.75rem 1rem;
    border-radius: 8px;
    border: 1px solid #eee;
}

.form-control:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
}

/* Category Icon */
.category-icon {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(67, 97, 238, 0.1);
    border-radius: 8px;
}

/* Quick Tips Card */
.bg-primary.bg-gradient {
    background: linear-gradient(45deg, var(--primary-color), var(--accent-color)) !important;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .container-fluid {
        padding: 1rem !important;
    }
    
    .card {
        margin-bottom: 1rem;
    }
}
</style>

<script>
// Show edit modal if edit parameter is in URL
<?php if($category_data): ?>
    document.addEventListener('DOMContentLoaded', function() {
        const editModal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
        document.getElementById('edit_category_id').value = '<?= $category_data['category_id'] ?>';
        document.getElementById('edit_name').value = '<?= $category_data['name'] ?>';
        document.getElementById('edit_description').value = '<?= $category_data['description'] ?>';
        editModal.show();
    });
<?php endif; ?>
</script>

</body>
</html>