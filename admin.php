<?php
require_once 'db.php';

// Admin only
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: login.php');
    exit;
}

$msg = '';

// Handle product add
if (isset($_POST['action'])) {

    if ($_POST['action'] == 'add_product') {
        $name     = trim($_POST['name']);
        $price    = floatval($_POST['price']);
        $image    = trim($_POST['image']);
        $category = trim($_POST['category']);
        if ($name != '' && $price > 0 && $image != '' && $category != '') {
            $stmt = $conn->prepare("INSERT INTO products (name, price, image, category) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sdss", $name, $price, $image, $category);
            $stmt->execute();
            $stmt->close();
            $msg = '✅ Product added successfully!';
        } else {
            $msg = '⚠️ All fields are required.';
        }
    }

    if ($_POST['action'] == 'delete_product') {
        $id = intval($_POST['product_id']);
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        $msg = '🗑️ Product deleted.';
    }

    if ($_POST['action'] == 'update_order_status') {
        $id     = intval($_POST['order_id']);
        $status = $_POST['status'];
        $allowed = ['Placed', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];
        if ($id > 0 && in_array($status, $allowed)) {
            $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $status, $id);
            $stmt->execute();
            $stmt->close();
            $msg = '✅ Order status updated.';
        }
    }
}

// Stats
$totalProducts = $conn->query("SELECT COUNT(*) FROM products")->fetch_row()[0];
$totalOrders   = $conn->query("SELECT COUNT(*) FROM orders")->fetch_row()[0];
$totalUsers    = $conn->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetch_row()[0];
$totalRevenue  = $conn->query("SELECT SUM(total_amount) FROM orders WHERE status != 'Cancelled'")->fetch_row()[0];
if (!$totalRevenue) $totalRevenue = 0;

// All products
$products = $conn->query("SELECT * FROM products ORDER BY category, name")->fetch_all(MYSQLI_ASSOC);

// All orders
$orders = $conn->query("SELECT o.id, o.total_amount, o.status, o.payment_method, o.created_at, u.name AS customer, u.email FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 50")->fetch_all(MYSQLI_ASSOC);

// All users
$users = $conn->query("SELECT id, name, email, mobile, role, created_at FROM users ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Crave &amp; Order</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:Arial,sans-serif; }
        body { background:#f4f4f4; display:flex; }
        .sidebar { width:220px; background:#3b1414; color:white; height:100vh; padding-top:20px; position:sticky; top:0; }
        .sidebar h2 { text-align:center; margin-bottom:30px; font-size:18px; }
        .sidebar a { display:block; color:white; padding:12px 20px; text-decoration:none; font-size:14px; }
        .sidebar a:hover { background:#5a2020; }
        .main { flex:1; padding:25px; overflow-y:auto; }
        h1 { margin-bottom:20px; color:#3b1414; }
        .cards { display:flex; gap:15px; margin-bottom:30px; flex-wrap:wrap; }
        .card { flex:1; min-width:150px; background:white; padding:20px; border-radius:10px; box-shadow:0 3px 10px rgba(0,0,0,0.08); text-align:center; }
        .card h3 { font-size:28px; color:#6b1f1f; }
        .card p { color:#666; font-size:13px; margin-top:5px; }
        .msg { padding:12px 16px; border-radius:8px; margin-bottom:15px; background:#dcfce7; color:#166534; }
        .section { background:white; border-radius:10px; padding:20px; margin-bottom:25px; box-shadow:0 2px 8px rgba(0,0,0,0.07); }
        .section h2 { margin-bottom:15px; color:#3b1414; font-size:18px; }
        form.inline { display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end; margin-bottom:15px; }
        form.inline input, form.inline select { padding:8px 12px; border:1px solid #ccc; border-radius:6px; font-size:13px; }
        form.inline button, button.btn-admin { background:#793030; color:white; border:none; padding:9px 18px; border-radius:6px; cursor:pointer; font-size:13px; }
        button.del { background:#c0392b; color:white; border:none; padding:6px 12px; border-radius:5px; cursor:pointer; font-size:12px; }
        table { width:100%; border-collapse:collapse; font-size:13px; }
        th, td { padding:10px 12px; border-bottom:1px solid #eee; text-align:left; }
        th { background:#f9f0f0; color:#3b1414; }
        tr:hover { background:#fdf5f5; }
        select.status-sel { padding:5px; border-radius:4px; font-size:12px; }
        .tab-btns { display:flex; gap:10px; margin-bottom:20px; }
        .tab-btn { padding:10px 20px; border:none; border-radius:8px; cursor:pointer; background:#eee; font-size:14px; }
        .tab-btn.active { background:#793030; color:white; }
        .tab { display:none; }
        .tab.active { display:block; }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>🍰 Admin</h2>
    <a href="index.php">🏠 Back to Site</a>
    <a href="admin.php">📊 Dashboard</a>
    <a href="logout.php">🚪 Logout</a>
</div>

<div class="main">
    <h1>Admin Dashboard</h1>

    <?php if ($msg): ?>
        <div class="msg"><?= $msg ?></div>
    <?php endif; ?>

    <!-- Stats Cards -->
    <div class="cards">
        <div class="card"><h3><?= $totalProducts ?></h3><p>Products</p></div>
        <div class="card"><h3><?= $totalOrders ?></h3><p>Orders</p></div>
        <div class="card"><h3><?= $totalUsers ?></h3><p>Users</p></div>
        <div class="card"><h3>₹<?= number_format($totalRevenue, 0) ?></h3><p>Revenue</p></div>
    </div>

    <!-- Tabs -->
    <div class="tab-btns">
        <button class="tab-btn active" onclick="showTab('products')">🛍️ Products</button>
        <button class="tab-btn" onclick="showTab('orders')">📦 Orders</button>
        <button class="tab-btn" onclick="showTab('users')">👥 Users</button>
    </div>

    <!-- PRODUCTS TAB -->
    <div id="tab-products" class="tab active">
        <div class="section">
            <h2>Add New Product</h2>
            <form method="POST" class="inline">
                <input type="hidden" name="action" value="add_product">
                <input type="text" name="name" placeholder="Product Name" required>
                <input type="number" name="price" placeholder="Price (₹)" step="0.01" required>
                <input type="text" name="image" placeholder="Image path (e.g. brownies/classic.jpg)" required>
                <select name="category">
                    <option value="brownie">Brownie</option>
                    <option value="donut">Donut</option>
                    <option value="cake">Cake</option>
                    <option value="coffee">Coffee</option>
                    <option value="chocolate">Chocolate</option>
                    <option value="ice-cream">Ice Cream</option>
                    <option value="pastry">Pastry</option>
                    <option value="bread">Bread</option>
                </select>
                <button type="submit">Add Product</button>
            </form>
        </div>

        <div class="section">
            <h2>Product List (<?= count($products) ?>)</h2>
            <table>
                <thead><tr><th>#</th><th>Name</th><th>Price</th><th>Category</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($products as $p): ?>
                    <tr>
                        <td><?= $p['id'] ?></td>
                        <td><?= htmlspecialchars($p['name']) ?></td>
                        <td>₹<?= number_format($p['price'], 2) ?></td>
                        <td><?= $p['category'] ?></td>
                        <td>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this product?')">
                                <input type="hidden" name="action" value="delete_product">
                                <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                <button type="submit" class="del">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ORDERS TAB -->
    <div id="tab-orders" class="tab">
        <div class="section">
            <h2>Orders (<?= count($orders) ?>)</h2>
            <table>
                <thead><tr><th>#</th><th>Customer</th><th>Amount</th><th>Payment</th><th>Date</th><th>Status</th><th>Update</th></tr></thead>
                <tbody>
                <?php foreach ($orders as $o): ?>
                    <tr>
                        <td><?= $o['id'] ?></td>
                        <td><?= htmlspecialchars($o['customer']) ?><br><small style="color:#999;"><?= htmlspecialchars($o['email']) ?></small></td>
                        <td>₹<?= number_format($o['total_amount'], 2) ?></td>
                        <td><?= strtoupper($o['payment_method']) ?></td>
                        <td><?= date('d M Y', strtotime($o['created_at'])) ?></td>
                        <td><span style="color:green;"><?= $o['status'] ?></span></td>
                        <td>
                            <form method="POST" style="display:flex;gap:5px;align-items:center;">
                                <input type="hidden" name="action" value="update_order_status">
                                <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                <select name="status" class="status-sel">
                                    <?php foreach(['Placed','Processing','Shipped','Delivered','Cancelled'] as $s): ?>
                                        <option value="<?= $s ?>" <?= $o['status'] == $s ? 'selected' : '' ?>><?= $s ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="del" style="background:#2980b9;">Update</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- USERS TAB -->
    <div id="tab-users" class="tab">
        <div class="section">
            <h2>Registered Users (<?= count($users) ?>)</h2>
            <table>
                <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Mobile</th><th>Role</th><th>Joined</th></tr></thead>
                <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= $u['id'] ?></td>
                        <td><?= htmlspecialchars($u['name']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><?= htmlspecialchars($u['mobile']) ?></td>
                        <td><span style="<?= $u['role'] == 'admin' ? 'color:#c0392b;font-weight:bold;' : '' ?>"><?= $u['role'] ?></span></td>
                        <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
function showTab(name) {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    event.target.classList.add('active');
}
</script>
</body>
</html>
