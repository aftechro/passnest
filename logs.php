<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);



include('config.php');  // Include the database configuration to connect to the database

include "functions.php";

include "header.php";


// Pagination settings
$limit = 15; // Default rows per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? $_GET['search'] : '';

// SQL query for counting the logs
$countSql = "SELECT COUNT(*) FROM logs l JOIN users u ON l.user_id = u.user_id WHERE u.first_name LIKE :search OR u.second_name LIKE :search";
$countStmt = $pdo->prepare($countSql);
$countStmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
$countStmt->execute();
$totalLogs = $countStmt->fetchColumn();
$totalPages = ceil($totalLogs / $limit);

// SQL query for fetching the logs
$sql = "SELECT l.*, u.first_name, u.second_name FROM logs l JOIN users u ON l.user_id = u.user_id WHERE u.first_name LIKE :search OR u.second_name LIKE :search LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Logs</h2>

    <form method="GET" class="form-inline mb-3">
        <input type="text" name="search" class="form-control" placeholder="Search by name" value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Action</th>
                <th>User</th>
                <th>Credential Name</th>
                <th>Old Value</th>
                <th>New Value</th>
                <th>Time</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= htmlspecialchars($log['action']) ?></td>
                    <td><?= htmlspecialchars($log['first_name']) ?> <?= htmlspecialchars($log['second_name']) ?></td>
                    <td><?= htmlspecialchars($log['credential_id']) ?></td>
                    <td><?= htmlspecialchars($log['old_value']) ?></td>
                    <td><?= htmlspecialchars($log['new_value']) ?></td>
                    <td><?= htmlspecialchars($log['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <nav>
        <ul class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $i ?>&search=<?= htmlspecialchars($search) ?>"><?= $i ?></a></li>
            <?php endfor; ?>
        </ul>
    </nav>
</div>
</body>

            
 <?php include_once ".footer.php"; ?>            
            
<!-- Include Bootstrap JS -->
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>
   