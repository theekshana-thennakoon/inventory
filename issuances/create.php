<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

redirectIfNotLoggedIn();

// Get all departments with members
$departments = $pdo->query("
    SELECT d.id, d.name, dm.id as member_id, dm.name as member_name, dm.role 
    FROM departments d
    JOIN department_members dm ON d.id = dm.department_id
    ORDER BY d.name, dm.role DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Get all available items (quantity > 0)
$items = $pdo->query("
    SELECT id, name, quantity , serial_no 
    FROM items 
    WHERE quantity > 0 
    ORDER BY name
")->fetchAll(PDO::FETCH_ASSOC);

$errors = [];
$departmentMemberId = $reason = '';
$issueDate = date('Y-m-d');
$selectedItems = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $departmentMemberId = $_POST['department_member_id'];
    $reason = trim($_POST['reason']);
    $issueDate = $_POST['issue_date'];
    $selectedItems = $_POST['items'] ?? [];

    // Validation
    if (empty($departmentMemberId)) {
        $errors['department_member_id'] = 'Please select who you are issuing to';
    }

    if (empty($reason)) {
        $errors['reason'] = 'Reason for issuance is required';
    } elseif (strlen($reason) > 255) {
        $errors['reason'] = 'Reason must be less than 255 characters';
    }

    // Filter out items with quantity <= 0
    $selectedItemsWithQuantity = array_filter($selectedItems, function ($quantity) {
        return $quantity > 0;
    });

    if (empty($selectedItemsWithQuantity)) {
        $errors['items'] = 'Please select at least one item with quantity greater than 0';
    } else {
        // Validate item quantities for items that have quantity > 0
        foreach ($selectedItemsWithQuantity as $itemId => $quantity) {
            // Check if item exists and has sufficient quantity
            $item = $pdo->prepare("SELECT quantity FROM items WHERE id = ?");
            $item->execute([$itemId]);
            $itemData = $item->fetch(PDO::FETCH_ASSOC);

            if (!$itemData || $itemData['quantity'] < $quantity) {
                $errors['items'] = 'One or more items have insufficient quantity';
                break;
            }
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Create issuance record
            $stmt = $pdo->prepare("
                INSERT INTO issuances 
                (technical_officer_id, department_member_id, reason, issue_date) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $_SESSION['technical_officer_id'],
                $departmentMemberId,
                $reason,
                $issueDate
            ]);
            $issuanceId = $pdo->lastInsertId();

            // Add issuance items and update inventory (only for items with quantity > 0)
            foreach ($selectedItems as $itemId => $quantity) {
                if ($quantity > 0) {
                    // Add to issuance_items
                    $stmt = $pdo->prepare("
                        INSERT INTO issuance_items 
                        (issuance_id, item_id, quantity) 
                        VALUES (?, ?, ?)
                    ");
                    $stmt->execute([$issuanceId, $itemId, $quantity]);

                    // Update item quantity
                    $stmt = $pdo->prepare("
                        UPDATE items 
                        SET quantity = quantity - ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([$quantity, $itemId]);
                }
            }

            $pdo->commit();

            header("Location: view.php?id=$issuanceId&success=Items issued successfully");
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = "Error issuing items: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issue Items - FOT Media Inventory</title>
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
                    <h1 class="h2"><i class="bi bi-clipboard-check"></i> Issue Items</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="../issuances/" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Issuances
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
                                        <label for="department_member_id" class="form-label">Issue To *</label>
                                        <select class="form-select <?php echo isset($errors['department_member_id']) ? 'is-invalid' : ''; ?>"
                                            id="department_member_id" name="department_member_id" required>
                                            <option value="">Select Department Member</option>
                                            <?php
                                            $currentDept = null;
                                            foreach ($departments as $dept):
                                                if ($dept['name'] != $currentDept) {
                                                    if ($currentDept !== null) echo '</optgroup>';
                                                    echo '<optgroup label="' . htmlspecialchars($dept['name']) . '">';
                                                    $currentDept = $dept['name'];
                                                }
                                            ?>
                                                <option value="<?php echo $dept['member_id']; ?>" <?php echo $departmentMemberId == $dept['member_id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($dept['member_name']); ?> (<?php echo ucfirst($dept['role']); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                            </optgroup>
                                        </select>
                                        <?php if (isset($errors['department_member_id'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['department_member_id']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="issue_date" class="form-label">Issue Date *</label>
                                        <input type="date" class="form-control"
                                            id="issue_date" name="issue_date"
                                            value="<?php echo htmlspecialchars($issueDate); ?>" readonly required>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="mb-3">
                                        <label for="reason" class="form-label">Reason for Issue *</label>
                                        <textarea class="form-control <?php echo isset($errors['reason']) ? 'is-invalid' : ''; ?>"
                                            id="reason" name="reason" rows="3" required><?php echo htmlspecialchars($reason); ?></textarea>
                                        <?php if (isset($errors['reason'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['reason']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <h5 class="mb-3">Items to Issue</h5>
                                    <?php if (isset($errors['items'])): ?>
                                        <div class="alert alert-danger"><?php echo $errors['items']; ?></div>
                                    <?php endif; ?>

                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead class="table-light">
                                                <tr>
                                                    <td colspan="1">
                                                        <input type="text" id="itemSearch" class="form-control" placeholder="Search by item name or Serial No...">
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Serial No</th>
                                                    <th>Item</th>
                                                    <th>Available</th>
                                                    <th>Quantity to Issue</th>
                                                </tr>

                                                <script>
                                                    document.addEventListener('DOMContentLoaded', function() {
                                                        const searchInput = document.getElementById('itemSearch');
                                                        const table = searchInput.closest('table');
                                                        const rows = Array.from(table.querySelectorAll('tbody tr'));

                                                        searchInput.addEventListener('input', function() {
                                                            const query = this.value.trim().toLowerCase();
                                                            rows.forEach(row => {
                                                                const name = row.cells[1].textContent.toLowerCase();
                                                                const id = row.cells[0].textContent.toLowerCase();
                                                                //const id = row.querySelector('input[type="number"]').name.match(/\d+/)[0];
                                                                if (name.includes(query) || id.includes(query)) {
                                                                    row.style.display = '';
                                                                } else {
                                                                    row.style.display = 'none';
                                                                }
                                                            });
                                                        });
                                                    });
                                                </script>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($items as $item): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($item['serial_no']); ?></td>
                                                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                                                        <td><?php echo $item['quantity']; ?></td>
                                                        <td>
                                                            <input type="number"
                                                                name="items[<?php echo $item['id']; ?>]"
                                                                class="form-control"
                                                                min="0"
                                                                max="<?php echo $item['quantity']; ?>"
                                                                value="<?php echo $selectedItems[$item['id']] ?? 0; ?>">
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="col-12 mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-check-circle"></i> Issue Items
                                    </button>
                                    <a href="../issuances/" class="btn btn-outline-secondary">
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