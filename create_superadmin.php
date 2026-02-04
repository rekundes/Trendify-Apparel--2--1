<?php
require_once 'config.php';

// Check if a superadmin already exists
$check_super = "SELECT COUNT(*) as count FROM users WHERE role = 'superadmin'";
$result = $conn->query($check_super);
$row = $result->fetch_assoc();

if ($row['count'] > 0) {
    die("❌ A superadmin account already exists. You can only have one superadmin.");
}

// Get form data or defaults
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$first_name = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
$last_name = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';

// If no POST data, show form
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Create Superadmin Account</title>
        <link rel="stylesheet" href="styles.css">
        <style>
            body {
                font-family: Inter, system-ui, Segoe UI, Arial, Helvetica, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0;
            }
            .container {
                background: white;
                padding: 40px;
                border-radius: 12px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                max-width: 400px;
                width: 100%;
            }
            h1 {
                text-align: center;
                color: #111827;
                margin: 0 0 10px 0;
                font-size: 24px;
            }
            .subtitle {
                text-align: center;
                color: #6b7280;
                margin-bottom: 30px;
                font-size: 14px;
            }
            .form-group {
                margin-bottom: 18px;
            }
            label {
                display: block;
                margin-bottom: 6px;
                color: #374151;
                font-weight: 500;
                font-size: 14px;
            }
            input {
                width: 100%;
                padding: 10px 12px;
                border: 1px solid #d1d5db;
                border-radius: 6px;
                font-size: 14px;
                box-sizing: border-box;
                transition: all 0.2s;
            }
            input:focus {
                outline: none;
                border-color: #667eea;
                box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            }
            .btn {
                width: 100%;
                padding: 12px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                border: none;
                border-radius: 6px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.2s;
                font-size: 14px;
            }
            .btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
            }
            .info {
                background: #f0f4ff;
                padding: 12px;
                border-radius: 6px;
                font-size: 13px;
                color: #374151;
                margin-bottom: 24px;
                line-height: 1.5;
            }
            .info strong {
                display: block;
                color: #667eea;
                margin-bottom: 4px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🔐 Create Superadmin</h1>
            <p class="subtitle">Set up the superadmin account for Trendify</p>
            
            <div class="info">
                <strong>⚠️ Important:</strong> The superadmin has full system access and can manage admins, users, and orders. Use a strong password.
            </div>

            <form method="POST">
                <div class="form-group">
                    <label for="first_name">First Name *</label>
                    <input type="text" id="first_name" name="first_name" required placeholder="John">
                </div>

                <div class="form-group">
                    <label for="last_name">Last Name *</label>
                    <input type="text" id="last_name" name="last_name" required placeholder="Doe">
                </div>

                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required placeholder="superadmin@trendify.com">
                </div>

                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" required placeholder="••••••••" minlength="8">
                </div>

                <button type="submit" class="btn">Create Superadmin Account</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Validate input
if (empty($email) || empty($password) || empty($first_name) || empty($last_name)) {
    die("❌ All fields are required");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("❌ Invalid email format");
}

if (strlen($password) < 8) {
    die("❌ Password must be at least 8 characters");
}

// Check if email already exists
$check_email = "SELECT user_id FROM users WHERE email = ?";
$stmt = $conn->prepare($check_email);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    die("❌ Email already registered");
}
$stmt->close();

// Hash password
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Create superadmin account
$insert_sql = "INSERT INTO users (email, password_hash, first_name, last_name, is_admin, role) 
               VALUES (?, ?, ?, ?, 1, 'superadmin')";
$insert_stmt = $conn->prepare($insert_sql);
$insert_stmt->bind_param("ssss", $email, $password_hash, $first_name, $last_name);

if ($insert_stmt->execute()) {
    $user_id = $insert_stmt->insert_id;
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Superadmin Created</title>
        <link rel="stylesheet" href="styles.css">
        <style>
            body {
                font-family: Inter, system-ui, Segoe UI, Arial, Helvetica, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0;
            }
            .container {
                background: white;
                padding: 40px;
                border-radius: 12px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                max-width: 500px;
                width: 100%;
                text-align: center;
            }
            .success {
                background: #d1fae5;
                color: #065f46;
                padding: 16px;
                border-radius: 8px;
                margin-bottom: 24px;
                font-weight: 600;
            }
            .info-box {
                background: #f3f4f6;
                padding: 20px;
                border-radius: 8px;
                margin: 20px 0;
                text-align: left;
            }
            .info-box p {
                margin: 8px 0;
                color: #374151;
                font-size: 14px;
            }
            .info-box strong {
                color: #667eea;
            }
            .btn {
                display: inline-block;
                padding: 12px 24px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                border: none;
                border-radius: 6px;
                font-weight: 600;
                cursor: pointer;
                text-decoration: none;
                transition: all 0.2s;
                font-size: 14px;
                margin-top: 16px;
            }
            .btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
            }
            h1 {
                color: #111827;
                margin: 0 0 10px 0;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>✅ Superadmin Account Created!</h1>
            <div class="success">Superadmin account has been successfully created</div>
            
            <div class="info-box">
                <p><strong>Account ID:</strong> #<?= $user_id ?></p>
                <p><strong>Name:</strong> <?= htmlspecialchars($first_name . ' ' . $last_name) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($email) ?></p>
                <p><strong>Role:</strong> Superadmin</p>
            </div>

            <div style="background: #fef3c7; padding: 16px; border-radius: 8px; margin: 20px 0; color: #92400e; font-size: 14px;">
                ⚠️ Save these credentials securely. You'll need them to log in to the superadmin dashboard.
            </div>

            <a href="sign-in.html" class="btn">Go to Login</a>
        </div>
    </body>
    </html>
    <?php
} else {
    die("❌ Error creating superadmin account: " . $conn->error);
}

$insert_stmt->close();
$conn->close();
?>
