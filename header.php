<?php if (session_status() != PHP_SESSION_ACTIVE) session_start(); ?>
<header>
    <h1>🍰 Crave &amp; Order</h1>
    <nav>
        <a href="index.php">Home</a>
        <a href="menu.php">Menu</a>
        <a href="contact.php">Contact</a>
        <a href="aboutus.php">About Us</a>
        <a href="cart.php">🛒 Cart
            <?php
            // Show cart count from session
            $cartCount = 0;
            if (!empty($_SESSION['cart'])) {
                foreach ($_SESSION['cart'] as $item) $cartCount = $cartCount + $item['quantity'];
            }
            if ($cartCount > 0) echo "($cartCount)";
            ?>
        </a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="profile.php">👤 <?= htmlspecialchars($_SESSION['user_name']) ?></a>
            <?php if ($_SESSION['user_role'] == 'admin'): ?>
                <a href="admin.php">⚙️ Admin</a>
            <?php endif; ?>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
            <a href="signup.php">Sign Up</a>
        <?php endif; ?>
    </nav>
</header>
