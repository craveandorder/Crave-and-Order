<?php
require_once 'db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if (isset($_POST['register'])) {
    $name            = trim($_POST['name']);
    $email           = trim($_POST['email']);
    $mobile          = trim($_POST['mobile']);
    $password        = trim($_POST['password']);
    $confirmPassword = trim($_POST['confirmPassword']);

    if ($name == '' || $email == '' || $mobile == '' || $password == '' || $confirmPassword == '') {
        $error = 'All fields are required.';
    } elseif (strlen($name) < 3) {
        $error = 'Name must be at least 3 characters long.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address.';
    } elseif (!preg_match('/^\d{10}$/', $mobile)) {
        $error = 'Enter a valid 10-digit mobile number.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password != $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = 'An account already exists with this email.';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $stmt->close();
            $stmt = $conn->prepare("INSERT INTO users (name, email, mobile, password) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $mobile, $hashedPassword);
            if ($stmt->execute()) {
                $success = 'Registration Successful! Redirecting to login...';
                header('refresh:2;url=login.php');
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Crave &amp; Order</title>
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

        .signup-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            animation: fadeIn 0.6s ease;
        }

        .signup-card {
            background: #fff;
            border-radius: 25px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.12);
            width: 100%;
            max-width: 500px;
            overflow: hidden;
        }

        .signup-header {
            background: linear-gradient(135deg, #311111 0%, #6B3C3C 100%);
            color: #fff;
            padding: 40px 30px;
            text-align: center;
            animation: slideDown 0.6s ease;
        }

        .signup-header .logo-icon {
            font-size: 3rem;
            margin-bottom: 15px;
            animation: popIn 0.6s ease;
        }

        .signup-header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .signup-header p {
            font-size: 0.95rem;
            opacity: 0.95;
        }

        .signup-content {
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

        .alert a {
            color: inherit;
            font-weight: 700;
            text-decoration: underline;
        }

        /* Form */
        .signup-form {
            display: flex;
            flex-direction: column;
            gap: 18px;
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

        .password-strength {
            font-size: 0.8rem;
            margin-top: 4px;
            font-weight: 600;
        }

        .strength-weak {
            color: #dc2626;
        }

        .strength-medium {
            color: #f59e0b;
        }

        .strength-strong {
            color: #16a34a;
        }

        /* Submit Button */
        .btn-signup {
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

        .btn-signup:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(49, 17, 17, 0.3);
        }

        .btn-signup:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Divider */
        .divider {
            text-align: center;
            margin: 25px 0;
            position: relative;
            color: #999;
            font-weight: 600;
        }

        .divider::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            width: 45%;
            height: 2px;
            background: #e5e7eb;
        }

        .divider::after {
            content: '';
            position: absolute;
            right: 0;
            top: 50%;
            width: 45%;
            height: 2px;
            background: #e5e7eb;
        }

        /* Login Link */
        .login-text {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 0.95rem;
        }

        .login-text a {
            color: #311111;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.3s ease;
        }

        .login-text a:hover {
            color: #1F0A0A;
            text-decoration: underline;
        }

        /* Terms Checkbox */
        .terms-section {
            display: flex;
            gap: 10px;
            font-size: 0.9rem;
            align-items: flex-start;
            margin-top: 15px;
        }

        .terms-section input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #311111;
            margin-top: 2px;
            flex-shrink: 0;
        }

        .terms-section label {
            cursor: pointer;
            color: #666;
            line-height: 1.5;
        }

        .terms-section a {
            color: #311111;
            text-decoration: none;
            font-weight: 600;
        }

        .terms-section a:hover {
            text-decoration: underline;
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
            .signup-container {
                padding: 20px;
            }

            .signup-card {
                border-radius: 20px;
            }

            .signup-header {
                padding: 30px 20px;
            }

            .signup-header h1 {
                font-size: 1.5rem;
            }

            .signup-header .logo-icon {
                font-size: 2.5rem;
            }

            .signup-header p {
                font-size: 0.9rem;
            }

            .signup-content {
                padding: 25px 20px;
            }

            .signup-form {
                gap: 16px;
            }

            .form-group label {
                font-size: 0.9rem;
            }

            .form-group input {
                padding: 12px 14px 12px 40px;
                font-size: 0.95rem;
            }
        }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="signup-container">
    <div class="signup-card">
        <!-- Header -->
        <div class="signup-header">
            <div class="logo-icon">👤</div>
            <h1>Create Account</h1>
            <p>Join Crave & Order Today</p>
        </div>

        <!-- Content -->
        <div class="signup-content">
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

            <form method="POST" action="signup.php" class="signup-form" id="signupForm" novalidate>
                <input type="hidden" name="register" value="1">
                <!-- Name Field -->
                <div class="form-group">
                    <label for="name">
                        <i class="fas fa-user"></i> Full Name
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-user"></i>
                        <input type="text" id="name" name="name" placeholder="Enter your full name"
                               value="<?= htmlspecialchars(isset($_POST['name']) ? $_POST['name'] : '') ?>" maxlength="50" required>
                    </div>
                    <span class="error-text" id="nameError"></span>
                </div>

                <!-- Email Field -->
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i> Email Address
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" placeholder="Enter your email address"
                               value="<?= htmlspecialchars(isset($_POST['email']) ? $_POST['email'] : '') ?>" required>
                    </div>
                    <span class="error-text" id="emailError"></span>
                </div>

                <!-- Mobile Field -->
                <div class="form-group">
                    <label for="mobile">
                        <i class="fas fa-phone"></i> Mobile Number
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-phone"></i>
                        <input type="text" id="mobile" name="mobile" placeholder="Enter 10-digit mobile number"
                               value="<?= htmlspecialchars(isset($_POST['mobile']) ? $_POST['mobile'] : '') ?>" maxlength="10" required>
                    </div>
                    <span class="error-text" id="mobileError"></span>
                </div>

                <!-- Password Field -->
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" 
                               placeholder="Enter password (min 6 characters)" required>
                    </div>
                    <span class="error-text" id="passwordError"></span>
                    <span class="password-strength" id="strengthIndicator"></span>
                </div>

                <!-- Confirm Password Field -->
                <div class="form-group">
                    <label for="confirmPassword">
                        <i class="fas fa-lock"></i> Confirm Password
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="confirmPassword" name="confirmPassword" 
                               placeholder="Confirm your password" required>
                    </div>
                    <span class="error-text" id="confirmError"></span>
                </div>

                <!-- Terms & Conditions -->
                <div class="terms-section">
                    <input type="checkbox" id="terms" name="terms" required>
                    <label for="terms">
                        I agree to the <a href="terms.php" target="_blank">Terms &amp; Conditions</a> and 
                        <a href="privacy.php" target="_blank">Privacy Policy</a>
                    </label>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn-signup" id="signupBtn">
                    <i class="fas fa-user-check"></i> Create Account
                </button>

                <!-- Divider -->
                <div class="divider">OR</div>

                <!-- Login Link -->
                <p class="login-text">
                    Already have an account? <a href="login.php">Login here</a>
                </p>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<script>
// Name Validation
function validateName(name) {
    if (!name) {
        return { valid: false, message: 'Name is required' };
    }
    if (name.length < 3) {
        return { valid: false, message: 'Name must be at least 3 characters' };
    }
    if (!/^[a-zA-Z\s'-]+$/.test(name)) {
        return { valid: false, message: 'Name can only contain letters, spaces, hyphens, and apostrophes' };
    }
    return { valid: true, message: '' };
}

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

// Mobile Validation
function validateMobile(mobile) {
    if (!mobile) {
        return { valid: false, message: 'Mobile number is required' };
    }
    if (!/^\d{10}$/.test(mobile)) {
        return { valid: false, message: 'Enter a valid 10-digit mobile number' };
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

// Password Strength Indicator
function getPasswordStrength(password) {
    let strength = 'weak';
    let hasUpper = /[A-Z]/.test(password);
    let hasLower = /[a-z]/.test(password);
    let hasNumber = /[0-9]/.test(password);
    let hasSpecial = /[!@#$%^&*]/.test(password);
    let count = [hasUpper, hasLower, hasNumber, hasSpecial].filter(Boolean).length;
    
    if (password.length >= 8 && count >= 3) strength = 'strong';
    else if (password.length >= 6 && count >= 2) strength = 'medium';
    
    return strength;
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

// Real-time Validation
document.addEventListener('DOMContentLoaded', function() {
    const nameInput = document.getElementById('name');
    const emailInput = document.getElementById('email');
    const mobileInput = document.getElementById('mobile');
    const passwordInput = document.getElementById('password');
    const confirmInput = document.getElementById('confirmPassword');
    const signupForm = document.getElementById('signupForm');
    const signupBtn = document.getElementById('signupBtn');
    const strengthIndicator = document.getElementById('strengthIndicator');

    // Name validation
    nameInput?.addEventListener('blur', function() {
        const result = validateName(this.value);
        if (!result.valid && this.value) {
            showError('name', 'nameError', result.message);
        } else {
            showSuccess('name', 'nameError');
        }
    });

    nameInput?.addEventListener('input', function() {
        if (this.classList.contains('input-error')) {
            const result = validateName(this.value);
            if (result.valid) {
                showSuccess('name', 'nameError');
            }
        }
    });

    // Email validation
    emailInput?.addEventListener('blur', function() {
        const result = validateEmail(this.value);
        if (!result.valid && this.value) {
            showError('email', 'emailError', result.message);
        } else {
            showSuccess('email', 'emailError');
        }
    });

    emailInput?.addEventListener('input', function() {
        if (this.classList.contains('input-error')) {
            const result = validateEmail(this.value);
            if (result.valid) {
                showSuccess('email', 'emailError');
            }
        }
    });

    // Mobile validation
    mobileInput?.addEventListener('blur', function() {
        const result = validateMobile(this.value);
        if (!result.valid && this.value) {
            showError('mobile', 'mobileError', result.message);
        } else {
            showSuccess('mobile', 'mobileError');
        }
    });

    mobileInput?.addEventListener('input', function() {
        this.value = this.value.replace(/\D/g, '').substring(0, 10);
        if (this.classList.contains('input-error')) {
            const result = validateMobile(this.value);
            if (result.valid) {
                showSuccess('mobile', 'mobileError');
            }
        }
    });

    // Password validation
    passwordInput?.addEventListener('blur', function() {
        const result = validatePassword(this.value);
        if (!result.valid && this.value) {
            showError('password', 'passwordError', result.message);
        } else {
            showSuccess('password', 'passwordError');
        }
    });

    passwordInput?.addEventListener('input', function() {
        if (this.value) {
            const strength = getPasswordStrength(this.value);
            let strengthText = '';
            let strengthClass = '';
            
            if (strength === 'weak') {
                strengthText = '⚠️ Weak password';
                strengthClass = 'strength-weak';
            } else if (strength === 'medium') {
                strengthText = '📊 Medium strength';
                strengthClass = 'strength-medium';
            } else {
                strengthText = '✅ Strong password';
                strengthClass = 'strength-strong';
            }
            
            strengthIndicator.textContent = strengthText;
            strengthIndicator.className = 'password-strength ' + strengthClass;
        } else {
            strengthIndicator.textContent = '';
        }

        if (this.classList.contains('input-error')) {
            const result = validatePassword(this.value);
            if (result.valid) {
                showSuccess('password', 'passwordError');
            }
        }
    });

    // Confirm password validation
    confirmInput?.addEventListener('blur', function() {
        const password = passwordInput.value;
        if (!this.value) {
            showError('confirmPassword', 'confirmError', 'Please confirm your password');
        } else if (this.value !== password) {
            showError('confirmPassword', 'confirmError', 'Passwords do not match');
        } else {
            showSuccess('confirmPassword', 'confirmError');
        }
    });

    confirmInput?.addEventListener('input', function() {
        if (this.classList.contains('input-error')) {
            const password = passwordInput.value;
            if (this.value === password && this.value) {
                showSuccess('confirmPassword', 'confirmError');
            }
        }
    });

    // Form submission validation
    signupForm?.addEventListener('submit', function(e) {
        const name = nameInput.value;
        const email = emailInput.value;
        const mobile = mobileInput.value;
        const password = passwordInput.value;
        const confirm = confirmInput.value;
        const termsCheckbox = document.getElementById('terms');

        const nameResult = validateName(name);
        const emailResult = validateEmail(email);
        const mobileResult = validateMobile(mobile);
        const passwordResult = validatePassword(password);

        let hasError = false;

        if (!nameResult.valid) {
            e.preventDefault();
            showError('name', 'nameError', nameResult.message);
            hasError = true;
        }

        if (!emailResult.valid) {
            e.preventDefault();
            showError('email', 'emailError', emailResult.message);
            hasError = true;
        }

        if (!mobileResult.valid) {
            e.preventDefault();
            showError('mobile', 'mobileError', mobileResult.message);
            hasError = true;
        }

        if (!passwordResult.valid) {
            e.preventDefault();
            showError('password', 'passwordError', passwordResult.message);
            hasError = true;
        }

        if (!confirm) {
            e.preventDefault();
            showError('confirmPassword', 'confirmError', 'Please confirm your password');
            hasError = true;
        } else if (password !== confirm) {
            e.preventDefault();
            showError('confirmPassword', 'confirmError', 'Passwords do not match');
            hasError = true;
        }

        if (!termsCheckbox.checked) {
            e.preventDefault();
            alert('Please accept the Terms & Conditions');
            hasError = true;
        }

        if (!hasError) {
            setTimeout(function() {
                signupBtn.disabled = true;
                signupBtn.innerHTML = '<span class="spinner"></span> Creating Account...';
            }, 50);
        }
    });
});
</script>

</body>
</html>
