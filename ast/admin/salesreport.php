<?php
include '../components/connect.php';
require_once('../tcpdf/tcpdf.php');

session_start();

$admin_id = $_SESSION['admin_id'];

if(!isset($admin_id)){
   header('location:index.php');
}

class PremiumSalesReportPDF extends TCPDF {
    protected $companyInfo = [
        'name' => 'Keens',
        'tagline' => 'Sales Performance Report',
        'address' => '123 Main Street, Colombo, Sri Lanka',
        'phone' => '075 897 3807',
        'email' => 'support@keens.lk',
        'website' => 'www.keens.lk'
    ];

    public function Header() {
        // Header background
        $this->Rect(0, 0, $this->getPageWidth(), 60, 'F', array(), array(252, 253, 255));
        $this->Rect(0, 60, $this->getPageWidth(), 2, 'F', array(), array(79, 70, 229));
        
        // Company name and title
        $this->SetY(15);
        $this->SetX(15);
        $this->SetFont('helvetica', 'B', 28);
        $this->SetTextColor(43, 52, 82);
        $this->Cell(100, 12, $this->companyInfo['name'], 0, 1, 'L');
        
        // Report title
        $this->SetFont('helvetica', 'B', 14);
        $this->SetTextColor(79, 70, 229);
        $this->SetX(15);
        $this->Cell(100, 8, 'Completed Sales Report', 0, 1, 'L');
        
        // Date and time
        $this->SetFont('helvetica', '', 10);
        $this->SetTextColor(108, 117, 125);
        $this->SetX(15);
        $this->Cell(100, 8, 'Generated: ' . date('d M Y, H:i'), 0, 1, 'L');
        
        // Company details on right
        $this->SetFont('helvetica', '', 9);
        $this->SetTextColor(108, 117, 125);
        $this->SetXY($this->getPageWidth() - 80, 15);
        $this->MultiCell(65, 5, 
            $this->companyInfo['address'] . "\n" .
            "Tel: " . $this->companyInfo['phone'] . "\n" .
            "Email: " . $this->companyInfo['email'],
            0, 'R');
    }

    public function Footer() {
        $this->SetY(-30);
        
        // Separator line
        $this->SetDrawColor(200, 200, 200);
        $this->Line(15, $this->GetY(), $this->getPageWidth()-15, $this->GetY());
        
        // Footer text
        $this->SetY(-25);
        $this->SetFont('helvetica', '', 8);
        $this->SetTextColor(108, 117, 125);
        $this->Cell(0, 10, 'Confidential Business Report', 0, 1, 'C');
        
        // Page number
        $this->SetY(-20);
        $this->Cell(0, 10, 'Page ' . $this->getAliasNumPage() . ' of ' . $this->getAliasNbPages(), 0, 0, 'C');
    }

    public function SummaryBox($total_orders, $total_sales) {
        // Summary box with gradient background
        $this->SetFillColor(247, 248, 250);
        $this->Rect(15, 75, 180, 30, 'F');
        
        $this->SetY(80);
        $this->SetFont('helvetica', 'B', 12);
        $this->SetTextColor(43, 52, 82);
        $this->Cell(90, 8, 'Total Orders', 0, 0, 'C');
        $this->Cell(90, 8, 'Total Revenue', 0, 1, 'C');
        
        $this->SetFont('helvetica', 'B', 14);
        $this->SetTextColor(79, 70, 229);
        $this->Cell(90, 12, $total_orders, 0, 0, 'C');
        $this->Cell(90, 12, '$' . number_format($total_sales, 2), 0, 1, 'C');
    }

    public function OrderDetails($order, $y_position) {
        // Order box background
        $this->SetFillColor(252, 253, 255);
        $this->Rect(15, $y_position, 180, 45, 'F');
        
        // Order header
        $this->SetY($y_position + 5);
        $this->SetX(20);
        $this->SetFont('helvetica', 'B', 11);
        $this->SetTextColor(43, 52, 82);
        $this->Cell(100, 8, 'Order #' . sprintf('%06d', $order['id']), 0, 0);
        $this->SetFont('helvetica', '', 10);
        $this->SetTextColor(108, 117, 125);
        $this->Cell(75, 8, date('d M Y', strtotime($order['placed_on'])), 0, 1, 'R');
        
        // Customer info
        $this->SetX(20);
        $this->SetFont('helvetica', '', 9);
        $this->Cell(90, 6, $order['name'] . ' | ' . $order['number'], 0, 1);
        $this->SetX(20);
        $this->Cell(90, 6, $order['email'], 0, 1);
        
        // Products
        $products = explode(',', $order['total_products']);
        $this->SetX(20);
        $this->SetFont('helvetica', 'B', 10);
        $this->SetTextColor(43, 52, 82);
        $this->Cell(90, 8, 'Products:', 0, 1);
        
        $this->SetFont('helvetica', '', 9);
        $this->SetTextColor(108, 117, 125);
        foreach($products as $product) {
            $this->SetX(25);
            $this->Cell(90, 5, '• ' . trim($product), 0, 1);
        }
        
        // Total
        $this->SetX(20);
        $this->SetFont('helvetica', 'B', 10);
        $this->SetTextColor(79, 70, 229);
        $this->Cell(155, 8, 'Total Amount:', 0, 0, 'R');
        $this->Cell(15, 8, '$' . number_format($order['total_price'], 2), 0, 1, 'R');
        
        // Return the height used
        return 45 + (count($products) * 5);
    }
}

// Fetch completed orders
$select_completed_orders = $conn->prepare("
    SELECT id, name, number, email, address, total_products, total_price, placed_on 
    FROM `orders` 
    WHERE payment_status = 'completed' 
    ORDER BY placed_on DESC
");
$select_completed_orders->execute();
$completed_orders = $select_completed_orders->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$total_sales = array_sum(array_column($completed_orders, 'total_price'));
$total_orders = count($completed_orders);

// Create PDF
$pdf = new PremiumSalesReportPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('Keens Admin Panel');
$pdf->SetAuthor('Keens');
$pdf->SetTitle('Completed Sales Report');

// Set margins
$pdf->SetMargins(15, 70, 15);
$pdf->SetHeaderMargin(0);
$pdf->SetFooterMargin(0);

// Set auto page breaks
$pdf->SetAutoPageBreak(TRUE, 40);

// Add first page
$pdf->AddPage();

// Add summary box
$pdf->SummaryBox($total_orders, $total_sales);

// Add order details
$y_position = 120;
foreach ($completed_orders as $order) {
    if ($y_position > $pdf->getPageHeight() - 60) {
        $pdf->AddPage();
        $y_position = 75;
    }
    
    $height_used = $pdf->OrderDetails($order, $y_position);
    $y_position += $height_used + 15; // Add spacing between orders
}

// Output PDF
$pdf->Output('sales_report_' . date('Y-m-d') . '.pdf', 'I');
?>