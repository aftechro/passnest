<?php
session_start();
ob_start(); // Start output buffering

include 'header.php';
include 'config.php';

// Ensure the user is an admin or manager
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'manager'])) {
    header('Location: dashboard.php');
    exit();
}

// Function to add a department
function addDepartment($departmentName) {
    global $pdo;
    try {
        $sql = "INSERT INTO departments (department_name) VALUES (:departmentName)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':departmentName', $departmentName);
        return $stmt->execute();
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Database error: " . $e->getMessage();
        return false;
    }
}

// Function to update a department
function updateDepartment($departmentId, $departmentName, $userIds) {
    global $pdo;
    try {
        // Update department name
        $sql = "UPDATE departments SET department_name = :departmentName WHERE department_id = :departmentId";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':departmentName', $departmentName);
        $stmt->bindParam(':departmentId', $departmentId);
        $stmt->execute();

        // Remove all users from the department first
        $sql = "DELETE FROM department_members WHERE department_id = :departmentId";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['departmentId' => $departmentId]);

        // Add selected users back to the department
        foreach ($userIds as $userId) {
            $sql = "INSERT INTO department_members (department_id, user_id) VALUES (:departmentId, :userId)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['departmentId' => $departmentId, 'userId' => $userId]);
        }

        return true;
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Database error: " . $e->getMessage();
        return false;
    }
}

// Function to delete a department
function deleteDepartment($departmentId) {
    global $pdo;
    try {
        // First, remove all users associated with this department
        $sql = "DELETE FROM department_members WHERE department_id = :departmentId";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['departmentId' => $departmentId]);

        // Now, delete the department itself
        $sql = "DELETE FROM departments WHERE department_id = :departmentId";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['departmentId' => $departmentId]);

        return true;
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Database error: " . $e->getMessage();
        return false;
    }
}

// Handle department addition, editing, and deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['addDepartment'])) {
        $departmentName = trim($_POST['departmentName']);

        if (!empty($departmentName) && addDepartment($departmentName)) {
            $_SESSION['success_message'] = "Department $departmentName has been added!";
        } else {
            $_SESSION['error_message'] = "Error adding department.";
        }
    } elseif (isset($_POST['editDepartment'])) {
        $departmentId = $_POST['departmentId'];
        $departmentName = trim($_POST['departmentName']);
        $userIds = $_POST['userIds'] ?? [];

        if (!empty($departmentName) && updateDepartment($departmentId, $departmentName, $userIds)) {
            $_SESSION['success_message'] = "Department and members have been updated!";
        } else {
            $_SESSION['error_message'] = "Error updating department.";
        }
    } elseif (isset($_POST['deleteDepartment'])) {
        $departmentId = $_POST['departmentId'];

        if (deleteDepartment($departmentId)) {
            $_SESSION['success_message'] = "Department has been deleted!";
        } else {
            $_SESSION['error_message'] = "Error deleting department.";
        }
    }

    // Reload the page to show the updated content
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Fetch all departments with their members
$sql = "SELECT d.department_id, d.department_name, COUNT(dm.user_id) AS user_count, 
               GROUP_CONCAT(CONCAT(u.first_name, ' ', u.second_name) SEPARATOR ', ') AS members
        FROM departments d
        LEFT JOIN department_members dm ON d.department_id = dm.department_id
        LEFT JOIN users u ON dm.user_id = u.user_id
        GROUP BY d.department_id, d.department_name";
$stmt = $pdo->query($sql);
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all users for assigning to departments
$sql = "SELECT user_id, first_name, second_name FROM users WHERE role != 'admin'";  // Exclude admins from member selection
$stmt = $pdo->query($sql);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Departments</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Departments</h4>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDepartmentModal">
            <i class="fas fa-plus"></i> Add Department
        </button>
    </div>
    <hr>

    <!-- Success and Error Messages -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Department Table -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-building"></i> Departments and Members
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Department</th>
                        <th>Members</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($departments as $department): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($department['department_name']); ?></td>
                            <td>
                                <span class="badge bg-secondary">
                                    <i class="fas fa-users"></i> <?php echo $department['user_count']; ?>
                                </span>
                                <?php echo htmlspecialchars($department['members']); ?>
                            </td>
                            <td class="text-right">
                                <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editDepartmentModal<?php echo $department['department_id']; ?>">
                                    <i class="fas fa-pencil-alt"></i> Edit
                                </button>
                                <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteDepartmentModal<?php echo $department['department_id']; ?>">
                                    <i class="fas fa-trash-alt"></i> Delete
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Include Modals -->
<?php include '.modal_departments.php'; ?>

<!-- Bootstrap JS and dependencies -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>
<?php include '.footer.php'; ?>
</html>