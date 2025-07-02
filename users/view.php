<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

redirectIfNotLoggedIn();

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$userId = $_GET['id'];

// Get user info
$stmt = $pdo->prepare("SELECT * FROM technical_officers WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: index.php?error=User not found");
    exit();
}

// Get recent issuances by this user
$issuances = $pdo->prepare("SELECT i.id, i.issue_date, i.reason, 
                           d.name as department_name,
                           dm.name as issued_to
                           FROM issuances i
                           JOIN department_members dm ON i.department_member_id = dm.id
                           JOIN departments d ON dm.department_id = d.id
                           WHERE i.technical_officer_id = ?
                           ORDER BY i.issue_date DESC
                           LIMIT 5");
$issuances->execute([$userId]);
$issuances = $issuances->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user['name']); ?> - FOT Media Inventory</title>
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
                        <i class="bi bi-person"></i> <?php echo htmlspecialchars($user['name']); ?>
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <?php
                            if ($technical_officer_status == 'admin') {
                            ?>
                                <a href="edit.php?id=<?php echo $userId; ?>" class="btn btn-sm btn-outline-secondary">
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
                                <h5 class="mb-0">Officer Information</h5>
                            </div>
                            <div class="card-body">
                                <dl class="row">
                                    <dt class="col-sm-4">Full Name</dt>
                                    <dd class="col-sm-8"><?php echo htmlspecialchars($user['name']); ?></dd>

                                    <dt class="col-sm-4">Reg no / Email</dt>
                                    <dd class="col-sm-8"><?php echo htmlspecialchars($user['email']); ?></dd>

                                    <dt class="col-sm-4">Joined On</dt>
                                    <dd class="col-sm-8"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></dd>

                                    <dt class="col-sm-4">Last Updated</dt>
                                    <dd class="col-sm-8"><?php echo date('M d, Y', strtotime($user['updated_at'])); ?></dd>
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
                                    <p class="text-muted">No recent issuances</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Issued To</th>
                                                    <th>Department</th>
                                                    <th>Reason</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($issuances as $issuance): ?>
                                                    <tr>
                                                        <td><?php echo date('M d', strtotime($issuance['issue_date'])); ?></td>
                                                        <td><?php echo htmlspecialchars($issuance['issued_to']); ?></td>
                                                        <td><?php echo htmlspecialchars($issuance['department_name']); ?></td>
                                                        <td><?php echo htmlspecialchars(substr($issuance['reason'], 0, 20)) . (strlen($issuance['reason']) > 20 ? '...' : ''); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <a href="../issuances/?officer_id=<?php echo $userId; ?>" class="btn btn-sm btn-outline-primary mt-2">
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