<?php
require_once 'db.php';

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
if (count($cart) == 0) {
    header('Location: cart.php');
    exit;
}

$total = 0;
foreach ($cart as $item) $total = $total + ($item['price'] * $item['quantity']);

$error = '';

if (isset($_POST['pay_now'])) {
    $method = trim($_POST['payment']);
    $allowed_methods = ['upi', 'card', 'cod'];
    if (!in_array($method, $allowed_methods)) {
        $error = 'Please select a payment method.';
    } else {
        // Save order to DB
        $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, payment_method, status) VALUES (?, ?, ?, 'Placed')");
        $stmt->bind_param("ids", $_SESSION['user_id'], $total, $method);
        $stmt->execute();
        $orderId = $stmt->insert_id;
        $stmt->close();

        // Save order items
        $stmt = $conn->prepare("INSERT INTO order_items (order_id, name, price, quantity) VALUES (?, ?, ?, ?)");
        foreach ($cart as $item) {
            $stmt->bind_param("isdi", $orderId, $item['name'], $item['price'], $item['quantity']);
            $stmt->execute();
        }
        $stmt->close();

        // Clear cart
        $_SESSION['cart'] = [];
        $_SESSION['last_order_id'] = $orderId;

        header('Location: invoice.php?order=' . $orderId);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - Crave &amp; Order</title>
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

        .payment-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        /* Header */
        .payment-header {
            margin-bottom: 35px;
            animation: slideDown 0.6s ease;
        }

        .payment-header h1 {
            font-size: 2.5rem;
            color: #1a1a2e;
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 10px;
        }

        .payment-header h1 i {
            color: #8B4513;
            font-size: 2.2rem;
        }

        .payment-header p {
            color: #666;
            font-size: 1rem;
        }

        /* Layout */
        .payment-layout {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 30px;
            animation: slideUp 0.6s ease;
        }

        /* Error Alert */
        .error-alert {
            background: #fee2e2;
            border-left: 5px solid #dc2626;
            color: #991b1b;
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.1);
        }

        .error-alert i {
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        /* Payment Methods Section */
        .payment-methods-section h2 {
            font-size: 1.3rem;
            color: #1a1a2e;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 15px;
            border-bottom: 3px solid #8B4513;
        }

        .payment-methods-section h2 i {
            color: #8B4513;
        }

        /* Payment Options */
        .payment-options {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }

        .payment-option {
            position: relative;
            cursor: pointer;
        }

        .payment-option input[type="radio"] {
            display: none;
        }

        .payment-option-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 25px 20px;
            background: #fff;
            border: 3px solid #e5e7eb;
            border-radius: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }

        .payment-option input[type="radio"]:checked + .payment-option-label {
            border-color: #8B4513;
            background: linear-gradient(135deg, #fef5e0 0%, #f5e6d3 100%);
            box-shadow: 0 8px 25px rgba(139, 69, 19, 0.15);
        }

        .payment-option-label i {
            font-size: 2rem;
            color: #8B4513;
            margin-bottom: 10px;
        }

        .payment-option-label span {
            font-weight: 600;
            color: #1a1a2e;
            font-size: 1rem;
        }

        .payment-option input[type="radio"]:checked + .payment-option-label span {
            color: #8B4513;
        }

        /* Payment Details */
        .payment-details {
            background: #fff;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 25px;
            display: none;
            animation: slideDown 0.4s ease;
        }

        .payment-details.active {
            display: block;
        }

        .payment-details h3 {
            font-size: 1.1rem;
            color: #1a1a2e;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .payment-details h3 i {
            color: #8B4513;
        }

        /* UPI Box */
        .upi-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }

        .qr-code-box {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8ec 100%);
            padding: 20px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .qr-code-box img {
            width: 150px;
            height: 150px;
            border-radius: 10px;
            background: #fff;
            padding: 5px;
        }

        .upi-input-wrapper {
            width: 100%;
        }

        /* Form Inputs */
        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #1a1a2e;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .form-group input:focus {
            outline: none;
            border-color: #8B4513;
            box-shadow: 0 0 0 3px rgba(139, 69, 19, 0.1);
            background: #fef5f0;
        }

        .error-text {
            display: block;
            color: #dc2626;
            font-size: 0.85rem;
            margin-top: 6px;
            font-weight: 500;
        }

        .form-group input.input-error {
            border-color: #dc2626;
            background: #fef2f2;
        }

        .form-group input.input-error:focus {
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
        }

        .form-group input.input-valid {
            border-color: #16a34a;
        }

        .success-text {
            display: block;
            color: #16a34a;
            font-size: 0.85rem;
            margin-top: 6px;
            font-weight: 500;
        }

        /* Card Fields Grid */
        .card-fields {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .card-fields .form-group:nth-child(2) {
            grid-column: 1 / -1;
        }

        .order-summary-sticky {
            position: sticky;
            top: 20px;
            background: #fff;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            border-top: 4px solid #311111;
        }

        .order-summary-sticky h3 {
            font-size: 1.2rem;
            color: #1a1a2e;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .order-summary-sticky h3 i {
            color: #311111;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 0.95rem;
        }

        .summary-item span {
            color: #666;
        }

        .summary-item strong {
            color: #1a1a2e;
        }

        .summary-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #311111;
            font-size: 1.2rem;
            font-weight: 700;
            color: #311111;
        }

        /* Order Summary */
        .order-summary {
            height: fit-content;
            position: sticky;
            top: 100px;
        }

        .summary-card {
            background: #fff;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            border-top: 5px solid #8B4513;
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
            color: #8B4513;
        }

        .order-items {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
            max-height: 300px;
            overflow-y: auto;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.95rem;
            color: #666;
        }

        .item-name {
            flex: 1;
        }

        .item-qty {
            color: #8B4513;
            font-weight: 600;
            margin: 0 10px;
        }

        .item-price {
            color: #1a1a2e;
            font-weight: 600;
            min-width: 80px;
            text-align: right;
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
            border-top: 2px solid #8B4513;
            margin-top: 12px;
            font-size: 1.4rem;
            font-weight: 700;
            color: #8B4513;
        }

        /* Submit Button */
        .submit-btn {
            width: 100%;
            padding: 16px 24px;
            background: linear-gradient(135deg, #8B4513 0%, #A0522D 100%);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 40px rgba(139, 69, 19, 0.35);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        /* Loader */
        .processing-loader {
            display: none;
            text-align: center;
            margin: 20px 0;
            color: #8B4513;
            font-weight: 600;
        }

        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f0f0f0;
            border-top: 3px solid #8B4513;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin-right: 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
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
            .payment-header h1 {
                font-size: 1.8rem;
            }

            .payment-layout {
                grid-template-columns: 1fr;
            }

            .order-summary {
                position: static;
            }

            .payment-options {
                grid-template-columns: 1fr;
            }

            .card-fields {
                grid-template-columns: 1fr;
            }

            .card-fields .form-group:nth-child(2) {
                grid-column: auto;
            }
        }

        @media (max-width: 600px) {
            .payment-container {
                padding: 20px 15px;
            }

            .payment-header h1 {
                font-size: 1.5rem;
            }

            .summary-card {
                padding: 20px;
            }

            .payment-option-label {
                padding: 20px 15px;
            }

            .payment-option-label i {
                font-size: 1.5rem;
            }

            .payment-option-label span {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="payment-container">
    <!-- Header -->
    <div class="payment-header">
        <h1><i class="fas fa-credit-card"></i> Secure Payment</h1>
        <p>Choose your preferred payment method</p>
    </div>

    <?php if ($error): ?>
        <div class="error-alert">
            <i class="fas fa-exclamation-circle"></i>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>

    <!-- Payment Layout -->
    <div class="payment-layout">
        <!-- Payment Form -->
        <div>
            <form method="POST" action="payment.php" id="paymentForm">
                <input type="hidden" name="pay_now" value="1">
                
                <!-- Payment Methods -->
                <div class="payment-methods-section">
                    <h2><i class="fas fa-wallet"></i> Select Payment Method</h2>
                    
                    <div class="payment-options">
                        <!-- UPI -->
                        <div class="payment-option">
                            <input type="radio" id="upi" name="payment" value="upi" onchange="showPaymentDetails('upi')">
                            <label for="upi" class="payment-option-label">
                                <i class="fas fa-mobile-alt"></i>
                                <span>UPI</span>
                            </label>
                        </div>

                        <!-- Card -->
                        <div class="payment-option">
                            <input type="radio" id="card" name="payment" value="card" onchange="showPaymentDetails('card')">
                            <label for="card" class="payment-option-label">
                                <i class="fas fa-credit-card"></i>
                                <span>Card</span>
                            </label>
                        </div>

                        <!-- Cash on Delivery -->
                        <div class="payment-option">
                            <input type="radio" id="cod" name="payment" value="cod" onchange="showPaymentDetails('cod')">
                            <label for="cod" class="payment-option-label">
                                <i class="fas fa-money-bill"></i>
                                <span>Cash on Delivery</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- UPI Details -->
                <div id="upiBox" class="payment-details">
                    <h3><i class="fas fa-qrcode"></i> Scan & Pay with UPI</h3>
                    <div class="upi-content">
                        <div class="qr-code-box">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=upi://pay?pa=test@upi" alt="UPI QR Code">
                        </div>
                        <div class="upi-input-wrapper">
                            <div class="form-group">
                                <label for="upi_id"><i class="fas fa-user-circle"></i> UPI ID</label>
                                <input type="text" id="upi_id" name="upi_id" placeholder="example@okhdfcbank">
                                <span class="error-text" id="upi_error"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card Details -->
                <div id="cardBox" class="payment-details">
                    <h3><i class="fas fa-credit-card"></i> Card Payment</h3>
                    <div class="card-fields">
                        <div class="form-group">
                            <label for="card_number">Card Number</label>
                            <input type="text" id="card_number" name="card_number" placeholder="1234 5678 9012 3456" maxlength="19">
                            <span class="error-text" id="card_number_error"></span>
                        </div>
                        <div class="form-group">
                            <label for="card_name">Card Holder Name</label>
                            <input type="text" id="card_name" name="card_name" placeholder="John Doe">
                            <span class="error-text" id="card_name_error"></span>
                        </div>
                        <div class="form-group">
                            <label for="card_expiry">Expiry (MM/YY)</label>
                            <input type="text" id="card_expiry" name="card_expiry" placeholder="12/25" maxlength="5">
                            <span class="error-text" id="card_expiry_error"></span>
                        </div>
                        <div class="form-group">
                            <label for="card_cvv">CVV</label>
                            <input type="text" id="card_cvv" name="card_cvv" placeholder="123" maxlength="4">
                            <span class="error-text" id="card_cvv_error"></span>
                        </div>
                    </div>
                </div>

                <!-- COD Notice -->
                <div id="codBox" class="payment-details">
                    <h3><i class="fas fa-check-circle"></i> Cash on Delivery</h3>
                    <p style="color: #666; line-height: 1.6;">
                        You can pay for your order when it's delivered to your address. No prepayment needed!
                    </p>
                </div>

                <!-- Processing Loader & Button -->
                <div class="processing-loader" id="loader">
                    <span class="spinner"></span> Processing Payment...
                </div>
                <button type="submit" class="submit-btn" id="submitBtn">
                    <i class="fas fa-lock"></i> Pay Now
                </button>

            </form>
        </div>

        <!-- Order Summary (Sticky) -->
        <div class="order-summary">
            <div class="summary-card">
                <h3><i class="fas fa-receipt"></i> Order Summary</h3>
                
                <div class="order-items">
                    <?php foreach ($cart as $i => $item): 
                        $itemTotal = $item['price'] * $item['quantity'];
                    ?>
                        <div class="order-item">
                            <span class="item-name"><?= htmlspecialchars(substr($item['name'], 0, 20)) ?></span>
                            <span class="item-qty">×<?= $item['quantity'] ?></span>
                            <span class="item-price">₹<?= number_format($itemTotal, 2) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="summary-row">
                    <span><i class="fas fa-box"></i> Subtotal</span>
                    <span>₹<?= number_format($total, 2) ?></span>
                </div>
                <div class="summary-row">
                    <span><i class="fas fa-truck"></i> Delivery</span>
                    <span><span style="color: #16a34a; font-weight: 600;">FREE</span></span>
                </div>
                <div class="summary-row">
                    <span><i class="fas fa-tag"></i> Discount</span>
                    <span>₹0.00</span>
                </div>
                
                <div class="summary-row total">
                    <span>Total Amount</span>
                    <span>₹<?= number_format($total, 2) ?></span>
                </div>

                <div style="background: #f0fdf4; border-left: 4px solid #16a34a; padding: 12px; border-radius: 8px; margin-top: 20px; font-size: 0.9rem; color: #166534;">
                    <i class="fas fa-shield-alt"></i> Secure & encrypted payment
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<script>
function showPaymentDetails(method) {
    document.getElementById('upiBox').classList.remove('active');
    document.getElementById('cardBox').classList.remove('active');
    document.getElementById('codBox').classList.remove('active');
    
    if (method === 'upi') {
        document.getElementById('upiBox').classList.add('active');
    } else if (method === 'card') {
        document.getElementById('cardBox').classList.add('active');
    } else if (method === 'cod') {
        document.getElementById('codBox').classList.add('active');
    }
    
    updateSubmitButton();
}

function updateSubmitButton() {
    const selected = document.querySelector('input[name="payment"]:checked');
    const submitBtn = document.getElementById('submitBtn');
    const total = <?= json_encode(number_format($total, 2)) ?>;
    
    if (selected) {
        submitBtn.innerHTML = '<i class="fas fa-lock"></i> Pay Now ₹' + total;
    }
}

// UPI Validation
function validateUPI(upiId) {
    const upiRegex = /^[a-zA-Z0-9._-]+@[a-zA-Z]{3,}$/;
    
    if (!upiId) {
        return { valid: false, message: 'UPI ID is required' };
    }
    if (upiId.length < 5 || upiId.length > 60) {
        return { valid: false, message: 'UPI ID must be between 5 and 60 characters' };
    }
    if (!upiRegex.test(upiId)) {
        return { valid: false, message: 'Invalid UPI ID format (e.g., user@okhdfcbank)' };
    }
    return { valid: true, message: '' };
}

// Card Number Validation (Luhn Algorithm)
function validateCardNumber(cardNumber) {
    const digitsOnly = cardNumber.replace(/\s/g, '');
    
    if (!digitsOnly) {
        return { valid: false, message: 'Card number is required' };
    }
    if (!/^\d{13,19}$/.test(digitsOnly)) {
        return { valid: false, message: 'Card number must be 13-19 digits' };
    }
    
    // Luhn Algorithm
    let sum = 0;
    let isEven = false;
    for (let i = digitsOnly.length - 1; i >= 0; i--) {
        let digit = parseInt(digitsOnly.charAt(i), 10);
        if (isEven) {
            digit *= 2;
            if (digit > 9) digit -= 9;
        }
        sum += digit;
        isEven = !isEven;
    }
    
    if (sum % 10 !== 0) {
        return { valid: false, message: 'Invalid card number' };
    }
    return { valid: true, message: '' };
}

// Card Holder Name Validation
function validateCardName(name) {
    if (!name) {
        return { valid: false, message: 'Card holder name is required' };
    }
    if (name.length < 3 || name.length > 50) {
        return { valid: false, message: 'Name must be between 3 and 50 characters' };
    }
    if (!/^[a-zA-Z\s'-]+$/.test(name)) {
        return { valid: false, message: 'Name can only contain letters, spaces, hyphens and apostrophes' };
    }
    return { valid: true, message: '' };
}

// Expiry Date Validation
function validateExpiry(expiry) {
    if (!expiry) {
        return { valid: false, message: 'Expiry date is required' };
    }
    if (!/^\d{2}\/\d{2}$/.test(expiry)) {
        return { valid: false, message: 'Expiry format must be MM/YY' };
    }
    
    const [month, year] = expiry.split('/');
    const monthNum = parseInt(month, 10);
    
    if (monthNum < 1 || monthNum > 12) {
        return { valid: false, message: 'Month must be between 01 and 12' };
    }
    
    const currentYear = new Date().getFullYear() % 100;
    const currentMonth = new Date().getMonth() + 1;
    const expireYear = parseInt(year, 10);
    
    if (expireYear < currentYear || (expireYear === currentYear && monthNum < currentMonth)) {
        return { valid: false, message: 'Card has expired' };
    }
    
    return { valid: true, message: '' };
}

// CVV Validation
function validateCVV(cvv) {
    if (!cvv) {
        return { valid: false, message: 'CVV is required' };
    }
    if (!/^\d{3,4}$/.test(cvv)) {
        return { valid: false, message: 'CVV must be 3 or 4 digits' };
    }
    return { valid: true, message: '' };
}

// Format Card Number
function formatCardNumber(input) {
    const value = input.value.replace(/\s/g, '');
    const formattedValue = value.replace(/(\d{4})/g, '$1 ').trim();
    input.value = formattedValue;
}

// Format Expiry
function formatExpiry(input) {
    let value = input.value.replace(/\D/g, '');
    if (value.length >= 2) {
        value = value.substring(0, 2) + '/' + value.substring(2, 4);
    }
    input.value = value;
}

// Format CVV
function formatCVV(input) {
    input.value = input.value.replace(/\D/g, '').substring(0, 4);
}

// Show Error
function showError(inputId, errorId, message) {
    const input = document.getElementById(inputId);
    const errorEl = document.getElementById(errorId);
    input.classList.add('input-error');
    input.classList.remove('input-valid');
    errorEl.textContent = message;
}

// Show Success
function showSuccess(inputId, errorId) {
    const input = document.getElementById(inputId);
    const errorEl = document.getElementById(errorId);
    input.classList.remove('input-error');
    input.classList.add('input-valid');
    errorEl.textContent = '';
}

// Real-time validation for UPI
document.addEventListener('DOMContentLoaded', function() {
    const upiInput = document.getElementById('upi_id');
    if (upiInput) {
        upiInput.addEventListener('blur', function() {
            const result = validateUPI(this.value);
            if (!result.valid && this.value) {
                showError('upi_id', 'upi_error', result.message);
            } else {
                showSuccess('upi_id', 'upi_error');
            }
        });
    }

    // Card Number formatting and validation
    const cardNumberInput = document.getElementById('card_number');
    if (cardNumberInput) {
        cardNumberInput.addEventListener('input', function() {
            formatCardNumber(this);
        });
        cardNumberInput.addEventListener('blur', function() {
            const result = validateCardNumber(this.value);
            if (!result.valid && this.value) {
                showError('card_number', 'card_number_error', result.message);
            } else {
                showSuccess('card_number', 'card_number_error');
            }
        });
    }

    // Card Name validation
    const cardNameInput = document.getElementById('card_name');
    if (cardNameInput) {
        cardNameInput.addEventListener('blur', function() {
            const result = validateCardName(this.value);
            if (!result.valid && this.value) {
                showError('card_name', 'card_name_error', result.message);
            } else {
                showSuccess('card_name', 'card_name_error');
            }
        });
    }

    // Expiry formatting and validation
    const expiryInput = document.getElementById('card_expiry');
    if (expiryInput) {
        expiryInput.addEventListener('input', function() {
            formatExpiry(this);
        });
        expiryInput.addEventListener('blur', function() {
            const result = validateExpiry(this.value);
            if (!result.valid && this.value) {
                showError('card_expiry', 'card_expiry_error', result.message);
            } else {
                showSuccess('card_expiry', 'card_expiry_error');
            }
        });
    }

    // CVV formatting and validation
    const cvvInput = document.getElementById('card_cvv');
    if (cvvInput) {
        cvvInput.addEventListener('input', function() {
            formatCVV(this);
        });
        cvvInput.addEventListener('blur', function() {
            const result = validateCVV(this.value);
            if (!result.valid && this.value) {
                showError('card_cvv', 'card_cvv_error', result.message);
            } else {
                showSuccess('card_cvv', 'card_cvv_error');
            }
        });
    }
});

// Form submission validation
document.getElementById('paymentForm').addEventListener('submit', function(e) {
    const selected = document.querySelector('input[name="payment"]:checked');
    if (!selected) {
        e.preventDefault();
        alert('Please select a payment method');
        return;
    }
    
    // Validate based on payment method
    if (selected.value === 'upi') {
        const upiId = document.getElementById('upi_id').value;
        const upiResult = validateUPI(upiId);
        if (!upiResult.valid) {
            e.preventDefault();
            showError('upi_id', 'upi_error', upiResult.message);
            return;
        }
    } else if (selected.value === 'card') {
        const cardNumber = document.getElementById('card_number').value;
        const cardName = document.getElementById('card_name').value;
        const expiry = document.getElementById('card_expiry').value;
        const cvv = document.getElementById('card_cvv').value;
        
        const cardNumberResult = validateCardNumber(cardNumber);
        const cardNameResult = validateCardName(cardName);
        const expiryResult = validateExpiry(expiry);
        const cvvResult = validateCVV(cvv);
        
        if (!cardNumberResult.valid) {
            e.preventDefault();
            showError('card_number', 'card_number_error', cardNumberResult.message);
            return;
        }
        if (!cardNameResult.valid) {
            e.preventDefault();
            showError('card_name', 'card_name_error', cardNameResult.message);
            return;
        }
        if (!expiryResult.valid) {
            e.preventDefault();
            showError('card_expiry', 'card_expiry_error', expiryResult.message);
            return;
        }
        if (!cvvResult.valid) {
            e.preventDefault();
            showError('card_cvv', 'card_cvv_error', cvvResult.message);
            return;
        }
    }
    
    document.getElementById('loader').style.display = 'flex';
    setTimeout(function() {
        document.getElementById('submitBtn').disabled = true;
    }, 50);
});
</script>

</body>
</html>
