<?php
include '../components/connect.php';
require_once('../tcpdf/tcpdf.php');

session_start();

if(!isset($_SESSION['manager_id'])) {
    die('Unauthorized access');
}

// Get total revenue
$total_revenue = $conn->prepare("SELECT SUM(total_price) as total FROM `orders` WHERE payment_status = 'completed'");
$total_revenue->execute();
$revenue = $total_revenue->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Get total orders
$total_orders = $conn->prepare("SELECT COUNT(*) as count FROM `orders`");
$total_orders->execute();
$orders_count = $total_orders->fetch(PDO::FETCH_ASSOC)['count'];

// Get total products
$total_products = $conn->prepare("SELECT COUNT(*) as count FROM `products`");
$total_products->execute();
$products_count = $total_products->fetch(PDO::FETCH_ASSOC)['count'];

// Get average rating
$avg_rating = $conn->prepare("SELECT AVG(rating) as avg FROM `reviews`");
$avg_rating->execute();
$rating = $avg_rating->fetch(PDO::FETCH_ASSOC)['avg'] ?? 0;

// Get top products
$top_products = $conn->prepare("
    SELECT p.name, p.price, COUNT(o.id) as order_count 
    FROM products p 
    LEFT JOIN orders o ON FIND_IN_SET(p.id, o.total_products) 
    GROUP BY p.id 
    ORDER BY order_count DESC 
    LIMIT 5
");
$top_products->execute();
$top_products_data = $top_products->fetchAll(PDO::FETCH_ASSOC);

// Get recent reviews
$recent_reviews = $conn->prepare("
    SELECT r.*, p.name as product_name 
    FROM reviews r 
    JOIN products p ON r.product_id = p.id 
    ORDER BY r.created_at DESC 
    LIMIT 5
");
$recent_reviews->execute();
$recent_reviews_data = $recent_reviews->fetchAll(PDO::FETCH_ASSOC);

// Get sales data for the past 7 days
$sales_data = $conn->prepare("
    SELECT DATE_FORMAT(placed_on, '%a') as day, 
           SUM(total_price) as daily_sales
    FROM orders
    WHERE placed_on >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE_FORMAT(placed_on, '%Y-%m-%d')
    ORDER BY placed_on
");
$sales_data->execute();
$daily_sales = $sales_data->fetchAll(PDO::FETCH_ASSOC);

class AnalyticsReportPDF extends TCPDF {
    protected $companyInfo = [
        'name' => 'Keens',
        'tagline' => 'Galaxy of Technology',
        'address' => '143, Colombo 03, Sri Lanka',
        'phone' => '(+1) 555-123-4567',
        'email' => 'support@keens.ls',
        'website' => 'www.keens.ls'
    ];

    public function Header() {
        // Add background color for header
        $this->Rect(0, 0, $this->getPageWidth(), 60, 'F', array(), array(252, 253, 255));
        
        // Add blue line under header
        $this->Rect(0, 60, $this->getPageWidth(), 2, 'F', array(), array(79, 70, 229));
        
        // Company name and info (left side)
        $this->SetY(15);
        $this->SetX(15);
        $this->SetFont('helvetica', 'B', 28);
        $this->SetTextColor(43, 52, 82);
        $this->Cell(100, 12, $this->companyInfo['name'], 0, 1, 'L');
        
        // Tagline
        $this->SetFont('helvetica', 'b', 12);
        $this->SetTextColor(79, 70, 229);
        $this->SetX(15);
        $this->Cell(100, 8, $this->companyInfo['tagline'], 0, 1, 'L');
        
        // Company details (right side)
        $this->SetFont('helvetica', '', 9);
        $this->SetTextColor(108, 117, 125);
        $this->SetXY($this->getPageWidth() - 80, 15);
        $this->MultiCell(65, 5, 
            $this->companyInfo['address'] . "\n" .
            "Tel: " . $this->companyInfo['phone'] . "\n" .
            "Email: " . $this->companyInfo['email'] . "\n" .
            "Web: " . $this->companyInfo['website'],
            0, 'R');
    }

    public function Footer() {
        $this->SetY(-30);
        
        // Add separator line
        $this->SetDrawColor(200, 200, 200);
        $this->Line(15, $this->GetY(), $this->getPageWidth()-15, $this->GetY());
        
        // Footer text
        $this->SetY(-25);
        $this->SetFont('helvetica', '', 8);
        $this->SetTextColor(108, 117, 125);
        $this->Cell(0, 10, 'Confidential - For internal use only', 0, 1, 'C');
        
        // Page number
        $this->SetY(-20);
        $this->Cell(0, 10, 'Page ' . $this->getAliasNumPage() . ' of ' . $this->getAliasNbPages(), 0, 0, 'C');
    }

    public function PrintAnalyticsReport($revenue, $orders_count, $products_count, $rating, $top_products, $recent_reviews, $daily_sales) {
        // Report Title
        $this->SetY(70);
        $this->SetFont('helvetica', 'B', 24);
        $this->SetTextColor(43, 52, 82);
        $this->Cell(0, 15, 'ANALYTICS REPORT', 0, 1, 'R');
        
        // Date Info Box
        $this->SetY(90);
        $this->SetFillColor(247, 248, 250);
        $this->Rect(15, 90, 180, 20, 'F');
        
        // Report details
        $this->SetFont('helvetica', 'B', 10);
        $this->SetTextColor(43, 52, 82);
        $this->SetXY(20, 95);
        
        // Report period and date
        $this->Cell(40, 6, 'Report Period:', 0, 0);
        $this->SetFont('helvetica', '', 10);
        $this->Cell(60, 6, 'Last 30 Days', 0, 0);
        
        $this->SetFont('helvetica', 'B', 10);
        $this->Cell(20, 6, 'Date:', 0, 0);
        $this->SetFont('helvetica', '', 10);
        $this->Cell(60, 6, date('d M Y'), 0, 1);
        
        // KPI Section Title
        $this->SetY(120);
        $this->SetFont('helvetica', 'B', 16);
        $this->SetTextColor(43, 52, 82);
        $this->Cell(0, 10, 'Key Performance Indicators', 0, 1, 'L');
        
        // KPI Cards
        $this->SetY(135);
        $this->SetDrawColor(240, 240, 240);
        
        // Row 1: Revenue and Orders
        $kpi_width = 87;
        $kpi_height = 40;
        $gap = 6;
        
        // Revenue KPI
        $this->SetFillColor(247, 248, 250);
        $this->RoundedRect(15, 135, $kpi_width, $kpi_height, 5, '1111', 'F');
        
        $this->SetXY(20, 140);
        $this->SetFont('helvetica', 'B', 10);
        $this->SetTextColor(79, 70, 229);
        $this->Cell(40, 6, 'TOTAL REVENUE', 0, 1);
        
        $this->SetXY(20, 148);
        $this->SetFont('helvetica', 'B', 18);
        $this->SetTextColor(43, 52, 82);
        $this->Cell(40, 10, '$' . number_format($revenue, 2), 0, 1);
        
        $this->SetXY(20, 160);
        $this->SetFont('helvetica', '', 8);
        $this->SetTextColor(40, 167, 69);
        $this->Cell(40, 6, '↑ 8.5% vs previous period', 0, 1);
        
        // Orders KPI
        $this->SetFillColor(247, 248, 250);
        $this->RoundedRect(15 + $kpi_width + $gap, 135, $kpi_width, $kpi_height, 5, '1111', 'F');
        
        $this->SetXY(20 + $kpi_width + $gap, 140);
        $this->SetFont('helvetica', 'B', 10);
        $this->SetTextColor(240, 140, 0);
        $this->Cell(40, 6, 'TOTAL ORDERS', 0, 1);
        
        $this->SetXY(20 + $kpi_width + $gap, 148);
        $this->SetFont('helvetica', 'B', 18);
        $this->SetTextColor(43, 52, 82);
        $this->Cell(40, 10, number_format($orders_count), 0, 1);
        
        $this->SetXY(20 + $kpi_width + $gap, 160);
        $this->SetFont('helvetica', '', 8);
        $this->SetTextColor(40, 167, 69);
        $this->Cell(40, 6, '↑ 12.4% vs previous period', 0, 1);
        
        // Row 2: Products and Rating
        $row2_y = 135 + $kpi_height + $gap;
        
        // Products KPI
        $this->SetFillColor(247, 248, 250);
        $this->RoundedRect(15, $row2_y, $kpi_width, $kpi_height, 5, '1111', 'F');
        
        $this->SetXY(20, $row2_y + 5);
        $this->SetFont('helvetica', 'B', 10);
        $this->SetTextColor(23, 162, 184);
        $this->Cell(40, 6, 'ACTIVE PRODUCTS', 0, 1);
        
        $this->SetXY(20, $row2_y + 13);
        $this->SetFont('helvetica', 'B', 18);
        $this->SetTextColor(43, 52, 82);
        $this->Cell(40, 10, number_format($products_count), 0, 1);
        
        $this->SetXY(20, $row2_y + 25);
        $this->SetFont('helvetica', '', 8);
        $this->SetTextColor(220, 53, 69);
        $this->Cell(40, 6, '↓ 3.2% vs previous period', 0, 1);
        
        // Rating KPI
        $this->SetFillColor(247, 248, 250);
        $this->RoundedRect(15 + $kpi_width + $gap, $row2_y, $kpi_width, $kpi_height, 5, '1111', 'F');
        
        $this->SetXY(20 + $kpi_width + $gap, $row2_y + 5);
        $this->SetFont('helvetica', 'B', 10);
        $this->SetTextColor(40, 167, 69);
        $this->Cell(40, 6, 'AVERAGE RATING', 0, 1);
        
        $this->SetXY(20 + $kpi_width + $gap, $row2_y + 13);
        $this->SetFont('helvetica', 'B', 18);
        $this->SetTextColor(43, 52, 82);
        $this->Cell(40, 10, number_format($rating, 1) . ' / 5.0', 0, 1);
        
        $this->SetXY(20 + $kpi_width + $gap, $row2_y + 25);
        $this->SetFont('helvetica', '', 8);
        $this->SetTextColor(40, 167, 69);
        $this->Cell(40, 6, '↑ 1.8% vs previous period', 0, 1);
        
        $this->Cell(40, 6, ' ', 0, 1);
        $this->Cell(40, 6, ' ', 0, 1);
        $this->Cell(40, 6, ' ', 0, 1);
        $this->Cell(40, 6, ' ', 0, 1);
        $this->Cell(40, 6, ' ', 0, 1);
        $this->Cell(40, 6, ' ', 0, 1);
        $this->Cell(40, 6, ' ', 0, 1);
        $this->Cell(40, 6, ' ', 0, 1);
        
        // Top Products Table
        $table_y = $this->GetY();
        $this->SetFillColor(247, 248, 250);
        
        // Table Header
        $this->SetFont('helvetica', 'B', 10);
        $this->SetTextColor(43, 52, 82);
        $this->SetFillColor(79, 70, 229);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(90, 10, 'Product Name', 1, 0, 'L', true);
        $this->Cell(45, 10, 'Price', 1, 0, 'C', true);
        $this->Cell(45, 10, 'Orders', 1, 1, 'C', true);
        
        // Table Content
        $this->SetFont('helvetica', '', 10);
        $this->SetTextColor(43, 52, 82);
        $fillRow = false;
        
        foreach ($top_products as $product) {
            $this->SetFillColor(247, 248, 250);
            $this->Cell(90, 10, $product['name'], 1, 0, 'L', $fillRow);
            $this->Cell(45, 10, '$' . number_format($product['price'], 2), 1, 0, 'C', $fillRow);
            $this->Cell(45, 10, $product['order_count'], 1, 1, 'C', $fillRow);
            $fillRow = !$fillRow;
        }
        
        // Recent Reviews Section
        $this->AddPage();
        $this->SetY(70);
        $this->SetFont('helvetica', 'B', 16);
        $this->SetTextColor(43, 52, 82);
        $this->Cell(0, 10, 'Recent Customer Reviews', 0, 1, 'L');
        
        // Reviews Table
        $this->SetY(85);
        
        // Table Header
        $this->SetFont('helvetica', 'B', 10);
        $this->SetFillColor(79, 70, 229);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(50, 10, 'Product', 1, 0, 'L', true);
        $this->Cell(25, 10, 'Rating', 1, 0, 'C', true);
        $this->Cell(80, 10, 'Review', 1, 0, 'L', true);
        $this->Cell(25, 10, 'Date', 1, 1, 'C', true);
        
        // Table Content
        $this->SetFont('helvetica', '', 10);
        $this->SetTextColor(43, 52, 82);
        $fillRow = false;
        
        foreach ($recent_reviews as $review) {
            $this->SetFillColor(247, 248, 250);
            $this->Cell(50, 10, $review['product_name'], 1, 0, 'L', $fillRow);
            
            // Rating stars
            $this->Cell(25, 10, $review['rating'] . '/5', 1, 0, 'C', $fillRow);
            
            // Review text (truncated)
            $review_text = substr($review['review_text'], 0, 100);
            if (strlen($review['review_text']) > 100) {
                $review_text .= '...';
            }
            $this->Cell(80, 10, $review_text, 1, 0, 'L', $fillRow);
            
            // Date
            $this->Cell(25, 10, date('M d, Y', strtotime($review['created_at'])), 1, 1, 'C', $fillRow);
            $fillRow = !$fillRow;
        }
        
        // Insights and Recommendations
        $this->SetY($this->GetY() + 20);
        $this->SetFont('helvetica', 'B', 16);
        $this->SetTextColor(43, 52, 82);
        $this->Cell(0, 10, 'Insights & Recommendations', 0, 1, 'L');
        
        $this->SetFillColor(247, 248, 250);
        $this->Rect(15, $this->GetY(), 180, 60, 'F');
        
        $this->SetY($this->GetY() + 5);
        $this->SetX(20);
        $this->SetFont('helvetica', 'B', 12);
        $this->Cell(0, 10, 'Key Insights:', 0, 1);
        
        $this->SetX(25);
        $this->SetFont('helvetica', '', 10);
        $bullet_points = [
            'Revenue is trending upward with 8.5% growth compared to the previous period.',
            'Customer order volume has increased by 12.4%, indicating strong demand.',
            'Product catalog has decreased slightly by 3.2%, suggesting potential optimization opportunities.',
            'Customer satisfaction remains strong with average ratings of ' . number_format($rating, 1) . '/5.'
        ];
        
        foreach ($bullet_points as $point) {
            $this->SetX(25);
            $this->Cell(5, 6, '•', 0, 0);
            $this->MultiCell(165, 6, $point, 0, 'L');
        }
        
        $this->SetY($this->GetY() + 10);
        $this->SetX(20);
        $this->SetFont('helvetica', 'B', 12);
        $this->Cell(0, 10, 'Recommendations:', 0, 1);
        
        $this->SetX(25);
        $this->SetFont('helvetica', '', 10);
        $recommendations = [
            'Focus marketing efforts on top-performing products to maximize revenue growth.',
            'Consider expanding inventory of high-demand items to capitalize on increased order volume.',
            'Review underperforming products and consider product line optimization.',
        ];
        
        foreach ($recommendations as $rec) {
            $this->SetX(25);
            $this->Cell(5, 6, '•', 0, 0);
            $this->MultiCell(165, 6, $rec, 0, 'L');
        }
    }
    
    // Helper function to draw rounded rectangles


    protected function _Arc($x1, $y1, $x2, $y2, $x3, $y3) {
        $h = $this->h;
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c ', $x1*$this->k, ($h-$y1)*$this->k,
            $x2*$this->k, ($h-$y2)*$this->k, $x3*$this->k, ($h-$y3)*$this->k));
    }
}

// Create PDF document
$pdf = new AnalyticsReportPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('Keens');
$pdf->SetAuthor('Product Management Team');
$pdf->SetTitle('Product Analytics Report');
$pdf->SetSubject('Monthly Performance Analytics');
$pdf->SetKeywords('analytics, products, sales, performance');

// Set margins
$pdf->SetMargins(15, 70, 15);
$pdf->SetHeaderMargin(0);
$pdf->SetFooterMargin(0);

// Set auto page breaks
$pdf->SetAutoPageBreak(TRUE, 40);

// Add a page
$pdf->AddPage();

// Print report details
$pdf->PrintAnalyticsReport($revenue, $orders_count, $products_count, $rating, $top_products_data, $recent_reviews_data, $daily_sales);

// Output PDF
$pdf->Output('product_analytics_report.pdf', 'I');
?>