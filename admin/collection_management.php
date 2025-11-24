<?php
session_start();
include "db_connect.php";

// Check if barangay admin is logged in
$admin_id = $_SESSION['barangay_admin_id'] ?? null;
if (!$admin_id) {
    header("Location: ../login.php");
    exit;
}

// Fetch schedules with collector usernames
$schedules = [];
$sql = "
    SELECT cs.id, cs.barangay, cs.date, cs.time, 
           (SELECT username FROM collection_crew WHERE barangay = cs.barangay LIMIT 1) AS collector_username
    FROM collection_schedule cs
    ORDER BY cs.id ASC
";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $schedules[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Collection Management</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background: #eef1ee; font-family: Arial, sans-serif; }
.content { margin-left: 260px; padding: 20px; margin-top: 20px; }
.card { border-radius: 14px; border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.15); }
.modal-header { background: #3f4a36; color: white; border-radius: 14px 14px 0 0; }
</style>
</head>
<body>

<?php include 'header.php'; ?>
<?php include 'nav.php'; ?>

<div class="content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-dark">Collection Management</h2>
        <button class="btn btn-success px-4 py-2" data-bs-toggle="modal" data-bs-target="#addModal">+ Add Schedule</button>
    </div>

    <div class="card p-3">
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Barangay</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Collector</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($schedules)): ?>
                    <?php $i = 1; ?>
                    <?php foreach ($schedules as $row): ?>
                        <tr>
                            <td><?= $i++; ?></td>
                            <td><?= htmlspecialchars($row['barangay']); ?></td>
                            <td><?= htmlspecialchars($row['date']); ?></td>
                            <td><?= htmlspecialchars($row['time']); ?></td>
                            <td>
                                <?php if ($row['collector_username']): ?>
                                    <?= htmlspecialchars($row['collector_username']); ?>
                                <?php else: ?>
                                    <span class="text-danger fw-bold">No Collector Assigned</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['id']; ?>">Edit</button>
                                <a href="delete_schedule.php?id=<?= $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this schedule?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">No schedules found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Schedule Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Collection Schedule</h5>
        <button type="button" class="btn-close bg-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="add_schedule_process.php" method="POST">
        <div class="modal-body">
            <label class="form-label">Barangay:</label>
            <input type="text" class="form-control mb-3" name="barangay" required>

            <label class="form-label">Date:</label>
            <input type="date" class="form-control mb-3" name="date" required>

            <label class="form-label">Time:</label>
            <input type="time" class="form-control mb-3" name="time" required>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success px-4">Add</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Schedule Modals -->
<?php foreach ($schedules as $row): ?>
<div class="modal fade" id="editModal<?= $row['id']; ?>" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Schedule</h5>
        <button type="button" class="btn-close bg-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="update_schedule_process.php" method="POST">
        <div class="modal-body">
            <input type="hidden" name="schedule_id" value="<?= $row['id']; ?>">

            <label class="form-label">Barangay:</label>
            <input type="text" class="form-control mb-3" name="barangay" value="<?= htmlspecialchars($row['barangay']); ?>" required>

            <label class="form-label">Date:</label>
            <input type="date" class="form-control mb-3" name="date" value="<?= $row['date']; ?>" required>

            <label class="form-label">Time:</label>
            <input type="time" class="form-control mb-3" name="time" value="<?= $row['time']; ?>" required>
        </div>
        <div class="modal-footer">
            <button type="submit" class="btn btn-primary px-4">Update</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endforeach; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
