<?php
require 'db.php';
session_start();

// 1. Authentication check
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// 2. Strict Role Guard: Only Admin and Staff are allowed
if ($_SESSION['role'] === 'Client') {
    header("Location: pos_order.php");
    exit();
}

$feedback = "";

// Handle Stock In / Stock Out / Waste
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['transact'])) {
    $id = intval($_POST['item_id']);
    $qty = floatval($_POST['qty']);
    $action =$_POST['action_type'];
    $notes = htmlspecialchars(trim($_POST['notes']));

    if ($qty > 0) {
        $item_stmt =$conn->prepare("SELECT item_name, current_stock, unit FROM inventory WHERE id = ?");
        $item_stmt->bind_param("i", $id);$item_stmt->execute();
        $item =$item_stmt->get_result()->fetch_assoc();

        if ($item) {
            if ($action === "STOCK IN") {
                $new_qty = $item['current_stock'] +$qty;
            } else {
                $new_qty = max(0, $item['current_stock'] -$qty);
            }

            $upd =$conn->prepare("UPDATE inventory SET current_stock = ? WHERE id = ?");
            $upd->bind_param("di", $new_qty, $id);$upd->execute();

            $log =$conn->prepare("INSERT INTO logs (item_id, item_name, action_type, quantity, unit, logged_by, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $log->bind_param("issdsss", $id,$item['item_name'], $action,$qty, $item['unit'],$_SESSION['user'], $notes);
            $log->execute();

            $feedback = "<p style='color:#10b981; font-weight:bold; margin:0;'>Updated {$item['item_name']} successfully.</p>";
        }
    }
}

$items =$conn->query("SELECT * FROM inventory ORDER BY category, item_name ASC");
$recent_logs =$conn->query("SELECT * FROM logs ORDER BY timestamp DESC LIMIT 8");
$low_stock_count =$conn->query("SELECT COUNT(*) as total FROM inventory WHERE current_stock <= min_alert")->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Portal - Don Felix Sizzling House</title>
    <style>
        body { 
            font-family: sans-serif; 
            background-color: #0b0f19; 
            color: white; 
            margin: 0; 
            padding: 15px; 
            box-sizing: border-box;
        }
        header { 
            background-color: #111827; 
            padding: 15px 20px; 
            border-radius: 8px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            border-bottom: 3px solid #ef4444; 
        }
        h1 { 
            margin: 0; 
            color: #ef4444; 
            font-size: 1.3rem; 
        }
        .nav-links {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 8px 14px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: bold;
            background-color: #374151;
            transition: background 0.2s;
        }
        .nav-links a:hover {
            background-color: #ef4444;
        }
        .nav-links a.logout {
            background-color: #1f2937;
            border: 1px solid #374151;
        }
        .nav-links a.logout:hover {
            background-color: #dc2626;
        }
        .banner { 
            background: #111827; 
            border-left: 4px solid #ef4444; 
            padding: 12px 20px; 
            border-radius: 6px; 
            margin: 15px 0; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
        }
        .sec { 
            background: #111827; 
            padding: 20px; 
            border-radius: 8px; 
            margin-bottom: 20px; 
            overflow-x: auto; 
        }
        h2 { 
            margin-top: 0; 
            color: #ef4444; 
            border-bottom: 1px solid #374151; 
            padding-bottom: 10px; 
            font-size: 1.1rem; 
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            min-width: 650px; 
        }
        th, td { 
            padding: 10px; 
            text-align: left; 
            border-bottom: 1px solid #1f2937; 
            font-size: 0.9rem; 
        }
        th { 
            background-color: #1f2937; 
            color: #ef4444; 
        }
        .badge { 
            padding: 4px 8px; 
            border-radius: 4px; 
            font-size: 0.75rem; 
            font-weight: bold; 
        }
        .badge-safe { 
            background: #10b981; 
        }
        .badge-low { 
            background: #ef4444; 
        }
        .action-form { 
            display: flex; 
            gap: 6px; 
            align-items: center; 
        }
        .action-form select, .action-form input { 
            background: #1f2937; 
            color: white; 
            border: 1px solid #374151; 
            padding: 5px; 
            border-radius: 4px; 
            font-size: 0.85rem; 
        }
        .btn-go { 
            background: #ef4444; 
            color: white; 
            border: none; 
            padding: 6px 12px; 
            border-radius: 4px; 
            cursor: pointer; 
            font-weight: bold; 
        }
        .btn-go:hover { 
            background: #dc2626; 
        }
    </style>
</head>
<body>
    <header>
        <div>
            <h1>Staff Kitchen Operations</h1>
            <small style="color:#9ca3af;">Logged in as: <strong><?php echo htmlspecialchars($_SESSION['user']); ?></strong> (<?php echo$_SESSION['role']; ?>)</small>
        </div>
        <div class="nav-links">
            <!-- Only Admin can navigate back to Admin Dashboard or POS -->
            <?php if ($_SESSION['role'] === 'Admin'): ?>
                <a href="admin_dashboard.php">Admin Dashboard</a>
                <a href="pos_order.php">Client POS</a>
            <?php endif; ?>
            <a href="logout.php" class="logout">Logout</a>
        </div>
    </header>

    <div class="banner">
        <span>Active Alerts: <strong><?php echo $low_stock_count; ?> items</strong> running low on stock.</span>
        <?php echo $feedback; ?>
    </div>

    <div class="sec">
        <h2>Ingredient Stock Levels & Logging</h2>
        <table>
            <thead>
                <tr>
                    <th>Item Name</th>
                    <th>Category</th>
                    <th>Current Stock</th>
                    <th>Status</th>
                    <th>Action (Stock In / Out)</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $items->fetch_assoc()):$is_low = $row['current_stock'] <=$row['min_alert'];
                ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($row['item_name']); ?></strong></td>
                    <td style="color:#9ca3af;"><?php echo htmlspecialchars($row['category']); ?></td>
                    <td><strong><?php echo number_format($row['current_stock'], 2) . " " . htmlspecialchars($row['unit']); ?></strong></td>
                    <td>
                        <?php echo $is_low ? '<span class="badge badge-low">LOW STOCK</span>' : '<span class="badge badge-safe">OK</span>'; ?>
                    </td>
                    <td>
                        <form action="staff_portal.php" method="POST" class="action-form">
                            <input type="hidden" name="item_id" value="<?php echo $row['id']; ?>">
                            <select name="action_type">
                                <option value="STOCK IN">Stock In (Delivered)</option>
                                <option value="STOCK OUT">Stock Out (Kitchen Prep)</option>
                                <option value="WASTE/SPOILAGE">Waste / Spoilage</option>
                            </select>
                            <input type="number" step="0.01" name="qty" placeholder="Qty" style="width:70px;" required>
                            <input type="text" name="notes" placeholder="Batch / reason note" style="width:140px;">
                            <button type="submit" name="transact" class="btn-go">Update</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div class="sec">
        <h2>Recent Movement Logs</h2>
        <table>
            <thead>
                <tr>
                    <th>Time</th>
                    <th>Item</th>
                    <th>Action</th>
                    <th>Amount</th>
                    <th>Staff</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($log =$recent_logs->fetch_assoc()): ?>
                <tr>
                    <td><small style="color:#9ca3af;"><?php echo htmlspecialchars($log['timestamp']); ?></small></td>
                    <td><?php echo htmlspecialchars($log['item_name']); ?></td>
                    <td style="color:<?php echo $log['action_type'] === 'STOCK IN' ? '#10b981' : '#ef4444'; ?>; font-weight:bold;">
                        <?php echo htmlspecialchars($log['action_type']); ?>
                    </td>
                    <td><?php echo number_format($log['quantity'], 2) . " " . htmlspecialchars($log['unit']); ?></td>
                    <td><?php echo htmlspecialchars($log['logged_by']); ?></td>
                    <td style="color:#9ca3af; font-size:0.85rem;"><?php echo htmlspecialchars($log['notes'] ?? '-'); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>