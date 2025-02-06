<?php
// Include configuration and functions
require_once 'config.php';
require_once 'functions.php';
require_once 'header.php';

// Ensure session is already started (session_start() already in functions.php)
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: login.php");
    exit;
}

// Fetch user details and department
$query = "SELECT u.*, d.department_name FROM users u
          LEFT JOIN department_members dm ON u.user_id = dm.user_id
          LEFT JOIN departments d ON dm.department_id = d.department_id
          WHERE u.user_id = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch department members if the user is a manager
$team_members = [];
$departments = [];
if ($user['role'] === 'manager') {
    $team_query = "SELECT d.department_name, COUNT(u.user_id) as user_count, 
                          GROUP_CONCAT(CONCAT(u.first_name, ' ', u.second_name) SEPARATOR ', ') AS members
                   FROM departments d
                   JOIN department_members dm ON d.department_id = dm.department_id
                   JOIN users u ON dm.user_id = u.user_id
                   GROUP BY d.department_name";
    $team_stmt = $pdo->prepare($team_query);
    $team_stmt->execute();
    $departments = $team_stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch departments for staff role
$staff_departments = [];
if ($user['role'] === 'staff') {
    $staff_query = "SELECT d.department_name, COUNT(c.credential_id) AS credential_count 
                    FROM departments d
                    JOIN department_members dm ON d.department_id = dm.department_id
                    LEFT JOIN credentials c ON d.department_id = c.department_id
                    WHERE dm.user_id = ?
                    GROUP BY d.department_name";
    $staff_stmt = $pdo->prepare($staff_query);
    $staff_stmt->execute([$user_id]);
    $staff_departments = $staff_stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle password update and validation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['password'] ?? '';
    $errors = [];

    if (strlen($new_password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    }
    if (!preg_match('/[0-9]/', $new_password)) {
        $errors[] = "Password must contain at least one number.";
    }
    if (!preg_match('/[@$!%*?&]/', $new_password)) {
        $errors[] = "Password must contain at least one special character.";
    }

    if (empty($errors)) {
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
        $update_query = "UPDATE users SET password = ? WHERE user_id = ?";
        $update_stmt = $pdo->prepare($update_query);
        $update_stmt->execute([$hashed_password, $user_id]);

        $success_message = "Password updated successfully!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome, <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['second_name']); ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        body {
            background-color: #f8f9fa;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .card-header {
            background-color: #343a40;
            color: white;
            border-radius: 10px 10px 0 0;
        }
        .password-strength-bar {
            height: 5px;
            width: 0;
            background-color: red;
            transition: width 0.5s ease, background-color 0.5s ease;
        }
        .password-strength-text {
            font-size: 0.9rem;
            margin-top: 5px;
        }
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

     .department-card {
    background: white;
    border-radius: 10px;
    padding: 20px;
    text-align: center;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.department-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
}

.department-card .count {
    font-size: 3rem;
    font-weight: bold;
    color: #343a40; /* Dark color for the count */
}

.department-card hr {
    border-top: 2px solid #e0e0e0;
    width: 50%;
    margin: 1rem auto;
}

.department-card .department-name {
    font-size: 1.5rem;
    color: #343a40; /* Dark color for the department name */
    margin-bottom: 0;
}

.department-card .text-muted {
    font-size: 0.9rem;
}
    </style>
</head>
<body>
    <div class="container mt-5">
        <!-- Page Title -->
        <h3 class="text-center mb-4">Welcome, <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['second_name']); ?></h3><hr>

        <div class="row">
            <!-- Profile Info Card -->
            <div class="col-md-6 col-12 mb-4">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-user"></i> Profile Information
                    </div>
                    <div class="card-body">
                        <p><strong><i class="fas fa-id-card"></i> Name:</strong> <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['second_name']); ?></p>
                        <p><strong><i class="fas fa-envelope"></i> Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                        <p><strong><i class="fas fa-user-tag"></i> Role:</strong> <?php echo htmlspecialchars($user['role']); ?></p>
                    </div>
                </div>
            </div>

            <!-- Password Change Card -->
            <div class="col-md-6 col-12 mb-4">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-lock"></i> Change Your Password
                    </div>
                    <div class="card-body">
                        <form method="POST1">
                            <div class="form-group">
                                <label for="password"> You Must Choose, But Choose Wisely! </label><hr>
                                <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" id="password" name="password" class="form-control" placeholder="Enter new password" oninput="checkPasswordStrength()" required>
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-secondary" onclick="togglePasswordVisibility()">
                                            <i class="fas fa-eye" id="view-password-icon"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary" onclick="generatePassword()">
                                            <i class="fas fa-random"></i>
                                        </button>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Update
                                        </button>
                                    </div>
                                </div>
                                <div class="password-strength-bar mt-2"></div>
                                <div id="password-strength" class="password-strength-text"></div>
                            </div>
                            <?php if (isset($success_message)) : ?>
                                <div class="alert alert-success mt-3"><?php echo $success_message; ?></div>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Manager's Department Table -->
        <?php if ($user['role'] === 'manager') : ?>
            <div class="row mt-4">
                <div class="col-12">
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
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($departments as $department) : ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($department['department_name']); ?></td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <i class="fas fa-users"></i> <?php echo $department['user_count']; ?>
                                                </span>
                                                <?php echo htmlspecialchars($department['members']); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?><hr>

<!-- Staff's Department Cards -->
<?php if ($user['role'] === 'staff') : ?>
    <div class="row mt-4">
        <?php foreach ($staff_departments as $staff_department) : ?>
            <div class="col-md-4 col-12 mb-4">
                <div class="department-card text-center p-2 border border-secondary rounded">
                    
                    <div class="count display-4 fw-bold text-dark">
                        <?php echo htmlspecialchars($staff_department['credential_count']); ?>
                    </div>
                    <small class="text-muted">Credentials for</small>
                    <hr class="my-3">
                    <div class="department-name h5 text-dark">
                        <i class="fas fa-building  mb-3 text-secondary"></i> <?php echo htmlspecialchars($staff_department['department_name']); ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <hr>
<?php endif; ?>
                                        
                                        
                                        
    </div>

    <!-- Footer -->
    <?php require_once '.footer.php'; ?>

    <!-- Bootstrap JS and Custom Script -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password Strength Check
        function checkPasswordStrength() {
            var password = document.getElementById('password').value;
            var strengthBar = document.querySelector('.password-strength-bar');
            var strengthText = document.getElementById('password-strength');
            var strength = 0;

            if (password.length >= 8) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[@$!%*?&]/.test(password)) strength++;

            var strengthPercentage = (strength / 4) * 100;
            strengthBar.style.width = strengthPercentage + '%';

            if (strengthPercentage < 50) {
                strengthBar.style.backgroundColor = 'red';
                strengthText.textContent = 'Weak';
            } else if (strengthPercentage < 75) {
                strengthBar.style.backgroundColor = 'orange';
                strengthText.textContent = 'Medium';
            } else {
                strengthBar.style.backgroundColor = 'green';
                strengthText.textContent = 'Strong';
            }

            strengthText.classList.add("fade-in");
            setTimeout(() => {
                strengthText.classList.remove("fade-in");
            }, 500);
        }

        // Toggle password visibility
        function togglePasswordVisibility() {
            var passwordField = document.getElementById('password');
            var viewPasswordIcon = document.getElementById('view-password-icon');
            if (passwordField.type === "password") {
                passwordField.type = "text";
                viewPasswordIcon.classList.remove('fa-eye');
                viewPasswordIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = "password";
                viewPasswordIcon.classList.remove('fa-eye-slash');
                viewPasswordIcon.classList.add('fa-eye');
            }
        }

        // Generate Password
        function generatePassword() {
            var password = Math.random().toString(36).slice(-8) + '!';
            document.getElementById('password').value = password;
            checkPasswordStrength();
        }
    </script>
</body>
</html>