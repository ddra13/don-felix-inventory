<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

$msg = "";

// 1. Add new inventory item
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_item'])) {
    $item_name = trim($_POST['item_name']);
    $category = $_POST['category'];
    $current_stock = floatval($_POST['current_stock']);
    $unit = $_POST['unit'];
    $min_alert = floatval($_POST['min_alert']);
    $cost = floatval($_POST['cost_per_unit']);

    $stmt = $conn->prepare("INSERT INTO inventory (item_name, category, current_stock, unit, min_alert, cost_per_unit) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdssd", $item_name, $category, $current_stock, $unit, $min_alert, $cost);
    if ($stmt->execute()) {
        $msg = "<p style='color:#10b981;'>Added '$item_name' to catalog.</p>";
    } else {
        $msg = "<p style='color:#f87171;'>Failed to add item.</p>";
    }
}

// 2. Update existing inventory item
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_item'])) {
    $id = intval($_POST['edit_id']);
    $item_name = trim($_POST['item_name']);
    $category = $_POST['category'];
    $current_stock = floatval($_POST['current_stock']);
    $unit = $_POST['unit'];
    $min_alert = floatval($_POST['min_alert']);
    $cost = floatval($_POST['cost_per_unit']);

    $stmt = $conn->prepare("UPDATE inventory SET item_name = ?, category = ?, current_stock = ?, unit = ?, min_alert = ?, cost_per_unit = ? WHERE id = ?");
    $stmt->bind_param("ssdssdi", $item_name, $category, $current_stock, $unit, $min_alert, $cost, $id);
    if ($stmt->execute()) {
        $msg = "<p style='color:#10b981;'>Item #$id updated successfully.</p>";
    }
}

// 3. Delete inventory item
if (isset($_GET['delete_id'])) {
    $del_id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM inventory WHERE id = ?");
    $stmt->bind_param("i", $del_id);
    $stmt->execute();
    header("Location: admin_dashboard.php");
    exit();
}

// 4. Delete user account
if (isset($_GET['delete_user_id'])) {
    $user_id = intval($_GET['delete_user_id']);
    if ($user_id != $_SESSION['user_id']) { // Prevent deleting own active session
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
    }
    header("Location: admin_dashboard.php#usersSec");
    exit();
}

// 5. Update or Add recipe ingredient requirement
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_recipe_ing'])) {
    $menu_id = intval($_POST['menu_item_id']);
    $ing_id = intval($_POST['ingredient_id']);
    $qty_req = floatval($_POST['quantity_required']);

    // Check if link exists
    $chk = $conn->prepare("SELECT id FROM recipes WHERE menu_item_id = ? AND ingredient_id = ?");
    $chk->bind_param("ii", $menu_id, $ing_id);
    $chk->execute();
    $res = $chk->get_result();

    if ($res->num_rows > 0) {
        $upd = $conn->prepare("UPDATE recipes SET quantity_required = ? WHERE menu_item_id = ? AND ingredient_id = ?");
        $upd->bind_param("dii", $qty_req, $menu_id, $ing_id);
        $upd->execute();
    } else {
        $ins = $conn->prepare("INSERT INTO recipes (menu_item_id, ingredient_id, quantity_required) VALUES (?, ?, ?)");
        $ins->bind_param("iid", $menu_id, $ing_id, $qty_req);
        $ins->execute();
    }
    $msg = "<p style='color:#10b981;'>Recipe requirement saved.</p>";
}

// 6. Delete ingredient from recipe
if (isset($_GET['del_recipe_id'])) {
    $r_id = intval($_GET['del_recipe_id']);
    $conn->query("DELETE FROM recipes WHERE id = $r_id");
    header("Location: admin_dashboard.php#recipesSec");
    exit();
}

// Metrics
$total_items = $conn->query("SELECT COUNT(*) as total FROM inventory")->fetch_assoc()['total'];
$low_stock = $conn->query("SELECT COUNT(*) as total FROM inventory WHERE current_stock <= min_alert")->fetch_assoc()['total'];
$valuation = $conn->query("SELECT SUM(current_stock * cost_per_unit) as total_val FROM inventory")->fetch_assoc()['total_val'] ?? 0;

$inventory = $conn->query("SELECT * FROM inventory ORDER BY category, item_name ASC");
$users = $conn->query("SELECT id, fullname, username, role, created_at FROM users ORDER BY created_at DESC");
$menu_items = $conn->query("SELECT * FROM menu_items");
$all_ingredients = $conn->query("SELECT id, item_name, unit FROM inventory ORDER BY item_name ASC");

// Fetch active recipes
$recipe_list = $conn->query("
    SELECT r.id as recipe_id, m.dish_name, i.item_name, r.quantity_required, i.unit 
    FROM recipes r
    JOIN menu_items m ON r.menu_item_id = m.id
    JOIN inventory i ON r.ingredient_id = i.id
    ORDER BY m.dish_name, i.item_name
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Dashboard - Don Felix Sizzling House</title>
    <style>
        body { font-family: sans-serif; background-color: #0b0f19; color: white; margin: 0; padding: 15px; }
        header { background-color: #111827; padding: 15px 20px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #ef4444; }
        h1 { margin: 0; color: #ef4444; font-size: 1.4rem; }
        .nav-links a { color: white; text-decoration: none; padding: 8px 12px; border-radius: 5px; font-size: 0.85rem; font-weight: bold; background: #374151; margin-left: 5px; }
        .nav-links a:hover { background: #ef4444; }
        .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; margin: 20px 0; }
        .card { background: #111827; padding: 20px; border-radius: 8px; text-align: center; }
        .card .num { font-size: 1.8rem; font-weight: bold; color: #ef4444; margin-top: 5px; }
        .sec { background: #111827; padding: 20px; border-radius: 8px; margin-bottom: 20px; overflow-x: auto; }
        h2 { margin-top: 0; color: #ef4444; border-bottom: 1px solid #374151; padding-bottom: 10px; font-size: 1.1rem; display: flex; justify-content: space-between; align-items: center; }
        table { width: 100%; border-collapse: collapse; min-width: 750px; margin-top: 10px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #1f2937; font-size: 0.9rem; }
        th { background-color: #1f2937; color: #ef4444; }
        .btn-print { background: #3b82f6; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; }
        .btn-edit { background: #3b82f6; color: white; border: none; padding: 4px 10px; border-radius: 4px; cursor: pointer; font-size: 0.8rem; font-weight: bold; margin-right: 6px; }
        .del-link { color: #f87171; text-decoration: none; font-size: 0.85rem; }
        .del-link:hover { text-decoration: underline; }
        .form-row { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 10px; }
        .form-row input, .form-row select { background: #1f2937; color: white; border: 1px solid #374151; padding: 9px; border-radius: 6px; font-size: 0.9rem; flex: 1; min-width: 130px; }
        .btn-submit { background: #ef4444; color: white; border: none; padding: 10px 18px; border-radius: 6px; cursor: pointer; font-weight: bold; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: bold; }
        .badge-admin { background: #ef4444; }
        .badge-staff { background: #3b82f6; }
        .badge-client { background: #10b981; }

        /* Modal Styles */
        .modal { display: none; position: fixed; z-index: 100; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.75); justify-content: center; align-items: center; }
        .modal-content { background-color: #111827; padding: 25px; border-radius: 10px; border-top: 4px solid #3b82f6; width: 90%; max-width: 520px; box-shadow: 0 5px 25px rgba(0, 0, 0, 0.8); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .modal-header h3 { margin: 0; color: #3b82f6; font-size: 1.2rem; }
        .close-btn { color: #9ca3af; font-size: 1.5rem; font-weight: bold; cursor: pointer; border: none; background: none; }
        .modal-group { margin-bottom: 12px; }
        .modal-group label { display: block; color: #9ca3af; font-size: 0.85rem; margin-bottom: 4px; }
        .modal-group input, .modal-group select { width: 100%; padding: 9px; background: #1f2937; border: 1px solid #374151; color: white; border-radius: 6px; box-sizing: border-box; }
        .btn-update { width: 100%; background: #3b82f6; color: white; border: none; padding: 11px; font-size: 1rem; font-weight: bold; border-radius: 6px; cursor: pointer; margin-top: 10px; }

        @media print {
            body * { visibility: hidden; }
            #printable, #printable * { visibility: visible; }
            #printable { position: absolute; left: 0; top: 0; width: 100%; color: black !important; }
            table, th, td { color: black !important; border: 1px solid #333; }
            .del-link, .btn-print, .btn-edit, header, .card, .form-row, .modal, #usersSec, #recipesSec { display: none !important; }
        }
    </style>
</head>
<body>
    <header>
        <div>
            <h1>Owner Management Dashboard</h1>
            <small style="color:#9ca3af;">Admin User: <strong><?php echo htmlspecialchars($_SESSION['user']); ?></strong></small>
        </div>
        <div class="nav-links">
            <a href="pos_order.php">Client POS</a>
            <a href="staff_portal.php">Staff View</a>
            <a href="logout.php">Logout</a>
        </div>
    </header>

    <div class="kpi-grid">
        <div class="card"><h3>Total Catalog Items</h3><div class="num"><?php echo $total_items; ?></div></div>
        <div class="card"><h3>Critical Reorder Alerts</h3><div class="num"><?php echo $low_stock; ?></div></div>
        <div class="card"><h3>Inventory Valuation</h3><div class="num" style="color:#10b981;">₱<?php echo number_format($valuation, 2); ?></div></div>
    </div>

    <!-- Inventory Catalog -->
    <div class="sec" id="printable">
        <h2>
            <span>Current Inventory & Valuation</span>
            <button onclick="window.print()" class="btn-print">Print Shift Audit</button>
        </h2>
        <?php echo $msg; ?>
        
        <!-- Add Item Form -->
        <form action="admin_dashboard.php" method="POST" style="margin-top:15px;">
            <div class="form-row">
                <input type="text" name="item_name" placeholder="Item Name (e.g. Pork Chop Cuts)" required>
                <select name="category" required>
                    <option value="Meat & Poultry">Meat & Poultry</option>
                    <option value="Produce">Produce</option>
                    <option value="Dry Goods & Seasonings">Dry Goods & Seasonings</option>
                    <option value="Packaging & Disposables">Packaging & Disposables</option>
                </select>
                <input type="number" step="0.01" name="current_stock" placeholder="Initial Stock" required>
                <select name="unit" required>
                    <option value="kg">kg</option>
                    <option value="pieces">pieces</option>
                    <option value="liters">liters</option>
                    <option value="packs">packs</option>
                    <option value="sacks">sacks</option>
                </select>
                <input type="number" step="0.01" name="min_alert" placeholder="Reorder Threshold" required>
                <input type="number" step="0.01" name="cost_per_unit" placeholder="Cost per Unit (₱)" required>
                <button type="submit" name="add_item" class="btn-submit">Add Item</button>
            </div>
        </form>

        <table>
            <thead>
                <tr>
                    <th>ID</th><th>Item</th><th>Category</th><th>Current Stock</th><th>Threshold</th><th>Unit Cost</th><th>Total Asset</th><th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $inventory->fetch_assoc()): 
                    $subtotal = $row['current_stock'] * $row['cost_per_unit'];
                    $is_low = $row['current_stock'] <= $row['min_alert'];
                ?>
                <tr>
                    <td>#<?php echo $row['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($row['item_name']); ?></strong></td>
                    <td style="color:#9ca3af;"><?php echo $row['category']; ?></td>
                    <td style="color:<?php echo $is_low ? '#ef4444' : '#10b981'; ?>; font-weight:bold;">
                        <?php echo number_format($row['current_stock'], 2) . " " . $row['unit']; ?>
                    </td>
                    <td><?php echo number_format($row['min_alert'], 2) . " " . $row['unit']; ?></td>
                    <td>₱<?php echo number_format($row['cost_per_unit'], 2); ?></td>
                    <td>₱<?php echo number_format($subtotal, 2); ?></td>
                    <td>
                        <button type="button" class="btn-edit" onclick='openEditModal(<?php echo json_encode($row); ?>)'>Edit</button>
                        <a href="admin_dashboard.php?delete_id=<?php echo $row['id']; ?>" class="del-link" onclick="return confirm('Delete this item?');">Delete</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Recipe Bill of Materials Editor -->
    <div class="sec" id="recipesSec">
        <h2>Meal Recipes & Ingredient Depletion Rules (BOM)</h2>
        <form action="admin_dashboard.php" method="POST" style="margin-top:10px;">
            <div class="form-row">
                <select name="menu_item_id" required>
                    <option value="">-- Select Cooked Meal Dish --</option>
                    <?php 
                    $menu_items->data_seek(0);
                    while($m = $menu_items->fetch_assoc()): ?>
                        <option value="<?php echo $m['id']; ?>"><?php echo htmlspecialchars($m['dish_name']); ?> (₱<?php echo $m['price']; ?>)</option>
                    <?php endwhile; ?>
                </select>

                <select name="ingredient_id" required>
                    <option value="">-- Select Raw Ingredient to Deduct --</option>
                    <?php 
                    $all_ingredients->data_seek(0);
                    while($ing = $all_ingredients->fetch_assoc()): ?>
                        <option value="<?php echo $ing['id']; ?>"><?php echo htmlspecialchars($ing['item_name']); ?> (<?php echo $ing['unit']; ?>)</option>
                    <?php endwhile; ?>
                </select>

                <input type="number" step="0.01" name="quantity_required" placeholder="Qty Used (e.g., 0.18 for kg, 1 for pcs)" required>
                <button type="submit" name="save_recipe_ing" class="btn-submit" style="background:#3b82f6;">Save Recipe Rule</button>
            </div>
        </form>

        <table>
            <thead>
                <tr><th>Cooked Dish</th><th>Raw Ingredient Consumed</th><th>Deduction Quantity per Plate</th><th>Action</th></tr>
            </thead>
            <tbody>
                <?php while($r = $recipe_list->fetch_assoc()): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($r['dish_name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($r['item_name']); ?></td>
                    <td style="color:#f97316; font-weight:bold;"><?php echo number_format($r['quantity_required'], 2) . " " . $r['unit']; ?></td>
                    <td><a href="admin_dashboard.php?del_recipe_id=<?php echo $r['recipe_id']; ?>" class="del-link" onclick="return confirm('Remove ingredient from this dish?');">Remove</a></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- User Account Management -->
    <div class="sec" id="usersSec">
        <h2>Registered System Accounts</h2>
        <table>
            <thead>
                <tr><th>ID</th><th>Full Name</th><th>Username</th><th>System Role</th><th>Created Date</th><th>Action</th></tr>
            </thead>
            <tbody>
                <?php while ($u = $users->fetch_assoc()): ?>
                <tr>
                    <td>#<?php echo $u['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($u['fullname']); ?></strong></td>
                    <td><?php echo htmlspecialchars($u['username']); ?></td>
                    <td>
                        <span class="badge <?php echo $u['role'] === 'Admin' ? 'badge-admin' : ($u['role'] === 'Staff' ? 'badge-staff' : 'badge-client'); ?>">
                            <?php echo $u['role']; ?>
                        </span>
                    </td>
                    <td><small style="color:#9ca3af;"><?php echo $u['created_at']; ?></small></td>
                    <td>
                        <?php if ($u['id'] != $_SESSION['user_id']): ?>
                            <a href="admin_dashboard.php?delete_user_id=<?php echo $u['id']; ?>" class="del-link" onclick="return confirm('Delete user account?');">Delete User</a>
                        <?php else: ?>
                            <span style="color:#9ca3af; font-size:0.8rem;">(Active Session)</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Modal for Editing Inventory Item -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Ingredient Details</h3>
                <button type="button" class="close-btn" onclick="closeEditModal()">&times;</button>
            </div>
            <form action="admin_dashboard.php" method="POST">
                <input type="hidden" name="edit_id" id="edit_id">
                <div class="modal-group">
                    <label>Ingredient Name</label>
                    <input type="text" name="item_name" id="edit_name" required>
                </div>
                <div class="modal-group">
                    <label>Category</label>
                    <select name="category" id="edit_category" required>
                        <option value="Meat & Poultry">Meat & Poultry</option>
                        <option value="Produce">Produce</option>
                        <option value="Dry Goods & Seasonings">Dry Goods & Seasonings</option>
                        <option value="Packaging & Disposables">Packaging & Disposables</option>
                    </select>
                </div>
                <div style="display:flex; gap:10px;">
                    <div class="modal-group" style="flex:1;">
                        <label>Current Stock</label>
                        <input type="number" step="0.01" name="current_stock" id="edit_stock" required>
                    </div>
                    <div class="modal-group" style="flex:1;">
                        <label>Unit of Measure</label>
                        <select name="unit" id="edit_unit" required>
                            <option value="kg">kg</option>
                            <option value="pieces">pieces</option>
                            <option value="liters">liters</option>
                            <option value="packs">packs</option>
                            <option value="sacks">sacks</option>
                        </select>
                    </div>
                </div>
                <div style="display:flex; gap:10px;">
                    <div class="modal-group" style="flex:1;">
                        <label>Reorder Alert Threshold</label>
                        <input type="number" step="0.01" name="min_alert" id="edit_min_alert" required>
                    </div>
                    <div class="modal-group" style="flex:1;">
                        <label>Cost per Unit (₱)</label>
                        <input type="number" step="0.01" name="cost_per_unit" id="edit_cost" required>
                    </div>
                </div>
                <button type="submit" name="update_item" class="btn-update">Save Changes</button>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(item) {
            document.getElementById('edit_id').value = item.id;
            document.getElementById('edit_name').value = item.item_name;
            document.getElementById('edit_category').value = item.category;
            document.getElementById('edit_stock').value = item.current_stock;
            document.getElementById('edit_unit').value = item.unit;
            document.getElementById('edit_min_alert').value = item.min_alert;
            document.getElementById('edit_cost').value = item.cost_per_unit;
            document.getElementById('editModal').style.display = 'flex';
        }
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        window.onclick = function(event) {
            var modal = document.getElementById('editModal');
            if (event.target == modal) { closeEditModal(); }
        }
    </script>
</body>
</html>