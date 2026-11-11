<?php
require 'db.php'; session_start();
if (!isset($_SESSION['user'])) { header("Location: login.php"); exit(); }

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['transact'])) {
    $id = $_POST['item_id']; $qty = intval($_POST['qty']); $action = $_POST['action_type'];
    $item = $conn->query("SELECT * FROM inventory WHERE id=$id")->fetch_assoc();
    
    if($action == "STOCK IN") { $new_qty = $item['current_stock'] + $qty; } 
    else { $new_qty = max(0, $item['current_stock'] - $qty); }
    
    $conn->query("UPDATE inventory SET current_stock=$new_qty WHERE id=$id");
    $conn->query("INSERT INTO logs (item_id, item_name, action_type, quantity) VALUES ($id, '{$item['item_name']}', '$action', $qty)");
}

$critical_count = $conn->query("SELECT COUNT(*) as total FROM inventory WHERE current_stock <= min_alert")->fetch_assoc()['total'];
$total_count = $conn->query("SELECT COUNT(*) as total FROM inventory")->fetch_assoc()['total'];
$inventory_result = $conn->query("SELECT * FROM inventory");
$logs_result = $conn->query("SELECT * FROM logs ORDER BY timestamp DESC LIMIT 5");

$food_images = [
    1 => "https://unsplash.com",
    2 => "https://unsplash.com",
    3 => "https://unsplash.com",
    4 => "https://unsplash.com",
    5 => "https://unsplash.com"
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Don Felix</title>
    <style>
        body { font-family: sans-serif; background-color: #0b0f19; color: white; margin: 0; padding: 15px; box-sizing: border-box; }
        header { background-color: #111827; padding: 15px 20px; border-radius: 8px; display: flex; flex-direction: column; gap: 10px; border-bottom: 3px solid #ef4444; }
        @media(min-width: 768px) { header { flex-direction: row; justify-content: space-between; align-items: center; } }
        h1 { margin: 0; color: #ef4444; font-size: 1.5rem; }
        .logout { background-color: #374151; color: white; padding: 8px 16px; text-decoration: none; border-radius: 6px; font-size: 0.9rem; font-weight: bold; text-align: center; }
        .logout:hover { background-color: #ef4444; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0; }
        .card { background: #111827; padding: 20px; border-radius: 8px; text-align: center; }
        .card .num { font-size: 2rem; font-weight: bold; color: #ef4444; margin-top: 5px; }
        .sec { background: #111827; padding: 20px; border-radius: 8px; margin-bottom: 20px; overflow-x: auto; }
        h2 { margin-top: 0; color: #ef4444; border-bottom: 1px solid #374151; padding-bottom: 10px; display: flex; justify-content: space-between; align-items: center; font-size: 1.2rem; }
        table { width: 100%; border-collapse: collapse; min-width: 600px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #1f2937; font-size: 0.9rem; vertical-align: middle; }
        th { background-color: #1f2937; color: #ef4444; }
        .item-img { width: 45px; height: 45px; border-radius: 6px; object-fit: cover; border: 2px solid #374151; }
        .item-cell { display: flex; align-items: center; gap: 10px; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: bold; }
        .badge-safe { background: #10b981; }
        .badge-low { background: #ef4444; animation: pulse 1.5s infinite; }
        .btn-print { background: #3b82f6; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 0.85rem; }
        .action-form { display: flex; gap: 5px; }
        .action-form select, .action-form input { background: #1f2937; color: white; border: 1px solid #374151; padding: 4px; border-radius: 4px; font-size: 0.85rem; }
        .btn-go { background: #ef4444; color: white; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.6; } }
        @media print { body * { visibility: hidden; } #printSection, #printSection * { visibility: visible; } #printSection { position: absolute; left: 0; top: 0; width: 100%; color: black !important; } table, th, td { color: black !important; border: 1px solid #000; } .item-img, .btn-print, .action-form { display: none; } }
    </style>
</head>
<body>
    <header>
        <div>
            <h1>Don Felix Sizzling Modules</h1>
            <small>Active User: <strong><?php echo $_SESSION['user']; ?></strong> (<?php echo $_SESSION['role']; ?>)</small>
        </div>
        <a href="logout.php" class="logout">Logout</a>
    </header>

    <div class="grid">
        <div class="card"><h3>Catalog Items</h3><div class="num"><?php echo $total_count; ?></div></div>
        <div class="card"><h3>Low Stock Warning</h3><div class="num"><?php echo $critical_count; ?></div></div>
        <div class="card"><h3>Pipeline Users</h3><div class="num" style="color:#10b981;">3</div></div>
    </div>

    <div class="sec" id="printSection">
        <h2><span>Ingredient Tracking Catalog</span><button onclick="window.print()" class="btn-print">Export Report</button></h2>
        <table>
            <thead>
                <tr><th>ID</th><th>Ingredient Item</th><th>Category</th><th>Current Stock</th><th>Threshold</th><th>Smart Warning</th><th>Pipeline Control</th></tr>
            </thead>
            <tbody>
                <?php while($row = $inventory_result->fetch_assoc()): $low = $row['current_stock'] <= $row['min_alert']; ?>
                <tr>
                    <td>#0<?php echo $row['id']; ?></td>
                    <td>
                        <div class="item-cell">
                            <img src="<?php echo isset($food_images[$row['id']]) ? $food_images[$row['id']] : 'https://placeholder.com'; ?>" class="item-img">
                            <strong><?php echo $row['item_name']; ?></strong>
                        </div>
                    </td>
                    <td><?php echo $row['category']; ?></td>
                    <td><?php echo $row['current_stock']; ?> units</td>
                    <td><?php echo $row['min_alert']; ?> units</td>
                    <td><?php echo $low ? '<span class="badge badge-low">CRITICAL</span>' : '<span class="badge badge-safe">SAFE</span>'; ?></td>
                    <td>
                        <form action="homepage.php" method="POST" class="action-form">
                            <input type="hidden" name="item_id" value="<?php echo $row['id']; ?>">
                            <select name="action_type"><option value="STOCK IN">Stock In</option><option value="STOCK OUT">Stock Out</option></select>
                            <input type="number" name="qty" min="1" value="5" style="width:45px;" required>
                            <button type="submit" name="transact" class="btn-go">Go</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div class="sec">
        <h2>Live Process Pipeline Logs</h2>
        <table>
            <thead><tr><th>Timestamp</th><th>Ingredient Item</th><th>Action Type</th><th>Quantity</th></tr></thead>
            <tbody>
                <?php while($log = $logs_result->fetch_assoc()): ?>
                <tr>
                    <td><small><?php echo $log['timestamp']; ?></small></td>
                    <td><?php echo $log['item_name']; ?></td>
                    <td style="color:<?php echo $log['action_type']=='STOCK IN'?'#10b981':'#ef4444'; ?>;"><strong><?php echo $log['action_type']; ?></strong></td>
                    <td><?php echo $log['quantity']; ?> units</td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>