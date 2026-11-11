<?php
require 'db.php';
session_start();
$msg = "";

// Display banner if redirected from registration
if (isset($_GET['registered'])) {
    $msg = "<p style='color:#10b981; font-size:0.9rem;'>Registration successful! Please log in.</p>";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Prepared statement against SQL injection
    $stmt = $conn->prepare("SELECT id, fullname, username, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['user']     = $user['fullname'];
            $_SESSION['role']     = $user['role'];

            // 3-Tier Role Routing
            if ($user['role'] === 'Admin') {
                header("Location: admin_dashboard.php");
            } elseif ($user['role'] === 'Client') {
                header("Location: pos_order.php");
            } else {
                header("Location: staff_portal.php");
            }
            exit();
        } else {
            $msg = "<p style='color:#f87171;'>Incorrect password.</p>";
        }
    } else {
        $msg = "<p style='color:#f87171;'>User not found.</p>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Don Felix Sizzling House</title>
    <style>
        body { font-family: sans-serif; background-color: #0b0f19; color: white; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 15px; box-sizing: border-box; }
        .box { background: #111827; padding: 35px; border-radius: 10px; width: 100%; max-width: 380px; box-shadow: 0 4px 15px rgba(0,0,0,0.5); text-align: center; border-top: 4px solid #ef4444; box-sizing: border-box; }
        h2 { color: #ef4444; margin: 0 0 10px 0; }
        p.sub { color: #9ca3af; font-size: 0.85rem; margin-bottom: 20px; }
        .group { margin-bottom: 18px; text-align: left; }
        label { display: block; color: #9ca3af; margin-bottom: 6px; font-size: 0.9rem; }
        input { width: 100%; padding: 11px; border: 1px solid #374151; background: #1f2937; color: white; border-radius: 6px; box-sizing: border-box; font-size: 1rem; }
        input:focus { outline: none; border-color: #ef4444; }
        .btn { width: 100%; background: #ef4444; color: white; border: none; padding: 12px; font-weight: bold; border-radius: 6px; cursor: pointer; font-size: 1rem; margin-top: 5px; }
        .btn:hover { background: #dc2626; }
        a { color: #ef4444; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="box">
        <h2>Don Felix System</h2>
        <p class="sub">Smart Inventory Management Portal</p>
        <?php echo $msg; ?>
        <form action="login.php" method="POST">
            <div class="group"><label>Username</label><input type="text" name="username" required></div>
            <div class="group"><label>Password</label><input type="password" name="password" required></div>
            <button type="submit" class="btn">Log In</button>
        </form>
        <p style="font-size:0.85rem; margin-top:20px; color:#9ca3af;">Need staff credentials? <a href="register.php">Register</a></p>
    </div>
</body>
</html>