<?php
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$success = '';
$error   = '';

// Fetch fresh user data first
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    header('Location: login.php');
    exit;
}

// Handle profile update
if (isset($_POST['save_profile'])) {
    $name   = trim($_POST['name']);
    $mobile = trim($_POST['mobile']);
    $bio    = trim($_POST['bio']);

    if ($name == '' || $mobile == '') {
        $error = 'Name and mobile are required.';
    } elseif (!preg_match('/^\d{10}$/', $mobile)) {
        $error = 'Enter a valid 10-digit mobile number.';
    } else {
        // Handle image upload
        $profileImage = isset($user['profile_image']) ? $user['profile_image'] : 'default-avatar.png';

        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            $fileType = $_FILES['profile_image']['type'];
            $maxSize = 2 * 1024 * 1024;

            if (!in_array($fileType, $allowedTypes)) {
                $error = 'Only JPG, PNG, and WebP images are allowed.';
            } elseif ($_FILES['profile_image']['size'] > $maxSize) {
                $error = 'Image size must be under 2MB.';
            } else {
                $ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
                $newFileName = 'profile_' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;
                $uploadPath = 'uploads/profiles/' . $newFileName;

                if (!file_exists('uploads/profiles')) {
                    mkdir('uploads/profiles', 0755, true);
                }

                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $uploadPath)) {
                    $profileImage = $newFileName;
                }
            }
        }

        if ($error == '') {
            $stmt = $conn->prepare("UPDATE users SET name = ?, mobile = ?, bio = ?, profile_image = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $name, $mobile, $bio, $profileImage, $_SESSION['user_id']);
            $stmt->execute();
            $stmt->close();
            $_SESSION['user_name'] = $name;
            $success = 'Profile updated successfully!';
            
            // Refresh user data
            $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        }
    }
}

// Get order stats
$stmt = $conn->prepare("SELECT COUNT(*) as total_orders, SUM(total_amount) as total_spent FROM orders WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get recent orders
$stmt = $conn->prepare("SELECT id, total_amount, status, created_at FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$recentOrders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$profileImgSrc = !empty($user['profile_image']) && file_exists('uploads/profiles/' . $user['profile_image'])
    ? 'uploads/profiles/' . $user['profile_image']
    : 'https://ui-avatars.com/api/?name=' . urlencode($user['name']) . '&size=150&background=ff6b35&color=fff';

$memberSince = date('F Y', strtotime(isset($user['created_at']) ? $user['created_at'] : 'now'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Crave &amp; Order</title>
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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #2c3e50;
        }

        .profile-page {
            min-height: 100vh;
            padding: 70px 20px 40px;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8ec 100%);
            animation: fadeIn 0.6s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .profile-container {
            width: min(900px, calc(100% - 40px));
            max-width: 900px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 30px;
            padding: 40px 20px;
            align-items: center;
        }

        .profile-sidebar {
            position: static;
            width: 100%;
            max-width: 900px;
        }

        .profile-content {
            width: 100%;
            max-width: 900px;
        }

        .profile-card {
            width: 100%;
            max-width: 520px;
            margin: 0 auto;
            background: #fff;
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04), 0 20px 60px rgba(0, 0, 0, 0.1);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(139, 69, 19, 0.08);
            background: linear-gradient(to bottom, #fff, #fafafa);
        }

        .profile-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08), 0 30px 80px rgba(139, 69, 19, 0.12);
            transform: translateY(-8px);
        }

        .profile-cover {
            height: 120px;
            background: linear-gradient(135deg, #311111 0%, #6B3C3C 50%, #8B4513 100%);
            position: relative;
            overflow: hidden;
        }

        .profile-cover::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%);
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        .profile-avatar-wrapper {
            position: relative;
            width: 130px;
            height: 130px;
            margin: -65px auto 0;
            animation: slideDown 0.6s ease;
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

        .profile-avatar {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid #fff;
            box-shadow: 0 8px 30px rgba(139, 69, 19, 0.2);
            transition: all 0.3s ease;
        }

        .profile-avatar:hover {
            transform: scale(1.03);
        }

        .avatar-edit-btn {
            position: absolute;
            bottom: 8px;
            right: 8px;
            width: 42px;
            height: 42px;
            background: linear-gradient(135deg, #8B4513 0%, #A0522D 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 6px 20px rgba(139, 69, 19, 0.5);
            border: 3px solid #fff;
            font-size: 1rem;
        }

        .avatar-edit-btn:hover {
            background: linear-gradient(135deg, #A0522D 0%, #8B4513 100%);
            transform: scale(1.2) rotate(-10deg);
            box-shadow: 0 10px 30px rgba(139, 69, 19, 0.6);
        }

        .profile-info {
            padding: 25px;
            text-align: center;
        }

        .profile-name {
            font-size: 1.6rem;
            font-weight: 700;
            color: #1a1a2e;
            margin: 0 0 5px;
            letter-spacing: -0.5px;
        }

        .profile-email,
        .profile-member {
            font-size: 0.88rem;
            color: #666;
            margin: 8px 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .profile-email:hover,
        .profile-member:hover {
            color: #8B4513;
        }

        .profile-email i,
        .profile-member i {
            color: #8B4513;
            width: 16px;
        }

        .profile-bio {
            margin-top: 15px;
            padding: 15px 20px;
            background: linear-gradient(135deg, #f8f9fa 0%, #f0f0f0 100%);
            border-left: 4px solid #8B4513;
            border-radius: 8px;
            font-size: 0.9rem;
            color: #555;
            line-height: 1.6;
            font-style: italic;
        }

        .profile-stats {
            display: flex;
            border-top: 1px solid #f0f0f0;
            padding: 25px 10px;
            background: linear-gradient(to bottom, transparent, rgba(139, 69, 19, 0.03));
        }

        .stat-item {
            flex: 1;
            text-align: center;
            border-right: 1px solid #f0f0f0;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .stat-item:hover {
            transform: scale(1.08);
        }

        .stat-item:last-child {
            border-right: none;
        }

        .stat-value {
            display: block;
            font-size: 1.6rem;
            font-weight: 700;
            color: #8B4513;
            transition: all 0.3s ease;
        }

        .stat-label {
            display: block;
            font-size: 0.75rem;
            color: #999;
            margin-top: 8px;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            font-weight: 600;
        }

        .btn-logout {
            display: block;
            margin: 0 20px 20px;
            padding: 13px 22px;
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #dc2626;
            text-align: center;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid #fecaca;
            box-shadow: 0 3px 12px rgba(220, 38, 38, 0.15);
        }

        .btn-logout:hover {
            background: linear-gradient(135deg, #ff4757 0%, #ff6b7a 100%);
            color: #fff;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 71, 87, 0.35);
            border-color: #ff4757;
        }

        .profile-content {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        .content-card {
            width: 100%;
            background: #fff;
            border-radius: 22px;
            padding: 35px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02), 0 12px 35px rgba(0, 0, 0, 0.08);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(139, 69, 19, 0.08);
            animation: fadeIn 0.5s ease forwards;
            background: linear-gradient(135deg, #fff 0%, #fafafa 100%);
            position: relative;
        }

        .content-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(139, 69, 19, 0.2), transparent);
            border-radius: 22px 22px 0 0;
        }

        .content-card:hover {
            box-shadow: 0 8px 12px rgba(0, 0, 0, 0.04), 0 20px 50px rgba(139, 69, 19, 0.12);
            transform: translateY(-4px);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f5f5f5;
            position: relative;
        }

        .card-header::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            height: 2px;
            background: linear-gradient(90deg, #8B4513, transparent);
            width: 50px;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .card-header h2 {
            font-size: 1.38rem;
            font-weight: 700;
            color: #1a1a2e;
            display: flex;
            align-items: center;
            gap: 14px;
            margin: 0;
            letter-spacing: -0.5px;
        }

        .card-header h2 i {
            color: #8B4513;
            font-size: 1.4rem;
        }

        .alert {
            padding: 16px 22px;
            border-radius: 14px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 14px;
            font-weight: 500;
            animation: slideDown 0.4s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
            border-left: 4px solid;
        }

        .alert-error {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #dc2626;
            border-left-color: #dc2626;
        }

        .alert-success {
            background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
            color: #16a34a;
            border-left-color: #16a34a;
        }

        .profile-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group label {
            font-weight: 600;
            color: #2c3e50;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-group label i {
            color: #8B4513;
            width: 16px;
        }

        .form-group input,
        .form-group textarea {
            padding: 16px 18px;
            border: 2px solid #e8e8e8;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: linear-gradient(135deg, #fafafa 0%, #fff 100%);
            font-family: inherit;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #8B4513;
            background: #fff;
            box-shadow: 0 0 0 5px rgba(139, 69, 19, 0.12), 0 4px 12px rgba(139, 69, 19, 0.1);
            transform: translateY(-2px);
        }

        .form-group input:disabled {
            background: #f0f0f0;
            color: #999;
            cursor: not-allowed;
        }

        .form-group small {
            color: #999;
            font-size: 0.8rem;
            font-style: italic;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .btn-primary {
            padding: 15px 36px;
            background: linear-gradient(135deg, #8B4513 0%, #A0522D 100%);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            align-self: flex-start;
            box-shadow: 0 6px 20px rgba(139, 69, 19, 0.25);
            position: relative;
            overflow: hidden;
        }

        .btn-primary::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.5s, height 0.5s;
        }

        .btn-primary:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(139, 69, 19, 0.4);
            background: linear-gradient(135deg, #A0522D 0%, #8B4513 100%);
        }

        .btn-primary:hover::before {
            width: 300px;
            height: 300px;
        }

        .orders-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 24px;
            background: linear-gradient(135deg, #fafafa 0%, #f5f5f5 100%);
            border-radius: 16px;
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(139, 69, 19, 0.08);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
        }

        .order-item:hover {
            background: linear-gradient(135deg, #fff 0%, #fafafa 100%);
            transform: translateX(8px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08), 0 8px 25px rgba(139, 69, 19, 0.1);
            border: 1px solid rgba(139, 69, 19, 0.12);
        }

        .order-info {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .order-id {
            font-weight: 700;
            color: #1a1a2e;
            font-size: 1.05rem;
        }

        .order-date {
            font-size: 0.85rem;
            color: #999;
        }

        .order-details {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .order-amount {
            font-weight: 700;
            color: #27ae60;
            font-size: 1.15rem;
        }

        .order-status {
            padding: 7px 16px;
            border-radius: 22px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .status-delivered { 
            background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
            color: #16a34a; 
        }
        .status-pending { 
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #d97706; 
        }
        .status-processing { 
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #2563eb; 
        }
        .status-cancelled { 
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #dc2626; 
        }
        .status-default { 
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            color: #6b7280; 
        }

        .order-view-btn {
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #fff 0%, #fafafa 100%);
            border-radius: 11px;
            color: #8B4513;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            border: 1px solid #f0f0f0;
            font-size: 1rem;
        }

        .order-view-btn:hover {
            background: linear-gradient(135deg, #8B4513 0%, #A0522D 100%);
            color: #fff;
            transform: scale(1.15) rotate(5deg);
            box-shadow: 0 8px 22px rgba(139, 69, 19, 0.3);
        }

        .empty-state {
            text-align: center;
            padding: 50px 20px;
        }

        .empty-state i {
            font-size: 3.5rem;
            color: #ddd;
            margin-bottom: 20px;
            opacity: 0.7;
        }

        .empty-state p {
            color: #999;
            margin-bottom: 25px;
            font-size: 1.1rem;
        }

        .btn-secondary {
            display: inline-block;
            padding: 13px 30px;
            background: linear-gradient(135deg, #f0f0f0 0%, #e8e8e8 100%);
            color: #333;
            text-decoration: none;
            border-radius: 11px;
            font-weight: 600;
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid #e0e0e0;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.06);
        }

        .btn-secondary:hover {
            background: linear-gradient(135deg, #ff6b35 0%, #ff8c5a 100%);
            color: #fff;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 107, 53, 0.35);
            border-color: #ff6b35;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .action-card {
            background: #fff;
            padding: 35px 25px;
            border-radius: 18px;
            text-align: center;
            text-decoration: none;
            color: #333;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.03), 0 10px 30px rgba(0, 0, 0, 0.08);
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(139, 69, 19, 0.06);
            background: linear-gradient(135deg, #fff 0%, #fafafa 100%);
            position: relative;
            overflow: hidden;
        }

        .action-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 107, 53, 0.08), transparent);
            transition: left 0.5s ease;
        }

        .action-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.05), 0 25px 60px rgba(255, 107, 53, 0.2);
            border: 1px solid rgba(255, 107, 53, 0.15);
        }

        .action-card:hover::before {
            left: 100%;
        }

        .action-card:hover i {
            color: #ff6b35;
            transform: scale(1.35) rotate(8deg);
        }

        .action-card i {
            font-size: 2.2rem;
            color: #8B4513;
            margin-bottom: 18px;
            display: block;
            transition: all 0.35s ease;
        }

        .action-card span {
            font-weight: 700;
            font-size: 0.98rem;
            letter-spacing: 0.3px;
            display: block;
        }

        @media (max-width: 992px) {
            .quick-actions {
                grid-template-columns: repeat(2, 1fr);
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }

        @media (max-width: 600px) {
            .profile-container {
                padding: 20px 15px;
            }
            
            .content-card {
                padding: 20px;
            }

            .order-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
            
            .order-details {
                width: 100%;
                justify-content: space-between;
            }
            
            .quick-actions {
                grid-template-columns: 1fr;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .profile-name {
                font-size: 1.3rem;
            }

            .card-header h2 {
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<main class="profile-page">
    <div class="profile-container">
        
        <!-- Left Sidebar - Profile Card -->
        <aside class="profile-sidebar">
            <div class="profile-card">
                <div class="profile-cover"></div>
                <div class="profile-avatar-wrapper">
                    <img id="profileImg" class="profile-avatar" src="<?= htmlspecialchars($profileImgSrc) ?>" alt="<?= htmlspecialchars($user['name']) ?>">
                    <label for="imageUpload" class="avatar-edit-btn" title="Change photo">
                        <i class="fas fa-camera"></i>
                    </label>
                </div>
                <div class="profile-info">
                    <h1 class="profile-name"><?= htmlspecialchars($user['name']) ?></h1>
                    <p class="profile-email"><i class="fas fa-envelope"></i> <?= htmlspecialchars($user['email']) ?></p>
                    <p class="profile-member"><i class="fas fa-calendar-alt"></i> Member since <?= $memberSince ?></p>
                    <?php if (!empty($user['bio'])): ?>
                        <p class="profile-bio"><?= htmlspecialchars($user['bio']) ?></p>
                    <?php endif; ?>
                </div>
                
                <div class="profile-stats">
                    <div class="stat-item">
                        <span class="stat-value"><?= isset($stats['total_orders']) ? $stats['total_orders'] : 0 ?></span>
                        <span class="stat-label">Orders</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value">₹<?= number_format(isset($stats['total_spent']) ? $stats['total_spent'] : 0, 0) ?></span>
                        <span class="stat-label">Total Spent</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value"><?= count($recentOrders) > 0 ? '⭐' : '—' ?></span>
                        <span class="stat-label">Status</span>
                    </div>
                </div>
                
                <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </aside>

        <!-- Right Content Area -->
        <div class="profile-content">
            
            <!-- Edit Profile Section -->
            <section class="content-card">
                <div class="card-header">
                    <h2><i class="fas fa-user-edit"></i> Edit Profile</h2>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="profile.php" enctype="multipart/form-data" class="profile-form">
                    <input type="hidden" name="save_profile" value="1">
                    <input type="file" id="imageUpload" name="profile_image" accept="image/*" hidden>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name"><i class="fas fa-user"></i> Full Name</label>
                            <input type="text" id="name" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="mobile"><i class="fas fa-phone"></i> Mobile Number</label>
                            <input type="tel" id="mobile" name="mobile" value="<?= htmlspecialchars(isset($user['mobile']) ? $user['mobile'] : '') ?>" placeholder="10-digit number" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
                        <input type="email" id="email" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                        <small>Email cannot be changed</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="bio"><i class="fas fa-info-circle"></i> Bio</label>
                        <textarea id="bio" name="bio" rows="3" placeholder="Tell us about yourself..."><?= htmlspecialchars(isset($user['bio']) ? $user['bio'] : '') ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </form>
            </section>

            <!-- Recent Orders Section -->
            <section class="content-card">
                <div class="card-header">
                    <h2><i class="fas fa-receipt"></i> Recent Orders</h2>
                    <a href="orders.php" class="view-all-link" style="color: #ff6b35; text-decoration: none; font-weight: 600; font-size: 0.9rem; display: flex; align-items: center; gap: 5px;">View All <i class="fas fa-arrow-right"></i></a>
                </div>
                
                <?php if (empty($recentOrders)): ?>
                    <div class="empty-state">
                        <i class="fas fa-shopping-bag"></i>
                        <p>No orders yet</p>
                        <a href="menu.php" class="btn-secondary">Browse Menu</a>
                    </div>
                <?php else: ?>
                    <div class="orders-list">
                        <?php foreach ($recentOrders as $order):
                            $statusLower = strtolower($order['status']);
                            if ($statusLower == 'delivered') {
                                $statusClass = 'status-delivered';
                            } elseif ($statusLower == 'pending') {
                                $statusClass = 'status-pending';
                            } elseif ($statusLower == 'processing' || $statusLower == 'preparing') {
                                $statusClass = 'status-processing';
                            } elseif ($statusLower == 'cancelled') {
                                $statusClass = 'status-cancelled';
                            } else {
                                $statusClass = 'status-default';
                            }
                        ?>
                        <div class="order-item">
                            <div class="order-info">
                                <span class="order-id">#<?= $order['id'] ?></span>
                                <span class="order-date"><?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></span>
                            </div>
                            <div class="order-details">
                                <span class="order-amount">₹<?= number_format($order['total_amount'], 2) ?></span>
                                <span class="order-status <?= $statusClass ?>"><?= htmlspecialchars($order['status']) ?></span>
                                <a href="invoice.php?order=<?= $order['id'] ?>" class="order-view-btn" title="View Invoice">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Quick Actions -->
            <section class="quick-actions">
                <a href="menu.php" class="action-card">
                    <i class="fas fa-utensils"></i>
                    <span>Order Food</span>
                </a>
                <a href="orders.php" class="action-card">
                    <i class="fas fa-history"></i>
                    <span>Order History</span>
                </a>
                <a href="contact.php" class="action-card">
                    <i class="fas fa-envelope"></i>
                    <span>Contact Us</span>
                </a>
                <a href="faq.php" class="action-card">
                    <i class="fas fa-question-circle"></i>
                    <span>Help & FAQ</span>
                </a>
            </section>

        </div>
    </div>
</main>

<?php include 'footer.php'; ?>

<script>
// Image preview on upload
document.getElementById('imageUpload').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('profileImg').src = e.target.result;
        };
        reader.readAsDataURL(file);
    }
});
</script>

</body>
</html>