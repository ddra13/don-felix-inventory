<?php
require 'db.php';
session_start();

// 1. Authentication check
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// 2. Strict Role Guard: Only Admin and Client (Cashier) are allowed
if ($_SESSION['role'] === 'Staff') {
    header("Location: staff_portal.php");
    exit();
}

$feedback = "";

// Handle POS Order with Custom Ingredients (Omit "No Egg", "No Rice", etc.)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['place_order'])) {
    $dish_id = intval($_POST['menu_item_id']);
    $order_qty = intval($_POST['order_qty']);
    $selected_ing_ids = isset($_POST['active_ingredients']) ? $_POST['active_ingredients'] : [];

    if ($dish_id > 0 && $order_qty > 0) {
        $conn->begin_transaction();

        try {
            $dish_query = $conn->prepare("SELECT dish_name, price FROM menu_items WHERE id = ?");
            $dish_query->bind_param("i", $dish_id);
            $dish_query->execute();
            $dish = $dish_query->get_result()->fetch_assoc();

            if (!$dish) {
                throw new Exception("Dish not found.");
            }

            // Retrieve all recipe ingredients for this dish
            $recipe_stmt = $conn->prepare("
                SELECT r.ingredient_id, r.quantity_required, i.item_name, i.current_stock, i.unit 
                FROM recipes r 
                JOIN inventory i ON r.ingredient_id = i.id 
                WHERE r.menu_item_id = ?
            ");
            $recipe_stmt->bind_param("i", $dish_id);
            $recipe_stmt->execute();
            $ingredients = $recipe_stmt->get_result();

            $omitted_names = [];

            while ($ing = $ingredients->fetch_assoc()) {
                $ing_id = $ing['ingredient_id'];

                // Check if cashier kept this ingredient checked
                if (in_array($ing_id, $selected_ing_ids)) {
                    $deduction = $ing['quantity_required'] * $order_qty;
                    $new_stock = max(0, $ing['current_stock'] - $deduction);

                    // Deduct inventory
                    $deduct_stmt = $conn->prepare("UPDATE inventory SET current_stock = ? WHERE id = ?");
                    $deduct_stmt->bind_param("di", $new_stock, $ing_id);
                    $deduct_stmt->execute();

                    // Log audit
                    $log_stmt = $conn->prepare("
                        INSERT INTO logs (item_id, item_name, action_type, quantity, unit, logged_by, notes) 
                        VALUES (?, ?, 'POS SALE', ?, ?, ?, ?)
                    ");
                    $note = "POS: {$order_qty}x {$dish['dish_name']}";
                    $log_stmt->bind_param("isdsss", $ing_id, $ing['item_name'], $deduction, $ing['unit'], $_SESSION['user'], $note);
                    $log_stmt->execute();
                } else {
                    // Record omitted ingredient
                    $omitted_names[] = $ing['item_name'];
                }
            }

            $conn->commit();
            $custom_msg = !empty($omitted_names) ? " (Custom Omitted: " . implode(", ", $omitted_names) . ")" : "";
            $total_cost = number_format($dish['price'] * $order_qty, 2);
            $feedback = "<p style='color:#10b981; font-weight:bold;'>Charged {$order_qty}x {$dish['dish_name']} (₱{$total_cost}){$custom_msg}. Stock updated accordingly.</p>";
        } catch (Exception $e) {
            $conn->rollback();
            $feedback = "<p style='color:#f87171;'>Transaction failed: " . $e->getMessage() . "</p>";
        }
    }
}

$dishes = $conn->query("SELECT * FROM menu_items WHERE is_available = 1");
$recent_pos = $conn->query("SELECT * FROM logs WHERE action_type = 'POS SALE' ORDER BY timestamp DESC LIMIT 6");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS Order Terminal - Don Felix</title>
    <style>
        body { font-family: sans-serif; background-color: #0b0f19; color: white; margin: 0; padding: 15px; }
        header { background-color: #111827; padding: 15px 20px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #ef4444; }
        h1 { margin: 0; color: #ef4444; font-size: 1.3rem; }
        .nav-links { display: flex; align-items: center; gap: 8px; }
        .nav-links a { color: white; text-decoration: none; padding: 7px 12px; border-radius: 5px; font-size: 0.85rem; font-weight: bold; background: #374151; transition: background 0.2s; }
        .nav-links a:hover { background: #ef4444; }
        .nav-links a.logout { background-color: #1f2937; border: 1px solid #374151; }
        .nav-links a.logout:hover { background-color: #dc2626; }
        .menu-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 15px; margin: 20px 0; }
        .card { background: #111827; padding: 20px; border-radius: 8px; border: 1px solid #1f2937; display: flex; flex-direction: column; justify-content: space-between; }
        .dish-title { color: #f97316; font-size: 1.15rem; font-weight: bold; margin: 0 0 5px 0; }
        .dish-price { font-size: 1.25rem; font-weight: bold; color: #10b981; margin-bottom: 12px; }
        .ing-box { background: #1f2937; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 0.85rem; }
        .ing-label { font-weight: bold; color: #9ca3af; margin-bottom: 6px; display: block; }
        .ing-item { display: flex; align-items: center; gap: 8px; margin-bottom: 4px; color: #d1d5db; }
        .ing-item input[type="checkbox"] { accent-color: #ef4444; cursor: pointer; }
        .sec { background: #111827; padding: 20px; border-radius: 8px; margin-bottom: 20px; overflow-x: auto; }
        h2 { margin-top: 0; color: #ef4444; border-bottom: 1px solid #374151; padding-bottom: 10px; font-size: 1.1rem; }
        .order-form-footer { display: flex; gap: 8px; align-items: center; }
        .order-form-footer input[type="number"] { background: #1f2937; color: white; border: 1px solid #374151; padding: 8px; border-radius: 4px; width: 60px; font-size: 0.95rem; text-align: center; }
        .btn-order { background: #ef4444; color: white; border: none; padding: 9px 16px; border-radius: 4px; cursor: pointer; font-weight: bold; flex: 1; }
        .btn-order:hover { background: #dc2626; }
        table { width: 100%; border-collapse: collapse; min-width: 600px; }
        th, td { padding: 9px; text-align: left; border-bottom: 1px solid #1f2937; font-size: 0.85rem; }
        th { background-color: #1f2937; color: #ef4444; }
    </style>
</head>
<body>
    <header>
        <div>
            <h1>Client POS Cashier Terminal</h1>
            <small style="color:#9ca3af;">Active Terminal: <strong><?php echo htmlspecialchars($_SESSION['user']); ?></strong> (<?php echo $_SESSION['role']; ?>)</small>
        </div>
        <div class="nav-links">
            <!-- Only Admin can jump to Admin Dashboard or Kitchen View -->
            <?php if ($_SESSION['role'] === 'Admin'): ?>
                <a href="admin_dashboard.php">Admin Dashboard</a>
                <a href="staff_portal.php">Staff View</a>
            <?php endif; ?>
            <a href="logout.php" class="logout">Logout</a>
        </div>
    </header>

    <div><?php echo $feedback; ?></div>

    <h2>Cooked Meals Menu</h2>
    <div class="menu-grid">
        <?php while ($d = $dishes->fetch_assoc()): 
            $d_id = $d['id'];
            $dish_ings = $conn->query("
                SELECT r.ingredient_id, r.quantity_required, i.item_name, i.unit 
                FROM recipes r 
                JOIN inventory i ON r.ingredient_id = i.id 
                WHERE r.menu_item_id = $d_id
            ");
        ?>
        <div class="card">
            <form action="pos_order.php" method="POST">
                <input type="hidden" name="menu_item_id" value="<?php echo $d['id']; ?>">
                
                <div class="dish-title"><?php echo htmlspecialchars($d['dish_name']); ?></div>
                <div style="color:#9ca3af; font-size:0.85rem; margin-bottom:8px;"><?php echo $d['category']; ?></div>
                <div class="dish-price">₱<?php echo number_format($d['price'], 2); ?></div>

                <div class="ing-box">
                    <span class="ing-label">Recipe Ingredients:</span>
                    <?php while($ing = $dish_ings->fetch_assoc()): ?>
                        <label class="ing-item">
                            <input type="checkbox" name="active_ingredients[]" value="<?php echo $ing['ingredient_id']; ?>" checked>
                            <span><?php echo htmlspecialchars($ing['item_name']); ?> (<?php echo $ing['quantity_required'] . ' ' . $ing['unit']; ?>)</span>
                        </label>
                    <?php endwhile; ?>
                </div>

                <div class="order-form-footer">
                    <input type="number" name="order_qty" value="1" min="1" max="50" required>
                    <button type="submit" name="place_order" class="btn-order">Charge & Deduct</button>
                </div>
            </form>
        </div>
        <?php endwhile; ?>
    </div>

    <div class="sec">
        <h2>Live POS Depletion Audit Log</h2>
        <table>
            <thead>
                <tr><th>Time</th><th>Ingredient Deducted</th><th>Deducted Amount</th><th>Cashier/Client</th><th>Reference</th></tr>
            </thead>
            <tbody>
                <?php while ($log = $recent_pos->fetch_assoc()): ?>
                <tr>
                    <td><small style="color:#9ca3af;"><?php echo $log['timestamp']; ?></small></td>
                    <td><?php echo htmlspecialchars($log['item_name']); ?></td>
                    <td style="color:#ef4444; font-weight:bold;">-<?php echo number_format($log['quantity'], 2) . " " . $log['unit']; ?></td>
                    <td><?php echo htmlspecialchars($log['logged_by']); ?></td>
                    <td style="color:#9ca3af;"><?php echo htmlspecialchars($log['notes']); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>