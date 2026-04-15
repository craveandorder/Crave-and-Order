<?php
require_once 'db.php';

$error = '';
$success = '';

// If already logged in, redirect
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Handle login form POST
if (isset($_POST['submit'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if ($username == '' && $password == '') {
        $error = 'Please enter username and password.';
    } elseif ($username == '') {
        $error = 'Username (email) is required.';
    } elseif ($password == '') {
        $error = 'Password is required.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        // Fetch user by email
        $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            // ✅ BUG FIX: Set ALL required session variables so cart/checkout works
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['user_name']  = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role']  = $user['role'];
            $_SESSION['isLoggedIn'] = true;   // ← This was missing in the original JS code!

            header('Location: index.php');
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Crave &amp; Order</title>
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

        .login-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            animation: fadeIn 0.6s ease;
        }

        .login-card {
            background: #fff;
            border-radius: 25px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.12);
            width: 100%;
            max-width: 450px;
            overflow: hidden;
        }

        .login-header {
            background: linear-gradient(135deg, #311111 0%, #6B3C3C 100%);
            padding: 40px 30px;
            text-align: center;
            color: #fff;
        }

        .login-header .logo-icon {
            font-size: 3rem;
            margin-bottom: 15px;
            animation: slideDown 0.6s ease;
        }

        .login-header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .login-header p {
            font-size: 0.95rem;
            opacity: 0.95;
        }

        .login-content {
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
        .login-form {
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

        .toggle-password {
            position: absolute;
            right: 16px;
            cursor: pointer;
            color: #311111;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }

        .toggle-password:hover {
            transform: scale(1.1);
        }

        /* Login Options */
        .login-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9rem;
        }

        .login-options label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            color: #666;
            font-weight: 500;
        }

        .login-options label input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #311111;
        }

        .login-options a {
            color: #311111;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .login-options a:hover {
            text-decoration: underline;
            color: #1F0A0A;
        }

        /* Login Button */
        .btn-login {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #311111 0%, #6B3C3C 100%);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 10px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(49, 17, 17, 0.35);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .btn-login:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
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

        /* Social Login */
        .social-login {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .social-btn {
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            background: #fafafa;
            color: #333;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 0.9rem;
        }

        .social-btn:hover {
            border-color: #311111;
            background: #fff;
            transform: translateY(-2px);
        }

        .social-btn i {
            font-size: 1.1rem;
        }

        /* Signup Link */
        .signup-text {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 0.95rem;
        }

        .signup-text a {
            color: #311111;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.3s ease;
        }

        .signup-text a:hover {
            color: #1F0A0A;
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
            margin-right: 8px;
        }

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
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        /* Responsive */
        @media (max-width: 600px) {
            .login-container {
                padding: 20px;
            }

            .login-card {
                border-radius: 20px;
            }

            .login-header {
                padding: 30px 20px;
            }

            .login-header h1 {
                font-size: 1.5rem;
            }

            .login-header .logo-icon {
                font-size: 2.5rem;
            }

            .login-content {
                padding: 25px 20px;
            }

            .social-login {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="login-container">
    <div class="login-card">
        <!-- Header -->
        <div class="login-header">
            <div class="logo-icon">🔐</div>
            <h1>Welcome Back</h1>
            <p>Login to your Crave & Order account</p>
        </div>

        <!-- Content -->
        <div class="login-content">
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

            <form method="POST" action="login.php" class="login-form" id="loginForm" novalidate>
                <input type="hidden" name="submit" value="1">
                <!-- Email Field -->
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i> Email Address
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="username" placeholder="Enter your email address"
                               value="<?= htmlspecialchars(isset($_POST['username']) ? $_POST['username'] : '') ?>" required>
                    </div>
                    <span class="error-text" id="emailError"></span>
                </div>

                <!-- Password Field -->
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                        <span class="toggle-password" onclick="togglePassword()">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                    <span class="error-text" id="passwordError"></span>
                </div>

                <!-- Login Options -->
                <div class="login-options">
                    <label>
                        <input type="checkbox" name="remember">
                        Remember me
                    </label>
                    <a href="forgotpassword.php">Forgot Password?</a>
                </div>

                <!-- Submit Button -->
                <button type="submit" name="submit" class="btn-login" id="loginBtn">
                    <i class="fas fa-sign-in-alt"></i> Login Now
                </button>

                <!-- Divider -->
                <div class="divider">OR</div>

                <!-- Social Login -->
                <div class="social-login">
                    <button type="button" class="social-btn" title="Coming soon">
                        <i class="fab fa-google"></i> Google
                    </button>
                    <button type="button" class="social-btn" title="Coming soon">
                        <i class="fab fa-facebook"></i> Facebook
                    </button>
                </div>
            </form>

            <!-- Signup Link -->
            <p class="signup-text">
                Don't have an account? <a href="signup.php">Create one now</a>
            </p>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<script>
// Toggle Password Visibility
function togglePassword() {
    const password = document.getElementById('password');
    const icon = document.querySelector('.toggle-password i');
    
    if (password.type === 'password') {
        password.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        password.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
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
    input.classList.add('input-error');
    input.classList.remove('input-valid');
    errorEl.textContent = message;
    errorEl.classList.add('show');
}

// Show Success
function showSuccess(inputId, errorId) {
    const input = document.getElementById(inputId);
    const errorEl = document.getElementById(errorId);
    input.classList.remove('input-error');
    input.classList.add('input-valid');
    errorEl.textContent = '';
    errorEl.classList.remove('show');
}

// Real-time Validation
document.addEventListener('DOMContentLoaded', function() {
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const loginForm = document.getElementById('loginForm');
    const loginBtn = document.getElementById('loginBtn');

    // Email blur validation
    emailInput.addEventListener('blur', function() {
        const result = validateEmail(this.value);
        if (!result.valid && this.value) {
            showError('email', 'emailError', result.message);
        } else {
            showSuccess('email', 'emailError');
        }
    });

    // Email input validation
    emailInput.addEventListener('input', function() {
        if (this.classList.contains('input-error')) {
            const result = validateEmail(this.value);
            if (result.valid) {
                showSuccess('email', 'emailError');
            }
        }
    });

    // Password blur validation
    passwordInput.addEventListener('blur', function() {
        const result = validatePassword(this.value);
        if (!result.valid && this.value) {
            showError('password', 'passwordError', result.message);
        } else {
            showSuccess('password', 'passwordError');
        }
    });

    // Password input validation
    passwordInput.addEventListener('input', function() {
        if (this.classList.contains('input-error')) {
            const result = validatePassword(this.value);
            if (result.valid) {
                showSuccess('password', 'passwordError');
            }
        }
    });

    // Form submission validation
    loginForm.addEventListener('submit', function(e) {
        const email = emailInput.value;
        const password = passwordInput.value;
        
        const emailResult = validateEmail(email);
        const passwordResult = validatePassword(password);
        
        if (!emailResult.valid) {
            e.preventDefault();
            showError('email', 'emailError', emailResult.message);
            emailInput.focus();
            return;
        }
        
        if (!passwordResult.valid) {
            e.preventDefault();
            showError('password', 'passwordError', passwordResult.message);
            passwordInput.focus();
            return;
        }
        
        // Show loading state after brief delay so form submits first
        setTimeout(function() {
            loginBtn.disabled = true;
            loginBtn.innerHTML = '<span class="spinner"></span> Logging in...';
        }, 50);
    });
});
</script>

</body>
</html>
