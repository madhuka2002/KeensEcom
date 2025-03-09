<?php
include '../components/connect.php';

session_start();

if(!isset($_SESSION['csr_id'])) {
   header('location:index.php');
   exit();
}

$csr_id = $_SESSION['csr_id'];

// Handle search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? $_GET['category'] : 'all';

// Fetch categories
$categories = $conn->prepare("
    SELECT DISTINCT category 
    FROM knowledge_base 
    ORDER BY category
");
$categories->execute();

// Fetch articles
$query = "
    SELECT 
        kb.*,
        csr.name as author_name
    FROM knowledge_base kb
    LEFT JOIN customer_sales_representatives csr ON kb.created_by = csr.csr_id
    WHERE 1=1
";

if ($search !== '') {
    $query .= " AND (kb.title LIKE :search OR kb.content LIKE :search)";
}

if ($category !== 'all') {
    $query .= " AND kb.category = :category";
}

$query .= " ORDER BY kb.created_at DESC";

$articles = $conn->prepare($query);

if ($search !== '') {
    $searchTerm = "%$search%";
    $articles->bindParam(':search', $searchTerm);
}

if ($category !== 'all') {
    $articles->bindParam(':category', $category);
}

$articles->execute();
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Keens | Knowledge Base</title>

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
</head>
<body>

<?php include '../components/csr_header.php'; ?>

<section class="knowledge-base-section mt-5">
    <div class="container">
        <div class="content-card">
            <div class="card-header d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="header-title mb-0">Knowledge Base</h2>
                    <p class="text-muted mb-0">Search through support articles and guides</p>
                </div>
                <a href="new_article.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>New Article
                </a>
            </div>

            <!-- Search and Filter -->
            <div class="search-section mb-4">
                <form action="" method="GET" class="row g-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="text" 
                                   name="search" 
                                   class="form-control" 
                                   placeholder="Search articles..."
                                   value="<?= htmlspecialchars($search) ?>">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <select name="category" class="form-select" onchange="this.form.submit()">
                            <option value="all">All Categories</option>
                            <?php while($cat = $categories->fetch(PDO::FETCH_COLUMN)): ?>
                                <option value="<?= htmlspecialchars($cat) ?>" 
                                        <?= $category == $cat ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </form>
            </div>

            <!-- Articles Grid -->
            <div class="row g-4">
                <?php if($articles->rowCount() > 0): ?>
                    <?php while($article = $articles->fetch(PDO::FETCH_ASSOC)): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="article-card">
                                <div class="article-category">
                                    <?= htmlspecialchars($article['category']) ?>
                                </div>
                                <h3 class="article-title">
                                    <?= htmlspecialchars($article['title']) ?>
                                </h3>
                                <div class="article-preview">
                                    <?= htmlspecialchars(substr($article['content'], 0, 100)) ?>...
                                </div>
                                <div class="article-meta">
                                    <div class="meta-item">
                                        <i class="fas fa-user me-1"></i>
                                        <?= htmlspecialchars($article['author_name']) ?>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-calendar me-1"></i>
                                        <?= date('M d, Y', strtotime($article['created_at'])) ?>
                                    </div>
                                </div>
                                <div class="article-actions">
                                    <a href="view_article.php?id=<?= $article['id'] ?>" class="btn btn-primary btn-sm">
                                        Read More
                                    </a>
                                    <?php if($article['created_by'] == $csr_id): ?>
                                        <a href="edit_article.php?id=<?= $article['id'] ?>" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="empty-state text-center py-5">
                            <i class="fas fa-book fa-3x text-muted mb-3"></i>
                            <h4>No Articles Found</h4>
                            <p class="text-muted">
                                <?= $search ? 'Try different search terms or categories' : 'Start by adding some articles to the knowledge base' ?>
                            </p>
                            <a href="new_article.php" class="btn btn-primary mt-3">
                                <i class="fas fa-plus me-2"></i>Add Article
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<style>
:root {
    --primary-color: #4361ee;
    --secondary-color: #3f37c9;
    --accent-color: #4895ef;
}

.content-card {
    background: white;
    border-radius: 20px;
    padding: 2rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.05);
}

.article-card {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    height: 100%;
    display: flex;
    flex-direction: column;
    transition: transform 0.2s ease;
}

.article-card:hover {
    transform: translateY(-5px);
}

.article-category {
    display: inline-block;
    background: rgba(67, 97, 238, 0.1);
    color: var(--primary-color);
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.875rem;
    margin-bottom: 1rem;
}

.article-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 1rem;
    color: #2b3452;
}

.article-preview {
    color: #6c757d;
    font-size: 0.875rem;
    margin-bottom: 1rem;
    flex-grow: 1;
}

.article-meta {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
    font-size: 0.875rem;
    color: #6c757d;
}

.article-actions {
    display: flex;
    gap: 0.5rem;
    margin-top: auto;
}

.empty-state {
    background: #f8f9fa;
    border-radius: 15px;
}

@media (max-width: 768px) {
    .content-card {
        padding: 1rem;
    }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>