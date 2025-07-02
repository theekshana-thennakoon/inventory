<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

redirectIfNotLoggedIn();

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$itemId = $_GET['id'];

// Get item info
$stmt = $pdo->prepare("SELECT i.*, c.name as category_name 
                      FROM items i
                      LEFT JOIN item_categories c ON i.category_id = c.id
                      WHERE i.id = ?");
$stmt->execute([$itemId]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    header("Location: index.php?error=Item not found");
    exit();
}

// Get issuance history
// Get issuance history
$issuances = $pdo->prepare("SELECT ii.quantity, ii.returned_quantity, ii.return_date, ii.return_condition,
                           i.issue_date, i.reason,
                           dm.name as issued_to, d.name as department_name,
                           tech.name as issued_by
                           FROM issuance_items ii
                           JOIN issuances i ON ii.issuance_id = i.id
                           JOIN department_members dm ON i.department_member_id = dm.id
                           JOIN departments d ON dm.department_id = d.id
                           JOIN technical_officers tech ON i.technical_officer_id = tech.id
                           WHERE ii.item_id = ?
                           ORDER BY i.issue_date DESC
                           LIMIT 5");
$issuances->execute([$itemId]);
$issuances = $issuances->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($item['name']); ?> - FOT Media Inventory</title>
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
                        <i class="bi bi-box-seam"></i> <?php echo htmlspecialchars($item['name']); ?>
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <?php
                            if($technical_officer_status == 'admin'){
                            ?>
                            <a href="edit.php?id=<?php echo $itemId; ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-pencil"></i> Edit
                            </a>
                            <?php
                            }
                            ?>
                            <a href="index.php" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Back
                            </a>
                        </div>
                    </div>
                </div>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($_GET['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-6">
                        <div class="card shadow-sm mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Item Information</h5>
                            </div>
                            <div class="card-body">
                                <dl class="row">
                                    <dt class="col-sm-4">Item Name</dt>
                                    <dd class="col-sm-8"><?php echo htmlspecialchars($item['name']); ?></dd>

                                    <dt class="col-sm-4">Serial No</dt>
                                    <dd class="col-sm-8"><?php echo htmlspecialchars($item['serial_no']); ?></dd>

                                    <dt class="col-sm-4">Category</dt>
                                    <dd class="col-sm-8"><?php echo htmlspecialchars($item['category_name'] ?? 'Uncategorized'); ?></dd>

                                    <dt class="col-sm-4">Description</dt>
                                    <dd class="col-sm-8"><?php echo htmlspecialchars($item['description'] ?? 'N/A'); ?></dd>

                                    <dt class="col-sm-4">Current Quantity</dt>
                                    <dd class="col-sm-8">
                                        <?php echo $item['quantity']; ?>
                                        <!-- <?php if ($item['quantity'] <= 0): ?>
                                            <span class="badge bg-danger ms-2">Out of Stock</span>
                                        <?php elseif ($item['quantity'] <= $item['minimum_quantity']): ?>
                                            <span class="badge bg-warning text-dark ms-2">Low Stock</span>
                                        <?php else: ?>
                                            <span class="badge bg-success ms-2">In Stock</span>
                                        <?php endif; ?> -->
                                    </dd>

                                    <dt class="col-sm-4">Minimum Quantity</dt>
                                    <dd class="col-sm-8"><?php echo $item['minimum_quantity']; ?></dd>

                                    <dt class="col-sm-4">Created On</dt>
                                    <dd class="col-sm-8"><?php echo date('M d, Y', strtotime($item['created_at'])); ?></dd>

                                    <dt class="col-sm-4">Last Updated</dt>
                                    <dd class="col-sm-8"><?php echo date('M d, Y', strtotime($item['updated_at'])); ?></dd>
                                </dl>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card shadow-sm mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Recent Issuances</h5>
                                <span class="badge bg-primary rounded-pill"><?php echo count($issuances); ?></span>
                            </div>
                            <div class="card-body">
                                <?php if (empty($issuances)): ?>
                                    <p class="text-muted">No issuance history for this item</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Issued To</th>
                                                    <th>Qty</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($issuances as $issuance): ?>
                                                    <tr>
                                                        <td><?php echo date('M d', strtotime($issuance['issue_date'])); ?></td>
                                                        <td><?php echo htmlspecialchars($issuance['issued_to']); ?></td>
                                                        <td><?php echo $issuance['quantity']; ?></td>
                                                        <td>
                                                            <?php if ($issuance['returned_quantity'] === null): ?>
                                                                <span class="badge bg-warning text-dark">Issued</span>
                                                            <?php elseif ($issuance['returned_quantity'] < $issuance['quantity']): ?>
                                                                <span class="badge bg-info">Partially Returned</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-success">Returned</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <a href="../issuances/?item_id=<?php echo $itemId; ?>" class="btn btn-sm btn-outline-primary mt-2">
                                        View All Issuances
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>