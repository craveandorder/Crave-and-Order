<?php
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$orderId = intval(isset($_GET['order']) ? $_GET['order'] : (isset($_SESSION['last_order_id']) ? $_SESSION['last_order_id'] : 0));
if (!$orderId) {
    header('Location: orders.php');
    exit;
}

// Fetch order
$stmt = $conn->prepare("SELECT o.*, u.name, u.email FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ? AND o.user_id = ?");
$stmt->bind_param("ii", $orderId, $_SESSION['user_id']);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    header('Location: orders.php');
    exit;
}

// Fetch items
$stmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
$stmt->bind_param("i", $orderId);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - Crave &amp; Order</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8ec 100%);
            font-family: 'Franklin Gothic Medium', Arial, sans-serif;
            color: #333;
        }

        .invoice-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        /* Header Section */
        .invoice-header-section {
            margin-bottom: 40px;
            animation: slideDown 0.6s ease;
        }

        .invoice-header-section h1 {
            font-size: 2.5rem;
            color: #1a1a2e;
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 10px;
        }

        .invoice-header-section h1 i {
            color: #311111;
            font-size: 2.2rem;
        }

        .invoice-header-section p {
            color: #666;
            font-size: 1rem;
        }

        /* Success Card */
        .success-card {
            background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
            border: 2px solid #16a34a;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 40px;
            box-shadow: 0 10px 30px rgba(22, 163, 74, 0.15);
            animation: slideUp 0.6s ease;
        }

        .success-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .success-icon {
            font-size: 2.5rem;
            animation: popIn 0.6s ease;
        }

        .success-card h3 {
            color: #16a34a;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .success-card h3 i {
            font-size: 1.4rem;
        }

        .invoice-info {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }

        .info-item {
            background: rgba(255, 255, 255, 0.8);
            padding: 15px;
            border-radius: 10px;
        }

        .info-label {
            color: #16a34a;
            font-size: 0.85rem;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .info-value {
            color: #1a1a2e;
            font-size: 1.1rem;
            font-weight: 600;
        }

        /* Invoice Details Card */
        .invoice-details-card {
            background: #fff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
            animation: slideUp 0.7s ease;
        }

        .card-header {
            background: linear-gradient(135deg, #311111 0%, #6B3C3C 100%);
            color: #fff;
            padding: 25px 30px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.3rem;
            font-weight: 700;
        }

        .card-header i {
            font-size: 1.5rem;
        }

        .card-content {
            padding: 30px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
            padding-bottom: 30px;
            border-bottom: 2px solid #f0f0f0;
        }

        .info-box h4 {
            color: #311111;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-box h4 i {
            font-size: 1rem;
        }

        .info-box p {
            color: #1a1a2e;
            margin-bottom: 10px;
            font-weight: 500;
        }

        .info-box p span {
            color: #666;
            font-weight: 400;
        }

        /* Items Table */
        .items-header {
            font-size: 1.2rem;
            color: #1a1a2e;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
        }

        .items-header i {
            color: #311111;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .items-table thead {
            background: #f8f9fa;
            border-top: 2px solid #e0e0e0;
            border-bottom: 2px solid #e0e0e0;
        }

        .items-table th {
            padding: 15px;
            text-align: left;
            color: #666;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .items-table td {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            color: #333;
        }

        .item-name {
            font-weight: 600;
            color: #1a1a2e;
        }

        .item-amount {
            text-align: right;
            font-weight: 600;
            color: #311111;
            font-size: 1rem;
        }

        /* Summary */
        .summary-section {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 30px;
        }

        .summary-box {
            background: #f8f9fa;
            padding: 20px 30px;
            border-radius: 12px;
            width: 100%;
            max-width: 350px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #e0e0e0;
            color: #666;
        }

        .summary-row.total {
            padding: 15px 0;
            border-bottom: none;
            border-top: 2px solid #311111;
            margin-top: 10px;
            font-size: 1.3rem;
            font-weight: 700;
            color: #311111;
        }

        .summary-row i {
            color: #311111;
            margin-right: 8px;
            width: 16px;
        }

        /* Action Buttons */
        .invoice-actions {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            padding-top: 30px;
            border-top: 2px solid #f0f0f0;
        }

        .action-btn {
            padding: 14px 20px;
            border: none;
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-print {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            color: #fff;
        }

        .btn-print:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(99, 102, 241, 0.3);
        }

        .btn-primary {
            background: linear-gradient(135deg, #311111 0%, #6B3C3C 100%);
            color: #fff;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(49, 17, 17, 0.3);
        }

        .btn-secondary {
            background: #f0f0f0;
            color: #333;
            border: 2px solid #ddd;
        }

        .btn-secondary:hover {
            background: #fff;
            border-color: #8B4513;
            color: #8B4513;
        }

        /* Animations */
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes popIn {
            0% {
                opacity: 0;
                transform: scale(0.5);
            }
            50% {
                transform: scale(1.1);
            }
            100% {
                opacity: 1;
                transform: scale(1);
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .invoice-header-section h1 {
                font-size: 1.8rem;
            }

            .invoice-info {
                grid-template-columns: 1fr 1fr;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .invoice-actions {
                grid-template-columns: repeat(2, 1fr);
            }

            .items-table th,
            .items-table td {
                padding: 10px;
                font-size: 0.9rem;
            }
        }

        @media (max-width: 600px) {
            .invoice-container {
                padding: 20px 15px;
            }

            .invoice-header-section h1 {
                font-size: 1.5rem;
            }

            .success-card {
                padding: 20px;
            }

            .invoice-info {
                grid-template-columns: 1fr;
            }

            .info-grid {
                gap: 15px;
            }

            .card-content {
                padding: 20px 15px;
            }

            .invoice-actions {
                grid-template-columns: 1fr;
            }

            .summary-box {
                max-width: 100%;
            }
        }

        @media print {
            .invoice-actions {
                display: none;
            }
            body {
                background: #fff;
            }
        }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="invoice-container">
    <!-- Header -->
    <div class="invoice-header-section">
        <h1><i class="fas fa-file-invoice"></i> Invoice Details</h1>
        <p>Order confirmation and payment receipt</p>
    </div>

    <!-- Success Banner -->
    <div class="success-card">
        <div class="success-header">
            <div class="success-icon">🎉</div>
            <div>
                <h3><i class="fas fa-check-circle"></i> Order Confirmed!</h3>
                <p style="color: #15803d; margin: 0;">Thank you for your order. We're preparing it now!</p>
            </div>
        </div>

        <div class="invoice-info">
            <div class="info-item">
                <div class="info-label">Invoice #</div>
                <div class="info-value"><?= $orderId ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Date</div>
                <div class="info-value"><?= date('d M Y', strtotime($order['created_at'])) ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Total Amount</div>
                <div class="info-value" style="color: #16a34a;">₹<?= number_format($order['total_amount'], 2) ?></div>
            </div>
        </div>
    </div>

    <!-- Invoice Details -->
    <div class="invoice-details-card">
        <div class="card-header">
            <i class="fas fa-clipboard-list"></i> Invoice Information
        </div>

        <div class="card-content">
            <!-- Customer & Payment Info -->
            <div class="info-grid">
                <div class="info-box">
                    <h4><i class="fas fa-user"></i> Customer Details</h4>
                    <p><?= htmlspecialchars($order['name']) ?></p>
                    <p><i class="fas fa-envelope" style="margin-right: 5px; color: #ff6b35;"></i><span><?= htmlspecialchars($order['email']) ?></span></p>
                </div>
                <div class="info-box">
                    <h4><i class="fas fa-credit-card"></i> Payment Details</h4>
                    <p><strong>Method:</strong> <span><?= strtoupper($order['payment_method']) ?></span></p>
                    <p><strong>Status:</strong> <span style="color: #16a34a; font-weight: 600;">✓ Confirmed</span></p>
                </div>
            </div>

            <!-- Order Items -->
            <div class="items-header">
                <i class="fas fa-shopping-bag"></i> Order Items
            </div>

            <table class="items-table">
                <thead>
                    <tr>
                        <th>Item Name</th>
                        <th style="text-align: center;">Quantity</th>
                        <th style="text-align: right;">Unit Price</th>
                        <th style="text-align: right;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td class="item-name"><?= htmlspecialchars($item['name']) ?></td>
                            <td style="text-align: center;"><?= $item['quantity'] ?></td>
                            <td style="text-align: right;">₹<?= number_format($item['price'], 2) ?></td>
                            <td class="item-amount">₹<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Summary -->
            <div class="summary-section">
                <div class="summary-box">
                    <div class="summary-row">
                        <span><i class="fas fa-boxes"></i> Subtotal</span>
                        <span>₹<?= number_format($order['total_amount'], 2) ?></span>
                    </div>
                    <div class="summary-row">
                        <span><i class="fas fa-truck"></i> Delivery</span>
                        <span>FREE</span>
                    </div>
                    <div class="summary-row">
                        <span><i class="fas fa-tag"></i> Discount</span>
                        <span>₹0.00</span>
                    </div>
                    <div class="summary-row total">
                        <span><i class="fas fa-receipt"></i> Total</span>
                        <span>₹<?= number_format($order['total_amount'], 2) ?></span>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="invoice-actions">
                <a href="javascript:window.print()" class="action-btn btn-print">
                    <i class="fas fa-print"></i> Print
                </a>
                <a href="orders.php" class="action-btn btn-secondary">
                    <i class="fas fa-history"></i> All Orders
                </a>
                <a href="profile.php" class="action-btn btn-secondary">
                    <i class="fas fa-user"></i> Profile
                </a>
                <a href="menu.php" class="action-btn btn-primary">
                    <i class="fas fa-utensils"></i> Order More
                </a>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

</body>
