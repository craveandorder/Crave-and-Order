<?php
require_once 'db.php';

// Handle AJAX add to cart
if (isset($_POST['action'])) {
    header('Content-Type: application/json');

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Please login first!', 'redirect' => 'login.php']);
        exit;
    }

    $action = $_POST['action'];

    if ($action == 'add') {
        $name  = trim($_POST['name']);
        $price = floatval($_POST['price']);
        if ($name == '' || $price <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid item.']);
            exit;
        }
        if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
        $found = false;
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['name'] == $name) {
                $item['quantity'] = $item['quantity'] + 1;
                $found = true;
                break;
            }
        }
        if (!$found) $_SESSION['cart'][] = ['name' => $name, 'price' => $price, 'quantity' => 1];
        $count = array_sum(array_column($_SESSION['cart'], 'quantity'));
        echo json_encode(['success' => true, 'message' => $name . ' added to cart!', 'count' => $count]);
        exit;
    }

    if ($action == 'remove') {
        $index = intval($_POST['index']);
        if (isset($_SESSION['cart'][$index])) {
            array_splice($_SESSION['cart'], $index, 1);
        }
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action == 'change_qty') {
        $index  = intval($_POST['index']);
        $change = intval($_POST['change']);
        if (isset($_SESSION['cart'][$index])) {
            $_SESSION['cart'][$index]['quantity'] = $_SESSION['cart'][$index]['quantity'] + $change;
            if ($_SESSION['cart'][$index]['quantity'] <= 0) {
                array_splice($_SESSION['cart'], $index, 1);
            }
        }
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action == 'clear') {
        $_SESSION['cart'] = [];
        echo json_encode(['success' => true]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart - Crave &amp; Order</title>
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

        .cart-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        /* Header */
        .cart-header {
            margin-bottom: 40px;
            animation: slideDown 0.6s ease;
        }

        .cart-header h1 {
            font-size: 2.5rem;
            color: #1a1a2e;
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 10px;
        }

        .cart-header h1 i {
            color: #311111;
            font-size: 2.2rem;
        }

        .cart-header p {
            color: #666;
            font-size: 1rem;
        }

        /* Empty State */
        .empty-state {
            background: #fff;
            border-radius: 20px;
            padding: 60px 40px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            animation: slideUp 0.6s ease;
        }

        .empty-state i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 20px;
            display: block;
        }

        .empty-state h2 {
            color: #666;
            font-size: 1.5rem;
            margin-bottom: 15px;
        }

        .empty-state p {
            color: #999;
            margin-bottom: 30px;
            font-size: 1rem;
        }

        .browse-btn {
            background: linear-gradient(135deg, #311111 0%, #6B3C3C 100%);
            color: #fff;
            padding: 14px 30px;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
        }

        .browse-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(49, 17, 17, 0.3);
        }

        /* Cart Layout */
        .cart-layout {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 30px;
            animation: slideUp 0.6s ease;
        }

        /* Cart Items */
        .cart-items-section h2 {
            font-size: 1.3rem;
            color: #1a1a2e;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 15px;
            border-bottom: 3px solid #311111;
        }

        .cart-items-section h2 i {
            color: #311111;
        }

        .cart-items {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .cart-item {
            background: #fff;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 20px;
            align-items: center;
            transition: all 0.3s ease;
            border-left: 5px solid #311111;
        }

        .cart-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
        }

        .item-info h3 {
            font-size: 1.1rem;
            color: #1a1a2e;
            margin-bottom: 8px;
        }

        .item-price {
            font-size: 1.3rem;
            font-weight: 700;
            color: #311111;
        }

        .item-subtotal {
            font-size: 0.9rem;
            color: #666;
            margin-top: 5px;
        }

        .item-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .qty-controls {
            display: flex;
            align-items: center;
            gap: 12px;
            background: #f0f0f0;
            padding: 8px 12px;
            border-radius: 10px;
        }

        .qty-btn {
            width: 32px;
            height: 32px;
            border: none;
            background: #311111;
            color: #fff;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }

        .qty-btn:hover {
            background: #1F0A0A;
            transform: scale(1.1);
        }

        .qty-display {
            min-width: 30px;
            text-align: center;
            font-weight: 600;
            color: #1a1a2e;
        }

        .remove-btn {
            background: #fee2e2;
            color: #dc2626;
            border: none;
            padding: 8px 14px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.9rem;
        }

        .remove-btn:hover {
            background: #fecaca;
            transform: scale(1.05);
        }

        /* Cart Summary */
        .cart-summary {
            height: fit-content;
            position: sticky;
            top: 100px;
        }

        .summary-card {
            background: #fff;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            border-top: 5px solid #311111;
        }

        .summary-card h3 {
            font-size: 1.2rem;
            color: #1a1a2e;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .summary-card h3 i {
            color: #311111;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
            color: #666;
        }

        .summary-row.total {
            padding: 18px 0;
            border-bottom: none;
            border-top: 2px solid #311111;
            margin-top: 12px;
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
        .cart-actions {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 2px solid #f0f0f0;
        }

        .action-btn {
            padding: 14px 20px;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-checkout {
            background: linear-gradient(135deg, #311111 0%, #6B3C3C 100%);
            color: #fff;
        }

        .btn-checkout:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(49, 17, 17, 0.3);
        }

        .btn-clear {
            background: #f0f0f0;
            color: #666;
            border: 2px solid #ddd;
        }

        .btn-clear:hover {
            background: #fee2e2;
            color: #dc2626;
            border-color: #fecaca;
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

        /* Responsive */
        @media (max-width: 768px) {
            .cart-header h1 {
                font-size: 1.8rem;
            }

            .cart-layout {
                grid-template-columns: 1fr;
            }

            .cart-summary {
                position: static;
            }

            .cart-item {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .item-actions {
                justify-content: space-between;
                width: 100%;
            }
        }

        @media (max-width: 600px) {
            .cart-container {
                padding: 20px 15px;
            }

            .cart-header h1 {
                font-size: 1.5rem;
            }

            .summary-card {
                padding: 20px;
            }

            .cart-item {
                padding: 15px;
            }

            .item-info h3 {
                font-size: 1rem;
            }

            .empty-state {
                padding: 40px 20px;
            }
        }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="cart-container">
    <!-- Header -->
    <div class="cart-header">
        <h1><i class="fas fa-shopping-cart"></i> Your Cart</h1>
        <p>Review and manage your items</p>
    </div>

    <?php
    $cart  = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
    $total = 0;
    
    if (empty($cart)): ?>
        <!-- Empty State -->
        <div class="empty-state">
            <i class="fas fa-shopping-bag"></i>
            <h2>Your Cart is Empty</h2>
            <p>Start adding delicious items to your cart!</p>
            <a href="menu.php" class="browse-btn">
                <i class="fas fa-utensils"></i> Browse Menu
            </a>
        </div>

    <?php else: ?>
        <!-- Cart Layout -->
        <div class="cart-layout">
            <!-- Cart Items -->
            <div class="cart-items-section">
                <h2><i class="fas fa-list"></i> Items in Cart (<?= count($cart) ?>)</h2>
                
                <div class="cart-items" id="cartItems">
                    <?php foreach ($cart as $i => $item): 
                        $subtotal = $item['price'] * $item['quantity'];
                        $total += $subtotal; 
                    ?>
                        <div class="cart-item" id="cart-row-<?= $i ?>">
                            <div class="item-info">
                                <h3><?= htmlspecialchars($item['name']) ?></h3>
                                <div class="item-price">₹<?= number_format($item['price'], 2) ?></div>
                                <div class="item-subtotal">Subtotal: ₹<?= number_format($subtotal, 2) ?></div>
                            </div>
                            <div class="item-actions">
                                <div class="qty-controls">
                                    <button class="qty-btn" onclick="changeQty(<?= $i ?>, -1)">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <span class="qty-display" id="qty-<?= $i ?>"><?= $item['quantity'] ?></span>
                                    <button class="qty-btn" onclick="changeQty(<?= $i ?>, 1)">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                                <button class="remove-btn" onclick="removeItem(<?= $i ?>)">
                                    <i class="fas fa-trash"></i> Remove
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Cart Summary -->
            <div class="cart-summary">
                <div class="summary-card">
                    <h3><i class="fas fa-receipt"></i> Order Summary</h3>
                    
                    <div class="summary-row">
                        <span><i class="fas fa-box"></i> Subtotal</span>
                        <span id="subtotal">₹<?= number_format($total, 2) ?></span>
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
                        <span id="total">₹<?= number_format($total, 2) ?></span>
                    </div>

                    <div class="cart-actions">
                        <button onclick="goToPayment()" class="action-btn btn-checkout">
                            <i class="fas fa-credit-card"></i> Proceed to Checkout
                        </button>
                        <button class="action-btn btn-clear" onclick="clearCart()">
                            <i class="fas fa-trash-alt"></i> Clear Cart
                        </button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>

<script>
function changeQty(index, change) {
    fetch('cart.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=change_qty&index=${index}&change=${change}`
    }).then(() => location.reload());
}

function removeItem(index) {
    if (confirm('Remove this item from cart?')) {
        fetch('cart.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=remove&index=${index}`
        }).then(() => location.reload());
    }
}

function clearCart() {
    if (confirm('Clear entire cart?')) {
        fetch('cart.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=clear'
        }).then(() => location.reload());
    }
}

function goToPayment() {
    <?php if (!isset($_SESSION['user_id'])): ?>
        alert('Please login first!');
        window.location.href = 'login.php';
        return;
    <?php elseif (empty($cart)): ?>
        alert('Your cart is empty!');
        return;
    <?php else: ?>
        window.location.href = 'payment.php';
    <?php endif; ?>
}
</script>

</body>
</html>
