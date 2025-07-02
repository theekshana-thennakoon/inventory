<?php
$technical_officer_status = $_SESSION["technical_officer_status"];
?>
<div class="card h-100 border-0 shadow-none">
    <div class="card-body p-2">
        <div class="list-group list-group-flush">
            <a href="../dashboard.php" class="list-group-item list-group-item-action">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard
            </a>
            <?php
            if ($technical_officer_status == 'admin' || $technical_officer_status == 'to') {
            ?>
                <a href="../departments/" class="list-group-item list-group-item-action">
                    <i class="bi bi-building me-2"></i> Departments
                </a>
            <?php
            }
            ?>
            <?php
            if ($technical_officer_status == 'admin' || $technical_officer_status == 'to') {
            ?>
                <a href="../users/" class="list-group-item list-group-item-action">
                    <i class="bi bi-people me-2"></i> Users
                </a>
            <?php
            }
            ?>
            <?php
            if ($technical_officer_status == 'admin' || $technical_officer_status == 'to') {
            ?>

                <a href="../items/" class="list-group-item list-group-item-action">
                    <i class="bi bi-box-seam me-2"></i> Item List
                </a>
            <?php
            }
            ?>
            <?php
            if ($technical_officer_status == 'admin' || $technical_officer_status == 'to') {
            ?>
                <a href="../categories/" class="list-group-item list-group-item-action">
                    <i class="bi bi-tags me-2"></i> Item Categories
                </a>
            <?php
            }
            ?>
            <a href="../issuances/" class="list-group-item list-group-item-action">
                <i class="bi bi-clipboard-check me-2"></i> Item Issuances
            </a>
            <a href="../reports.php/" class="list-group-item list-group-item-action">
                <i class="bi bi-graph-up me-2"></i> Reports
            </a>
        </div>
    </div>
</div>