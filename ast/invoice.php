<?php
include 'components/connect.php';
require_once('tcpdf/tcpdf.php');

session_start();

if(!isset($_SESSION['user_id'])) {
    die('Unauthorized access');
}

if(!isset($_GET['order_id'])) {
    die('No order specified');
}

$user_id = $_SESSION['user_id'];
$order_id = $_GET['order_id'];

$select_order = $conn->prepare("SELECT * FROM `orders` WHERE id = ? AND user_id = ?");
$select_order->execute([$order_id, $user_id]);

if($select_order->rowCount() == 0) {
    die('Order not found');
}

$order = $select_order->fetch(PDO::FETCH_ASSOC);

class PremiumInvoicePDF extends TCPDF {
    protected $companyInfo = [
        'name' => 'keens.lk',
        'tagline' => 'Galaxy of Technology',
        'address' => '47 Keens Systems, Malimbada, Palatuwa, Matara LK',
        'phone' => '(+94) 71 2494 975',
        'email' => 'support@keens.lk',
        'website' => 'www.keens.lk'
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
        $this->Cell(0, 10, 'Thank you for your business!', 0, 1, 'C');
        
        // Page number
        $this->SetY(-20);
        $this->Cell(0, 10, 'Page ' . $this->getAliasNumPage() . ' of ' . $this->getAliasNbPages(), 0, 0, 'C');
    }

    public function PrintInvoiceDetails($order) {
        // Invoice Title
        $this->SetY(70);
        $this->SetFont('helvetica', 'B', 24);
        $this->SetTextColor(43, 52, 82);
        $this->Cell(0, 15, 'INVOICE', 0, 1, 'R');
        
        // Invoice Info Box
        $this->SetY(90);
        $this->SetFillColor(247, 248, 250);
        $this->Rect(15, 90, 180, 30, 'F');
        
        // Invoice details
        $this->SetFont('helvetica', 'B', 10);
        $this->SetTextColor(43, 52, 82);
        $this->SetXY(20, 95);
        
        // Invoice number and date
        $this->Cell(30, 6, 'Invoice No:', 0, 0);
        $this->SetFont('helvetica', '', 10);
        $this->Cell(60, 6, '#' . sprintf('%06d', $order['id']), 0, 1);
        
        $this->SetX(20);
        $this->SetFont('helvetica', 'B', 10);
        $this->Cell(30, 6, 'Date:', 0, 0);
        $this->SetFont('helvetica', '', 10);
        $this->Cell(60, 6, date('d M Y', strtotime($order['placed_on'])), 0, 1);
        
        // Payment Status
        $this->SetXY(120, 95);
        $this->SetFont('helvetica', 'B', 10);
        $this->Cell(30, 6, 'Status:', 0, 0);
        
        // Status with color
        $this->SetFont('helvetica', '', 10);
        if($order['payment_status'] == 'completed') {
            $this->SetTextColor(40, 167, 69); // Green for completed
        } else {
            $this->SetTextColor(220, 53, 69); // Red for pending
        }
        $this->Cell(40, 6, ucfirst($order['payment_status']), 0, 1);
        
        // Reset text color
        $this->SetTextColor(43, 52, 82);
        
        // Bill To Section
        $this->SetY(130);
        $this->SetFillColor(247, 248, 250);
        $this->Rect(15, 130, 180, 40, 'F');
        
        $this->SetXY(20, 135);
        $this->SetFont('helvetica', 'B', 12);
        $this->Cell(0, 8, 'Bill To:', 0, 1);
        
        $this->SetX(20);
        $this->SetFont('helvetica', '', 10);
        $this->MultiCell(170, 6, 
            $order['name'] . "\n" .
            $order['email'] . "\n" .
            $order['number'] . "\n" .
            $order['address'], 
            0, 'L');
        
        // Products Table
        $this->SetY(180);
        
        // Table Header
        $this->SetFillColor(247, 248, 250);
        $this->SetFont('helvetica', 'B', 10);
        $this->Cell(90, 10, 'Product', 1, 0, 'L', true);
        $this->Cell(30, 10, 'Quantity', 1, 0, 'C', true);
        $this->Cell(60, 10, 'Price', 1, 1, 'R', true);
        
        // Table Content
        $this->SetFont('helvetica', '', 10);
        $products = explode(',', $order['total_products']);
        $pricePerItem = $order['total_price'] / count($products);
        
        $fill = false;
        foreach ($products as $product) {
            $this->Cell(90, 10, trim($product), 1, 0, 'L', $fill);
            $this->Cell(30, 10, '1', 1, 0, 'C', $fill);
            $this->Cell(60, 10, '$' . number_format($pricePerItem, 2), 1, 1, 'R', $fill);
            $fill = !$fill;
        }
        
        // Total
        $this->Ln(5);
        $this->SetFont('helvetica', 'B', 12);
        $this->Cell(120, 10, '', 0, 0);
        $this->Cell(30, 10, 'Total:', 0, 0, 'R');
        $this->SetTextColor(79, 70, 229);
        $this->Cell(30, 10, '$' . number_format($order['total_price'], 2), 0, 1, 'R');
    }
}

// Create PDF document
$pdf = new PremiumInvoicePDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('KEENS');
$pdf->SetAuthor('KEENS');
$pdf->SetTitle('Invoice #' . sprintf('%06d', $order['id']));

// Set margins
$pdf->SetMargins(15, 70, 15);
$pdf->SetHeaderMargin(0);
$pdf->SetFooterMargin(0);

// Set auto page breaks
$pdf->SetAutoPageBreak(TRUE, 40);

// Add a page
$pdf->AddPage();

// Print invoice details
$pdf->PrintInvoiceDetails($order);

// Output PDF
$pdf->Output('invoice_' . sprintf('%06d', $order['id']) . '.pdf', 'I');
?>