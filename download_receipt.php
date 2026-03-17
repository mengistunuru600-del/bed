<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';
$conn->select_db("bed_reservation");

$request_id = isset($_GET['request_id']) ? intval($_GET['request_id']) : 0;
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Get reservation details
if ($user_role == 'customer') {
    $stmt = $conn->prepare("SELECT rr.*, b.name as bed_name, b.room_size, b.accessories, u.username, u.full_name, u.email, u.phone FROM reservation_requests rr JOIN beds b ON rr.bed_id = b.id JOIN users u ON rr.customer_id = u.id WHERE rr.id = ? AND rr.customer_id = ? AND rr.payment_status = 'completed'");
    $stmt->bind_param("ii", $request_id, $user_id);
} else {
    $stmt = $conn->prepare("SELECT rr.*, b.name as bed_name, b.room_size, b.accessories, u.username, u.full_name, u.email, u.phone FROM reservation_requests rr JOIN beds b ON rr.bed_id = b.id JOIN users u ON rr.customer_id = u.id WHERE rr.id = ? AND rr.payment_status = 'completed'");
    $stmt->bind_param("i", $request_id);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "Receipt not found or payment not completed.";
    exit();
}

$reservation = $result->fetch_assoc();
$stmt->close();
$conn->close();

// Calculate days
$check_in = new DateTime($reservation['check_in']);
$check_out = new DateTime($reservation['check_out']);
$days = $check_out->diff($check_in)->days;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .receipt { border: 2px solid #000; padding: 30px; background: white; }
        .receipt-header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 20px; margin-bottom: 20px; }
        .receipt-header h1 { margin: 0; color: #007bff; }
        .receipt-header p { margin: 5px 0; color: #666; }
        .receipt-info { margin: 20px 0; }
        .info-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px dashed #ddd; }
        .info-label { font-weight: bold; color: #333; }
        .info-value { color: #666; }
        .total-section { background: #f8f9fa; padding: 15px; margin: 20px 0; border-radius: 5px; }
        .total-amount { font-size: 24px; font-weight: bold; color: #28a745; text-align: right; }
        .receipt-footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 2px solid #000; color: #666; font-size: 12px; }
        .print-btn { background: #007bff; color: white; padding: 10px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; margin: 10px 5px; }
        .download-btn { background: #28a745; color: white; padding: 10px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; margin: 10px 5px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="no-print" style="text-align:center;margin-bottom:20px;">
        <button onclick="window.print()" class="print-btn">🖨️ Print Receipt</button>
        <button onclick="window.history.back()" class="download-btn">← Back</button>
    </div>

    <div class="receipt">
        <div class="receipt-header">
            <h1>PAYMENT RECEIPT</h1>
            <p>Bed Reservation System</p>
            <p>Receipt #<?php echo str_pad($reservation['id'], 6, '0', STR_PAD_LEFT); ?></p>
            <p>Date: <?php echo date('F j, Y H:i:s'); ?></p>
        </div>

        <div class="receipt-info">
            <h3>Customer Information</h3>
            <div class="info-row">
                <span class="info-label">Customer Name:</span>
                <span class="info-value"><?php echo htmlspecialchars($reservation['full_name'] ?: $reservation['username']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Email:</span>
                <span class="info-value"><?php echo htmlspecialchars($reservation['email']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Phone:</span>
                <span class="info-value"><?php echo htmlspecialchars($reservation['phone'] ?: 'N/A'); ?></span>
            </div>
        </div>

        <div class="receipt-info">
            <h3>Reservation Details</h3>
            <div class="info-row">
                <span class="info-label">Bed:</span>
                <span class="info-value"><?php echo htmlspecialchars($reservation['bed_name']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Room Size:</span>
                <span class="info-value"><?php echo htmlspecialchars($reservation['room_size']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Check-in Date:</span>
                <span class="info-value"><?php echo date('F j, Y', strtotime($reservation['check_in'])); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Check-out Date:</span>
                <span class="info-value"><?php echo date('F j, Y', strtotime($reservation['check_out'])); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Number of Days:</span>
                <span class="info-value"><?php echo $days; ?> day<?php echo $days > 1 ? 's' : ''; ?></span>
            </div>
        </div>

        <div class="receipt-info">
            <h3>Payment Information</h3>
            <div class="info-row">
                <span class="info-label">Payment Method:</span>
                <span class="info-value"><?php echo strtoupper($reservation['payment_method']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Transaction Reference:</span>
                <span class="info-value"><?php echo htmlspecialchars($reservation['payment_reference']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Payment Date:</span>
                <span class="info-value"><?php echo date('F j, Y H:i', strtotime($reservation['updated_at'])); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Payment Status:</span>
                <span class="info-value" style="color:#28a745;font-weight:bold;">✓ COMPLETED</span>
            </div>
        </div>

        <div class="total-section">
            <div class="info-row" style="border:none;">
                <span class="info-label">Price per Day:</span>
                <span class="info-value">ETB 500.00</span>
            </div>
            <div class="info-row" style="border:none;">
                <span class="info-label">Number of Days:</span>
                <span class="info-value"><?php echo $days; ?></span>
            </div>
            <hr>
            <div class="total-amount">
                TOTAL PAID: ETB <?php echo number_format($reservation['payment_amount'] ?? ($days * 500), 2); ?>
            </div>
        </div>

        <div class="receipt-footer">
            <p><strong>Thank you for your payment!</strong></p>
            <p>This is an official receipt for your bed reservation.</p>
            <p>For inquiries, please contact us at info@bedreservation.com</p>
            <p style="margin-top:15px;">Generated on <?php echo date('F j, Y \a\t H:i:s'); ?></p>
        </div>
    </div>
</body>
</html>
