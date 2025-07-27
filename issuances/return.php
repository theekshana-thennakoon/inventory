<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

redirectIfNotLoggedIn();

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$issuanceId = $_GET['id'];

// Verify issuance exists
$stmt = $pdo->prepare("SELECT id FROM issuances WHERE id = ?");
$stmt->execute([$issuanceId]);
if (!$stmt->fetch()) {
    header("Location: index.php?error=Issuance not found");
    exit();
}

// Get issued items that haven't been fully returned
$items = $pdo->prepare("
    SELECT ii.id, ii.item_id, ii.quantity, ii.returned_quantity,
           i.name as item_name, i.serial_no as item_serial_no, i.description as item_description
    FROM issuance_items ii
    JOIN items i ON ii.item_id = i.id
    WHERE ii.issuance_id = ? 
    AND (ii.returned_quantity IS NULL OR ii.returned_quantity < ii.quantity)
");
$items->execute([$issuanceId]);
$items = $items->fetchAll(PDO::FETCH_ASSOC);
// print_r($items);
$errors = [];
$returnDate = date('Y-m-d');
$returnConditions = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $returnDate = $_POST['return_date'];
    $returnConditions = $_POST['return_condition'] ?? [];
    $returnQuantities = $_POST['return_quantity'] ?? [];

    // Validation
    if (empty($returnQuantities)) {
        $errors['items'] = 'Please specify quantities for at least one item';
    } else {
        foreach ($returnQuantities as $issuanceItemId => $quantity) {
            // if ($quantity <= 0) {
            //     $errors['items'] = 'Return quantity must be greater than 0';
            //     break;
            // }

            // Find the original issuance item
            $originalItem = null;
            foreach ($items as $item) {
                if ($item['id'] == $issuanceItemId) {
                    $originalItem = $item;
                    break;
                }
            }

            if (!$originalItem) {
                $errors['items'] = 'Invalid item selected for return';
                break;
            }

            $maxReturnable = $originalItem['quantity'] - ($originalItem['returned_quantity'] ?? 0);
            if ($quantity > $maxReturnable) {
                $errors['items'] = 'Return quantity cannot exceed issued quantity';
                break;
            }
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            foreach ($returnQuantities as $issuanceItemId => $quantity) {
                if ($quantity > 0) {
                    $condition = $returnConditions[$issuanceItemId] ?? 'good';

                    // Update issuance item
                    $stmt = $pdo->prepare("
                        UPDATE issuance_items 
                        SET returned_quantity = IFNULL(returned_quantity, 0) + ?,
                            return_date = ?,
                            return_condition = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $quantity,
                        $returnDate,
                        $condition,
                        $issuanceItemId
                    ]);

                    // Update inventory
                    $stmt = $pdo->prepare("
                        UPDATE items 
                        SET quantity = quantity + ? 
                        WHERE id = (
                            SELECT item_id FROM issuance_items WHERE id = ?
                        )
                    ");
                    $stmt->execute([$quantity, $issuanceItemId]);
                }
            }

            $pdo->commit();

            header("Location: view.php?id=$issuanceId&success=Item returns recorded successfully");
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = "Error recording returns: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Record Returns - FOT Media Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="../../assets/css/style.css" rel="stylesheet">
</head>

<body>
    <?php include '../includes/header.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-lg-3 col-md-4 d-md-block sidebar collapse" id="sidebarMenu">
                <?php include '../includes/sidebar.php'; ?>
            </div>

            <main class="col-lg-9 col-md-8 ms-sm-auto px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="bi bi-box-arrow-in-down"></i> Record Returns for Issuance #<?php echo $issuanceId; ?>
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="view.php?id=<?php echo $issuanceId; ?>" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Issuance
                        </a>
                    </div>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="card shadow-sm">
                    <div class="card-body">
                        <form method="POST">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="return_date" class="form-label">Return Date *</label>
                                        <input type="date" class="form-control"
                                            id="return_date" name="return_date"
                                            value="<?php echo htmlspecialchars($returnDate); ?>" required readonly>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <h5 class="mb-3">Items to Return</h5>
                                    <?php if (empty($items)): ?>
                                        <div class="alert alert-info">
                                            All items from this issuance have already been returned.
                                        </div>
                                    <?php else: ?>
                                        <?php if (isset($errors['items'])): ?>
                                            <div class="alert alert-danger"><?php echo $errors['items']; ?></div>
                                        <?php endif; ?>

                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Item</th>
                                                        <th>Serial No</th>
                                                        <th>Issued Qty</th>
                                                        <th>Action</th>
                                                        <th>Condition</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($items as $item): ?>
                                                        <tr>
                                                            <td>
                                                                <strong><?php echo htmlspecialchars($item['item_name']); ?></strong>
                                                                <?php if ($item['item_description']): ?>
                                                                    <br><small class="text-muted"><?php echo htmlspecialchars($item['item_description']); ?></small>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td><?php echo $item['item_serial_no']; ?></td>
                                                            <td><?php echo $item['quantity']; ?></td>
                                                            <td>
                                                                <button type="button" class="btn btn-outline-success btn-sm select-return-item"
                                                                    data-item-id="<?php echo $item['id']; ?>"
                                                                    data-item-name="<?php echo htmlspecialchars($item['item_name']); ?>"
                                                                    data-item-desc="<?php echo htmlspecialchars($item['item_description']); ?>"
                                                                    data-max="<?php echo $item['quantity'] - ($item['returned_quantity'] ?? 0); ?>"
                                                                    id="select-btn-<?php echo $item['id']; ?>">
                                                                    <i class="bi bi-plus-circle"></i> Select
                                                                </button>
                                                            </td>
                                                            <td>
                                                                <select class="form-select"
                                                                    name="return_condition[<?php echo $item['id']; ?>]">
                                                                    <option value="good">Good</option>
                                                                    <option value="damaged">Damaged</option>
                                                                    <option value="lost">Lost</option>
                                                                </select>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>

                                            <!-- Table for selected items to return -->
                                            <div class="mt-4">
                                                <h6>Selected Items for Return</h6>
                                                <table class="table table-bordered table-sm" id="selected-items-table" style="display:none;">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>Item</th>
                                                            <th>Return Qty *</th>
                                                            <th>Condition</th>
                                                            <th>Remove</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <!-- JS will populate rows here -->
                                                    </tbody>
                                                </table>
                                            </div>

                                            <script>
                                                const itemsData = <?php echo json_encode($items); ?>;
                                                const selectedItems = {};

                                                document.querySelectorAll('.select-return-item').forEach(btn => {
                                                    btn.addEventListener('click', function() {
                                                        const itemId = this.getAttribute('data-item-id');
                                                        if (selectedItems[itemId]) return; // Already selected

                                                        const item = itemsData.find(i => i.id == itemId);
                                                        if (!item) return;

                                                        selectedItems[itemId] = item;

                                                        // Build row
                                                        const tbody = document.querySelector('#selected-items-table tbody');
                                                        const row = document.createElement('tr');
                                                        row.setAttribute('data-item-id', itemId);

                                                        // Item name
                                                        const tdName = document.createElement('td');
                                                        tdName.innerHTML = `<strong>${escapeHtml(item.item_name)}</strong>` +
                                                            (item.item_description ? `<br><small class="text-muted">${escapeHtml(item.item_description)}</small>` : '');
                                                        row.appendChild(tdName);

                                                        // Return Qty input
                                                        const max = item.quantity - (item.returned_quantity || 0);
                                                        const tdQty = document.createElement('td');
                                                        tdQty.innerHTML = `<input type="number" min="1" max="${max}" class="form-control form-control-sm" 
                                                        name="return_quantity[${itemId}]" value="${max}" readonly required>`;
                                                        row.appendChild(tdQty);

                                                        // Condition select
                                                        const tdCond = document.createElement('td');
                                                        tdCond.innerHTML = `<select class="form-select form-select-sm" name="return_condition[${itemId}]">
                                                        <option value="good">Good</option>
                                                        <option value="damaged">Damaged</option>
                                                        <option value="lost">Lost</option>
                                                    </select>`;
                                                        row.appendChild(tdCond);

                                                        // Remove button
                                                        const tdRemove = document.createElement('td');
                                                        tdRemove.innerHTML = `<button type="button" class="btn btn-danger btn-sm remove-selected-item" data-item-id="${itemId}">
                                                        <i class="bi bi-x"></i>
                                                    </button>`;
                                                        row.appendChild(tdRemove);

                                                        tbody.appendChild(row);

                                                        document.getElementById('selected-items-table').style.display = '';
                                                        this.disabled = true;
                                                    });
                                                });

                                                // Remove selected item
                                                document.addEventListener('click', function(e) {
                                                    if (e.target.closest('.remove-selected-item')) {
                                                        const btn = e.target.closest('.remove-selected-item');
                                                        const itemId = btn.getAttribute('data-item-id');
                                                        delete selectedItems[itemId];
                                                        const row = document.querySelector(`#selected-items-table tr[data-item-id="${itemId}"]`);
                                                        if (row) row.remove();
                                                        // Re-enable select button
                                                        const selectBtn = document.getElementById('select-btn-' + itemId);
                                                        if (selectBtn) selectBtn.disabled = false;
                                                        // Hide table if empty
                                                        if (document.querySelectorAll('#selected-items-table tbody tr').length === 0) {
                                                            document.getElementById('selected-items-table').style.display = 'none';
                                                        }
                                                    }
                                                });

                                                // Escape HTML utility
                                                function escapeHtml(text) {
                                                    return text.replace(/[&<>"']/g, function(m) {
                                                        return ({
                                                            '&': '&amp;',
                                                            '<': '&lt;',
                                                            '>': '&gt;',
                                                            '"': '&quot;',
                                                            "'": '&#39;'
                                                        })[m];
                                                    });
                                                }
                                            </script>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="col-12 mt-4">
                                    <?php if (!empty($items)): ?>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-check-circle"></i> Record Returns
                                        </button>
                                    <?php endif; ?>
                                    <a href="view.php?id=<?php echo $issuanceId; ?>" class="btn btn-outline-secondary">
                                        <i class="bi bi-x-circle"></i> Cancel
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>