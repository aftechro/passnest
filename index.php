<?php
session_start();

// Include the database configuration
include('config.php');

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the username and password from the form
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Check if the username and password are provided
    if (!empty($username) && !empty($password)) {
        try {
            // Prepare the SQL query to fetch user data from the database
            $stmt = $pdo->prepare("SELECT user_id, first_name, second_name, role, password, status, verified FROM users WHERE username = :username");
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Check if the user exists
            if ($user) {
                // Check if the password matches
                if (password_verify($password, $user['password'])) {
                    // Check the status of the user (suspended or verified)
                    if ($user['status'] == 'suspended') {
                        // If the account is suspended, show the suspension modal
                        $modal_message = "Hello, " . htmlspecialchars($user['first_name']) . " " . htmlspecialchars($user['second_name']) . "!<br>Your account is suspended! Please contact your manager for access restoration.";
                        $modal_title = "Account Suspended";
                    } elseif ($user['verified'] == 0) {
                        // If the account is not verified, show the verification modal
                        $modal_message = "Hello, " . htmlspecialchars($user['first_name']) . " " . htmlspecialchars($user['second_name']) . "!<br>Your account is not verified. Please check your inbox or spam for the verification link, or contact your IT administrator.";
                        $modal_title = "Account Not Verified";
                    } else {
                        // If user is active and verified, proceed with login
                        $_SESSION['user_id'] = $user['user_id'];
                        $_SESSION['role'] = $user['role'];

                        // Get the current timestamp and IP address
                        $last_login = date('Y-m-d H:i:s');
                        $ip_address = $_SERVER['REMOTE_ADDR'];

                        // Check if the user has an entry in the users_access table
                        $checkStmt = $pdo->prepare("SELECT * FROM users_access WHERE user_id = :user_id");
                        $checkStmt->execute(['user_id' => $user['user_id']]);
                        $accessRecord = $checkStmt->fetch(PDO::FETCH_ASSOC);

                        if ($accessRecord) {
                            // If the user has an existing record, update last_login and ip_signed_in_from
                            $updateStmt = $pdo->prepare("UPDATE users_access SET last_login = :last_login, ip_signed_in_from = :ip_address WHERE user_id = :user_id");
                            $updateStmt->execute([
                                'last_login' => $last_login,
                                'ip_address' => $ip_address,
                                'user_id' => $user['user_id']
                            ]);
                        } else {
                            // If no record exists, insert a new one
                            $insertStmt = $pdo->prepare("INSERT INTO users_access (user_id, last_login, ip_signed_in_from) VALUES (:user_id, :last_login, :ip_address)");
                            $insertStmt->execute([
                                'user_id' => $user['user_id'],
                                'last_login' => $last_login,
                                'ip_address' => $ip_address
                            ]);
                        }

                        // Redirect to the dashboard
                        header('Location: dashboard.php');
                        exit();
                    }
                } else {
                    // Invalid password
                    $error_message = "Invalid username or password.";
                }
            } else {
                // Invalid username
                $error_message = "Invalid username or password.";
            }
        } catch (PDOException $e) {
            // Handle database connection errors
            $error_message = "Error: " . $e->getMessage();
        }
    } else {
        $error_message = "Please enter both username and password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PassNest Login</title>

    <!-- Add Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Add Font Awesome for icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #f8f9fa;
        }
        .card {
            background-color: #ffffff;
        }
        .input-group-text, .form-control {
            background-color: #f8f9fa;
            border-color: #ced4da;
        }
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }
        .btn-close {
            background: transparent;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>

    <div class="container-fluid min-vh-100 d-flex align-items-center">
        <div class="row w-100">

            <!-- Left Section: Logo and Description -->
            <div class="col-md-6 d-flex flex-column justify-content-center align-items-start px-5">
                <h1><i class="fa fa-key"></i> PassNest</h1>
                <p class="lead">The ultimate password manager designed to securely store, manage, and access passwords anytime, anywhere. Say goodbye to insecure password sharing and simplify your workflow with PassNest.</p>
                <hr>
                <div class="mt-3">
                    <h5>Features:</h5>
                    <div class="d-flex flex-wrap gap-3">
                        <div>
                            <i class="fas fa-check text-success"></i> No more shared Excel files
                        </div>
                        <div>
                            <i class="fas fa-check text-success"></i> Real-time password updates
                        </div>
                        <div>
                            <i class="fas fa-check text-success"></i> Robust encryption
                        </div>
                        <div>
                            <i class="fas fa-check text-success"></i> Multi-user access control
                        </div>
                        <div>
                            <i class="fas fa-check text-success"></i> Activity logs for accountability
                        </div>
                        <div>
                            <i class="fas fa-check text-success"></i> Two-factor authentication (2FA)
                        </div>
                    </div>
                </div>
                <hr>
                <div class="mt-3">
                    <h5>To do:</h5>
                    <ul>
                        <li>Logs for activity</li>
                        <li>Profile page for managing private credentials and 2fa</li>
                        <li>Browser extension</li>
                        <li>Team/private password: credentials visible for team/dept. and private credentials</li>
                        <li>Password sharing</li>
                        <li>OTP Functionality</li>
                    </ul>
                </div>
            </div>

            <!-- Right Section: Login Form -->
            <div class="col-md-6 d-flex justify-content-center align-items-center">
                <div class="card p-4 shadow-sm" style="max-width: 400px; width: 100%;">
                    <center>
                        <h4 class="header-title">
                            <i class="fa fa-key"></i>
                            Pass<span class="muted-text">Nest</span>
                        </h4>
                    </center>
                    <br>

                    <form method="POST" action="index.php">
                        <div class="row mb-3">
                            <!-- Username Input with Icon on Left -->
                            <div class="col-md-12">
                                <label for="username" class="form-label">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user-circle"></i></span>
                                    <input type="text" class="form-control" name="username" id="username" required placeholder="Enter username">
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <!-- Password Input with Icon on Left -->
                            <div class="col-md-12">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" name="password" id="password" required placeholder="Enter password">
                                </div>
                            </div>
                        </div><hr>
                                <small> Manager: <strong>manger / manager</strong> <br> Standard user: <strong>staff / staff</strong> <br>Suspended user: <strong>jane / jane</strong> <br> Active user but not email verified: <strong>john / john</strong> </small>

                        <!-- Error Message -->
                        <?php if (isset($error_message)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo htmlspecialchars($error_message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        
                                          

                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-primary w-100 mt-3">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>

    <!-- Modal for Suspended or Unverified Accounts -->
    <?php if (isset($modal_message)): ?>
        <div class="modal fade" id="accountModal" tabindex="-1" aria-labelledby="accountModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                        <h5 class="modal-title" id="accountModalLabel"><?php echo htmlspecialchars($modal_title); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <?php echo $modal_message; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Add Bootstrap JS (optional but recommended for interactivity) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Show Modal if Set -->
    <script>
        <?php if (isset($modal_message)): ?>
            var myModal = new bootstrap.Modal(document.getElementById('accountModal'));
            myModal.show();
        <?php endif; ?>
    </script>
</body>
</html>

