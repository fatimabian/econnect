<?php
session_start();
include "db_connect.php";

/*   
   ADD CREW MEMBER
*/
if (isset($_POST['add_crew'])) {
    $barangay = trim($_POST['barangay']);
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $status = "Inactive";

    $check = $conn->prepare("SELECT id FROM collection_crew WHERE username=?");
    $check->bind_param("s", $username);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $_SESSION['error'] = "Username already exists.";
    } else {
        $stmt = $conn->prepare("INSERT INTO collection_crew (barangay, username, status, password) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $barangay, $username, $status, $password);
        $stmt->execute();
        $_SESSION['success'] = "Crew member added successfully.";
    }
    header("Location: crew_accounts.php");
    exit();
}

/*   
   EDIT CREW MEMBER
*/
if (isset($_POST['edit_crew'])) {
    $id = intval($_POST['edit_id']);
    $barangay = trim($_POST['edit_barangay']);
    $username = trim($_POST['edit_username']);
    $status = trim($_POST['edit_status']);

    if (!empty($_POST['edit_password'])) {
        $password = password_hash($_POST['edit_password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE collection_crew SET barangay=?, username=?, status=?, password=? WHERE id=?");
        $stmt->bind_param("ssssi", $barangay, $username, $status, $password, $id);
    } else {
        $stmt = $conn->prepare("UPDATE collection_crew SET barangay=?, username=?, status=? WHERE id=?");
        $stmt->bind_param("sssi", $barangay, $username, $status, $id);
    }
    $stmt->execute();
    $_SESSION['success'] = "Crew member updated.";
    header("Location: crew_accounts.php");
    exit();
}

/*   
   DELETE CREW MEMBER
*/
if (isset($_POST['delete_crew'])) {
    $id = intval($_POST['delete_id']);
    $stmt = $conn->prepare("DELETE FROM collection_crew WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $_SESSION['success'] = "Crew member removed.";
    header("Location: crew_accounts.php");
    exit();
}

/*   
   TOGGLE STATUS
*/
if (isset($_GET['toggle_id'])) {
    $id = intval($_GET['toggle_id']);
    $row = $conn->query("SELECT status FROM collection_crew WHERE id=$id")->fetch_assoc();
    $new_status = ($row['status'] === "Active") ? "Inactive" : "Active";

    $stmt = $conn->prepare("UPDATE collection_crew SET status=? WHERE id=?");
    $stmt->bind_param("si", $new_status, $id);
    $stmt->execute();
    $_SESSION['success'] = "Status updated.";
    header("Location: crew_accounts.php");
    exit();
}

/*   
   FETCH CREW LIST
*/
$result = $conn->query("SELECT * FROM collection_crew ORDER BY id ASC");
$crews = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Collection Crew Accounts</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
:root {
    --primary-color: #3f4a36;
    --secondary-color: #ffffff;
    --success-color: #28a745;
    --warning-color: #ffc107;
    --danger-color: #dc3545;
    --light-bg: rgba(68, 64, 51, 0.4);
    --dark-text: #000000;
    --card-shadow: 0 4px 12px rgba(0,0,0,0.08);
    --transition: all 0.3s ease;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    background-color: white;
    font-family: Arial, sans-serif;
    color: var(--dark-text);
    min-height: 100vh;
    padding-top: 70px;
    padding-left: 100px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    body {
        padding-left: 0;
        padding-top: 60px;
    }
}

/* Header */
.page-header {
    background-color: var(--primary-color);
    color: white;
    padding: 1.25rem;
    border-radius: 14px;
    margin-bottom: 1.5rem;
    box-shadow: var(--card-shadow);
}

.page-header h1 {
    font-weight: 600;
    font-size: 1.75rem;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

/* Cards */
.card {
    border: none;
    border-radius: 14px;
    box-shadow: var(--card-shadow);
    background: white;
    overflow: hidden;
}

.card-header {
    background-color: var(--primary-color);
    color: white;
    border-radius: 14px 14px 0 0 !important;
    padding: 1rem 1.25rem;
    font-weight: 600;
}

/* Buttons */
.btn-black {
    background-color: white;
    color: var(--dark-text);
    border: 1px solid var(--dark-text);
    font-weight: 600;
    padding: 0.5rem 1.25rem;
    border-radius: 6px;
    transition: var(--transition);
}

.btn-black:hover {
    background-color: #e9ecef;
    color: var(--dark-text);
    border-color: var(--dark-text);
    transform: translateY(-2px);
}

.btn-outline-custom {
    border: 1px solid var(--dark-text);
    color: var(--dark-text);
    background: transparent;
    padding: 0.4rem 1rem;
    border-radius: 6px;
    font-weight: 600;
    transition: var(--transition);
}

.btn-outline-custom:hover {
    background-color: #e9ecef;
    color: var(--dark-text);
}

/* Table */
.table-responsive {
    border-radius: 10px;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.table {
    margin-bottom: 0;
    border-collapse: separate;
    border-spacing: 0;
    min-width: 650px; /* Ensure table doesn't get too narrow */
}

.table thead {
    background-color: var(--primary-color);
    color: white;
}

.table th {
    border: none;
    padding: 0.75rem;
    font-weight: 600;
    vertical-align: middle;
    white-space: nowrap;
}

.table td {
    padding: 0.75rem;
    vertical-align: middle;
    border-bottom: 1px solid #dee2e6;
    word-wrap: break-word;
}

.table tbody tr:hover {
    background-color: rgba(63, 74, 54, 0.05);
}

/* Badges */
.badge {
    padding: 0.35rem 0.75rem;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.8rem;
    white-space: nowrap;
}

.badge-active {
    background-color: var(--success-color) !important;
    color: white;
}

.badge-inactive {
    background-color: #6c757d !important;
    color: white;
}

/* Search Box */
.search-container {
    position: relative;
    max-width: 100%;
}

.search-container i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
    z-index: 10;
}

.search-container input {
    padding-left: 40px;
    border-radius: 6px;
    border: 1px solid #ced4da;
    width: 100%;
}

.search-container input:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(63, 74, 54, 0.25);
}

/* Modal Fixes */
.modal {
    z-index: 1060 !important;
    padding-top: 70px !important; /* Add padding to prevent hiding behind header */
}

.modal-dialog {
    margin-top: 40px !important; /* Push modal down */
    margin-bottom: 20px !important; /* Add bottom margin for mobile */
}

@media (max-width: 768px) {
    .modal {
        padding-top: 20px !important;
    }
    
    .modal-dialog {
        margin: 10px !important;
        max-height: calc(100vh - 20px);
    }
    
    .modal-content {
        max-height: calc(100vh - 40px);
        overflow-y: auto;
    }
}

.modal-content {
    border: none;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
}

.modal-header {
    background-color: var(--primary-color);
    color: white;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.modal-title {
    font-weight: 600;
    font-size: 1.25rem;
}

.modal-body {
    padding: 1.25rem;
}

.modal-footer {
    padding: 1rem 1.25rem;
    border-top: 1px solid #dee2e6;
    background-color: #f8f9fa;
}

.btn-close-white {
    filter: invert(1) grayscale(100%) brightness(200%);
}

/* Form Controls */
.form-control, .form-select {
    border: 1px solid #ced4da;
    border-radius: 6px;
    padding: 0.5rem 0.75rem;
    transition: var(--transition);
    font-size: 0.9rem;
}

.form-control:focus, .form-select:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(63, 74, 54, 0.25);
}

.input-group-text {
    background-color: #f8f9fa;
    border: 1px solid #ced4da;
    color: var(--dark-text);
    font-size: 0.9rem;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 0.35rem;
    flex-wrap: wrap;
    justify-content: center;
}

.action-btn {
    padding: 0.35rem 0.6rem;
    border-radius: 4px;
    font-size: 0.8rem;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: var(--transition);
    border: none;
    white-space: nowrap;
    text-decoration: none;
    cursor: pointer;
}

.action-btn-edit {
    background-color: rgba(63, 74, 54, 0.1);
    color: var(--primary-color);
    border: 1px solid var(--primary-color);
}

.action-btn-edit:hover {
    background-color: var(--primary-color);
    color: white;
}

.action-btn-delete {
    background-color: rgba(220, 53, 69, 0.1);
    color: var(--danger-color);
    border: 1px solid var(--danger-color);
}

.action-btn-delete:hover {
    background-color: var(--danger-color);
    color: white;
}

.action-btn-toggle {
    background-color: rgba(255, 193, 7, 0.1);
    color: #856404;
    border: 1px solid #ffc107;
}

.action-btn-toggle:hover {
    background-color: #ffc107;
    color: #212529;
}

/* Mobile Optimizations */
@media (max-width: 768px) {
    .page-header {
        padding: 1rem;
        margin-bottom: 1rem;
    }
    
    .page-header h1 {
        font-size: 1.4rem;
    }
    
    .table-responsive {
        margin: 0 -0.75rem;
        width: calc(100% + 1.5rem);
    }
    
    .table {
        min-width: 650px;
    }
    
    .table th, .table td {
        padding: 0.5rem;
        font-size: 0.85rem;
    }
    
    .action-buttons {
        flex-wrap: wrap;
        justify-content: flex-start;
    }
    
    .action-btn {
        flex: 1;
        min-width: 70px;
        justify-content: center;
        font-size: 0.75rem;
        padding: 0.3rem 0.4rem;
    }
    
    .badge {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
    
    .search-container input {
        font-size: 0.9rem;
    }
    
    .btn-black, .btn-outline-custom {
        padding: 0.4rem 0.8rem;
        font-size: 0.9rem;
    }
    
    /* Stack filter buttons on mobile */
    .d-flex.gap-2 {
        flex-wrap: wrap;
    }
    
    .d-flex.gap-2 button {
        flex: 1;
        min-width: 100px;
        margin-bottom: 0.25rem;
    }
    
    /* Adjust column widths for mobile */
    .table th:nth-child(1),
    .table td:nth-child(1) {
        min-width: 50px;
        max-width: 50px;
    }
    
    .table th:nth-child(2),
    .table td:nth-child(2) {
        min-width: 120px;
        max-width: 150px;
    }
    
    .table th:nth-child(3),
    .table td:nth-child(3) {
        min-width: 120px;
        max-width: 150px;
    }
    
    .table th:nth-child(4),
    .table td:nth-child(4) {
        min-width: 80px;
        max-width: 100px;
    }
    
    .table th:nth-child(5),
    .table td:nth-child(5) {
        min-width: 200px;
    }
}

@media (max-width: 576px) {
    body {
        padding-top: 56px;
    }
    
    .container-fluid {
        padding-left: 0.5rem !important;
        padding-right: 0.5rem !important;
    }
    
    .page-header {
        border-radius: 10px;
        padding: 0.75rem;
    }
    
    .page-header h1 {
        font-size: 1.25rem;
    }
    
    .card {
        border-radius: 10px;
    }
    
    .table th, .table td {
        padding: 0.4rem 0.25rem;
        font-size: 0.8rem;
    }
    
    /* Make table horizontally scrollable on very small screens */
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    /* Stack stats cards */
    .row.mt-3 .col-md-4 {
        margin-bottom: 0.75rem;
    }
    
    /* Full width buttons on mobile */
    .btn-black, .btn-outline-custom {
        width: 100%;
        margin-bottom: 0.5rem;
    }
    
    /* Adjust modal for mobile */
    .modal-dialog {
        max-width: 95%;
    }
    
    /* Better text truncation for small screens */
    .table td {
        max-width: 150px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
}

/* Very small screens (phones) */
@media (max-width: 400px) {
    .action-buttons {
        flex-direction: column;
    }
    
    .action-btn {
        width: 100%;
        justify-content: center;
    }
    
    .table th:nth-child(2),
    .table td:nth-child(2),
    .table th:nth-child(3),
    .table td:nth-child(3) {
        max-width: 100px;
    }
    
    .page-header h1 {
        font-size: 1.1rem;
    }
    
    .modal-title {
        font-size: 1.1rem;
    }
}

/* Animations */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.fade-in {
    animation: fadeIn 0.3s ease-out;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 2rem 1rem;
    color: #6c757d;
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
    color: #adb5bd;
}

.empty-state h4 {
    font-size: 1.25rem;
    margin-bottom: 0.5rem;
}

/* Stats Cards */
.stats-card {
    border-radius: 10px;
    border: none;
    box-shadow: var(--card-shadow);
}

.stats-card.bg-primary {
    background-color: var(--primary-color) !important;
}

/* Loading Overlay */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    display: none;
}

.spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid var(--primary-color);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Alert Styles */
.alert {
    border-radius: 8px;
    border: none;
    box-shadow: var(--card-shadow);
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border-left: 4px solid var(--success-color);
}

.alert-danger {
    background-color: #f8d7da;
    color: #721c24;
    border-left: 4px solid var(--danger-color);
}

/* Icon adjustments */
.fas, .bi {
    font-size: 0.9em;
}

/* Text truncation for table cells */
.text-truncate-cell {
    max-width: 150px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

@media (max-width: 768px) {
    .text-truncate-cell {
        max-width: 120px;
    }
}

@media (max-width: 576px) {
    .text-truncate-cell {
        max-width: 100px;
    }
}

/* Ensure table is readable on small screens */
.table td, .table th {
    min-width: 50px;
}

/* Fix for long barangay names */
.barangay-cell {
    max-width: 150px;
    word-wrap: break-word;
}

.username-cell {
    max-width: 120px;
    word-wrap: break-word;
}
</style>
</head>
<body>

<?php include "header.php"; ?>

<div class="loading-overlay">
    <div class="spinner"></div>
</div>

<div class="container-fluid px-2 px-md-3 py-2 fade-in">
    <!-- Page Header -->
    <div class="page-header">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center">
            <h1>
                <i class="fas fa-truck-loading me-2"></i> Collection Crew Accounts
            </h1>
            <button class="btn btn-black mt-2 mt-sm-0" data-bs-toggle="modal" data-bs-target="#addCrewModal">
                <i class="fas fa-user-plus me-2"></i> Add Crew
            </button>
        </div>
        <p class="mb-0 mt-2 opacity-75">Manage collection crew accounts and working status</p>
    </div>

    <!-- Alerts -->
    <div class="row mb-3">
        <div class="col-12">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i> <?= $_SESSION['success']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i> <?= $_SESSION['error']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Search and Filter -->
    <div class="row mb-3">
        <div class="col-md-6 mb-2 mb-md-0">
            <div class="search-container">
                <i class="fas fa-search"></i>
                <input type="text" id="searchBar" class="form-control" placeholder="Search crew...">
            </div>
        </div>
        <div class="col-md-6">
            <div class="d-flex flex-wrap gap-2 justify-content-md-end">
                <button class="btn btn-outline-custom" id="filterActive">
                    <i class="fas fa-toggle-on me-1"></i> Active
                </button>
                <button class="btn btn-outline-custom" id="filterInactive">
                    <i class="fas fa-toggle-off me-1"></i> Inactive
                </button>
                <button class="btn btn-outline-custom" id="resetFilter">
                    <i class="fas fa-sync-alt me-1"></i> Reset
                </button>
            </div>
        </div>
    </div>

    <!-- Crew Table -->
    <div class="card">
        <div class="card-header d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center">
            <h5 class="mb-0 mb-sm-0"><i class="fas fa-list me-2"></i> Collection Crew Members</h5>
            <span class="badge bg-light text-dark mt-1 mt-sm-0">
                <i class="fas fa-users me-1"></i> Total: <?= count($crews) ?>
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="min-width: 50px;">#</th>
                            <th style="min-width: 120px;">Barangay</th>
                            <th style="min-width: 120px;">Username</th>
                            <th style="min-width: 90px;">Status</th>
                            <th style="min-width: 200px;" class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="crewTable">
                        <?php if (empty($crews)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <div class="empty-state">
                                        <i class="fas fa-truck"></i>
                                        <h4 class="mt-2">No Crew Members Found</h4>
                                        <p class="mb-0">Add your first collection crew member to get started</p>
                                        <button class="btn btn-black mt-3" data-bs-toggle="modal" data-bs-target="#addCrewModal">
                                            <i class="fas fa-user-plus me-2"></i> Add Crew
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $i = 1; foreach($crews as $row): ?>
                            <tr data-status="<?= strtolower($row['status']) ?>">
                                <td class="fw-bold"><?= $i++ ?></td>
                                <td class="barangay-cell">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-map-marker-alt text-primary me-2"></i>
                                        <span class="text-truncate" style="max-width: 130px;" title="<?= htmlspecialchars($row['barangay']) ?>">
                                            <?= htmlspecialchars($row['barangay']) ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="username-cell">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-user text-secondary me-2"></i>
                                        <span class="text-truncate" style="max-width: 110px;" title="<?= htmlspecialchars($row['username']) ?>">
                                            <?= htmlspecialchars($row['username']) ?>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($row['status'] === "Active"): ?>
                                        <span class="badge badge-active">
                                            <i class="fas fa-check-circle me-1"></i> Active
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-inactive">
                                            <i class="fas fa-times-circle me-1"></i> Inactive
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="action-btn action-btn-edit" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editCrewModal"
                                            data-id="<?= $row['id'] ?>"
                                            data-barangay="<?= htmlspecialchars($row['barangay']) ?>"
                                            data-username="<?= htmlspecialchars($row['username']) ?>"
                                            data-status="<?= $row['status'] ?>">
                                            <i class="fas fa-edit me-1"></i> <span class="d-none d-sm-inline">Manage</span>
                                        </button>
                                        
                                        <button class="action-btn action-btn-delete" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#deleteCrewModal"
                                            data-id="<?= $row['id'] ?>">
                                            <i class="fas fa-trash me-1"></i> <span class="d-none d-sm-inline">Delete</span>
                                        </button>
                                        
                                        <a href="?toggle_id=<?= $row['id'] ?>" 
                                           class="action-btn action-btn-toggle">
                                            <?php if ($row['status'] === "Active"): ?>
                                                <i class="fas fa-ban me-1"></i> <span class="d-none d-sm-inline">Deactivate</span>
                                            <?php else: ?>
                                                <i class="fas fa-check me-1"></i> <span class="d-none d-sm-inline">Activate</span>
                                            <?php endif; ?>
                                        </a>
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
    
    <!-- Stats Footer -->
    <div class="row mt-3">
        <div class="col-md-4 mb-2">
            <div class="card stats-card bg-primary text-white">
                <div class="card-body py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Total Crew</h6>
                            <h4 class="mb-0 fw-bold"><?= count($crews) ?></h4>
                        </div>
                        <i class="fas fa-truck fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-2">
            <div class="card stats-card bg-success text-white">
                <div class="card-body py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Active</h6>
                            <h4 class="mb-0 fw-bold"><?= count(array_filter($crews, fn($c) => $c['status'] === 'Active')) ?></h4>
                        </div>
                        <i class="fas fa-toggle-on fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-2">
            <div class="card stats-card bg-secondary text-white">
                <div class="card-body py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Inactive</h6>
                            <h4 class="mb-0 fw-bold"><?= count(array_filter($crews, fn($c) => $c['status'] === 'Inactive')) ?></h4>
                        </div>
                        <i class="fas fa-toggle-off fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ADD CREW MODAL -->
<div class="modal fade" id="addCrewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content" id="addCrewForm">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus me-2"></i> Add Crew Member
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2">
                    <label class="form-label fw-bold">Barangay</label>
                    <input type="text" name="barangay" class="form-control" placeholder="Barangay" required>
                </div>
                
                <div class="mb-2">
                    <label class="form-label fw-bold">Username</label>
                    <input type="text" name="username" class="form-control" placeholder="Username" required>
                </div>
                
                <div class="mb-2">
                    <label class="form-label fw-bold">Password</label>
                    <div class="input-group">
                        <input type="password" name="password" id="newPassword" class="form-control" placeholder="Password" required>
                        <button type="button" class="btn btn-outline-secondary" id="toggleNewPassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="progress mt-2" style="height: 3px;">
                        <div class="progress-bar" id="passwordStrength" role="progressbar"></div>
                    </div>
                    <small class="text-muted">Minimum 8 characters with letters and numbers</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-custom" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cancel
                </button>
                <button type="submit" name="add_crew" class="btn btn-black">
                    <i class="fas fa-user-plus me-2"></i> Add
                </button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT CREW MODAL -->
<div class="modal fade" id="editCrewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content" id="editCrewForm">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i> Edit Crew Member
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="edit_id" name="edit_id">
                
                <div class="mb-2">
                    <label class="form-label fw-bold">Barangay</label>
                    <input type="text" id="edit_barangay" name="edit_barangay" class="form-control" required>
                </div>
                
                <div class="mb-2">
                    <label class="form-label fw-bold">Username</label>
                    <input type="text" id="edit_username" name="edit_username" class="form-control" required>
                </div>
                
                <div class="mb-2">
                    <label class="form-label fw-bold">Status</label>
                    <select id="edit_status" name="edit_status" class="form-select">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
                
                <div class="mb-2">
                    <label class="form-label fw-bold">New Password (optional)</label>
                    <div class="input-group">
                        <input type="password" id="edit_password" name="edit_password" class="form-control" placeholder="Leave blank to keep current">
                        <button type="button" class="btn btn-outline-secondary" id="toggleEditPassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-custom" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cancel
                </button>
                <button type="submit" name="edit_crew" class="btn btn-black">
                    <i class="fas fa-save me-2"></i> Update
                </button>
            </div>
        </form>
    </div>
</div>

<!-- DELETE CREW MODAL -->
<div class="modal fade" id="deleteCrewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content">
            <div class="modal-header" style="background-color: var(--primary-color);">
                <h5 class="modal-title">
                    <i class="fas fa-trash me-2"></i> Remove Crew Member
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-3">
                <div class="mb-2">
                    <i class="fas fa-exclamation-triangle text-warning fa-3x"></i>
                </div>
                <h5 class="mb-2">Are you sure you want to remove this crew member?</h5>
                <p class="text-muted mb-0">This action cannot be undone.</p>
                <input type="hidden" id="delete_id" name="delete_id">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-custom" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> No
                </button>
                <button type="submit" name="delete_crew" class="btn btn-black">
                    <i class="fas fa-check me-2"></i> Yes
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// DOM Ready
document.addEventListener('DOMContentLoaded', function() {
    // Fill Edit Modal
    document.getElementById('editCrewModal').addEventListener('show.bs.modal', function(event) {
        const btn = event.relatedTarget;
        this.querySelector('#edit_id').value = btn.dataset.id;
        this.querySelector('#edit_barangay').value = btn.dataset.barangay;
        this.querySelector('#edit_username').value = btn.dataset.username;
        this.querySelector('#edit_status').value = btn.dataset.status;
    });

    // Fill Delete Modal
    document.getElementById('deleteCrewModal').addEventListener('show.bs.modal', function(event) {
        const btn = event.relatedTarget;
        this.querySelector('#delete_id').value = btn.dataset.id;
    });

    // Toggle password visibility for new password
    document.getElementById('toggleNewPassword').addEventListener('click', function() {
        const passwordField = document.getElementById('newPassword');
        const icon = this.querySelector('i');
        if (passwordField.type === 'password') {
            passwordField.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            passwordField.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });

    // Toggle password visibility for edit password
    document.getElementById('toggleEditPassword').addEventListener('click', function() {
        const passwordField = document.getElementById('edit_password');
        const icon = this.querySelector('i');
        if (passwordField.type === 'password') {
            passwordField.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            passwordField.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });

    // Password strength indicator
    const newPasswordField = document.getElementById('newPassword');
    const passwordStrength = document.getElementById('passwordStrength');
    
    if (newPasswordField && passwordStrength) {
        newPasswordField.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            if (password.length >= 8) strength += 25;
            if (/[A-Z]/.test(password)) strength += 25;
            if (/[0-9]/.test(password)) strength += 25;
            if (/[^A-Za-z0-9]/.test(password)) strength += 25;
            
            passwordStrength.style.width = strength + '%';
            
            if (strength < 50) {
                passwordStrength.className = 'progress-bar bg-danger';
            } else if (strength < 75) {
                passwordStrength.className = 'progress-bar bg-warning';
            } else {
                passwordStrength.className = 'progress-bar bg-success';
            }
        });
    }

    // Search Functionality
    const searchBar = document.getElementById('searchBar');
    if (searchBar) {
        searchBar.addEventListener('keyup', function() {
            const query = this.value.toLowerCase();
            const rows = document.querySelectorAll('#crewTable tr');
            
            rows.forEach(row => {
                if (row.querySelector('.empty-state')) return;
                
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
    }

    // Filter Buttons
    document.getElementById('filterActive')?.addEventListener('click', function() {
        filterTable('active');
    });
    
    document.getElementById('filterInactive')?.addEventListener('click', function() {
        filterTable('inactive');
    });
    
    document.getElementById('resetFilter')?.addEventListener('click', function() {
        const rows = document.querySelectorAll('#crewTable tr');
        rows.forEach(row => {
            if (row.querySelector('.empty-state')) return;
            row.style.display = '';
        });
        if (searchBar) searchBar.value = '';
    });

    function filterTable(status) {
        const rows = document.querySelectorAll('#crewTable tr');
        rows.forEach(row => {
            if (row.querySelector('.empty-state')) return;
            
            const rowStatus = row.getAttribute('data-status');
            row.style.display = (rowStatus === status) ? '' : 'none';
        });
    }

    // Show loading on form submit
    document.getElementById('addCrewForm').addEventListener('submit', function() {
        document.querySelector('.loading-overlay').style.display = 'flex';
    });
    
    document.getElementById('editCrewForm').addEventListener('submit', function() {
        document.querySelector('.loading-overlay').style.display = 'flex';
    });
    
    // Auto-hide alerts after 5 seconds
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
    
    // Prevent modal hiding behind header by adjusting z-index and padding
    document.querySelectorAll('.modal').forEach(modal => {
        modal.style.zIndex = '1060';
    });
    
    // Fix for mobile touch scrolling in modals
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('touchmove', function(e) {
            e.stopPropagation();
        }, { passive: false });
    });
    
    // Add tooltips for truncated text on mobile
    if (window.innerWidth <= 768) {
        document.querySelectorAll('.text-truncate').forEach(element => {
            const text = element.getAttribute('title');
            if (text && text.length > 20) {
                element.setAttribute('data-bs-toggle', 'tooltip');
                element.setAttribute('data-bs-placement', 'top');
                element.setAttribute('data-bs-title', text);
            }
        });
        
        // Initialize Bootstrap tooltips
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
    }
});
</script>
</body>
</html>