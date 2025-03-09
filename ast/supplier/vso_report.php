<?php
// Include necessary files
require_once('../tcpdf/tcpdf.php');
include '../components/connect.php';

session_start();

if (!isset($_SESSION['supplier_id'])) {
    header('location:index.php');
    exit();
}

$supplier_id = $_SESSION['supplier_id'];

// Check if order ID is provided
if (!isset($_GET['id'])) {
    header('location:supply_orders.php');
    exit();
}

$order_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);

if (!$order_id) {
    header('location:supply_orders.php');
    exit();
}

// Fetch supply order details
$order_query = $conn->prepare("
    SELECT so.*, 
           COUNT(soi.supply_order_item_id) as total_items
    FROM supply_orders so
    LEFT JOIN supply_order_items soi ON so.supply_order_id = soi.supply_order_id
    WHERE so.supply_order_id = ? AND so.supplier_id = ?
");
$order_query->execute([$order_id, $supplier_id]);
$order = $order_query->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('location:supply_orders.php');
    exit();
}

// Fetch order items with product details
$items_query = $conn->prepare("
    SELECT 
        soi.*, 
        p.name as product_name
    FROM supply_order_items soi
    JOIN products p ON soi.product_id = p.id
    WHERE soi.supply_order_id = ?
");
$items_query->execute([$order_id]);
$order_items = $items_query->fetchAll(PDO::FETCH_ASSOC);

// Create new PDF document - use default TCPDF without custom header/footer
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Set document information
$pdf->SetCreator('Keens Supplier Portal');
$pdf->SetTitle('Supply Order #'.$order['supply_order_id']);

// Set margins
$pdf->SetMargins(15, 15, 15);

// Set auto page breaks
$pdf->SetAutoPageBreak(TRUE, 15);

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', 'B', 16);

// Company and Document Title
$pdf->Cell(0, 10, 'Keens - Supply Order', 0, 1, 'C');
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'Order #'.$order['supply_order_id'], 0, 1, 'C');

// Status
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Status: ' . ucfirst($order['status']), 0, 1, 'C');

// Add a line
$pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
$pdf->Ln(5);

// Order Details
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Order Details:', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 10);

// Simple two-column layout for details
$pdf->Cell(40, 8, 'Order Date:', 0, 0);
$pdf->Cell(0, 8, date('d M Y H:i', strtotime($order['order_date'])), 0, 1);

$pdf->Cell(40, 8, 'Expected Delivery:', 0, 0);
$pdf->Cell(0, 8, $order['expected_delivery'] 
    ? date('d M Y', strtotime($order['expected_delivery'])) 
    : 'Not specified', 0, 1);

$pdf->Cell(40, 8, 'Total Items:', 0, 0);
$pdf->Cell(0, 8, $order['total_items'] . ' Product(s)', 0, 1);

$pdf->Cell(40, 8, 'Total Amount:', 0, 0);
$pdf->Cell(0, 8, '$'.number_format($order['total_amount'], 2), 0, 1);

// Notes if any
if (!empty($order['notes'])) {
    $pdf->Ln(3);
    $pdf->Cell(40, 8, 'Notes:', 0, 0);
    $pdf->MultiCell(0, 8, $order['notes'], 0, 'L');
}

// Add a line
$pdf->Ln(5);
$pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
$pdf->Ln(5);

// Order Items
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Order Items:', 0, 1, 'L');

// Simple table headers
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(80, 7, 'Product', 1, 0, 'L');
$pdf->Cell(30, 7, 'Quantity', 1, 0, 'C');
$pdf->Cell(30, 7, 'Unit Cost', 1, 0, 'R');
$pdf->Cell(40, 7, 'Subtotal', 1, 1, 'R');

// Table content
$pdf->SetFont('helvetica', '', 10);
foreach ($order_items as $item) {
    // Handle potentially long product names
    $pdf->Cell(80, 7, $item['product_name'], 1, 0, 'L');
    $pdf->Cell(30, 7, $item['quantity'], 1, 0, 'C');
    $pdf->Cell(30, 7, '$'.number_format($item['unit_cost'], 2), 1, 0, 'R');
    $pdf->Cell(40, 7, '$'.number_format($item['subtotal'], 2), 1, 1, 'R');
}

// Total row
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(140, 7, 'Total', 1, 0, 'R');
$pdf->Cell(40, 7, '$'.number_format($order['total_amount'], 2), 1, 1, 'R');

// Add generation timestamp at bottom
$pdf->Ln(10);
$pdf->SetFont('helvetica', 'I', 8);
$pdf->Cell(0, 5, 'Generated on: '.date('Y-m-d H:i:s'), 0, 1, 'R');

// Output the PDF
$pdf->Output('order_'.$order_id.'.pdf', 'I');