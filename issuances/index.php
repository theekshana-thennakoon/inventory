<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

redirectIfNotLoggedIn();

// Pagination setup
$perPage = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $perPage;

// Filter parameters
$departmentId = isset($_GET['department_id']) ? (int)$_GET['department_id'] : null;
$itemId = isset($_GET['item_id']) ? (int)$_GET['item_id'] : null;
$officerId = isset($_GET['officer_id']) ? (int)$_GET['officer_id'] : null;
$status = isset($_GET['status']) ? $_GET['status'] : null;
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : null;
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : null;

// Build the base query and count query
// Using 'tech' instead of 'to' as the alias
$query = "SELECT i.id, i.issue_date, i.reason, 
          dm.name as member_name, d.name as department_name,
          tech.name as officer_name,
          COUNT(ii.id) as item_count,
          SUM(CASE WHEN ii.returned_quantity IS NULL OR ii.returned_quantity < ii.quantity THEN 1 ELSE 0 END) as pending_items
          FROM issuances i
          JOIN department_members dm ON i.department_member_id = dm.id
          JOIN departments d ON dm.department_id = d.id
          JOIN technical_officers tech ON i.technical_officer_id = tech.id
          JOIN issuance_items ii ON i.id = ii.issuance_id
          WHERE 1=1";

$countQuery = "SELECT COUNT(DISTINCT i.id) 
               FROM issuances i
               JOIN department_members dm ON i.department_member_id = dm.id
               JOIN technical_officers `to` ON i.technical_officer_id = `to`.id
               JOIN issuance_items ii ON i.id = ii.issuance_id
               WHERE 1=1";

// Apply filters
$params = [];
$countParams = [];

if ($departmentId) {
    $query .= " AND d.id = ?";
    $countQuery .= " AND dm.department_id = ?";
    $params[] = $departmentId;
    $countParams[] = $departmentId;
}

if ($itemId) {
    $query .= " AND ii.item_id = ?";
    $countQuery .= " AND ii.item_id = ?";
    $params[] = $itemId;
    $countParams[] = $itemId;
}

if ($officerId) {
    $query .= " AND i.technical_officer_id = ?";
    $countQuery .= " AND i.technical_officer_id = ?";
    $params[] = $officerId;
    $countParams[] = $officerId;
}

if ($dateFrom) {
    $query .= " AND i.issue_date >= ?";
    $countQuery .= " AND i.issue_date >= ?";
    $params[] = $dateFrom;
    $countParams[] = $dateFrom;
}

if ($dateTo) {
    $query .= " AND i.issue_date <= ?";
    $countQuery .= " AND i.issue_date <= ?";
    $params[] = $dateTo;
    $countParams[] = $dateTo;
}

// Complete the queries
$query .= " GROUP BY i.id ORDER BY i.id DESC LIMIT $perPage OFFSET $offset";

// Get issuances
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$issuances = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total count for pagination
$stmt = $pdo->prepare($countQuery);
$stmt->execute($countParams);
$totalIssuances = $stmt->fetchColumn();
$totalPages = ceil($totalIssuances / $perPage);

// Get filter options
$departments = $pdo->query("SELECT id, name FROM departments ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$items = $pdo->query("SELECT id, name FROM items ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$officers = $pdo->query("SELECT id, name, status FROM technical_officers ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Item Issuances - FOT Media Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="../assets/css/style.css" rel="stylesheet">
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
                    <h1 class="h2"><i class="bi bi-clipboard-check"></i> Item Issuances</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <?php
                        if ($technical_officer_status == 'admin' || $technical_officer_status == 'to') {
                        ?>
                            <a href="create.php" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> New Issuance
                            </a>
                        <?php
                        }
                        ?>
                    </div>
                </div>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($_GET['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Filter Form -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Filters</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label for="department_id" class="form-label">Department</label>
                                <select class="form-select" id="department_id" name="department_id">
                                    <option value="">All Departments</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo $dept['id']; ?>" <?php echo $departmentId == $dept['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($dept['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label for="item_id" class="form-label">Item</label>
                                <select class="form-select" id="item_id" name="item_id">
                                    <option value="">All Items</option>
                                    <?php foreach ($items as $item): ?>
                                        <option value="<?php echo $item['id']; ?>" <?php echo $itemId == $item['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($item['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label for="officer_id" class="form-label">Technical Officer</label>
                                <select class="form-select" id="officer_id" name="officer_id">
                                    <option value="">All Officers</option>
                                    <?php foreach ($officers as $officer): ?>
                                        <?php
                                        if ($officer['status'] == 'to' || $officer['status'] == 'admin') {
                                        ?>
                                            <option value="<?php echo $officer['id']; ?>" <?php echo $officerId == $officer['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($officer['name']); ?>
                                            </option>
                                        <?php
                                        }
                                        ?>

                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label for="date_from" class="form-label">From Date</label>
                                <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>">
                            </div>

                            <div class="col-md-3">
                                <label for="date_to" class="form-label">To Date</label>
                                <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>">
                            </div>

                            <div class="col-md-12 mt-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-funnel"></i> Apply Filters
                                </button>
                                <a href="index.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <?php if (empty($issuances)): ?>
                            <div class="alert alert-info">
                                No issuances found matching your criteria.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Date</th>
                                            <th>Issued To</th>
                                            <th>Department</th>
                                            <th>Items</th>
                                            <th>Issued By</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($issuances as $issuance): ?>
                                            <tr>
                                                <td><?php echo $issuance['id']; ?></td>
                                                <td><?php echo date('M d, Y', strtotime($issuance['issue_date'])); ?></td>
                                                <td><?php echo htmlspecialchars($issuance['member_name']); ?></td>
                                                <td><?php echo htmlspecialchars($issuance['department_name']); ?></td>
                                                <td><?php echo $issuance['item_count']; ?></td>
                                                <td><?php echo htmlspecialchars($issuance['officer_name']); ?></td>
                                                <td>
                                                    <?php if ($issuance['pending_items'] > 0): ?>
                                                        <span class="badge bg-warning text-dark">Pending (<?php echo $issuance['pending_items']; ?>)</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success">Completed</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="view.php?id=<?php echo $issuance['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-eye"></i> View
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <nav aria-label="Page navigation">
                                <ul class="pagination justify-content-center">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>">
                                                <i class="bi bi-chevron-double-left"></i>
                                            </a>
                                        </li>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                                <i class="bi bi-chevron-left"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                                <i class="bi bi-chevron-right"></i>
                                            </a>
                                        </li>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $totalPages])); ?>">
                                                <i class="bi bi-chevron-double-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>