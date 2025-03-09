<?php
include '../components/connect.php';

session_start();

$manager_id = $_SESSION['manager_id'];

if(!isset($manager_id)){
   header('location:index.php');
}

// Handle review response
if(isset($_POST['add_response'])) {
    $review_id = $_POST['review_id'];
    $response = $_POST['response'];
    
    $update_review = $conn->prepare("UPDATE `reviews` SET manager_response = ?, response_date = CURRENT_TIMESTAMP WHERE id = ?");
    $update_review->execute([$response, $review_id]);
    
    $message[] = 'Response added successfully!';
}

// Get filter values
$rating_filter = isset($_GET['rating']) ? $_GET['rating'] : 'all';
$product_filter = isset($_GET['product']) ? $_GET['product'] : 'all';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Keens | Product Reviews</title>

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<?php include '../components/product_manager_header.php'; ?>

<section class="reviews">
    <div class="container-fluid px-4 py-5">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-0">Product Reviews</h2>
                <p class="text-muted mb-0">Manage and respond to customer feedback</p>
            </div>
        </div>

        <!-- Review Stats -->
        <div class="row g-4 mb-4">
            <!-- Total Reviews -->
            <?php
                $total_reviews = $conn->prepare("SELECT COUNT(*) as count FROM `reviews`");
                $total_reviews->execute();
                $total = $total_reviews->fetch(PDO::FETCH_ASSOC)['count'];

                $avg_rating = $conn->prepare("SELECT AVG(rating) as avg FROM `reviews`");
                $avg_rating->execute();
                $average = round($avg_rating->fetch(PDO::FETCH_ASSOC)['avg'], 1);

                $pending_responses = $conn->prepare("SELECT COUNT(*) as count FROM `reviews` WHERE manager_response IS NULL");
                $pending_responses->execute();
                $pending = $pending_responses->fetch(PDO::FETCH_ASSOC)['count'];
            ?>
            <div class="col-12 col-sm-6 col-xl-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="icon-wrapper bg-primary bg-opacity-10 text-primary">
                                <i class="fas fa-star"></i>
                            </div>
                            <div class="ms-3">
                                <h6 class="text-muted mb-0">Average Rating</h6>
                                <h3 class="mb-0"><?= $average ?> / 5</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Reviews -->
            <div class="col-12 col-sm-6 col-xl-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="icon-wrapper bg-info bg-opacity-10 text-info">
                                <i class="fas fa-comments"></i>
                            </div>
                            <div class="ms-3">
                                <h6 class="text-muted mb-0">Total Reviews</h6>
                                <h3 class="mb-0"><?= number_format($total) ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pending Responses -->
            <div class="col-12 col-sm-6 col-xl-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="icon-wrapper bg-warning bg-opacity-10 text-warning">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="ms-3">
                                <h6 class="text-muted mb-0">Pending Responses</h6>
                                <h3 class="mb-0"><?= number_format($pending) ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Filter by Rating</label>
                        <select name="rating" class="form-select" onchange="this.form.submit()">
                            <option value="all" <?= $rating_filter == 'all' ? 'selected' : '' ?>>All Ratings</option>
                            <?php for($i = 5; $i >= 1; $i--): ?>
                                <option value="<?= $i ?>" <?= $rating_filter == $i ? 'selected' : '' ?>>
                                    <?= $i ?> Stars
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Filter by Product</label>
                        <select name="product" class="form-select" onchange="this.form.submit()">
                            <option value="all" <?= $product_filter == 'all' ? 'selected' : '' ?>>All Products</option>
                            <?php
                                $select_products = $conn->prepare("SELECT DISTINCT p.id, p.name FROM products p JOIN reviews r ON p.id = r.product_id");
                                $select_products->execute();
                                while($product = $select_products->fetch(PDO::FETCH_ASSOC)):
                            ?>
                                <option value="<?= $product['id'] ?>" <?= $product_filter == $product['id'] ? 'selected' : '' ?>>
                                    <?= $product['name'] ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Sort by</label>
                        <select name="sort" class="form-select" onchange="this.form.submit()">
                            <option value="newest" <?= $sort == 'newest' ? 'selected' : '' ?>>Newest First</option>
                            <option value="oldest" <?= $sort == 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                            <option value="highest" <?= $sort == 'highest' ? 'selected' : '' ?>>Highest Rating</option>
                            <option value="lowest" <?= $sort == 'lowest' ? 'selected' : '' ?>>Lowest Rating</option>
                        </select>
                    </div>
                </form>
            </div>
        </div>

        <!-- Reviews List -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <?php
                    // Build query based on filters
                    $query = "SELECT r.*, p.name as product_name, p.image_01, u.name as user_name 
                             FROM `reviews` r 
                             JOIN `products` p ON r.product_id = p.id 
                             JOIN `users` u ON r.user_id = u.id";
                    $params = [];

                    if($rating_filter != 'all') {
                        $query .= " WHERE r.rating = ?";
                        $params[] = $rating_filter;
                    }

                    if($product_filter != 'all') {
                        $query .= empty($params) ? " WHERE" : " AND";
                        $query .= " p.id = ?";
                        $params[] = $product_filter;
                    }

                    // Add sorting
                    switch($sort) {
                        case 'oldest':
                            $query .= " ORDER BY r.created_at ASC";
                            break;
                        case 'highest':
                            $query .= " ORDER BY r.rating DESC";
                            break;
                        case 'lowest':
                            $query .= " ORDER BY r.rating ASC";
                            break;
                        default:
                            $query .= " ORDER BY r.created_at DESC";
                    }

                    $select_reviews = $conn->prepare($query);
                    $select_reviews->execute($params);

                    if($select_reviews->rowCount() > 0):
                        while($review = $select_reviews->fetch(PDO::FETCH_ASSOC)):
                ?>
                <div class="review-item border-bottom p-4">
                    <div class="d-flex">
                        <!-- Product Image -->
                        <img src="../uploaded_img/<?= $review['image_01'] ?>" 
                             alt="<?= $review['product_name'] ?>"
                             class="rounded"
                             style="width: 60px; height: 60px; object-fit: cover;">
                        
                        <!-- Review Content -->
                        <div class="ms-3 flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h6 class="mb-1"><?= $review['product_name'] ?></h6>
                                    <div class="rating text-warning mb-2">
                                        <?php for($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star<?= $i <= $review['rating'] ? '' : '-o' ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <small class="text-muted">
                                    <?= date('M d, Y', strtotime($review['created_at'])) ?>
                                </small>
                            </div>

                            <p class="mb-2"><?= $review['review_text'] ?></p>
                            
                            <div class="d-flex align-items-center">
                                <i class="fas fa-user-circle text-muted me-2"></i>
                                <span class="text-muted"><?= $review['user_name'] ?></span>
                            </div>

                            <!-- Manager Response -->
                            <?php if($review['manager_response']): ?>
                                <div class="manager-response bg-light rounded p-3 mt-3">
                                    <p class="mb-1"><strong>Our Response:</strong></p>
                                    <p class="mb-2"><?= $review['manager_response'] ?></p>
                                    <small class="text-muted">
                                        Responded on <?= date('M d, Y', strtotime($review['response_date'])) ?>
                                    </small>
                                </div>
                            <?php else: ?>
                                <button class="btn btn-light mt-3" data-bs-toggle="modal" 
                                        data-bs-target="#responseModal<?= $review['id'] ?>">
                                    <i class="fas fa-reply me-2"></i>Respond
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Response Modal -->
                <div class="modal fade" id="responseModal<?= $review['id'] ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content border-0">
                            <form method="post">
                                <div class="modal-header border-0">
                                    <h5 class="modal-title">Respond to Review</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
                                    <div class="mb-3">
                                        <label class="form-label">Your Response</label>
                                        <textarea name="response" class="form-control" rows="4" required></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer border-0">
                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" name="add_response" class="btn btn-primary">
                                        Submit Response
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php 
                        endwhile;
                    else:
                        echo '<div class="text-center py-5">No reviews found</div>';
                    endif;
                ?>
            </div>
        </div>
    </div>
</section>

<style>
:root {
    --primary-color: #4361ee;
    --secondary-color: #3f37c9;
    --accent-color: #4895ef;
    --success-color: #2ecc71;
    --warning-color: #f39c12;
    --danger-color: #dc3545;
}

/* Card Styles */
.card {
    border-radius: 15px;
}

/* Icon Wrapper */
.icon-wrapper {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
}

/* Form Controls */
.form-control, .form-select {
    padding: 0.75rem 1rem;
    border-radius: 8px;
    border: 1px solid #e9ecef;
}

.form-control:focus, .form-select:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
}

/* Button Styles */
.btn-primary {
    background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
    border: none;
    padding: 0.75rem 1.5rem;
    font-weight:font-weight: 500;
}

.btn-light {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
}

/* Rating Stars */
.rating {
    color: #ffc107;
}

.rating .fa-star-o {
    color: #dee2e6;
}

/* Manager Response */
.manager-response {
    border-left: 3px solid var(--primary-color);
}

/* Review Item */
.review-item:hover {
    background-color: #f8f9fa;
}

.review-item:last-child {
    border-bottom: none !important;
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

/* Responsive Adjustments */
@media (max-width: 768px) {
    .container-fluid {
        padding: 1rem !important;
    }

    .card-body {
        padding: 1rem;
    }

    .review-item {
        padding: 1rem !important;
    }

    .btn {
        width: 100%;
        margin-bottom: 0.5rem;
    }
}
</style>

<script>
// Initialize tooltips
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl)
});

// Form validation
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(e) {
        const response = this.querySelector('textarea[name="response"]');
        if(response && response.value.trim().length < 10) {
            e.preventDefault();
            alert('Response must be at least 10 characters long');
        }
    });
});

// Handle response submission loading state
document.querySelectorAll('button[name="add_response"]').forEach(button => {
    button.addEventListener('click', function() {
        const form = this.closest('form');
        if(form.checkValidity()) {
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Submitting...';
        }
    });
});

// Auto-resize textareas
document.querySelectorAll('textarea').forEach(textarea => {
    textarea.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight + 2) + 'px';
    });
});

// Filter form auto-submit
document.querySelectorAll('select[onchange]').forEach(select => {
    select.addEventListener('change', function() {
        this.form.submit();
    });
});

// Rating filter shortcut buttons
function setRating(rating) {
    document.querySelector('select[name="rating"]').value = rating;
    document.querySelector('form').submit();
}

// Export reviews
function exportReviews(format) {
    const filters = new URLSearchParams(window.location.search);
    window.location.href = `export_reviews.php?${filters.toString()}&format=${format}`;
}

// Loading indicator while filters apply
window.addEventListener('beforeunload', function() {
    if(document.activeElement.tagName === 'SELECT') {
        document.querySelector('.card-body').style.opacity = '0.5';
    }
});
</script>

</body>
</html>