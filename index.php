<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Don Felix - Smart Inventory System</title>
    <style>
        * {
            box-sizing: border-box;
        }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(rgba(11, 15, 25, 0.88), rgba(11, 15, 25, 0.95)), 
                        url('https://images.unsplash.com/photo-1555396273-367ea4eb4db5?auto=format&fit=crop&w=1600&q=80') no-repeat center center fixed;
            background-size: cover; 
            color: white; 
            margin: 0; 
            padding: 20px;
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh;
        }
        .card { 
            max-width: 600px; 
            width: 100%; 
            background: rgba(17, 24, 39, 0.95); 
            padding: 40px 30px; 
            border-radius: 14px; 
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.6); 
            border-top: 5px solid #ef4444;
            backdrop-filter: blur(8px);
        }
        .main-logo {
            width: 100%; 
            max-width: 240px; 
            height: auto; 
            margin: 0 auto 20px auto;
            display: block;
            filter: drop-shadow(0 0 12px rgba(239, 68, 68, 0.35));
        }
        h2 {
            color: #ffffff;
            font-size: 1.6rem;
            margin: 0 0 8px 0;
            font-weight: 700;
        }
        h3 { 
            color: #f97316; 
            font-size: 1.05rem; 
            margin: 0 0 18px 0; 
            text-transform: uppercase; 
            letter-spacing: 1.5px; 
            font-weight: 600;
        }
        p { 
            color: #9ca3af; 
            font-size: 0.95rem; 
            line-height: 1.6; 
            margin: 0 auto 28px auto; 
            max-width: 480px;
        }
        .btn-group {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn { 
            display: inline-block; 
            padding: 12px 28px; 
            text-decoration: none; 
            font-weight: 600; 
            border-radius: 6px; 
            transition: all 0.2s ease-in-out; 
            font-size: 0.95rem;
            min-width: 160px;
        }
        .btn-primary {
            background-color: #ef4444; 
            color: white; 
        }
        .btn-primary:hover { 
            background-color: #dc2626; 
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.4); 
        }
        .btn-secondary {
            background-color: #1f2937;
            color: #d1d5db;
            border: 1px solid #374151;
        }
        .btn-secondary:hover {
            background-color: #374151;
            color: #ffffff;
        }
        .footer-note {
            margin-top: 25px;
            font-size: 0.8rem;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="card">
        <img src="logo.jpg" class="main-logo" alt="Don Felix Sizzling House Logo" onerror="this.style.display='none'">
        <h2>Don Felix Sizzling House</h2>
        <h3>Smart Inventory Management System</h3>
        <p>A centralized platform for ingredient tracking, stock replenishment, minimum reorder alerts, and daily kitchen shift audits.</p>
        
        <div class="btn-group">
            <a href="login.php" class="btn btn-primary">Access Portal</a>
            <a href="register.php" class="btn btn-secondary">Staff Register</a>
        </div>

        <div class="footer-note">
            Web Development 2 Term Project • OLFU Antipolo
        </div>
    </div>
</body>
</html>