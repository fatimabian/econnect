<?php
session_start();
include "db_connect.php";

// CHECK IF BARANGAY ADMIN LOGGED IN
$admin_id = $_SESSION['barangay_admin_id'] ?? null;
if (!$admin_id) {
    header("Location: ../login.php");
    exit;
}

// GET ADMIN BARANGAY
$stmt = $conn->prepare("SELECT barangay FROM barangay_admins WHERE id=?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin_barangay = $stmt->get_result()->fetch_assoc()['barangay'] ?? '';
$stmt->close();

// HANDLE UPDATE USER CONTACT
if (isset($_POST['update_user'])) {
    $id = intval($_POST['user_id']);
    $newContact = $_POST['new_contact'];

    // Validate 11-digit number
    if (!empty($newContact) && preg_match('/^\d{11}$/', $newContact)) {
        $stmt = $conn->prepare("UPDATE users SET contact=? WHERE id=? AND barangay=?");
        $stmt->bind_param("sis", $newContact, $id, $admin_barangay);
        $stmt->execute();
        $stmt->close();
        header("Location: user_management.php?updated=1");
        exit();
    } else {
        $errorMsg = "Contact number must be exactly 11 digits!";
    }
}

// HANDLE DELETE USER
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM users WHERE id=? AND barangay=?");
    $stmt->bind_param("is", $id, $admin_barangay);
    $stmt->execute();
    $stmt->close();
    header("Location: user_management.php?deleted=1");
    exit();
}

// FETCH USERS BY BARANGAY
$users = [];
$stmt = $conn->prepare("SELECT * FROM users WHERE barangay=? ORDER BY id DESC");
$stmt->bind_param("s", $admin_barangay);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Management</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
:root {
    --primary-color: #3f4a36;
    --secondary-color: #6c757d;
    --success-color: #28a745;
    --warning-color: #ffc107;
    --danger-color: #dc3545;
    --info-color: #17a2b8;
    --light-bg: rgba(68, 64, 51, 0.4);
    --card-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    --transition-speed: 0.3s;
    --border-color: #dee2e6;
    --darker-border: #adb5bd;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
    min-height: 100vh;
    padding-top: 80px;
    padding-left: 100px;
    color: #333;
}

@media (max-width: 768px) {
    body {
        padding-left: 0;
        padding-top: 70px;
    }
}

/* Header Section */
.page-header {
    background: linear-gradient(to right, var(--primary-color), #5a6c4a);
    color: white;
    padding: 1.5rem;
    border-radius: 14px;
    margin-bottom: 2rem;
    box-shadow: var(--card-shadow);
    border: 1px solid rgba(0, 0, 0, 0.1);
}

.page-header h1 {
    font-weight: 700;
    font-size: 1.8rem;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.page-subtitle {
    color: rgba(255, 255, 255, 0.9);
    font-size: 0.95rem;
    margin-top: 5px;
}

/* Main Card */
.main-card {
    background: white;
    border-radius: 14px;
    padding: 25px;
    box-shadow: var(--card-shadow);
    margin-bottom: 30px;
    border: 1px solid var(--border-color);
}

/* Search Box */
.search-container {
    position: relative;
    margin-bottom: 25px;
}

.search-container i {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--secondary-color);
    font-size: 1.1rem;
}

.search-input {
    padding-left: 45px;
    border: 2px solid var(--darker-border);
    border-radius: 12px;
    height: 50px;
    font-size: 1rem;
    transition: all var(--transition-speed);
}

.search-input:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.25rem rgba(63, 74, 54, 0.15);
}

/* Table Styling - Desktop */
.table-container {
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid var(--darker-border);
}

.custom-table {
    margin-bottom: 0;
    border-collapse: collapse;
    width: 100%;
}

.custom-table thead {
    background: linear-gradient(135deg, var(--primary-color) 0%, #5a6c4a 100%);
}

/* TABLE HEADERS IN BLACK */
.custom-table thead th {
    color: black !important; /* Changed from white to black */
    font-weight: 700;
    padding: 18px 15px;
    border: none;
    border-right: 1px solid rgba(0, 0, 0, 0.1);
    font-size: 0.95rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    white-space: nowrap;
    background: #f8f9fa !important; /* Light background for contrast */
    border-bottom: 2px solid var(--primary-color);
}

.custom-table thead th:last-child {
    border-right: none;
}

.custom-table tbody tr {
    transition: all var(--transition-speed);
    border-bottom: 1px solid var(--darker-border);
}

.custom-table tbody tr:last-child {
    border-bottom: none;
}

.custom-table tbody tr:hover {
    background-color: rgba(63, 74, 54, 0.05);
}

/* HIGHLIGHT NAME AND EMAIL COLUMNS */
.custom-table tbody td:nth-child(2) { /* Full Name column */
    font-weight: 600;
    color: var(--primary-color);
    background-color: rgba(63, 74, 54, 0.03);
}

.custom-table tbody td:nth-child(3) { /* Email column */
    font-weight: 500;
    color: #2c3e50;
    background-color: rgba(52, 152, 219, 0.03);
}

/* Ensure proper visibility */
.custom-table tbody td {
    padding: 16px 15px;
    vertical-align: middle;
    color: #333;
    border-right: 1px solid var(--border-color);
    word-break: break-word;
}

/* Make name and email columns more prominent on mobile */
@media (max-width: 768px) {
    .custom-table tbody td:nth-child(2),
    .custom-table tbody td:nth-child(3) {
        font-weight: 600;
    }
}

.custom-table tbody td:last-child {
    border-right: none;
}

/* Status Badges */
.status-badge {
    padding: 6px 14px;
    border-radius: 50px;
    font-weight: 600;
    font-size: 0.85rem;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    border: 1px solid;
}

.status-active {
    background-color: rgba(40, 167, 69, 0.15);
    color: var(--success-color);
    border-color: rgba(40, 167, 69, 0.5);
}

.status-inactive {
    background-color: rgba(108, 117, 125, 0.15);
    color: var(--secondary-color);
    border-color: rgba(108, 117, 125, 0.5);
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.btn-action {
    padding: 8px 14px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.85rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    transition: all 0.2s;
    min-width: 40px;
    border: 1px solid;
    text-decoration: none;
}

.btn-edit {
    background: linear-gradient(135deg, var(--primary-color) 0%, #5a6c4a 100%);
    color: white;
    border-color: #2e382a;
}

.btn-edit:hover {
    background: linear-gradient(135deg, #4a573f 0%, #6a7c58 100%);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(63, 74, 54, 0.2);
    border-color: #2e382a;
}

.btn-delete {
    background: linear-gradient(135deg, var(--danger-color) 0%, #e35d6a 100%);
    color: white;
    border-color: #c82333;
}

.btn-delete:hover {
    background: linear-gradient(135deg, #e04a59 0%, #e8747f 100%);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.2);
    border-color: #c82333;
}

/* Modal Styling */
.modal-custom .modal-header {
    background: linear-gradient(135deg, var(--primary-color) 0%, #5a6c4a 100%);
    color: white;
    border-radius: 0;
    border: none;
    border-bottom: 1px solid rgba(0, 0, 0, 0.1);
}

.modal-custom .modal-title {
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 10px;
}

.modal-custom .btn-close {
    filter: brightness(0) invert(1);
    opacity: 0.8;
}

.modal-custom .btn-close:hover {
    opacity: 1;
}

.modal-custom .modal-body {
    padding: 25px;
}

.modal-custom .form-control {
    padding: 12px 15px;
    border: 2px solid var(--darker-border);
    border-radius: 10px;
    font-size: 1rem;
    transition: all var(--transition-speed);
}

.modal-custom .form-control:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.25rem rgba(63, 74, 54, 0.15);
}

.modal-custom .modal-footer {
    border-top: 1px solid var(--darker-border);
    padding: 20px 25px;
}

/* Alert Styling */
.alert-custom {
    border-radius: 12px;
    padding: 16px 20px;
    border: 1px solid;
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    gap: 12px;
    box-shadow: var(--card-shadow);
}

.alert-custom i {
    font-size: 1.2rem;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border-left: 4px solid var(--success-color);
    border-color: #c3e6cb;
}

.alert-danger {
    background-color: #f8d7da;
    color: #721c24;
    border-left: 4px solid var(--danger-color);
    border-color: #f5c6cb;
}

.alert-warning {
    background-color: #fff3cd;
    color: #856404;
    border-left: 4px solid var(--warning-color);
    border-color: #ffeaa7;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: var(--secondary-color);
    border: 2px dashed var(--border-color);
    border-radius: 12px;
    margin: 20px;
}

.empty-state i {
    font-size: 4rem;
    color: #e9ecef;
    margin-bottom: 20px;
}

.empty-state h4 {
    color: var(--primary-color);
    margin-bottom: 10px;
}

.empty-state p {
    margin-bottom: 25px;
}

/* Modal Positioning Fix */
.modal {
    z-index: 1060 !important;
    padding-top: 70px !important;
}

.modal-dialog {
    margin-top: 40px !important;
    margin-bottom: 20px !important;
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

/* Responsive Table for Mobile */
@media (max-width: 768px) {
    body {
        padding-top: 70px;
    }
    
    .page-header {
        padding: 20px;
        margin: 15px;
        border-radius: 10px;
    }
    
    .page-header h1 {
        font-size: 1.5rem;
    }
    
    .main-content {
        padding: 0 15px;
    }
    
    .main-card {
        padding: 15px;
        margin: 15px 0;
        border-radius: 10px;
        border: 1px solid var(--darker-border);
    }
    
    /* Hide regular table on mobile */
    .table-container {
        display: none;
    }
    
    /* Mobile Card View */
    .mobile-user-cards {
        display: block;
    }
    
    .user-card {
        background: white;
        border: 1px solid var(--darker-border);
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 15px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        transition: all var(--transition-speed);
    }
    
    .user-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        transform: translateY(-2px);
    }
    
    .user-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
        padding-bottom: 10px;
        border-bottom: 1px solid var(--border-color);
    }
    
    .user-number {
        font-weight: 700;
        color: var(--primary-color);
        background: rgba(63, 74, 54, 0.1);
        padding: 4px 10px;
        border-radius: 6px;
        border: 1px solid rgba(63, 74, 54, 0.2);
    }
    
    .user-info-row {
        display: flex;
        margin-bottom: 10px;
        padding-bottom: 10px;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .user-info-row:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }
    
    .user-label {
        font-weight: 600;
        color: var(--primary-color);
        min-width: 100px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .user-value {
        flex: 1;
        word-break: break-word;
    }
    
    /* HIGHLIGHT NAME AND EMAIL IN MOBILE VIEW */
    .user-card-header strong {
        color: var(--primary-color);
        font-size: 1.1rem;
    }
    
    .user-info-row:nth-child(1) .user-value { /* Email row */
        font-weight: 500;
        color: #2c3e50;
    }
    
    .mobile-actions {
        display: flex;
        gap: 10px;
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid var(--border-color);
    }
    
    .mobile-actions .btn-action {
        flex: 1;
        justify-content: center;
        padding: 10px;
        font-size: 0.9rem;
    }
    
    .search-input {
        font-size: 16px; /* Prevents zoom on iOS */
        height: 45px;
    }
}

/* Desktop only - hide mobile cards */
@media (min-width: 769px) {
    .mobile-user-cards {
        display: none !important;
    }
    
    .table-container {
        display: block !important;
    }
}

/* Animation for table rows */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.custom-table tbody tr {
    animation: fadeIn 0.3s ease-out;
    animation-fill-mode: both;
}

.custom-table tbody tr:nth-child(1) { animation-delay: 0.05s; }
.custom-table tbody tr:nth-child(2) { animation-delay: 0.1s; }
.custom-table tbody tr:nth-child(3) { animation-delay: 0.15s; }
.custom-table tbody tr:nth-child(4) { animation-delay: 0.2s; }
.custom-table tbody tr:nth-child(5) { animation-delay: 0.25s; }
.custom-table tbody tr:nth-child(n+6) { animation-delay: 0.3s; }

.user-card {
    animation: fadeIn 0.3s ease-out;
    animation-fill-mode: both;
}

.user-card:nth-child(1) { animation-delay: 0.05s; }
.user-card:nth-child(2) { animation-delay: 0.1s; }
.user-card:nth-child(3) { animation-delay: 0.15s; }
.user-card:nth-child(4) { animation-delay: 0.2s; }
.user-card:nth-child(5) { animation-delay: 0.25s; }
.user-card:nth-child(n+6) { animation-delay: 0.3s; }

/* Stats Badge */
.stats-badge {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.9rem;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-left: 10px;
    border: 1px solid rgba(255, 255, 255, 0.3);
}

/* Column Visibility Enhancement */
.column-name {
    position: relative;
}

.column-name::before {
    content: "👤";
    margin-right: 5px;
}

.column-email::before {
    content: "📧";
    margin-right: 5px;
}

/* Adjust column widths for better visibility */
.custom-table th:nth-child(2), /* Name column */
.custom-table td:nth-child(2) {
    min-width: 200px;
    max-width: 250px;
}

.custom-table th:nth-child(3), /* Email column */
.custom-table td:nth-child(3) {
    min-width: 250px;
    max-width: 300px;
}

@media (max-width: 1200px) {
    .custom-table th:nth-child(2),
    .custom-table td:nth-child(2) {
        min-width: 180px;
        max-width: 220px;
    }
    
    .custom-table th:nth-child(3),
    .custom-table td:nth-child(3) {
        min-width: 200px;
        max-width: 250px;
    }
}

/* No results message */
.no-results {
    text-align: center;
    padding: 40px 20px;
    color: var(--secondary-color);
    font-style: italic;
    border: 2px dashed var(--border-color);
    border-radius: 10px;
    margin: 20px 0;
    display: none;
}

/* Ensure all columns are visible */
.table-responsive-container {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.custom-table {
    min-width: 1000px; /* Ensure table doesn't get too narrow */
}

@media (min-width: 1200px) {
    .custom-table {
        min-width: auto;
    }
}
</style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="main-content container-fluid px-2 px-md-4 py-3">
    <!-- Page Header -->
    <div class="page-header">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center">
            <div>
                <h1>
                    <i class="fas fa-users"></i> User Management
                </h1>
                <div class="page-subtitle">
                    Manage resident accounts in Barangay <?= htmlspecialchars($admin_barangay) ?>
                    <span class="stats-badge">
                        <i class="fas fa-user"></i> <?= count($users) ?> Users
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Alerts -->
    <div class="row mb-3">
        <div class="col-12">
            <?php if (isset($errorMsg)): ?>
                <div class="alert alert-warning alert-custom alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle"></i> <?= $errorMsg ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['updated'])): ?>
                <div class="alert alert-success alert-custom alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> User contact updated successfully!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['deleted'])): ?>
                <div class="alert alert-danger alert-custom alert-dismissible fade show" role="alert">
                    <i class="fas fa-trash"></i> User deleted successfully!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Main Card -->
    <div class="main-card">
        <!-- Search -->
        <div class="search-container">
            <i class="fas fa-search"></i>
            <input type="text" id="searchBar" class="form-control search-input" placeholder="Search users by name, email, or username...">
        </div>

        <!-- Desktop Table -->
        <div class="table-responsive-container">
            <div class="table-container">
                <table class="table custom-table">
                    <thead>
                        <tr>
                            <th style="width: 60px;">#</th>
                            <th class="column-name">Full Name</th>
                            <th class="column-email">Email</th>
                            <th>Username</th>
                            <!-- <th>Contact</th> -->
                            <th style="min-width: 120px;">Registered</th>
                            <th style="min-width: 200px;" class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="userTableBody">
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="8" class="empty-state">
                                    <i class="fas fa-users-slash"></i>
                                    <h4>No Users Found</h4>
                                    <p>No registered users in Barangay <?= htmlspecialchars($admin_barangay) ?> yet.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $i = 1; foreach($users as $user): ?>
                            <tr>
                                <td data-label="#"><?= $i++ ?></td>
                                <td data-label="Full Name" title="<?= htmlspecialchars($user['full_name']) ?>">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-user-circle text-primary me-2"></i>
                                        <span class="fw-semibold"><?= htmlspecialchars($user['full_name']) ?></span>
                                    </div>
                                </td>
                                <td data-label="Email" title="<?= htmlspecialchars($user['email']) ?>">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-envelope text-secondary me-2"></i>
                                        <span><?= htmlspecialchars($user['email']) ?></span>
                                    </div>
                                </td>
                                <td data-label="Username" title="<?= htmlspecialchars($user['username']) ?>">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-at text-info me-2"></i>
                                        <span><?= htmlspecialchars($user['username']) ?></span>
                                    </div>
                                </td>
                                <!-- <td data-label="Contact">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-phone text-success me-2"></i>
                                        <span><?= htmlspecialchars($user['contact']) ?></span>
                                    </div> -->
                                <td data-label="Registered">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-calendar text-warning me-2"></i>
                                        <span><?= date('M d, Y', strtotime($user['created_at'])) ?></span>
                                    </div>
                                </td>
                                <td data-label="Actions">
                                    <div class="action-buttons justify-content-center me-2">
                                        <button class="btn-action btn-edit" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editModal"
                                            data-id="<?= $user['id'] ?>" 
                                            data-contact="<?= htmlspecialchars($user['contact']) ?>">
                                            <i class="fas fa-edit"></i> Edit Contact
                                        </button>
                                        
                                        <button class="btn-action btn-delete" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#deleteModal"
                                            data-id="<?= $user['id'] ?>" 
                                            data-name="<?= htmlspecialchars($user['full_name']) ?>">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Mobile Cards -->
        <div class="mobile-user-cards" id="mobileUserCards">
            <?php if (empty($users)): ?>
                <div class="empty-state">
                    <i class="fas fa-users-slash"></i>
                    <h4>No Users Found</h4>
                    <p>No registered users in Barangay <?= htmlspecialchars($admin_barangay) ?> yet.</p>
                </div>
            <?php else: ?>
                <?php $i = 1; foreach($users as $user): ?>
                <div class="user-card" data-search="<?= strtolower(htmlspecialchars($user['full_name'] . ' ' . $user['email'] . ' ' . $user['username'])) ?>">
                    <div class="user-card-header">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-user-circle text-primary me-2"></i>
                            <strong><?= htmlspecialchars($user['full_name']) ?></strong>
                        </div>
                        <span class="user-number">#<?= $i++ ?></span>
                    </div>
                    
                    <div class="user-info-row">
                        <div class="user-label">
                            <i class="fas fa-envelope text-secondary"></i>
                            Email:
                        </div>
                        <div class="user-value"><?= htmlspecialchars($user['email']) ?></div>
                    </div>
                    
                    <div class="user-info-row">
                        <div class="user-label">
                            <i class="fas fa-at text-info"></i>
                            Username:
                        </div>
                        <div class="user-value"><?= htmlspecialchars($user['username']) ?></div>
                    </div>
                    
                    <div class="user-info-row">
                        <div class="user-label">
                            <i class="fas fa-phone text-success"></i>
                            Contact:
                        </div>
                        <div class="user-value"><?= htmlspecialchars($user['contact']) ?></div>
                    </div>
                    
                    <div class="user-info-row">
                        <div class="user-label">
                            <i class="fas fa-circle text-<?= $user['status'] == 'Active' ? 'success' : 'secondary' ?>"></i>
                            Status:
                        </div>
                        <div class="user-value">
                            <span class="status-badge <?= $user['status'] == 'Active' ? 'status-active' : 'status-inactive' ?>">
                                <?= $user['status'] ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="user-info-row">
                        <div class="user-label">
                            <i class="fas fa-calendar text-warning"></i>
                            Registered:
                        </div>
                        <div class="user-value"><?= date('M d, Y', strtotime($user['created_at'])) ?></div>
                    </div>
                    
                    <div class="mobile-actions">
                        <button class="btn-action btn-edit" 
                            data-bs-toggle="modal" 
                            data-bs-target="#editModal"
                            data-id="<?= $user['id'] ?>" 
                            data-contact="<?= htmlspecialchars($user['contact']) ?>">
                            <i class="fas fa-edit"></i> Edit Contact
                        </button>
                        
                        <button class="btn-action btn-delete" 
                            data-bs-toggle="modal" 
                            data-bs-target="#deleteModal"
                            data-id="<?= $user['id'] ?>" 
                            data-name="<?= htmlspecialchars($user['full_name']) ?>">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- No Results Message -->
        <div class="no-results" id="noResults">
            <i class="fas fa-search fa-2x mb-3"></i>
            <h5>No Users Found</h5>
            <p>Try adjusting your search terms</p>
        </div>
    </div>
</div>

<!-- EDIT MODAL -->
<div class="modal fade modal-custom" id="editModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i> Update Contact Number
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="user_id" id="edit_id">
                
                <div class="mb-4">
                    <label class="form-label fw-bold">New Contact Number</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light">
                            <i class="fas fa-phone text-primary"></i>
                        </span>
                        <input type="text" name="new_contact" id="edit_contact" class="form-control" 
                               pattern="\d{11}" title="Enter exactly 11 digits" required
                               placeholder="11-digit contact number">
                    </div>
                    <small class="text-muted">Must be exactly 11 digits (e.g., 09123456789)</small>
                </div>
                
                <div class="d-grid">
                    <button type="submit" name="update_user" class="btn btn-edit">
                        <i class="fas fa-check-circle me-2"></i> Update Contact
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- DELETE MODAL -->
<div class="modal fade modal-custom" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="GET" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-trash me-2"></i> Delete User
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <div class="mb-4">
                    <i class="fas fa-exclamation-triangle text-warning fa-4x"></i>
                </div>
                <h5 class="fw-bold mb-3">Confirm Deletion</h5>
                <p class="text-muted">Are you sure you want to delete <strong id="deleteName" class="text-primary"></strong>?</p>
                <p class="text-danger small mb-4">
                    <i class="fas fa-exclamation-circle me-1"></i> This action cannot be undone.
                </p>
                <input type="hidden" name="delete" id="delete_id">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cancel
                </button>
                <button type="submit" class="btn btn-delete">
                    <i class="fas fa-trash me-2"></i> Delete User
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
    document.getElementById('editModal').addEventListener('show.bs.modal', function(event) {
        const btn = event.relatedTarget;
        this.querySelector('#edit_id').value = btn.dataset.id;
        this.querySelector('#edit_contact').value = btn.dataset.contact;
    });

    // Fill Delete Modal
    document.getElementById('deleteModal').addEventListener('show.bs.modal', function(event) {
        const btn = event.relatedTarget;
        this.querySelector('#delete_id').value = btn.dataset.id;
        this.querySelector('#deleteName').textContent = btn.dataset.name;
    });

    // Search Functionality
    const searchBar = document.getElementById('searchBar');
    const desktopTable = document.getElementById('userTableBody');
    const mobileCards = document.getElementById('mobileUserCards');
    const noResults = document.getElementById('noResults');
    
    searchBar.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        let hasResults = false;
        
        // Search in desktop table
        if (desktopTable) {
            const rows = desktopTable.querySelectorAll('tr');
            rows.forEach(row => {
                if (row.querySelector('.empty-state')) return;
                
                const text = row.textContent.toLowerCase();
                const show = text.includes(query);
                row.style.display = show ? '' : 'none';
                
                if (show) hasResults = true;
            });
        }
        
        // Search in mobile cards
        if (mobileCards) {
            const cards = mobileCards.querySelectorAll('.user-card');
            cards.forEach(card => {
                if (card.classList.contains('empty-state')) return;
                
                const searchText = card.getAttribute('data-search') || card.textContent.toLowerCase();
                const show = searchText.includes(query);
                card.style.display = show ? 'block' : 'none';
                
                if (show) hasResults = true;
            });
        }
        
        // Show/hide no results message
        if (noResults) {
            noResults.style.display = (query && !hasResults) ? 'block' : 'none';
        }
        
        // Hide empty state if we have a search query
        const emptyStates = document.querySelectorAll('.empty-state');
        emptyStates.forEach(state => {
            state.style.display = (query && !hasResults) ? 'none' : '';
        });
    });

    // Auto-hide alerts after 5 seconds
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
    
    // Handle mobile/desktop switching
    function updateTableVisibility() {
        if (window.innerWidth <= 768) {
            // Mobile view - show cards, hide table
            const tableContainer = document.querySelector('.table-container');
            if (tableContainer) {
                tableContainer.style.display = 'none';
            }
            if (mobileCards) {
                mobileCards.style.display = 'block';
            }
        } else {
            // Desktop view - show table, hide cards
            const tableContainer = document.querySelector('.table-container');
            if (tableContainer) {
                tableContainer.style.display = 'block';
            }
            if (mobileCards) {
                mobileCards.style.display = 'none';
            }
        }
    }
    
    // Initial check
    updateTableVisibility();
    
    // Update on resize
    window.addEventListener('resize', updateTableVisibility);
    
    // Adjust table column widths for better visibility
    function adjustTableColumns() {
        const table = document.querySelector('.custom-table');
        if (table && window.innerWidth >= 769) {
            const nameCells = table.querySelectorAll('td:nth-child(2)');
            const emailCells = table.querySelectorAll('td:nth-child(3)');
            
            // Ensure name and email columns have adequate space
            nameCells.forEach(cell => {
                cell.style.minWidth = '180px';
                cell.style.maxWidth = '250px';
            });
            
            emailCells.forEach(cell => {
                cell.style.minWidth = '200px';
                cell.style.maxWidth = '300px';
            });
        }
    }
    
    // Run on load and resize
    adjustTableColumns();
    window.addEventListener('resize', adjustTableColumns);
});
</script>
</body>
</html>