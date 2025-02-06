<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'header.php';
include 'config.php';

// Check if the user is an admin or manager
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'manager'])) {
    header('Location: dashboard.php');
    exit();
}

// Fetch all users with the last login and IP address
$sql = "SELECT u.user_id, u.first_name, u.second_name, u.email, u.username, u.role, u.status, u.verified,
               COALESCE(ua.last_login, 'N/A') AS last_login, COALESCE(ua.ip_signed_in_from, 'N/A') AS ip_signed_in_from
        FROM users u
        LEFT JOIN users_access ua ON u.user_id = ua.user_id";
$stmt = $pdo->query($sql);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Function to validate role addition based on user privileges
function canAddRole($currentUserRole, $newRole) {
    if ($currentUserRole === 'admin') {
        // Admin can add any role
        return true;
    } elseif ($currentUserRole === 'manager') {
        // Manager can only add 'manager' or 'staff'
        return in_array($newRole, ['manager', 'staff']);
    }
    return false;
}

// Function to add a user
function addUser($currentUserRole, $firstName, $secondName, $email, $username, $password, $role, $status, $verified) {
    global $pdo, $base_url;

    // Validate role addition based on current user privileges
    if (!canAddRole($currentUserRole, $role)) {
        return false;
    }

    $status = ($status === 'active') ? 'active' : 'suspended';
    $verificationToken = bin2hex(random_bytes(16)); // Generate a random token
    $tokenExpiry = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token expires in 1 hour

    $sql = "INSERT INTO users (first_name, second_name, email, username, password, role, status, verified, verification_token, verification_token_expiry) 
            VALUES (:firstName, :secondName, :email, :username, :password, :role, :status, :verified, :verificationToken, :tokenExpiry)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':firstName', $firstName);
    $stmt->bindParam(':secondName', $secondName);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':password', $password);
    $stmt->bindParam(':role', $role);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':verified', $verified);
    $stmt->bindParam(':verificationToken', $verificationToken);
    $stmt->bindParam(':tokenExpiry', $tokenExpiry);

    if ($stmt->execute()) {
        // Send verification email
        $verificationUrl = $base_url . "/verify.php?token=" . $verificationToken;
        $subject = "Verify Your Email Address";
        $message = "Hi $firstName $secondName,\n\nPlease click the following link to verify your email address and activate your account:\n$verificationUrl\n\nThank you!";
        $headers = "From: no-reply@aftech.ro";

        mail($email, $subject, $message, $headers); // Send email
        return true;
    }
    return false;
}

// Function to update user fields
function updateUserFields($currentUserRole, $userId, $fields) {
    global $pdo;

    // Check if the user being edited is an admin
    $sql = "SELECT role FROM users WHERE user_id = :userId";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['userId' => $userId]);
    $userRole = $stmt->fetchColumn();

    if ($userRole === 'admin' && $currentUserRole !== 'admin') {
        // If a manager tries to edit an admin, deny the action
        return "Why??? Leave the admin alone!";
    }

    $setClause = [];
    $params = ['userId' => $userId];
    foreach ($fields as $key => $value) {
        if ($key == 'status') {
            $value = ($value === 'active') ? 'active' : 'suspended';
        }
        $setClause[] = "$key = :$key";
        $params[$key] = $value;
    }

    $sql = "UPDATE users SET " . implode(", ", $setClause) . " WHERE user_id = :userId";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute($params) ? true : "Error updating user.";
}




// Function to delete a user
function deleteUser($currentUserRole, $userId) {
    global $pdo;

    // Check if the user being deleted is an admin
    $sql = "SELECT role FROM users WHERE user_id = :userId";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['userId' => $userId]);
    $userRole = $stmt->fetchColumn();

    if ($userRole === 'admin' && $currentUserRole !== 'admin') {
        // If a manager tries to delete an admin, deny the action
        return "Nice try! Not nice of you trying to delete the admin!";
    }

    // First, delete the related records from users_access
    $sql = "DELETE FROM users_access WHERE user_id = :userId";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['userId' => $userId]);

    // Now, delete the user from the users table
    $sql = "DELETE FROM users WHERE user_id = :userId";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['userId' => $userId]);

    return true;
}

// Handle user addition, editing, and deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['addUser'])) {
        $firstName = $_POST['firstName'];
        $secondName = $_POST['secondName'];
        $email = $_POST['email'];
        $username = $_POST['username'];
        $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $role = $_POST['role'];
        $status = $_POST['status'];
        $verified = isset($_POST['verified']) ? 1 : 0;

        // Pass the current user's role to the addUser function for validation
        if (addUser($_SESSION['role'], $firstName, $secondName, $email, $username, $password, $role, $status, $verified)) {
            $_SESSION['success_message'] = "User $username has been added!";
        } else {
            $_SESSION['error_message'] = "Error adding user or invalid role selection.";
        }
    } elseif (isset($_POST['editUser'])) {
        $userId = $_POST['userId'];
        $fields = [];
        if (!empty($_POST['firstName'])) {
            $fields['first_name'] = $_POST['firstName'];
        }
        if (!empty($_POST['secondName'])) {
            $fields['second_name'] = $_POST['secondName'];
        }
        if (!empty($_POST['email'])) {
            $fields['email'] = $_POST['email'];
        }
        if (!empty($_POST['username'])) {
            $fields['username'] = $_POST['username'];
        }
        if (!empty($_POST['role'])) {
            $fields['role'] = $_POST['role'];
        }
        if (!empty($_POST['status'])) {
            $fields['status'] = $_POST['status'];
        }
        if (isset($_POST['verified'])) {
            $fields['verified'] = 1;
        } else {
            $fields['verified'] = 0;
        }

        if (!empty($_POST['password'])) {
            $fields['password'] = password_hash($_POST['password'], PASSWORD_BCRYPT);
        }

        $updateResult = updateUserFields($_SESSION['role'], $userId, $fields);

        if ($updateResult === true) {
            $_SESSION['success_message'] = "User has been updated!";
        } else {
            $_SESSION['error_message'] = $updateResult; // Display the "Leave him alone!" message or error
        }
    } elseif (isset($_POST['deleteUser'])) {
        $userId = $_POST['userId'];
        $deleteResult = deleteUser($_SESSION['role'], $userId);

        if ($deleteResult === true) {
            $_SESSION['success_message'] = "User has been deleted!";
        } else {
            $_SESSION['error_message'] = $deleteResult; // Display the "Nice try" message
        }
    }
    header('Location: users.php');
    exit();
}




// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sql = "SELECT u.user_id, u.first_name, u.second_name, u.email, u.username, u.role, u.status, u.verified,
               COALESCE(ua.last_login, 'N/A') AS last_login, COALESCE(ua.ip_signed_in_from, 'N/A') AS ip_signed_in_from
        FROM users u
        LEFT JOIN users_access ua ON u.user_id = ua.user_id
        WHERE u.first_name LIKE :search OR u.second_name LIKE :search
        LIMIT :offset, :perPage";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':perPage', $perPage, PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$countSql = "SELECT COUNT(*) 
             FROM users u 
             LEFT JOIN users_access ua ON u.user_id = ua.user_id
             WHERE u.first_name LIKE :search OR u.second_name LIKE :search";
$countStmt = $pdo->prepare($countSql);
$countStmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
$countStmt->execute();
$totalUsers = $countStmt->fetchColumn();
$totalPages = ceil($totalUsers / $perPage);

?>

 <style>
    
        .table-modern {
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        .table-modern th, .table-modern td {
            vertical-align: middle;
        }
        .table-modern thead th {
            background-color: #343a40;
            color: #fff;
            font-weight: 500;
        }
        .table-modern tbody tr:hover {
            background-color: #f8f9fa;
        }
        .btn-copy {
            padding: 5px 10px;
        }
        .btn-copy:hover {
            background-color: #e9ecef;
        }
        .card {
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .card-header {
            font-weight: 500;
        }
        .badge {
            font-size: 0.9em;
        }
    </style>
<div class="container mt-5">
    <h4 class="text-center mb-4">Admin Panel - User Management</h4>
    <hr>
    <div class="alert alert-danger text-center" role="alert">
        <i class="fa fa-exclamation-triangle" aria-hidden="true"></i> 
        CUD functions are disabled on demo preview for this section.
    </div>

    <!-- Success and Error Messages -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['success_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['error_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <!-- Search and Add User Button -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <form method="GET" class="d-flex gap-2">
            <input type="text" name="search" class="form-control" placeholder="Search by name" value="<?= htmlspecialchars($search ?? ''); ?>">
            <button type="submit" class="btn btn-primary">Search</button>
        </form>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="fa fa-plus"></i> Add User
        </button>
    </div>

    <!-- Users Table -->
    <div class="table-responsive">
        <table class="table table-bordered table-hover table-modern table-striped">
            <thead class="table-dark">
                <tr>
                    <th><i class="fa fa-user"></i> Name</th>
                    <th><i class="fa fa-envelope"></i> Email</th>
                    <th><i class="fa fa-user-circle"></i> Username</th>
                    <th><i class="fa fa-briefcase"></i> Role</th>
                    <th><i class="fa fa-toggle-on"></i> Status</th>
                    <th><i class="fa fa-check-circle"></i> Verified</th>
                    <th><i class="fa fa-calendar"></i> Last Login</th>
                    <th><i class="fa fa-globe"></i> IP Address</th>
                    <th><i class="fa fa-cogs"></i> Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($users)) : ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['first_name'] . ' ' . $user['second_name']); ?></td>
                            <td><?= htmlspecialchars($user['email']); ?></td>
                            <td><?= htmlspecialchars($user['username']); ?></td>
                            <td><?= htmlspecialchars($user['role']); ?></td>
                            <td>
                                <span class="badge <?= $user['status'] == 'active' ? 'bg-success' : 'bg-warning'; ?>">
                                    <?= $user['status'] == 'active' ? 'Active' : 'Suspended'; ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?= $user['verified'] == 1 ? 'bg-success' : 'bg-danger'; ?>">
                                    <?= $user['verified'] == 1 ? 'Yes' : 'No'; ?>
                                </span>
                            </td>
                            <td>
                                <?= $user['last_login'] !== 'N/A' ? 
                                    htmlspecialchars((new DateTime($user['last_login']))->format('d.m.Y - H:i:s')) : 'N/A'; ?>
                            </td>
                            <td><?= htmlspecialchars($user['ip_signed_in_from']); ?></td>
                            <td style="text-align: right;">
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editUserModal"
                                            data-id="<?= $user['user_id']; ?>" 
                                            data-first-name="<?= htmlspecialchars($user['first_name']); ?>" 
                                            data-second-name="<?= htmlspecialchars($user['second_name']); ?>" 
                                            data-email="<?= htmlspecialchars($user['email']); ?>" 
                                            data-username="<?= htmlspecialchars($user['username']); ?>" 
                                            data-role="<?= htmlspecialchars($user['role']); ?>" 
                                            data-status="<?= htmlspecialchars($user['status']); ?>" 
                                            data-verified="<?= $user['verified'] ? '1' : '0'; ?>">
                                        <i class="fa fa-edit"></i> 
                                    </button>
                                    <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteUserModal1"
                                            data-id="<?= $user['user_id']; ?>">
                                        <i class="fa fa-trash"></i> 
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="text-center">No users found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <nav>
        <ul class="pagination justify-content-center mt-4">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?search=<?= urlencode($search); ?>&page=<?= $i; ?>"><?= $i; ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>

    <!-- Include Modals -->
    <?php include '.modal_users.php'; ?>
</div>

<!-- Bootstrap JS and dependencies -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>

<script>
// Edit User Modal
const editUserModal = document.getElementById('editUserModal');
editUserModal.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    document.getElementById('editUserId').value = button.getAttribute('data-id');
    document.getElementById('editFirstName').value = button.getAttribute('data-first-name');
    document.getElementById('editSecondName').value = button.getAttribute('data-second-name');
    document.getElementById('editEmail').value = button.getAttribute('data-email');
    document.getElementById('editUsername').value = button.getAttribute('data-username');
    document.getElementById('editRole').value = button.getAttribute('data-role');
    document.getElementById('editStatus').value = button.getAttribute('data-status');
    document.getElementById('editVerified').checked = button.getAttribute('data-verified') == '1';
});

// Delete User Modal
const deleteUserModal = document.getElementById('deleteUserModal');
deleteUserModal.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    document.getElementById('deleteUserId').value = button.getAttribute('data-id');
});
</script>

<?php include '.footer.php'; ?>