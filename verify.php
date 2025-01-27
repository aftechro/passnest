<?php
// verify.php

// Start session and include necessary files
session_start();
include 'config.php';
include 'header.php'; // Include header to maintain the same layout

// Initialize variables
$message = '';
$verified = false;

// Check if the token is provided
if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Verify the token by checking against the database
    $sql = "SELECT user_id, first_name, second_name, verification_token_expiry FROM users WHERE verification_token = :token";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':token', $token);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Check if the token is expired
        $currentTime = new DateTime();
        $expiryTime = new DateTime($user['verification_token_expiry']);
        if ($currentTime > $expiryTime) {
            $message = "Verification link has expired.";
        } else {
            // Token is valid, verify the account
            $updateSql = "UPDATE users SET verified = 1, verification_token = NULL, verification_token_expiry = NULL WHERE user_id = :userId";
            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->bindParam(':userId', $user['user_id']);
            if ($updateStmt->execute()) {
                $verified = true;
                $message = "<h5 class='card-title'>Welcome, " . htmlspecialchars($user['first_name']) . " " . htmlspecialchars($user['second_name']) . "!</h5>
                            <p class='card-text'>Your account has been verified.</p>";
            } else {
                $message = "Error verifying your account.";
            }
        }
    } else {
        $message = "Invalid verification token.";
    }
} else {
    $message = "No verification token provided.";
}

// HTML Output
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container text-center mt-5">
    <!-- Logo and Title Section -->
    <div class="mb-4">
        <i class="fa fa-key fa-5x"></i>
        <h2 class="mt-2">KeyFlow - Email Verification</h2>
    </div>

    <!-- Bootstrap Card for Verification Status -->
    <div class="card mx-auto" style="max-width: 500px;">
        <div class="card-header">
            <i class="fa fa-key"></i> KeyFlow - Email Verification
        </div>
        <div class="card-body">
            <?php echo $message; ?>
            <a href="index.php" class="btn btn-primary">Login</a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
include 'footer.php'; // Include footer for consistent layout
?>
