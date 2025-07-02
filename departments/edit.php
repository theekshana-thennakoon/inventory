<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

redirectIfNotLoggedIn();

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$departmentId = $_GET['id'];

// Get department info
$stmt = $pdo->prepare("SELECT * FROM departments WHERE id = ?");
$stmt->execute([$departmentId]);
$department = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$department) {
    header("Location: index.php?error=Department not found");
    exit();
}

// Get department members
$stmt = $pdo->prepare("SELECT * FROM department_members WHERE department_id = ?");
$stmt->execute([$departmentId]);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Initialize variables
$errors = [];
$departmentName = $department['name'];
$headName = '';
$memberNames = [];

// Extract members
foreach ($members as $member) {
    if ($member['role'] === 'head') {
        $headName = $member['name'];
    } else {
        $memberNames[] = $member['name'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $departmentName = trim($_POST['department_name']);
    $headName = trim($_POST['head_name']);
    $memberNames = [
        trim($_POST['member1_name']),
        trim($_POST['member2_name'])
    ];

    // Validation
    if (empty($departmentName)) {
        $errors['department_name'] = 'Department name is required';
    }

    if (empty($headName)) {
        $errors['head_name'] = 'Head of department is required';
    }

    if (empty($memberNames[0])) {
        $errors['member1_name'] = 'Member 1 is required';
    }

    if (empty($memberNames[1])) {
        $errors['member2_name'] = 'Member 2 is required';
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Update department
            $stmt = $pdo->prepare("UPDATE departments SET name = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$departmentName, $departmentId]);

            // Delete existing members
            $stmt = $pdo->prepare("DELETE FROM department_members WHERE department_id = ?");
            $stmt->execute([$departmentId]);

            // Insert head
            $stmt = $pdo->prepare("INSERT INTO department_members (department_id, name, role) VALUES (?, ?, 'head')");
            $stmt->execute([$departmentId, $headName]);

            // Insert members
            $stmt = $pdo->prepare("INSERT INTO department_members (department_id, name, role) VALUES (?, ?, 'member')");
            foreach ($memberNames as $memberName) {
                $stmt->execute([$departmentId, $memberName]);
            }

            $pdo->commit();

            header("Location: view.php?id=$departmentId&success=Department updated successfully");
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = "Error updating department: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit <?php echo htmlspecialchars($department['name']); ?> - FOT Media Inventory</title>
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
                        <i class="bi bi-building"></i> Edit <?php echo htmlspecialchars($department['name']); ?>
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="view.php?id=<?php echo $departmentId; ?>" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back to View
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
                            <div class="mb-3">
                                <label for="department_name" class="form-label">Department Name *</label>
                                <input type="text" class="form-control <?php echo isset($errors['department_name']) ? 'is-invalid' : ''; ?>"
                                    id="department_name" name="department_name" value="<?php echo htmlspecialchars($departmentName); ?>" required>
                                <?php if (isset($errors['department_name'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['department_name']; ?></div>
                                <?php endif; ?>
                            </div>

                            <h5 class="mt-4">Department Members</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="head_name" class="form-label">Head of Department *</label>
                                        <input type="text" class="form-control <?php echo isset($errors['head_name']) ? 'is-invalid' : ''; ?>"
                                            id="head_name" name="head_name" value="<?php echo htmlspecialchars($headName); ?>" required>
                                        <?php if (isset($errors['head_name'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['head_name']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="member1_name" class="form-label">Member 1 *</label>
                                        <input type="text" class="form-control <?php echo isset($errors['member1_name']) ? 'is-invalid' : ''; ?>"
                                            id="member1_name" name="member1_name" value="<?php echo htmlspecialchars($memberNames[0] ?? ''); ?>" required>
                                        <?php if (isset($errors['member1_name'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['member1_name']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="member2_name" class="form-label">Member 2 *</label>
                                        <input type="text" class="form-control <?php echo isset($errors['member2_name']) ? 'is-invalid' : ''; ?>"
                                            id="member2_name" name="member2_name" value="<?php echo htmlspecialchars($memberNames[1] ?? ''); ?>" required>
                                        <?php if (isset($errors['member2_name'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['member2_name']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Save Changes
                                </button>
                                <a href="view.php?id=<?php echo $departmentId; ?>" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-circle"></i> Cancel
                                </a>
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