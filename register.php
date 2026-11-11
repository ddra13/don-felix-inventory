<?php
require 'db.php';
session_start();
$msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = trim($_POST['fullname']);
    $username = trim($_POST['username']);
    $role = $_POST['role'];
    $raw_password = $_POST['password'];

    // Enforce allowed ENUM values matching the database schema
    $allowed_roles = ['Admin', 'Staff', 'Client'];
    if (!in_array($role, $allowed_roles, true)) {
        $msg = "<p style='color:#f87171;'>Invalid role selected.</p>";
    } else {
        // Check if username already exists using prepared statements
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $check_stmt->bind_param("s", $username);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            $msg = "<p style='color:#f87171;'>Username is already taken.</p>";
        } else {
            $hashed_password = password_hash($raw_password, PASSWORD_DEFAULT);
            $insert_stmt = $conn->prepare("INSERT INTO users (fullname, username, role, password) VALUES (?, ?, ?, ?)");
            $insert_stmt->bind_param("ssss", $fullname, $username, $role, $hashed_password);

            if ($insert_stmt->execute()) {
                header("Location: login.php?registered=1");
                exit();
            } else {
                $msg = "<p style='color:#f87171;'>Registration failed. Please try again.</p>";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Don Felix System</title>
    <style>
        body { 
            font-family: sans-serif; 
            background-color: #0b0f19; 
            color: white; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; 
            margin: 0; 
            padding: 15px; 
            box-sizing: border-box; 
        }
        .box { 
            background: #111827; 
            padding: 30px; 
            border-radius: 10px; 
            width: 100%; 
            max-width: 420px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.5); 
            text-align: center; 
            border-top: 4px solid #ef4444; 
            box-sizing: border-box; 
        }
        h2 { 
            color: #ef4444; 
            margin: 0 0 5px 0; 
        }
        p.sub { 
            color: #9ca3af; 
            font-size: 0.85rem; 
            margin-bottom: 20px; 
        }
        .group { 
            margin-bottom: 15px; 
            text-align: left; 
        }
        label { 
            display: block; 
            color: #9ca3af; 
            margin-bottom: 6px; 
            font-size: 0.9rem; 
        }
        input, select { 
            width: 100%; 
            padding: 11px; 
            border: 1px solid #374151; 
            background: #1f2937; 
            color: white; 
            border-radius: 6px; 
            box-sizing: border-box; 
            font-size: 1rem; 
        }
        input:focus, select:focus {
            outline: none;
            border-color: #ef4444;
        }
        .btn { 
            width: 100%; 
            background: #ef4444; 
            color: white; 
            border: none; 
            padding: 12px; 
            font-weight: bold; 
            border-radius: 6px; 
            cursor: pointer; 
            font-size: 1rem; 
            margin-top: 10px; 
        }
        .btn:hover { 
            background: #dc2626; 
        }
        a { 
            color: #ef4444; 
            text-decoration: none; 
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="box">
        <h2>Create Account</h2>
        <p class="sub">Register authorized system personnel</p>
        <?php echo $msg; ?>
        <form action="register.php" method="POST">
            <div class="group">
                <label>Full Name</label>
                <input type="text" name="fullname" placeholder="e.g. Juan Dela Cruz" required>
            </div>
            <div class="group">
                <label>Username</label>
                <input type="text" name="username" placeholder="Choose a unique username" required>
            </div>
            <div class="group">
                <label>System Role</label>
                <select name="role" required>
                    <option value="Staff">Kitchen Staff / Inventory Custodian</option>
                    <option value="Admin">Restaurant Owner </option>
                    <option value="Client">Cashier / POS Terminal</option>
                </select>
            </div>
            <div class="group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Enter secure password" required>
            </div>
            <button type="submit" class="btn">Register Account</button>
        </form>
        <p style="font-size:0.9rem; margin-top:20px; color:#9ca3af;">
            Already have an account? <a href="login.php">Log in here</a>
        </p>
    </div>
</body>
</html>