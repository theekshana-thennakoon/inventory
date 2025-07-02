<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

redirectIfNotLoggedIn();

// Get all technical officers
$query = "SELECT id, name, email, status, created_at FROM technical_officers ORDER BY name";
$users = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technical Officers - FOT Media Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="../../assets/css/style.css" rel="stylesheet">
    <style>
        /* Responsive table styles */
        @media (max-width: 768px) {
            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            /* Card-style table for mobile */
            table {
                width: 100%;
                border-collapse: collapse;
            }

            table thead {
                display: none;
            }

            table tr {
                display: block;
                margin-bottom: 1rem;
                border: 1px solid #dee2e6;
                border-radius: 0.25rem;
                background-color: #fff;
            }

            table td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 0.75rem;
                text-align: right;
                border-bottom: 1px solid #dee2e6;
            }

            table td::before {
                content: attr(data-label);
                font-weight: bold;
                margin-right: 1rem;
                text-align: left;
                color: #6c757d;
            }

            table td:last-child {
                border-bottom: 0;
            }

            .table-hover tbody tr:hover {
                background-color: rgba(0, 0, 0, 0.03);
            }

            /* Adjust action buttons for mobile */
            .btn-sm {
                padding: 0.25rem 0.5rem;
                font-size: 0.875rem;
            }
        }

        /* Badge styles */
        .badge {
            font-size: 0.85em;
            padding: 0.35em 0.65em;
        }

        /* Action buttons */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        /* Status badge colors */
        .bg-admin {
            background-color: #0d6efd;
        }

        .bg-to {
            background-color: #198754;
        }

        .bg-union {
            background-color: #ffc107;
            color: #000 !important;
        }

        .bg-fot {
            background-color: #0dcaf0;
            color: #000 !important;
        }
    </style>
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
                    <h1 class="h2"><i class="bi bi-people"></i> Technical Officers</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <?php if ($technical_officer_status == 'admin'): ?>
                            <a href="create.php" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> <span class="d-none d-sm-inline">Add User</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($_GET['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($_GET['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Name</th>
                                        <th>Reg No / Email</th>
                                        <th class="text-nowrap">Status</th>
                                        <th class="text-nowrap">Joined On</th>
                                        <th class="text-nowrap">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($users)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-4">No technical officers found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td data-label="Name"><?php echo htmlspecialchars($user['name']); ?></td>
                                                <td data-label="Email"><?php echo htmlspecialchars($user['email']); ?></td>
                                                <td data-label="Status">
                                                    <span class="badge 
                                                        <?php
                                                        switch ($user['status']) {
                                                            case 'admin':
                                                                echo 'bg-admin';
                                                                break;
                                                            case 'to':
                                                                echo 'bg-to';
                                                                break;
                                                            case 'union':
                                                                echo 'bg-union';
                                                                break;
                                                            default:
                                                                echo 'bg-fot';
                                                        }
                                                        ?>">
                                                        <?php
                                                        switch ($user['status']) {
                                                            case 'admin':
                                                                echo "Admin";
                                                                break;
                                                            case 'to':
                                                                echo "Technical Officer";
                                                                break;
                                                            case 'union':
                                                                echo "Union";
                                                                break;
                                                            default:
                                                                echo "FOT";
                                                        }
                                                        ?>
                                                    </span>
                                                </td>
                                                <td data-label="Joined On" class="text-nowrap"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                                <td data-label="Actions">
                                                    <div class="action-buttons">
                                                        <a href="view.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                            <i class="bi bi-eye"></i> <span class="d-none d-md-inline">View</span>
                                                        </a>
                                                        <?php if ($technical_officer_status == 'admin'): ?>
                                                            <a href="edit.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                                <i class="bi bi-pencil"></i> <span class="d-none d-md-inline">Edit</span>
                                                            </a>
                                                            <?php if ($user['id'] != $_SESSION['technical_officer_id']): ?>
                                                                <button class="btn btn-sm btn-outline-danger delete-btn" data-id="<?php echo $user['id']; ?>">
                                                                    <i class="bi bi-trash"></i> <span class="d-none d-md-inline">Delete</span>
                                                                </button>
                                                            <?php else: ?>
                                                                <button class="btn btn-sm btn-outline-secondary" disabled>
                                                                    <i class="bi bi-trash"></i> <span class="d-none d-md-inline">Delete</span>
                                                                </button>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this technical officer? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form id="deleteForm" method="POST" action="delete.php">
                        <input type="hidden" name="id" id="deleteId">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Delete confirmation
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const userId = this.getAttribute('data-id');
                document.getElementById('deleteId').value = userId;
                const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
                deleteModal.show();
            });
        });

        // Make rows clickable (optional)
        document.querySelectorAll('tbody tr').forEach(row => {
            if (row.querySelector('a[href^="view.php"]') && !row.querySelector('.delete-btn')) {
                row.style.cursor = 'pointer';
                row.addEventListener('click', (e) => {
                    if (!e.target.closest('a, button')) {
                        window.location = row.querySelector('a[href^="view.php"]').href;
                    }
                });
            }
        });
    </script>
</body>

</html>