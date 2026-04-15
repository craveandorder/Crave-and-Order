<?php
require_once 'db.php';
$success = '';
$error   = '';

if (isset($_POST['send_message'])) {
    $name    = trim($_POST['cname']);
    $email   = trim($_POST['cemail']);
    $message = trim($_POST['cmessage']);
    if ($name == '' || $email == '' || $message == '') {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address.';
    } else {
        $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, message) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $message);
        $stmt->execute();
        $stmt->close();
        $success = 'Your message has been sent! We will get back to you soon.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Crave &amp; Order</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include 'header.php'; ?>

<section class="contact-section">
    <h2>Contact Us</h2>
    <p class="contact-subtitle">Have a question, suggestion, or order request? We'd love to hear from you 💬</p>

    <div class="contact-container">
        <div class="contact-info">
            <h3>📍 Our Bakery</h3>
            <p><strong>Address:</strong> Rajkot, Gujarat, India</p>
            <p><strong>Phone-1:</strong> +91 98765 43210</p>
            <p><strong>Phone-2:</strong> +91 98795 37988</p>
            <p><strong>Email:</strong> craveorder@gmail.com</p>
            <p><strong>Hours:</strong> 10:00 AM - 12:00 PM</p>
            <div class="social-box">
                <h4>Follow Us</h4>
                <a href="#">📘 Facebook</a>
                <a href="#">📸 Instagram</a>
                <a href="#">🐦 Twitter</a>
            </div>
        </div>

        <div class="contact-form">
            <h3>✉ Send Message</h3>

            <?php if ($error): ?>
                <div style="background:#fee2e2;color:#b91c1c;padding:10px 15px;border-radius:8px;margin-bottom:15px;">⚠️ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div style="background:#dcfce7;color:#166534;padding:10px 15px;border-radius:8px;margin-bottom:15px;">✅ <?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="POST" action="contact.php">
                <input type="hidden" name="send_message" value="1">
                <input type="text"  name="cname"    placeholder="Your Name"  required value="<?= htmlspecialchars(isset($_POST['cname']) ? $_POST['cname'] : '') ?>">
                <input type="email" name="cemail"   placeholder="Your Email" required value="<?= htmlspecialchars(isset($_POST['cemail']) ? $_POST['cemail'] : '') ?>">
                <textarea name="cmessage" rows="5"  placeholder="Your Message" required><?= htmlspecialchars(isset($_POST['cmessage']) ? $_POST['cmessage'] : '') ?></textarea>
                <button type="submit">Send Message</button>
            </form>
        </div>
    </div>

    <div class="contact-map">
        <h2>📍 Find Us Here</h2>
        <iframe src="https://maps.google.com/maps?q=rajkot%20gujarat&t=&z=13&ie=UTF8&iwloc=&output=embed" style="width:100%;height:350px;border:0;border-radius:12px;"></iframe>
    </div>
</section>

<?php include 'footer.php'; ?>

</body>
</html>
