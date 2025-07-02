<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

// Check if user is logged in
$id = $_SESSION['technical_officer_id'];
$name = $_SESSION['technical_officer_name'];
$status = $_SESSION['technical_officer_status'];
// Fetch inventory data
$stmt = $pdo->query("SELECT i.* , it.name AS item_name , ii.returned_quantity as returned_quantity, ii.return_date as return_date, it.name AS item_name, d.name AS department_name, dm.name AS department_member_name, t.name as issued_by_name FROM issuances i join issuance_items ii ON i.id = ii.issuance_id
                     JOIN items it ON it.id = ii.item_id join department_members dm ON dm.id = i.department_member_id
                     JOIN departments d ON d.id = dm.department_id JOIN technical_officers t ON t.id = i.technical_officer_id
                     ORDER BY i.issue_date DESC");
$products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="../assets/css/style.css" rel="stylesheet">
    <title>Inventory Report</title>
    <style>
        h1 {
            color: #333;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 8px 12px;
            text-align: left;
        }

        th {
            background: #f4f4f4;
        }

        tr:nth-child(even) {
            background: #fafafa;
        }
    </style>
</head>

<body>
    <?php include 'includes/header2.php'; ?>
    <div class="container-fluid mt-4">
        <div class="mb-3 d-flex gap-2 justify-content-end">

            <button id="downloadPdfBtn" class="btn btn-danger mb-3">
                <i class="bi bi-file-earmark-pdf"></i> Download PDF
            </button>
        </div>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
        <script>
            document.getElementById('downloadPdfBtn').addEventListener('click', function() {
                const inventoryDiv = document.querySelector('.inventory');
                html2canvas(inventoryDiv, {
                    scale: 2
                }).then(canvas => {
                    const imgData = canvas.toDataURL('image/png');
                    const pdf = new window.jspdf.jsPDF({
                        orientation: 'landscape',
                        unit: 'mm',
                        format: 'a4'
                    });
                    const pageWidth = pdf.internal.pageSize.getWidth();
                    const pageHeight = pdf.internal.pageSize.getHeight();
                    const margin = 5; // mm
                    const imgWidth = pageWidth - margin * 2;
                    const imgHeight = canvas.height * imgWidth / canvas.width;
                    let y = 0; // Center image vertically, but do not add margin to top and bottom
                    pdf.addImage(imgData, 'PNG', margin, y, imgWidth, imgHeight);
                    pdf.save('inventory_report <?= date('Y-m-d') ?>.pdf');
                });
            });
        </script>
        <div class="mb-3 d-flex justify-content-end">
            <input type="text" id="searchInput" class="form-control w-25" placeholder="Search inventory report...">
        </div>
        <div class="inventory pt-5">
            <center>
                <img src="https://fotmediainventory.great-site.net/assets/logoblack.png" class="img-fluid" style="width: 8%;" alt="">
            </center>
            <h1 class="text-center mb-3">FOT Media Inventory Report.</h1>
            <h5 style="text-align: end;" class="me-2 fs-5">Date: <?= date('Y-m-d') ?></h5>
            <div class="table-responsive">
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const searchInput = document.getElementById('searchInput');
                        const table = document.querySelector('table');
                        const rows = table.querySelectorAll('tbody tr');

                        searchInput.addEventListener('keyup', function() {
                            const query = this.value.toLowerCase();
                            rows.forEach(row => {
                                const text = row.textContent.toLowerCase();
                                row.style.display = text.includes(query) ? '' : 'none';
                            });
                        });
                    });
                </script>
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Issued date</th>
                            <th>Issued by</th>
                            <th>Reason</th>
                            <th>Department</th>
                            <th>Issued to</th>
                            <th>Issued items</th>
                            <th>Returned date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($products) === 0): ?>
                            <tr>
                                <td colspan="5">Inventory not found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($products as $i => $product): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><?= htmlspecialchars($product['issue_date']) ?></td>
                                    <td><?= htmlspecialchars($product['issued_by_name']) ?></td>
                                    <td><?= htmlspecialchars($product['reason']) ?></td>
                                    <td><?= htmlspecialchars($product['department_name']) ?></td>
                                    <td><?= htmlspecialchars($product['department_member_name']) ?></td>
                                    <td><?= htmlspecialchars($product['item_name']) ?></td>
                                    <td><?= htmlspecialchars($product['return_date']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</body>

</html>