<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

redirectIfNotLoggedIn();

$errors = [];
$departmentName = $headName = $member1Name = $member2Name = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $departmentName = trim($_POST['department_name']);
    $headName = trim($_POST['head_name']);
    $member1Name = trim($_POST['member1_name']);
    $member2Name = trim($_POST['member2_name']);

    // Validation
    if (empty($departmentName)) {
        $errors['department_name'] = 'Department name is required';
    }

    if (empty($headName)) {
        $errors['head_name'] = 'Head of department is required';
    }

    if (empty($member1Name)) {
        $errors['member1_name'] = 'Member 1 is required';
    }

    if (empty($member2Name)) {
        $errors['member2_name'] = 'Member 2 is required';
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Insert department
            $stmt = $pdo->prepare("INSERT INTO departments (name) VALUES (?)");
            $stmt->execute([$departmentName]);
            $departmentId = $pdo->lastInsertId();

            // Insert head
            $stmt = $pdo->prepare("INSERT INTO department_members (department_id, name, role) VALUES (?, ?, 'head')");
            $stmt->execute([$departmentId, $headName]);

            // Insert members
            $stmt = $pdo->prepare("INSERT INTO department_members (department_id, name, role) VALUES (?, ?, 'member')");
            $stmt->execute([$departmentId, $member1Name]);
            $stmt->execute([$departmentId, $member2Name]);

            $pdo->commit();

            header("Location: index.php?success=Department created successfully");
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = "Error creating department: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Department - FOT Media Inventory</title>
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
                    <h1 class="h2"><i class="bi bi-building"></i> Add New Department</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="index.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Departments
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
                                            id="member1_name" name="member1_name" value="<?php echo htmlspecialchars($member1Name); ?>" required>
                                        <?php if (isset($errors['member1_name'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['member1_name']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="member2_name" class="form-label">Member 2 *</label>
                                        <input type="text" class="form-control <?php echo isset($errors['member2_name']) ? 'is-invalid' : ''; ?>"
                                            id="member2_name" name="member2_name" value="<?php echo htmlspecialchars($member2Name); ?>" required>
                                        <?php if (isset($errors['member2_name'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['member2_name']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Create Department
                                </button>
                                <a href="index.php" class="btn btn-outline-secondary">
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