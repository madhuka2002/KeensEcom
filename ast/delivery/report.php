<?php
include '../components/connect.php';
require_once('../tcpdf/tcpdf.php'); // Make sure TCPDF library is included

session_start();

// Check if delivery agent is logged in
if (!isset($_SESSION['delivery_agent_id'])) {
    header('location:index.php');
    exit();
}

$delivery_agent_id = $_SESSION['delivery_agent_id'];

// Fetch delivery history
$delivery_history = $conn->prepare("
    SELECT da.*, o.*, da.status as delivery_status,
    (SELECT name FROM users WHERE id = o.user_id) as customer_name,
    (SELECT name FROM delivery_agents WHERE id = da.delivery_agent_id) as agent_name
    FROM `delivery_assignments` da
    JOIN `orders` o ON da.order_id = o.id
    WHERE da.delivery_agent_id = ?
    AND da.status = 'delivered'
    ORDER BY da.delivered_at DESC
");
$delivery_history->execute([$delivery_agent_id]);

// Create new PDF document
class MYPDF extends TCPDF {
    // Page header
    public function Header() {
        // Set background color for header
        $this->SetFillColor(51, 102, 204); // Blue color
        $this->Rect(0, 0, $this->getPageWidth(), 40, 'F');
        
        // Logo
        $image_file = '../images/logo.png';
        if (file_exists($image_file)) {
            $this->Image($image_file, 10, 10, 30, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        }
        
        // Set font
        $this->SetFont('helvetica', 'B', 24);
        $this->SetTextColor(255, 255, 255); // White text
        // Title
        $this->Cell(0, 30, 'Keens - Delivery History', 0, false, 'C', 0, '', 0, false, 'M', 'M');
        
        // Reset text color for the rest of the document
        $this->SetTextColor(0, 0, 0);
    }

    // Page footer
    public function Footer() {
        // Blue footer line
        $this->SetDrawColor(51, 102, 204);
        $this->Line(10, $this->getPageHeight() - 20, $this->getPageWidth() - 10, $this->getPageHeight() - 20);
        
        // Position at 15 mm from bottom
        $this->SetY(-15);
        // Set font
        $this->SetFont('helvetica', 'I', 8);
        $this->SetTextColor(51, 102, 204); // Blue text
        // Page number
        $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
        
        // Date and time
        $this->Cell(0, 10, 'Generated on: '.date('Y-m-d H:i:s'), 0, false, 'R', 0, '', 0, false, 'T', 'M');
        
        // Reset text color
        $this->SetTextColor(0, 0, 0);
    }
}

// Create new PDF document
$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('Keens Delivery System');
$pdf->SetAuthor('Keens');
$pdf->SetTitle('Delivery History');
$pdf->SetSubject('Delivery Agent History Report');
$pdf->SetKeywords('Keens, PDF, delivery, history, report');

// Set default header data
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);

// Set header and footer fonts
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// Set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// Set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// Set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// Set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// Add a page
$pdf->AddPage();

// Fetch delivery agent's information
$agent_info = $conn->prepare("SELECT * FROM delivery_agents WHERE id = ?");
$agent_info->execute([$delivery_agent_id]);
$agent = $agent_info->fetch(PDO::FETCH_ASSOC);

// Agent Info section with blue styling
$pdf->SetFont('helvetica', 'B', 16);
$pdf->SetTextColor(51, 102, 204); // Blue text for section headers
$pdf->Cell(0, 10, 'Delivery Agent Information', 0, 1, 'L');
$pdf->SetTextColor(0, 0, 0); // Reset to black text

// Blue box for agent info
$pdf->SetFillColor(240, 248, 255); // Light blue background
$pdf->SetDrawColor(51, 102, 204); // Blue border
$pdf->Rect(15, $pdf->GetY(), 180, 30, 'FD');

$pdf->SetFont('helvetica', 'B', 11);
$pdf->SetY($pdf->GetY() + 5);
$pdf->Cell(40, 7, 'Agent ID:', 0, 0, 'R');
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(60, 7, '#'.$agent['id'], 0, 1, 'L');

$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(40, 7, 'Name:', 0, 0, 'R');
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(60, 7, $agent['name'], 0, 1, 'L');

// Add some space
$pdf->Ln(15);

// Delivery History section
$pdf->SetFont('helvetica', 'B', 16);
$pdf->SetTextColor(51, 102, 204); // Blue text for section headers
$pdf->Cell(0, 10, 'Delivery History', 0, 1, 'L');
$pdf->SetTextColor(0, 0, 0); // Reset to black text

// Table header
$pdf->SetFillColor(51, 102, 204); // Blue header
$pdf->SetTextColor(255, 255, 255); // White text
$pdf->SetFont('helvetica', 'B', 10);

// Set the width of each column - made products column wider
$col1Width = 20;  // Order ID
$col2Width = 35;  // Customer
$col3Width = 40;  // Products - increased width for better visibility
$col4Width = 25;  // Total Price
$col5Width = 35;  // Pickup Date
$col6Width = 35;  // Delivery Date
$tableWidth = $col1Width + $col2Width + $col3Width + $col4Width + $col5Width + $col6Width;

$pdf->Cell($col1Width, 8, 'Order ID', 1, 0, 'C', 1);
$pdf->Cell($col2Width, 8, 'Customer', 1, 0, 'C', 1);
$pdf->Cell($col3Width, 8, 'Products', 1, 0, 'C', 1);
$pdf->Cell($col4Width, 8, 'Total Price', 1, 0, 'C', 1);
$pdf->Cell($col5Width, 8, 'Pickup Date', 1, 0, 'C', 1);
$pdf->Cell($col6Width, 8, 'Delivery Date', 1, 1, 'C', 1);

// Reset text color to black for table data
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('helvetica', '', 9);
$pdf->SetFillColor(240, 248, 255); // Light blue for alternating rows
$fill = false;

if($delivery_history->rowCount() > 0) {
    while($delivery = $delivery_history->fetch(PDO::FETCH_ASSOC)) {
        // Calculate row height based on content
        $productsText = $delivery['total_products'];
        // Use MultiCell to calculate height needed
        $productsLineCount = $pdf->getNumLines($productsText, $col3Width);
        $rowHeight = max(7, $productsLineCount * 5); // Minimum 7mm height, or more if needed
        
        // Use Cell for fixed columns
        $startY = $pdf->GetY();
        $pdf->Cell($col1Width, $rowHeight, '#'.$delivery['id'], 1, 0, 'C', $fill);
        
        // Customer name may need wrapping too
        $customerName = $delivery['customer_name'] ?? 'Customer #'.$delivery['user_id'];
        $pdf->Cell($col2Width, $rowHeight, $customerName, 1, 0, 'L', $fill);
        
        // Use MultiCell for products to allow wrapping
        $currentX = $pdf->GetX();
        $currentY = $pdf->GetY();
        $pdf->MultiCell($col3Width, $rowHeight, $productsText, 1, 'L', $fill, 0);
        $pdf->SetXY($currentX + $col3Width, $currentY);
        
        // Continue with other fixed columns
        $pdf->Cell($col4Width, $rowHeight, '$'.number_format($delivery['total_price'], 2), 1, 0, 'R', $fill);
        $pdf->Cell($col5Width, $rowHeight, date('d M Y H:i', strtotime($delivery['picked_up_at'])), 1, 0, 'C', $fill);
        $pdf->Cell($col6Width, $rowHeight, date('d M Y H:i', strtotime($delivery['delivered_at'])), 1, 1, 'C', $fill);
        
        $fill = !$fill; // Alternate row colors
    }
} else {
    $pdf->SetFont('helvetica', 'I', 10);
    $pdf->Cell($tableWidth, 10, 'No delivery history available', 1, 1, 'C');
}

// Statistics section
$pdf->Ln(15);

// Fetch statistics
$stats = $conn->prepare("
    SELECT 
        COUNT(*) as total_deliveries,
        SUM(o.total_price) as total_sales,
        MIN(da.delivered_at) as first_delivery,
        MAX(da.delivered_at) as last_delivery,
        AVG(TIMESTAMPDIFF(MINUTE, da.picked_up_at, da.delivered_at)) as avg_delivery_time
    FROM `delivery_assignments` da
    JOIN `orders` o ON da.order_id = o.id
    WHERE da.delivery_agent_id = ?
    AND da.status = 'delivered'
");
$stats->execute([$delivery_agent_id]);
$stat_data = $stats->fetch(PDO::FETCH_ASSOC);

// Display statistics with blue styling
$pdf->SetFont('helvetica', 'B', 16);
$pdf->SetTextColor(51, 102, 204); // Blue section header
$pdf->Cell(0, 10, 'Delivery Statistics', 0, 1, 'L');
$pdf->SetTextColor(0, 0, 0); // Reset to black text

// Create a table for statistics with blue styling
$pdf->SetDrawColor(51, 102, 204); // Blue border
$pdf->SetFillColor(240, 248, 255); // Light blue background

$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(90, 8, 'Total Completed Deliveries:', 1, 0, 'L', 1);
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(90, 8, $stat_data['total_deliveries'], 1, 1, 'L', 0);

$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(90, 8, 'Total Sales Amount:', 1, 0, 'L', 1);
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(90, 8, '$'.number_format($stat_data['total_sales'], 2), 1, 1, 'L', 0);

$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(90, 8, 'First Delivery Date:', 1, 0, 'L', 1);
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(90, 8, date('d M Y', strtotime($stat_data['first_delivery'])), 1, 1, 'L', 0);

$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(90, 8, 'Latest Delivery Date:', 1, 0, 'L', 1);
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(90, 8, date('d M Y', strtotime($stat_data['last_delivery'])), 1, 1, 'L', 0);

$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(90, 8, 'Average Delivery Time:', 1, 0, 'L', 1);
$pdf->SetFont('helvetica', '', 11);
$avg_time = round($stat_data['avg_delivery_time']);
$hours = floor($avg_time / 60);
$minutes = $avg_time % 60;
$pdf->Cell(90, 8, $hours.' hours '.$minutes.' minutes', 1, 1, 'L', 0);

// Add a blue summary box at the bottom
$pdf->Ln(10);
$pdf->SetFillColor(51, 102, 204);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Thank you for being a valued delivery partner with Keens!', 0, 1, 'C', 1);

// Output the PDF
$pdf->Output('delivery_history_'.date('Ymd').'.pdf', 'I'); // 'I' sends to browser
?>