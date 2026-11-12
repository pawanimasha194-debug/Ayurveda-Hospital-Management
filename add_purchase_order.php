<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
restrictTo(['pharmacist']);

$error = "";
$prefill_items = [];

// Check if coming from Low Stock Order List
if (isset($_SESSION['auto_order_items'])) {
    $prefill_items = $_SESSION['auto_order_items'];
    unset($_SESSION['auto_order_items']); // Clear after use
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $supplier_id = intval($_POST['supplier_id']);
    $order_date = $_POST['order_date'];
    $expected_date = !empty($_POST['expected_date']) ? $_POST['expected_date'] : null;
    $notes = trim($_POST['notes']);
    
    $medicine_ids = $_POST['medicine_id'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    $prices = $_POST['unit_price'] ?? [];
    
    if ($supplier_id == 0 || count($medicine_ids) == 0) {
        $error = "Please select a supplier and at least one medicine.";
    } else {
        $conn->begin_transaction();
        try {
            $created_by = $_SESSION['user_id'];
            $stmt = $conn->prepare("INSERT INTO purchase_orders (supplier_id, order_date, expected_date, notes, created_by) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("isssi", $supplier_id, $order_date, $expected_date, $notes, $created_by);
            $stmt->execute();
            $po_id = $conn->insert_id;
            
            $stmt2 = $conn->prepare("INSERT INTO purchase_order_items (po_id, medicine_id, quantity, unit_price) VALUES (?, ?, ?, ?)");
            for ($i = 0; $i < count($medicine_ids); $i++) {
                if ($medicine_ids[$i] == '') continue;
                $med_id = intval($medicine_ids[$i]);
                $qty = intval($quantities[$i]);
                $price = floatval($prices[$i]);
                $stmt2->bind_param("iiid", $po_id, $med_id, $qty, $price);
                $stmt2->execute();
            }
            
            $conn->commit();
            header("Location: purchase_orders.php");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Something went wrong. Try again.";
        }
    }
}

$suppliers = $conn->query("SELECT supplier_id, supplier_name FROM suppliers ORDER BY supplier_name");
$medicines = $conn->query("SELECT medicine_id, medicine_name, medicine_name_si, unit_price FROM medicines ORDER BY medicine_name");

$medicineOptions = "<option value=''>-- Select Medicine --</option>";
while ($m = $medicines->fetch_assoc()) {
    $si_name = !empty($m['medicine_name_si']) ? " (" . $m['medicine_name_si'] . ")" : "";
    $medicineOptions .= "<option value='{$m['medicine_id']}' data-price='{$m['unit_price']}'>" . htmlspecialchars($m['medicine_name'] . $si_name) . "</option>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Create Purchase Order - Ayurveda Hospital</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Zilla+Slab:wght@600;700&family=Karla:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<style>
.po-row { display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 10px; margin-bottom: 10px; align-items: center; }
@media (max-width: 768px) { .po-row { grid-template-columns: 1fr; } }
</style>
</head>
<body>

<div class="topbar">
    <h2>Create Purchase Order</h2>
    <a href="purchase_orders.php">&larr; Back to Orders</a>
</div>

<div class="container">
    <div class="form-box">
        <h3>New Order Details</h3>
        
        <form method="POST">
            <label>Supplier *</label>
            <select name="supplier_id" required>
                <option value="">-- Select Supplier --</option>
                <?php while ($s = $suppliers->fetch_assoc()): ?>
                    <option value="<?php echo $s['supplier_id']; ?>"><?php echo htmlspecialchars($s['supplier_name']); ?></option>
                <?php endwhile; ?>
            </select>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 15px;">
                <div>
                    <label>Order Date *</label>
                    <input type="date" name="order_date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div>
                    <label>Expected Delivery Date</label>
                    <input type="date" name="expected_date">
                </div>
            </div>

            <label>Notes / Instructions</label>
            <textarea name="notes" rows="2" placeholder="Any special instructions for the supplier..."></textarea>

            <hr style="margin: 20px 0;">
            
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; margin-bottom: 10px;">
                <label style="margin: 0;">Order Items *</label>
                <a href="low_stock_order_list.php" class="btn" style="background:#ff9800; font-size: 13px; padding: 8px 12px;">⚡ View Low Stock List</a>
            </div>

            <div id="po-items">
                <?php if (count($prefill_items) > 0): ?>
                    <?php foreach ($prefill_items as $item): ?>
                        <div class="po-row">
                            <select name="medicine_id[]" required><?php echo $medicineOptions; ?></select>
                            <input type="number" name="quantity[]" value="<?php echo $item['suggested_qty']; ?>" min="1" required>
                            <input type="number" step="0.01" name="unit_price[]" value="<?php echo $item['unit_price']; ?>" required>
                            <button type="button" class="remove-row-btn" onclick="removeRow(this)">✕</button>
                        </div>
                        <script>
                            document.addEventListener("DOMContentLoaded", function() {
                                let rows = document.querySelectorAll('.po-row');
                                let lastRow = rows[rows.length - 1];
                                let select = lastRow.querySelector('select');
                                select.value = "<?php echo $item['medicine_id']; ?>";
                            });
                        </script>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="po-row">
                        <select name="medicine_id[]" required><?php echo $medicineOptions; ?></select>
                        <input type="number" name="quantity[]" placeholder="Qty" min="1" required>
                        <input type="number" step="0.01" name="unit_price[]" placeholder="Unit Price" value="0.00">
                        <button type="button" class="remove-row-btn" onclick="removeRow(this)">✕</button>
                    </div>
                <?php endif; ?>
            </div>
            
            <button type="button" class="add-row-btn" onclick="addRow()">+ Add Another Item</button>
            <button type="submit">Create Order</button>
            
            <?php if ($error): ?><div class="error-msg"><?php echo $error; ?></div><?php endif; ?>
        </form>
    </div>
</div>

<script>
const medOptionsHtml = `<?php echo addslashes($medicineOptions); ?>`;

function addRow() {
    const container = document.getElementById('po-items');
    const row = document.createElement('div');
    row.className = 'po-row';
    row.innerHTML = `
        <select name="medicine_id[]" required>${medOptionsHtml}</select>
        <input type="number" name="quantity[]" placeholder="Qty" min="1" required>
        <input type="number" step="0.01" name="unit_price[]" placeholder="Unit Price" value="0.00">
        <button type="button" class="remove-row-btn" onclick="removeRow(this)">✕</button>
    `;
    container.appendChild(row);
    attachPriceEvents();
}

function removeRow(btn) {
    const container = document.getElementById('po-items');
    if (container.children.length > 1) {
        btn.parentElement.remove();
    }
}

function attachPriceEvents() {
    document.querySelectorAll('select[name="medicine_id[]"]').forEach(sel => {
        sel.onchange = function() {
            const price = this.options[this.selectedIndex].getAttribute('data-price');
            if (price) {
                this.parentElement.querySelector('input[name="unit_price[]"]').value = price;
            }
        };
    });
}
attachPriceEvents();
</script>

</body>
</html>