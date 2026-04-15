<?php
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Fetch all orders for this user
$stmt = $conn->prepare("SELECT o.*, GROUP_CONCAT(oi.name SEPARATOR ', ') AS item_names FROM orders o LEFT JOIN order_items oi ON o.id = oi.order_id WHERE o.user_id = ? GROUP BY o.id ORDER BY o.created_at DESC");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Crave &amp; Order</title>
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

        .orders-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .orders-header {
            margin-bottom: 40px;
            animation: slideDown 0.6s ease;
        }

        .orders-header h1 {
            font-size: 2.5rem;
            color: #3c1616;
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 10px;
        }

        .orders-header h1 i {
            color: #311111;
            font-size: 2.2rem;
        }

        .orders-header p {
            color: #666;
            font-size: 1rem;
        }

        /* Latest Order Card */
        .latest-order-card {
            background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
            border: 2px solid #16a34a;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 40px;
            box-shadow: 0 10px 30px rgba(22, 163, 74, 0.15);
            animation: slideUp 0.6s ease;
        }

        .latest-order-card h3 {
            color: #16a34a;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .latest-order-card h3 i {
            font-size: 1.8rem;
        }

        .order-details-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 15px;
        }

        .order-detail-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .order-detail-label {
            font-size: 0.9rem;
            color: #16a34a;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .order-detail-value {
            font-size: 1.1rem;
            color: #1a1a2e;
            font-weight: 600;
        }

        .order-items {
            background: rgba(255, 255, 255, 0.7);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 15px;
            border-left: 4px solid #16a34a;
        }

        .order-items strong {
            color: #16a34a;
        }

        .order-amount-large {
            font-size: 1.8rem;
            font-weight: 700;
            color: #16a34a;
            margin-bottom: 15px;
        }

        .status-badge-latest {
            display: inline-block;
            background: #fff;
            color: #16a34a;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
            border: 2px solid #16a34a;
            margin-bottom: 15px;
        }

        .btn-invoice-latest {
            background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
            color: #fff;
            padding: 14px 28px;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-invoice-latest:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(22, 163, 74, 0.3);
        }

        /* Order History Section */
        .order-history-section {
            animation: slideUp 0.7s ease;
        }

        .section-title {
            font-size: 1.5rem;
            color: #1a1a2e;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 3px solid #311111;
        }

        .section-title i {
            color: #311111;
            font-size: 1.5rem;
        }

        .orders-grid {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .order-card {
            background: #fff;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border-left: 5px solid #311111;
        }

        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }

        .order-card-content {
            padding: 25px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            align-items: center;
        }

        .order-card-left {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .order-id {
            font-size: 1.3rem;
            font-weight: 700;
            color: #1a1a2e;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .order-id i {
            color: #311111;
            font-size: 1.1rem;
        }

        .order-date {
            font-size: 0.95rem;
            color: #666;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .order-date i {
            color: #311111;
            width: 16px;
        }

        .order-items-list {
            background: #f8f9fa;
            padding: 12px 15px;
            border-radius: 10px;
            font-size: 0.9rem;
            color: #555;
            line-height: 1.6;
            border-left: 3px solid #311111;
        }

        .order-card-right {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 15px;
            text-align: right;
        }

        .order-amount {
            font-size: 1.8rem;
            font-weight: 700;
            color: #311111;
        }

        .order-status {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-placed {
            background: #fef3c7;
            color: #3a1c1c;
        }

        .status-processing {
            background: #dbeafe;
            color: #2563eb;
        }

        .status-shipped {
            background: #f3e8ff;
            color: #7c3aed;
        }

        .status-delivered {
            background: #dcfce7;
            color: #16a34a;
        }

        .status-cancelled {
            background: #fee2e2;
            color: #dc2626;
        }

        .btn-view-invoice {
            background: #311111;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }

        .btn-view-invoice:hover {
            background: #1F0A0A;
            transform: scale(1.05);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
        }

        .empty-state i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 20px;
            display: block;
        }

        .empty-state p {
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 25px;
        }

        .btn-browse {
            background: linear-gradient(135deg, #311111 0%, #6B3C3C 100%);
            color: #fff;
            padding: 12px 30px;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-browse:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(49, 17, 17, 0.3);
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .orders-header h1 {
                font-size: 1.8rem;
            }

            .order-card-content {
                grid-template-columns: 1fr;
            }

            .order-card-right {
                align-items: flex-start;
                text-align: left;
            }

            .order-details-row {
                grid-template-columns: 1fr;
            }

            .section-title {
                font-size: 1.3rem;
            }
        }

        @media (max-width: 600px) {
            .orders-container {
                padding: 20px 15px;
            }

            .orders-header h1 {
                font-size: 1.5rem;
            }

            .latest-order-card {
                padding: 20px;
            }

            .order-card-content {
                padding: 15px;
            }

            .order-amount {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="orders-container">
    <!-- Header -->
    <div class="orders-header">
        <h1><i class="fas fa-box"></i> My Orders</h1>
        <p>Track and manage all your orders</p>
    </div>

    <?php if (empty($orders)): ?>
        <!-- Empty State -->
        <div class="empty-state">
            <i class="fas fa-shopping-bag"></i>
            <p>No orders yet</p>
            <p style="font-size: 0.95rem; color: #888; margin-bottom: 25px;">Start ordering from our delicious menu!</p>
            <a href="menu.php" class="btn-browse">
                <i class="fas fa-utensils"></i> Browse Menu
            </a>
        </div>

    <?php else: ?>
        <!-- Latest Order -->
        <div class="latest-order-card">
            <h3><i class="fas fa-star"></i> Your Latest Order</h3>
            
            <div class="order-details-row">
                <div class="order-detail-item">
                    <span class="order-detail-label">Order ID</span>
                    <span class="order-detail-value">#<?= $orders[0]['id'] ?></span>
                </div>
                <div class="order-detail-item">
                    <span class="order-detail-label">Ordered On</span>
                    <span class="order-detail-value"><?= date('d M Y, h:i A', strtotime($orders[0]['created_at'])) ?></span>
                </div>
            </div>

            <div class="order-items">
                <strong>📦 Items:</strong> <?= htmlspecialchars($orders[0]['item_names']) ?>
            </div>

            <div class="order-amount-large">₹<?= number_format($orders[0]['total_amount'], 2) ?></div>

            <span class="status-badge-latest"><?= ucfirst($orders[0]['status']) ?></span>

            <a href="invoice.php?order=<?= $orders[0]['id'] ?>" class="btn-invoice-latest">
                <i class="fas fa-file-invoice"></i> View Invoice
            </a>
        </div>

        <!-- Order History -->
        <div class="order-history-section">
            <h2 class="section-title">
                <i class="fas fa-history"></i> Order History
            </h2>

            <div class="orders-grid">
                <?php foreach ($orders as $order): 
                    // Determine status class based on order status
                    $statusMap = [
                        'Placed' => 'status-placed',
                        'Processing' => 'status-processing',
                        'Shipped' => 'status-shipped',
                        'Delivered' => 'status-delivered',
                        'Cancelled' => 'status-cancelled'
                    ];
                    $statusClass = isset($statusMap[$order['status']]) ? $statusMap[$order['status']] : 'status-placed';
                ?>
                    <div class="order-card">
                        <div class="order-card-content">
                            <!-- Left Side -->
                            <div class="order-card-left">
                                <div class="order-id">
                                    <i class="fas fa-tag"></i> Order #<?= $order['id'] ?>
                                </div>
                                <div class="order-date">
                                    <i class="fas fa-calendar"></i> <?= date('d M Y, h:i A', strtotime($order['created_at'])) ?>
                                </div>
                                <div class="order-items-list">
                                    <?= htmlspecialchars($order['item_names']) ?>
                                </div>
                            </div>

                            <!-- Right Side -->
                            <div class="order-card-right">
                                <div class="order-amount">₹<?= number_format($order['total_amount'], 2) ?></div>
                                <span class="order-status <?= $statusClass ?>"><?= ucfirst($order['status']) ?></span>
                                <a href="invoice.php?order=<?= $order['id'] ?>" class="btn-view-invoice">
                                    <i class="fas fa-eye"></i> View Invoice
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>

</body>
</html>
