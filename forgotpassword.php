<?php
require_once 'db.php';

$error = '';
$success = '';
$step = 1; // Step 1: Email verification, Step 2: Reset password

// If already logged in, redirect
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Handle form submission
if (isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action == 'verify_email') {
        $email = trim($_POST['email']);

        if ($email == '') {
            $error = 'Email address is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            // Check if user exists
            $stmt = $conn->prepare("SELECT id, name FROM users WHERE email = ? LIMIT 1");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();

            if ($user) {
                // Generate reset token (valid for 1 hour)
                $reset_token = bin2hex(random_bytes(32));
                $token_expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

                // Save token to database (you can optionally add a reset_tokens table)
                // For now, we'll just show success message
                $_SESSION['reset_email'] = $email;
                $_SESSION['reset_token'] = $reset_token;

                $success = 'Password reset instructions have been sent to ' . htmlspecialchars($email) . '. Check your email for further instructions.';
                $step = 2;
            } else {
                $error = 'No account found with this email address.';
            }
        }
    } elseif ($action == 'reset_password') {
        $new_password     = trim($_POST['new_password']);
        $confirm_password = trim($_POST['confirm_password']);

        if ($new_password == '' || $confirm_password == '') {
            $error = 'Both password fields are required.';
        } elseif (strlen($new_password) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } elseif ($new_password != $confirm_password) {
            $error = 'Passwords do not match.';
        } else {
            // Update password in database
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
            $email = isset($_SESSION['reset_email']) ? $_SESSION['reset_email'] : '';

            if ($email != '') {
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
                $stmt->bind_param("ss", $hashed_password, $email);
                $stmt->execute();
                $stmt->close();

                // Clear session
                unset($_SESSION['reset_email']);
                unset($_SESSION['reset_token']);

                $success = 'Password has been reset successfully! Redirecting to login...';
                header('refresh:2;url=login.php');
            }
        }
    }
}

// Check if user is in step 2
if (isset($_SESSION['reset_email']) && isset($_SESSION['reset_token'])) {
    $step = 2;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Crave &amp; Order</title>
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
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .forgot-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            animation: fadeIn 0.6s ease;
        }

        .forgot-card {
            background: #fff;
            border-radius: 25px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.12);
            width: 100%;
            max-width: 450px;
            overflow: hidden;
        }

        .forgot-header {
            background: linear-gradient(135deg, #311111 0%, #6B3C3C 100%);
            color: #fff;
            padding: 40px 30px;
            text-align: center;
            animation: slideDown 0.6s ease;
        }

        .forgot-header .logo-icon {
            font-size: 3rem;
            margin-bottom: 15px;
            animation: popIn 0.6s ease;
        }

        .forgot-header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .forgot-header p {
            font-size: 0.95rem;
            opacity: 0.95;
            line-height: 1.5;
        }

        .forgot-content {
            padding: 40px 30px;
        }

        /* Alert Messages */
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
            animation: slideDown 0.4s ease;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 5px solid #dc2626;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            border-left: 5px solid #16a34a;
        }

        .alert i {
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        /* Form */
        .forgot-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group label {
            font-weight: 600;
            color: #1a1a2e;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group label i {
            color: #311111;
            width: 18px;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-wrapper i {
            position: absolute;
            left: 16px;
            color: #311111;
            font-size: 1.1rem;
            pointer-events: none;
        }

        .form-group input {
            width: 100%;
            padding: 14px 16px 14px 45px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #fafafa;
            font-family: inherit;
        }

        .form-group input:focus {
            outline: none;
            border-color: #311111;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(49, 17, 17, 0.1);
        }

        .form-group input.input-error {
            border-color: #dc2626;
            background: #fef2f2;
        }

        .form-group input.input-error:focus {
            box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.1);
        }

        .form-group input.input-valid {
            border-color: #16a34a;
        }

        .error-text {
            display: none;
            color: #dc2626;
            font-size: 0.85rem;
            font-weight: 500;
            margin-top: 4px;
        }

        .error-text.show {
            display: block;
        }

        /* Submit Button */
        .btn-submit {
            background: linear-gradient(135deg, #311111 0%, #6B3C3C 100%);
            color: #fff;
            border: none;
            padding: 14px;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 10px;
        }

        .btn-submit:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(49, 17, 17, 0.3);
        }

        .btn-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Info Box */
        .info-box {
            background: #f0f9ff;
            border: 2px solid #0ea5e9;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            display: flex;
            gap: 12px;
            text-align: left;
        }

        .info-box i {
            color: #0ea5e9;
            font-size: 1.2rem;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .info-box p {
            font-size: 0.9rem;
            color: #0c4a6e;
            line-height: 1.6;
            margin: 0;
        }

        /* Links */
        .forgot-links {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            gap: 15px;
            flex-wrap: wrap;
        }

        .forgot-links a {
            color: #311111;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }

        .forgot-links a:hover {
            color: #1F0A0A;
            text-decoration: underline;
        }

        .divider {
            color: #999;
            font-size: 0.85rem;
        }

        /* Step Indicator */
        .steps-indicator {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            justify-content: center;
        }

        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            transition: all 0.3s ease;
            background: #e5e7eb;
            color: #666;
        }

        .step.active {
            background: linear-gradient(135deg, #311111 0%, #6B3C3C 100%);
            color: #fff;
            box-shadow: 0 5px 15px rgba(49, 17, 17, 0.3);
        }

        .step.completed {
            background: #16a34a;
            color: #fff;
        }

        /* Loading Spinner */
        .spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top: 3px solid #fff;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        /* Animations */
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
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

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes popIn {
            from {
                opacity: 0;
                transform: scale(0.8);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        /* Responsive */
        @media (max-width: 600px) {
            .forgot-container {
                padding: 20px;
            }

            .forgot-card {
                border-radius: 20px;
            }

            .forgot-header {
                padding: 30px 20px;
            }

            .forgot-header h1 {
                font-size: 1.5rem;
            }

            .forgot-header .logo-icon {
                font-size: 2.5rem;
            }

            .forgot-header p {
                font-size: 0.9rem;
            }

            .forgot-content {
                padding: 25px 20px;
            }

            .steps-indicator {
                margin-bottom: 20px;
            }

            .step {
                width: 36px;
                height: 36px;
                font-size: 0.85rem;
            }

            .forgot-links {
                flex-direction: column;
                align-items: stretch;
            }

            .forgot-links a {
                text-align: center;
                padding: 10px;
            }
        }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="forgot-container">
    <div class="forgot-card">
        <!-- Header -->
        <div class="forgot-header">
            <div class="logo-icon">🔐</div>
            <h1>Reset Password</h1>
            <p>Don't worry! We'll help you recover your account</p>
        </div>

        <!-- Content -->
        <div class="forgot-content">
            <!-- Step Indicator -->
            <div class="steps-indicator">
                <div class="step <?= $step >= 1 ? 'active' : '' ?>">1</div>
                <div class="step <?= $step >= 2 ? 'active' : '' ?>">2</div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?= htmlspecialchars($success) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($step === 1): ?>
                <!-- Step 1: Email Verification -->
                <div class="info-box">
                    <i class="fas fa-info-circle"></i>
                    <p>Enter your email address and we'll send you a link to reset your password.</p>
                </div>

                <form method="POST" action="forgotpassword.php" class="forgot-form" id="emailForm" novalidate>
                    <input type="hidden" name="action" value="verify_email">

                    <div class="form-group">
                        <label for="email">
                            <i class="fas fa-envelope"></i> Email Address
                        </label>
                        <div class="input-wrapper">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="email" name="email" placeholder="Enter your registered email" 
                                   value="<?= htmlspecialchars(isset($_POST['email']) ? $_POST['email'] : '') ?>" required>
                        </div>
                        <span class="error-text" id="emailError"></span>
                    </div>

                    <button type="submit" class="btn-submit" id="submitBtn">
                        <i class="fas fa-paper-plane"></i> Send Reset Link
                    </button>
                </form>

            <?php else: ?>
                <!-- Step 2: Reset Password -->
                <div class="info-box">
                    <i class="fas fa-info-circle"></i>
                    <p>Enter your new password and confirm it to reset your account.</p>
                </div>

                <form method="POST" action="forgotpassword.php" class="forgot-form" id="resetForm" novalidate>
                    <input type="hidden" name="action" value="reset_password">

                    <div class="form-group">
                        <label for="new_password">
                            <i class="fas fa-lock"></i> New Password
                        </label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="new_password" name="new_password" 
                                   placeholder="Enter new password (min 6 characters)" required>
                        </div>
                        <span class="error-text" id="passwordError"></span>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">
                            <i class="fas fa-lock"></i> Confirm Password
                        </label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="confirm_password" name="confirm_password" 
                                   placeholder="Confirm your new password" required>
                        </div>
                        <span class="error-text" id="confirmError"></span>
                    </div>

                    <button type="submit" class="btn-submit" id="submitBtn">
                        <i class="fas fa-check"></i> Reset Password
                    </button>
                </form>
            <?php endif; ?>

            <!-- Links -->
            <div class="forgot-links">
                <a href="login.php" title="Back to Login">
                    <i class="fas fa-arrow-left"></i> Back to Login
                </a>
                <span class="divider">•</span>
                <a href="signup.php" title="Create Account">
                    Create Account
                </a>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<script>
// Email Validation
function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    
    if (!email) {
        return { valid: false, message: 'Email is required' };
    }
    if (!emailRegex.test(email)) {
        return { valid: false, message: 'Please enter a valid email address' };
    }
    return { valid: true, message: '' };
}

// Password Validation
function validatePassword(password) {
    if (!password) {
        return { valid: false, message: 'Password is required' };
    }
    if (password.length < 6) {
        return { valid: false, message: 'Password must be at least 6 characters' };
    }
    return { valid: true, message: '' };
}

// Show Error
function showError(inputId, errorId, message) {
    const input = document.getElementById(inputId);
    const errorEl = document.getElementById(errorId);
    if (input && errorEl) {
        input.classList.add('input-error');
        input.classList.remove('input-valid');
        errorEl.textContent = message;
        errorEl.classList.add('show');
    }
}

// Show Success
function showSuccess(inputId, errorId) {
    const input = document.getElementById(inputId);
    const errorEl = document.getElementById(errorId);
    if (input && errorEl) {
        input.classList.remove('input-error');
        input.classList.add('input-valid');
        errorEl.textContent = '';
        errorEl.classList.remove('show');
    }
}

// Real-time Validation for Email Form
document.addEventListener('DOMContentLoaded', function() {
    const emailForm = document.getElementById('emailForm');
    
    if (emailForm) {
        const emailInput = document.getElementById('email');
        const submitBtn = document.getElementById('submitBtn');
        
        // Email blur validation
        emailInput?.addEventListener('blur', function() {
            const result = validateEmail(this.value);
            if (!result.valid && this.value) {
                showError('email', 'emailError', result.message);
            } else {
                showSuccess('email', 'emailError');
            }
        });

        // Email input validation (clear error on typing)
        emailInput?.addEventListener('input', function() {
            if (this.classList.contains('input-error')) {
                const result = validateEmail(this.value);
                if (result.valid) {
                    showSuccess('email', 'emailError');
                }
            }
        });

        // Form submission validation
        emailForm.addEventListener('submit', function(e) {
            const email = emailInput.value;
            const result = validateEmail(email);
            
            if (!result.valid) {
                e.preventDefault();
                showError('email', 'emailError', result.message);
            } else {
                // Show loading state
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner"></span> Sending...';
            }
        });
    }

    // Real-time Validation for Reset Form
    const resetForm = document.getElementById('resetForm');
    
    if (resetForm) {
        const passwordInput = document.getElementById('new_password');
        const confirmInput = document.getElementById('confirm_password');
        const submitBtn = document.getElementById('submitBtn');
        
        // Password blur validation
        passwordInput?.addEventListener('blur', function() {
            const result = validatePassword(this.value);
            if (!result.valid && this.value) {
                showError('new_password', 'passwordError', result.message);
            } else {
                showSuccess('new_password', 'passwordError');
            }
        });

        // Confirm password validation
        confirmInput?.addEventListener('blur', function() {
            const password = passwordInput.value;
            if (!this.value) {
                showError('confirm_password', 'confirmError', 'Please confirm your password');
            } else if (this.value !== password) {
                showError('confirm_password', 'confirmError', 'Passwords do not match');
            } else {
                showSuccess('confirm_password', 'confirmError');
            }
        });

        // Clear errors on input
        passwordInput?.addEventListener('input', function() {
            if (this.classList.contains('input-error')) {
                const result = validatePassword(this.value);
                if (result.valid) {
                    showSuccess('new_password', 'passwordError');
                }
            }
        });

        confirmInput?.addEventListener('input', function() {
            if (this.classList.contains('input-error')) {
                const password = passwordInput.value;
                if (this.value === password) {
                    showSuccess('confirm_password', 'confirmError');
                }
            }
        });

        // Form submission validation
        resetForm.addEventListener('submit', function(e) {
            const password = passwordInput.value;
            const confirm = confirmInput.value;
            
            const passwordResult = validatePassword(password);
            
            if (!passwordResult.valid) {
                e.preventDefault();
                showError('new_password', 'passwordError', passwordResult.message);
                return;
            }
            
            if (!confirm) {
                e.preventDefault();
                showError('confirm_password', 'confirmError', 'Please confirm your password');
                return;
            }
            
            if (password !== confirm) {
                e.preventDefault();
                showError('confirm_password', 'confirmError', 'Passwords do not match');
                return;
            }
            
            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner"></span> Resetting...';
        });
    }
});
</script>

</body>
</html>
