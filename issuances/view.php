<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

redirectIfNotLoggedIn();

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$issuanceId = $_GET['id'];

// Get issuance details
$stmt = $pdo->prepare("
    SELECT i.*, dm.name as member_name, d.name as department_name,
           t_officer.name as officer_name, t_officer.email as officer_email
    FROM issuances i
    JOIN department_members dm ON i.department_member_id = dm.id
    JOIN departments d ON dm.department_id = d.id
    JOIN technical_officers t_officer ON i.technical_officer_id = t_officer.id
    WHERE i.id = ?
");
$stmt->execute([$issuanceId]);
$issuance = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$issuance) {
    header("Location: index.php?error=Issuance not found");
    exit();
}

// Get issued items
$items = $pdo->prepare("
    SELECT ii.*, i.name as item_name, i.description as item_description
    FROM issuance_items ii
    JOIN items i ON ii.item_id = i.id
    WHERE ii.issuance_id = ?
");
$items->execute([$issuanceId]);
$items = $items->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issuance #<?php echo $issuanceId; ?> - FOT Media Inventory</title>
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
                        <i class="bi bi-clipboard-check"></i> Issuance #<?php echo $issuanceId; ?>
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="index.php" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Back to Issuances
                            </a>
                            <?php
                            if($technical_officer_status == 'admin' || $technical_officer_status == 'to'){
                            ?>
                            <?php if (allItemsReturned($items)): ?>
                                <span class="btn btn-sm btn-success">
                                    <i class="bi bi-check-circle"></i> All Returned
                                </span>
                            <?php else: ?>
                                <a href="return.php?id=<?php echo $issuanceId; ?>" class="btn btn-sm btn-primary">
                                    <i class="bi bi-box-arrow-in-down"></i> Record Returns
                                </a>
                            <?php endif; ?>
                            <?php
                            }
                            ?>
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
                                <h5 class="mb-0">Issuance Details</h5>
                            </div>
                            <div class="card-body">
                                <dl class="row">
                                    <dt class="col-sm-4">Issued To</dt>
                                    <dd class="col-sm-8"><?php echo htmlspecialchars($issuance['member_name']); ?> (<?php echo htmlspecialchars($issuance['department_name']); ?>)</dd>

                                    <dt class="col-sm-4">Issued By</dt>
                                    <dd class="col-sm-8"><?php echo htmlspecialchars($issuance['officer_name']); ?> (<?php echo htmlspecialchars($issuance['officer_email']); ?>)</dd>

                                    <dt class="col-sm-4">Issue Date</dt>
                                    <dd class="col-sm-8"><?php echo date('M d, Y', strtotime($issuance['issue_date'])); ?></dd>

                                    <dt class="col-sm-4">Reason</dt>
                                    <dd class="col-sm-8"><?php echo htmlspecialchars($issuance['reason']); ?></dd>

                                    <dt class="col-sm-4">Status</dt>
                                    <dd class="col-sm-8">
                                        <?php if (allItemsReturned($items)): ?>
                                            <span class="badge bg-success">Completed</span>
                                        <?php elseif (someItemsReturned($items)): ?>
                                            <span class="badge bg-info">Partially Returned</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Pending Return</span>
                                        <?php endif; ?>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card shadow-sm mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Issued Items</h5>
                                <span class="badge bg-primary rounded-pill"><?php echo count($items); ?></span>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Item</th>
                                                <th>Qty Issued</th>
                                                <th>Qty Returned</th>
                                                <th>Status</th>
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
                                                    <td><?php echo $item['quantity']; ?></td>
                                                    <td><?php echo $item['returned_quantity'] ?? '0'; ?></td>
                                                    <td>
                                                        <?php if ($item['returned_quantity'] === null): ?>
                                                            <span class="badge bg-warning text-dark">Issued</span>
                                                        <?php elseif ($item['returned_quantity'] < $item['quantity']): ?>
                                                            <span class="badge bg-info">Not Returned</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-success">Returned</span>
                                                            <?php if ($item['return_date']): ?>
                                                                <br><small><?php echo date('M d, Y', strtotime($item['return_date'])); ?></small>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
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

<?php
// Helper functions
function allItemsReturned($items)
{
    foreach ($items as $item) {
        if ($item['returned_quantity'] === null || $item['returned_quantity'] < $item['quantity']) {
            return false;
        }
    }
    return true;
}

function someItemsReturned($items)
{
    foreach ($items as $item) {
        if ($item['returned_quantity'] !== null && $item['returned_quantity'] > 0) {
            return true;
        }
    }
    return false;
}
?>