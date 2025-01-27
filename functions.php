<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start the session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to the login page if the user is not logged in
    header('Location: index.php');
    exit();
}

// Get the logged-in user's user_id and role from session
$user_id = $_SESSION['user_id']; 
$role = $_SESSION['role']; 

// Display Success Message if exists
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Display Error Message if exists
if (isset($error_message)) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
    echo htmlspecialchars($error_message);
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
}

// Pagination settings
$limit = 10; // Default rows per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Get the page number from the URL, default is 1
$offset = ($page - 1) * $limit; // Calculate the offset for the SQL query

// Initialize $search with a default value
$search = isset($_GET['search']) ? $_GET['search'] : '';

// SQL query to get the total number of credentials for pagination
$countSql = "SELECT COUNT(*) FROM credentials c WHERE c.user_id = :user_id";
if ($search) {
    $countSql .= " AND c.name LIKE :search";
}

$countStmt = $pdo->prepare($countSql);
$countStmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
if ($search) {
    $countStmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
}
$countStmt->execute();
$totalCredentials = $countStmt->fetchColumn();
$totalPages = ceil($totalCredentials / $limit); // Calculate total number of pages




// Fetch credentials data for displaying with pagination and optional search
$sql = "SELECT c.*, u.first_name, u.second_name 
        FROM credentials c 
        JOIN users u ON c.user_id = u.user_id
        ORDER BY name ASC";

if ($search) {
    $sql .= " WHERE c.name LIKE :search";  // Add search filter
}

$sql .= " LIMIT :limit OFFSET :offset";  // Apply pagination

$stmt = $pdo->prepare($sql);
if ($search) {
    $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$credentials = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Functions

// Function to calculate days since last update
function calculateDaysOld($updatedAt) {
    $updatedDate = new DateTime($updatedAt);
    $currentDate = new DateTime();
    $interval = $updatedDate->diff($currentDate);
    return $interval->days; // Returns the number of days since the last update
}

// Flag to check if any credential needs to be updated
$showUpdateCard = false; 

// Loop through credentials to check if any needs updating
foreach ($credentials as $credential) {
    $daysOld = calculateDaysOld($credential['updated_at']);
    if ($daysOld > 90) {
        $showUpdateCard = true;
        break; // Exit the loop as we found at least one credential that needs updating
    }
}


// Add credentials logic
if (isset($_POST['addCredential'])) {
    $name = $_POST['credentialName'];
    $username = $_POST['credentialUsername'];
    $password = $_POST['credentialPassword']; 
    $otp = $_POST['credentialOTP'];
    $url = $_POST['credentialURL'];

    try {
        $stmt = $pdo->prepare("INSERT INTO credentials (user_id, name, username, password, otp, url, created_at) 
                               VALUES (:user_id, :name, :username, :password, :otp, :url, NOW())");

        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->bindParam(':password', $password, PDO::PARAM_STR);
        $stmt->bindParam(':otp', $otp, PDO::PARAM_STR);
        $stmt->bindParam(':url', $url, PDO::PARAM_STR);

        if ($stmt->execute()) {
            // Set success message in session
            $_SESSION['success_message'] = "Credentials for: $name have been added!";
        }
    } catch (PDOException $e) {
        $error_message = "Error: " . $e->getMessage();
    }

    // After the credentials are added, redirect to the dashboard page
    header('Location: dashboard.php');
    exit();
}


// Delete credentials logic
if (isset($_POST['deleteCredential']) && isset($_POST['credential_id'])) {
    $credential_id = $_POST['credential_id'];

    $stmt = $pdo->prepare("SELECT user_id, name FROM credentials WHERE credential_id = :credential_id");
    $stmt->bindValue(':credential_id', $credential_id, PDO::PARAM_INT);
    $stmt->execute();
    $credential = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($credential) {
        if ($credential['user_id'] == $user_id || in_array($role, ['admin', 'manager'])) {
            try {
                $deleteStmt = $pdo->prepare("DELETE FROM credentials WHERE credential_id = :credential_id");
                $deleteStmt->bindValue(':credential_id', $credential_id, PDO::PARAM_INT);
                if ($deleteStmt->execute()) {
                    $_SESSION['success_message'] = "Credentials for {$credential['name']} have been removed.";
                    header('Location: dashboard.php');
                    exit();
                }
            } catch (PDOException $e) {
                $error_message = "Error: " . $e->getMessage();
            }
        } else {
            $error_message = "You are not authorized to delete these credentials.";
        }
    } else {
        $error_message = "Credential not found.";
    }
}


// Edit credentials logic
if (isset($_POST['editCredential'])) {
    // Get the values from the form
    $credential_id = $_POST['credential_id'];
    $name = $_POST['credentialName'];
    $username = $_POST['credentialUsername'];
    $password = $_POST['credentialPassword'];
    $otp = $_POST['credentialOTP'];
    $url = $_POST['credentialURL'];

    // Prepare the SQL statement to update the credentials
    $stmt = $pdo->prepare("UPDATE credentials SET 
        name = :name, 
        username = :username, 
        password = :password, 
        otp = :otp, 
        url = :url 
        WHERE credential_id = :credential_id");

    // Bind the parameters
    $stmt->bindParam(':name', $name, PDO::PARAM_STR);
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
    $stmt->bindParam(':password', $password, PDO::PARAM_STR);
    $stmt->bindParam(':otp', $otp, PDO::PARAM_STR);
    $stmt->bindParam(':url', $url, PDO::PARAM_STR);
    $stmt->bindParam(':credential_id', $credential_id, PDO::PARAM_INT);

    // Debugging: Check if the query is executing
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Credentials for $name have been updated!";
        echo "Update successful"; // Debugging message
    } else {
        echo "Update failed"; // Debugging message
    }

    // After successful update, redirect to refresh the page
    header('Location: dashboard.php');
    exit();
}




// Function to generate a random token
function generateVerificationToken() {
    return bin2hex(random_bytes(16));
}

// Function to send verification email
function sendVerificationEmail($email, $userId) {
    $verifyToken = generateVerificationToken();
    global $pdo;

    // Save the token in the users_access table
    $stmt = $pdo->prepare("UPDATE users_access SET verified = 0 WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $userId]);

    $verifyUrl = "https://keys.aftech.ro/verify?token=$verifyToken&user_id=$userId";
    $subject = "Verify Your Email";
    $message = "Click the link below to verify your email:\n\n$verifyUrl";
    $headers = "From: no-reply@keys.aftech.ro";

    // Send email
    if (mail($email, $subject, $message, $headers)) {
        // Save token to database
        $stmt = $pdo->prepare("INSERT INTO users_access (user_id, verified) VALUES (:user_id, :verified)");
        $stmt->execute([':user_id' => $userId, ':verified' => 0]);
    }
}

// Function to verify user
function verifyUser($token, $userId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users_access WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $userId]);
    $userAccess = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($userAccess) {
        $stmt = $pdo->prepare("UPDATE users_access SET verified = 1 WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $userId]);
        return true;
    }
    return false;
}



// Function to update user fields in the database
function updateUserFields($userId, $fields) {
    global $pdo;

    // Prepare an SQL update query
    $setClauses = [];
    foreach ($fields as $column => $value) {
        // Ensure that only valid columns are updated
        if (in_array($column, ['first_name', 'second_name', 'email', 'username', 'password', 'role', 'status', 'verified'])) {
            $setClauses[] = "$column = :$column";
        }
    }
    $setClause = implode(", ", $setClauses);

    // Build the SQL query
    $sql = "UPDATE users SET $setClause, updated_at = CURRENT_TIMESTAMP WHERE user_id = :user_id";

    // Prepare the statement
    $stmt = $pdo->prepare($sql);

    // Bind the values for the fields
    foreach ($fields as $column => $value) {
        $stmt->bindValue(":$column", $value);
    }

    // Bind the user ID
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);

    // Execute the statement and check if it was successful
    return $stmt->execute();
}



?>