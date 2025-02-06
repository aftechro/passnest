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
            $modal_message = "Hello, <b>" . htmlspecialchars($user['first_name']) . " " . htmlspecialchars($user['second_name']) . "</b>!<br><br>Your account is suspended! Please contact your manager for access restoration.";
            $modal_title = "Account Suspended";
        } elseif ($user['verified'] == 0) {
            // If the account is not verified, show the verification modal
            $modal_message = "Hello, <b>" . htmlspecialchars($user['first_name']) . " " . htmlspecialchars($user['second_name']) . "</b>!<br><br>Your account is not verified. Please check your inbox or spam for the verification link, or contact your IT administrator.";
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
    
    <!-- Primary Meta Tags -->
    <title>PassNest - Free Password Manager | Secure Alternative to LastPass, 1Password, Keepr & Dashlane</title>
    <meta name="description" content="PassNest is a free, secure, and user-friendly password manager. A great alternative to LastPass, Dashlane, Keeper and 1Password. Store, manage, and autofill passwords effortlessly across all your devices.">
    <meta name="keywords" content="free password manager, PassNest, LastPass alternative, Dashlane alternative, 1Password alternative, Keeper alterntive, secure password manager, password management, autofill passwords, cross-device password manager">
    <meta name="author" content="PassNest">

    <!-- Open Graph / Facebook Meta Tags (for social sharing) -->
    <meta property="og:title" content="PassNest - Free Password Manager | Secure Alternative to LastPass & Dashlane">
    <meta property="og:description" content="PassNest is a free, secure, and user-friendly password manager. A great alternative to LastPass, Dashlane, and 1Password. Store, manage, and autofill passwords effortlessly across all your devices.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://www.passnest.com">
    <meta property="og:image" content="https://www.passnest.com/images/passnest-og-image.jpg">

    <!-- Twitter Meta Tags (for social sharing) -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="PassNest - Free Password Manager | Secure Alternative to LastPass, 1Password, Keepr & Dashlane">
    <meta name="twitter:description" content="PassNest is a free, secure, and user-friendly password manager. A great alternative to LastPass, Dashlane, and 1Password. Store, manage, and autofill passwords effortlessly across all your devices.">
    <meta name="twitter:image" content="https://www.passnest.com/images/passnest-twitter-image.jpg">

    <!-- Canonical Link (to avoid duplicate content issues) -->
    <link rel="canonical" href="https://www.passnest.com">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">

    <!-- Stylesheets -->
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

    <style>
      body {
            background-color: #f8f9fa;
            max-width: 1800px; /* Box the body */
            margin: 0 auto;
            padding: 0;
            padding-left: 2px; /* Left padding */
            padding-right: 2px; /* Right padding */
        }

        /* Content (Login Form and Description) */
        .boxed-container {
            padding: 30px;
            background-color: transparent; /* No background color on the cards */
            border-radius: 8px;
            box-shadow: none; /* Remove shadow */
        }

        .card {
            background-color: transparent !important; /* Remove the white background */
        }

        /* Mobile responsiveness for navbar */
        @media (max-width: 768px) {
            .boxed-container {
                padding: 20px;
            }

            /* Adjusting the layout of the todo items */
            .todo-list {
                flex-direction: column; /* Stack items vertically on mobile */
                gap: 5px;
            }

            .todo-item {
                font-size: 0.9rem; /* Reduce font size slightly for mobile */
            }

            /* Adjust the login form on mobile */
            .card {
                padding: 20px;
                width: 100%;
                max-width: 100%;
            }
        }

        /* Todo List in Line with Cog Icon */
        .todo-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .todo-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: darkred; /* Dark red text */
        }

        .todo-item i {
            font-size: 1.2rem;
            color: darkred; /* Dark red icons */
        }

        /* Beta label (moved to header) */
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
            <p class="lead"><strong>PassNest</strong>: A free alternative to LastPass, Dashlane, 1Password, and Keeper for secure password management.</p><br>

                <p class="lead">PassNest is the ultimate password manager designed to replace outdated and insecure methods of sharing passwords, such as Excel files. With PassNest, your team can securely store, manage, and access passwords anytime, anywhere. Simplify password sharing and enhance security with effortless password retrieval and verification, ensuring your company’s sensitive data stays safe and organized.</p>
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
                        <div>
                            <i class="fas fa-check text-success"></i> Profile page for managing private credentials 
                        </div>                               
                        <div>
                            <i class="fas fa-check text-success"></i> Team/private password: credentials visible for team/dept. and private credentials
                        </div>                            
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
