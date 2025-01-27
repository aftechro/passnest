<?php
$host = 'localhost'; // Your database host
$dbname = 'passnest'; // Your database name
$username = 'passnest'; // Your database username
$password = ''; // Your database password

try {
    // Create a PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Handle connection errors
    echo "Connection failed: " . $e->getMessage();
    exit();
}

$base_url = 'https://passnest.com';
?>
