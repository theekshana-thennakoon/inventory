<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

redirectIfNotLoggedIn();

// Get counts for dashboard cards
$departmentCount = $pdo->query("SELECT COUNT(*) FROM departments")->fetchColumn();
$userCount = $pdo->query("SELECT COUNT(*) FROM technical_officers")->fetchColumn();
$itemCount = $pdo->query("SELECT COUNT(*) FROM items")->fetchColumn();
$categoryCount = $pdo->query("SELECT COUNT(DISTINCT category_id) FROM items")->fetchColumn();

// Get recent issuances
$recentIssuances = $pdo->query("SELECT i.id, i.issue_date, i.reason, 
                               dm.name as member_name, d.name as department_name,
                               t.name as officer_name
                               FROM issuances i
                               JOIN department_members dm ON i.department_member_id = dm.id
                               JOIN departments d ON dm.department_id = d.id
                               JOIN technical_officers t ON i.technical_officer_id = t.id
                               ORDER BY i.issue_date DESC
                               LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - FOT Media Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="assets/css/style.css" rel="stylesheet">
</head>

<body>
    <?php include 'includes/header2.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Sidebar - Collapse on mobile -->
            <div class="col-lg-3 col-md-4 d-md-block sidebar collapse" id="sidebarMenu">
                <?php include 'includes/sidebar2.php'; ?>
            </div>

            <!-- Main content area -->
            <main class="col-lg-9 col-md-8 ms-sm-auto px-md-4">
                <!-- Mobile sidebar toggle button -->
                <div class="d-flex d-md-none justify-content-between align-items-center mb-3">
                    <h2><i class="bi bi-speedometer2"></i> Dashboard</h2>
                    <button class="btn btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu">
                        <i class="bi bi-list"></i> Menu
                    </button>
                </div>

                <!-- Desktop title -->
                <div class="d-none d-md-block">
                    <h2><i class="bi bi-speedometer2"></i> Dashboard</h2>
                    <hr>
                </div>

                <!-- Summary Cards - Responsive grid -->
                <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-4 mb-4">
                    <!-- Departments Card -->
                    <div class="col">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="text-muted mb-2">Departments</h6>
                                        <h3 class="mb-0"><?php echo $departmentCount; ?></h3>
                                    </div>
                                    <div class="bg-primary bg-opacity-10 p-3 rounded">
                                        <i class="bi bi-camera fs-4 text-primary"></i>
                                    </div>
                                </div>
                                <a href="departments/" class="stretched-link"></a>
                            </div>
                        </div>
                    </div>

                    <!-- Users Card -->
                    <div class="col">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="text-muted mb-2">Users</h6>
                                        <h3 class="mb-0"><?php echo $userCount; ?></h3>
                                    </div>
                                    <div class="bg-success bg-opacity-10 p-3 rounded">
                                        <i class="bi bi-people fs-4 text-success"></i>
                                    </div>
                                </div>
                                <a href="users/" class="stretched-link"></a>
                            </div>
                        </div>
                    </div>

                    <!-- Items Card -->
                    <div class="col">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="text-muted mb-2">Items</h6>
                                        <h3 class="mb-0"><?php echo $itemCount; ?></h3>
                                    </div>
                                    <div class="bg-info bg-opacity-10 p-3 rounded">
                                        <i class="bi bi-box-seam fs-4 text-info"></i>
                                    </div>
                                </div>
                                <a href="items/" class="stretched-link"></a>
                            </div>
                        </div>
                    </div>

                    <!-- Categories Card -->
                    <div class="col">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="text-muted mb-2">Item Categories</h6>
                                        <h3 class="mb-0"><?php echo $categoryCount; ?></h3>
                                    </div>
                                    <div class="bg-warning bg-opacity-10 p-3 rounded">
                                        <i class="bi bi-tags fs-4 text-warning"></i>
                                    </div>
                                </div>
                                <a href="categories/" class="stretched-link"></a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Issuances - Responsive table -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Recent Issuances</h5>
                        <a href="issuances/" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Issued To</th>
                                        <th class="d-none d-md-table-cell">Department</th>
                                        <th class="d-none d-sm-table-cell">Reason</th>
                                        <th>Issued By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentIssuances as $issuance): ?>
                                        <tr>
                                            <td><?php echo date('M d, Y', strtotime($issuance['issue_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($issuance['member_name']); ?></td>
                                            <td class="d-none d-md-table-cell"><?php echo htmlspecialchars($issuance['department_name']); ?></td>
                                            <td class="d-none d-sm-table-cell"><?php echo htmlspecialchars($issuance['reason']); ?></td>
                                            <td><?php echo htmlspecialchars($issuance['officer_name']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
</body>

</html>