<?php
session_start();

// Include the database configuration
include('config.php');

// Generate a CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Function to generate a TOTP code
function generateTOTP() {
    return str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

// Function to send TOTP via email
function sendTOTPByEmail($email, $totp, $first_name, $base_url) {
    $subject = "Your PassNest Login Verification Code";
    
    // HTML-formatted message
    $message = "
        <html>
        <body>
            <p>Hello <b>$first_name!</b></p>
            <p>Your verification code is: <b>$totp</b></p>
            <p>This code will expire in 1 minute.</p><br>
            <p>Thank you!</p>
            <p>PassNest Team<br>$base_url</p>
        </body>
        </html>
    ";
    
    // Headers to specify HTML content
    $headers = "From: PassNest - Password Manager <no-reply@aftech.ro>\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    return mail($email, $subject, $message, $headers);
}

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token.");
    }

    // Handle initial login (username/email and password)
    if (!isset($_SESSION['user_id_for_2fa'])) {
        // Get the username/email and password from the form
        $usernameOrEmail = htmlspecialchars($_POST['username']);
        $password = $_POST['password'];

        // Check if the username/email and password are provided
        if (!empty($usernameOrEmail) && !empty($password)) {
            try {
                // Prepare the SQL query to fetch user data from the database
                $stmt = $pdo->prepare("SELECT user_id, first_name, second_name, role, password, status, verified, email FROM users WHERE username = :usernameOrEmail OR email = :usernameOrEmail");
                $stmt->execute(['usernameOrEmail' => $usernameOrEmail]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                // Check if the user exists
                if ($user) {
                    // Check if the password matches
                    if (password_verify($password, $user['password'])) {
                        // Check the status of the user (suspended or verified)
                        if ($user['status'] == 'suspended') {
                            // If the account is suspended, show the suspension modal
                            $modal_message = "Hello, <b>" . htmlspecialchars($user['first_name']) . " " . htmlspecialchars($user['second_name']) . "</b>!<br><br>Your account is suspended! Please contact your manager for access restoration.";
                            $modal_title = "Account Suspended";
                        } elseif ($user['verified'] == 0) {
                            // If the account is not verified, show the verification modal
                            $modal_message = "Hello, <b>" . htmlspecialchars($user['first_name']) . " " . htmlspecialchars($user['second_name']) . "</b>!<br><br>Your account is not verified. Please check your inbox or spam for the verification link, or contact your IT administrator.";
                            $modal_title = "Account Not Verified";
                        } else {
                            // Generate a TOTP code
                            $totp = generateTOTP();
                            $totp_expiry = date('Y-m-d H:i:s', strtotime('+1 minute'));

                            // Update the user's record with the TOTP code and expiry time
                            $updateStmt = $pdo->prepare("UPDATE users SET totp_code = :totp_code, totp_expiry = :totp_expiry WHERE user_id = :user_id");
                            $updateStmt->execute([
                                'totp_code' => $totp,
                                'totp_expiry' => $totp_expiry,
                                'user_id' => $user['user_id']
                            ]);

                            // Send the TOTP code to the user's email
                            if (sendTOTPByEmail($user['email'], $totp, $user['first_name'], $base_url)) {
                                // Store the user ID in the session for verification
                                $_SESSION['user_id_for_2fa'] = $user['user_id'];

                                // Log the login attempt in users_access (before TOTP verification)
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

                                // Set a flag to show the OTP input field
                                $_SESSION['show_otp'] = true;
                            } else {
                                $error_message = "Failed to send the verification code. Please try again.";
                            }
                        }
                    } else {
                        // Invalid password
                        $error_message = "Invalid username/email or password.";
                    }
                } else {
                    // Invalid username/email
                    $error_message = "Invalid username/email or password.";
                }
            } catch (PDOException $e) {
                // Handle database connection errors
                $error_message = "Error: " . $e->getMessage();
            }
        } else {
            $error_message = "Please enter both username/email and password.";
        }
    } else {
        // Handle OTP verification
        $otp = htmlspecialchars($_POST['otp']);

        // Fetch the user's TOTP code and expiry time
        $stmt = $pdo->prepare("SELECT totp_code, totp_expiry, role FROM users WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $_SESSION['user_id_for_2fa']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && $user['totp_code'] === $otp && strtotime($user['totp_expiry']) > time()) {
            // TOTP code is valid, proceed with login
            $_SESSION['user_id'] = $_SESSION['user_id_for_2fa'];
            $_SESSION['role'] = $user['role'];

            // Log TOTP success in users_access
            $updateStmt = $pdo->prepare("UPDATE users_access SET totp_success = 1, totp_error = NULL WHERE user_id = :user_id ORDER BY id DESC LIMIT 1");
            $updateStmt->execute(['user_id' => $_SESSION['user_id']]);

            // Clear the session variables used for 2FA
            unset($_SESSION['user_id_for_2fa']);
            unset($_SESSION['show_otp']);

            // Redirect to the dashboard
            header('Location: dashboard.php');
            exit();
        } else {
            // TOTP code is invalid or expired
            $error_message = "Invalid or expired verification code.";

            // Log TOTP error in users_access
            $updateStmt = $pdo->prepare("UPDATE users_access SET totp_success = 0, totp_error = :error_message WHERE user_id = :user_id ORDER BY id DESC LIMIT 1");
            $updateStmt->execute([
                'error_message' => $error_message,
                'user_id' => $_SESSION['user_id_for_2fa']
            ]);

            // Clear the session variables to allow the user to go back to the login form
            unset($_SESSION['user_id_for_2fa']);
            unset($_SESSION['show_otp']);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PassNest - Free Password Manager</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            max-width: 1800px;
            margin: 0 auto;
            padding: 0 2px;
        }

        .card {
            background-color: transparent !important;
        }

        .todo-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .todo-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: darkred;
        }

        .todo-item i {
            font-size: 1.2rem;
            color: darkred;
        }

        .beta-label {
            background-color: yellow;
            color: red;
            font-weight: bold;
            padding: 5px 10px;
            border-radius: 5px;
            position: absolute;
            top: 0;
            right: 0;
            z-index: 1000;
            font-size: 0.8rem;
        }

        /* Progress Bar Animation */
        .progress-bar {
            width: 100%;
            height: 5px;
            background-color: #e9ecef;
            border-radius: 5px;
            overflow: hidden;
            margin-top: 10px;
        }

        .progress-bar-inner {
            height: 100%;
            width: 0;
            background-color: green;
            transition: width 1s linear, background-color 1s linear;
        }
    </style>
</head>
<body>
    <!-- Beta Label in the Header -->
    <div class="beta-label">
        Beta: Work in progress
    </div>

    <div class="container-fluid min-vh-100 d-flex align-items-center">
        <div class="row w-100">
            <!-- Left Section: Logo and Description -->
            <div class="col-md-6 d-flex flex-column justify-content-center align-items-start px-5">
                <h1><i class="fa fa-key"></i> PassNest</h1>
                <p class="lead"><strong>PassNest</strong>: A free alternative to LastPass, Dashlane, 1Password, and Keeper for secure password management.</p>
                <hr>
                <div class="mt-3">
                    <h5>Features:</h5>
                    <div class="d-flex flex-wrap gap-3">
                        <div><i class="fas fa-check text-success"></i> No more shared Excel files</div>
                        <div><i class="fas fa-check text-success"></i> Real-time password updates</div>
                        <div><i class="fas fa-check text-success"></i> Robust encryption</div>
                        <div><i class="fas fa-check text-success"></i> Multi-user access control</div>
                        <div><i class="fas fa-check text-success"></i> Activity logs for accountability</div>
                        <div><i class="fas fa-check text-success"></i> Two-factor authentication (2FA)</div>
                        <div><i class="fas fa-check text-success"></i> Profile page for managing private credentials</div>
                        <div><i class="fas fa-check text-success"></i> Team/private password: credentials visible for team/dept. and private credentials</div>
                    </div>
                </div>
                <hr style="border-top: 2px solid gray;">
                <div class="mt-3">
                    <h5>To do:</h5>
                    <div class="todo-list">
                        <div class="todo-item"><i class="fas fa-cogs"></i> Logs for activity</div>
                        <div class="todo-item"><i class="fas fa-cogs"></i> Browser extension</div>
                        <div class="todo-item"><i class="fas fa-cogs"></i> Password sharing</div>
                        <div class="todo-item"><i class="fas fa-cogs"></i> OTP Functionality</div>
                        <div class="todo-item"><i class="fas fa-cogs"></i> Managing 2fa</div>
                    </div>
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

                    <form method="POST" action="index.php" id="loginForm">
                        <!-- CSRF Token -->
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                        <!-- Username and Password Fields -->
                        <?php if (!isset($_SESSION['show_otp'])): ?>
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label for="username" class="form-label">Username or Email</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user-circle"></i></span>
                                        <input type="text" class="form-control" name="username" id="username" required placeholder="Enter username or email">
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label for="password" class="form-label">Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" name="password" id="password" required placeholder="Enter password">
                                    </div>
                                </div>
                            </div>
                            <hr>
                            <small> Manager: <strong>manager / manager</strong> <br> Standard user: <strong>staff / staff</strong> <br>Suspended user: <strong>jane / jane</strong> <br> Active user but not email verified: <strong>john / john</strong> </small>
                        <?php else: ?>
                            <!-- OTP Field -->
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label for="otp" class="form-label">Enter OTP</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-shield-alt"></i></span>
                                        <input type="text" class="form-control" name="otp" id="otp" required placeholder="Enter OTP">
                                    </div>
                                </div>
                            </div>
                            <!-- Progress Bar -->
                            <div class="progress-bar">
                                <div class="progress-bar-inner" id="progressBar"></div>
                            </div>
                        <?php endif; ?>

                        <!-- Error Message -->
                        <?php if (isset($error_message)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo htmlspecialchars($error_message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-primary w-100 mt-3">
                            <?php if (isset($_SESSION['show_otp'])): ?>
                                <i class="fas fa-check-circle"></i> Verify OTP
                            <?php else: ?>
                                <i class="fas fa-sign-in-alt"></i> Login
                            <?php endif; ?>
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

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Show Modal if Set -->
    <script>
        <?php if (isset($modal_message)): ?>
            var myModal = new bootstrap.Modal(document.getElementById('accountModal'));
            myModal.show();
        <?php endif; ?>
    </script>

    <!-- Progress Bar Animation and Form Handling -->
    <script>
        // Function to start the progress bar animation
        function startProgressBar() {
            const progressBar = document.getElementById('progressBar');
            let width = 0;
            const interval = 1000; // 1 second
            const totalTime = 60000; // 60 seconds
            const increment = (100 / (totalTime / interval));

            const timer = setInterval(() => {
                width += increment;
                progressBar.style.width = width + '%';

                // Change color based on time
                if (width <= 43) {
                    progressBar.style.backgroundColor = 'green';
                } else if (width <= 50) {
                    progressBar.style.backgroundColor = 'orange';
                } else {
                    progressBar.style.backgroundColor = 'red';
                }

                // Reset form after 60 seconds
                if (width >= 100) {
                    clearInterval(timer);
                    // Clear session variables and reload the page
                    fetch('reset_session.php')
                        .then(() => window.location.href = 'index.php');
                }
            }, interval);
        }

        // Start the progress bar if OTP field is visible
        <?php if (isset($_SESSION['show_otp'])): ?>
            startProgressBar();
        <?php endif; ?>

        // Prevent form submission from opening a new window
        document.getElementById('loginForm').addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent default form submission
            this.submit(); // Submit the form within the same window
        });
    </script>
</body>
</html>
