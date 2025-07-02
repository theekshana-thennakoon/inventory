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
$stmt = $pdo->prepare("SELECT * FROM items WHERE id = ?");
$stmt->execute([$itemId]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    header("Location: index.php?error=Item not found");
    exit();
}

// Get all categories for dropdown
$categories = $pdo->query("SELECT * FROM item_categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

$errors = [];
$name = $item['name'];
$description = $item['description'];
$quantity = $item['quantity'];
$minimumQuantity = $item['minimum_quantity'];
$categoryId = $item['category_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $quantity = (int)$_POST['quantity'];
    $minimumQuantity = (int)$_POST['minimum_quantity'];
    $categoryId = $_POST['category_id'] ?: null;

    // Validation
    if (empty($name)) {
        $errors['name'] = 'Item name is required';
    } elseif (strlen($name) > 100) {
        $errors['name'] = 'Name must be less than 100 characters';
    }

    if (strlen($description) > 255) {
        $errors['description'] = 'Description must be less than 255 characters';
    }

    if ($quantity < 0) {
        $errors['quantity'] = 'Quantity cannot be negative';
    }

    if ($minimumQuantity < 1) {
        $errors['minimum_quantity'] = 'Minimum quantity must be at least 1';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE items SET name = ?, description = ?, quantity = ?, minimum_quantity = ?, category_id = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$name, $description, $quantity, $minimumQuantity, $categoryId, $itemId]);

            header("Location: view.php?id=$itemId&success=Item updated successfully");
            exit();
        } catch (PDOException $e) {
            $errors['database'] = "Error updating item: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit <?php echo htmlspecialchars($item['name']); ?> - FOT Media Inventory</title>
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
                        <i class="bi bi-box-seam"></i> Edit <?php echo htmlspecialchars($item['name']); ?>
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="view.php?id=<?php echo $itemId; ?>" class="btn btn-secondary">
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
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Item Name *</label>
                                        <input type="text" class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>"
                                            id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                                        <?php if (isset($errors['name'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['name']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="category_id" class="form-label">Category</label>
                                        <select class="form-select" id="category_id" name="category_id">
                                            <option value="">-- Select Category --</option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?php echo $category['id']; ?>" <?php echo $categoryId == $category['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($category['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Description</label>
                                        <textarea class="form-control <?php echo isset($errors['description']) ? 'is-invalid' : ''; ?>"
                                            id="description" name="description" rows="3"><?php echo htmlspecialchars($description); ?></textarea>
                                        <?php if (isset($errors['description'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['description']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="quantity" class="form-label">Quantity *</label>
                                        <input type="number" class="form-control <?php echo isset($errors['quantity']) ? 'is-invalid' : ''; ?>"
                                            id="quantity" name="quantity" value="<?php echo htmlspecialchars($quantity); ?>" min="0" required>
                                        <?php if (isset($errors['quantity'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['quantity']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="minimum_quantity" class="form-label">Minimum Quantity *</label>
                                        <input type="number" class="form-control <?php echo isset($errors['minimum_quantity']) ? 'is-invalid' : ''; ?>"
                                            id="minimum_quantity" name="minimum_quantity" value="<?php echo htmlspecialchars($minimumQuantity); ?>" min="1" required>
                                        <?php if (isset($errors['minimum_quantity'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['minimum_quantity']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Save Changes
                                </button>
                                <a href="view.php?id=<?php echo $itemId; ?>" class="btn btn-outline-secondary">
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