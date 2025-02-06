<?php
session_start();
include 'config.php';
include 'header.php';

// Check if user is admin or manager
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager') {
    header('Location: index.php');
    exit();
}

// Pagination
$limit = 15;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Filters
$filter_user = isset($_GET['filter_user']) ? $_GET['filter_user'] : '';
$filter_date = isset($_GET['filter_date']) ? $_GET['filter_date'] : '';
$filter_login_status = isset($_GET['filter_login_status']) ? $_GET['filter_login_status'] : '';
$filter_password_history = isset($_GET['filter_password_history']) ? $_GET['filter_password_history'] : '';

// Build the query
$query = "SELECT l.*, u1.first_name AS user_first_name, u1.second_name AS user_second_name, 
                 u2.first_name AS target_user_first_name, u2.second_name AS target_user_second_name,
                 d.department_name, c.name AS credential_name
          FROM logs l
          LEFT JOIN users u1 ON l.user_id = u1.user_id
          LEFT JOIN users u2 ON l.target_user_id = u2.user_id
          LEFT JOIN departments d ON l.target_department_id = d.department_id
          LEFT JOIN credentials c ON l.target_credential_id = c.credential_id
          WHERE 1=1";

if ($filter_user) {
    $query .= " AND (u1.first_name LIKE :filter_user OR u1.second_name LIKE :filter_user)";
}
if ($filter_date) {
    $query .= " AND DATE(l.created_at) = :filter_date";
}
if ($filter_login_status) {
    $query .= " AND l.action = :filter_login_status";
}
if ($filter_password_history) {
    $query .= " AND l.action_type = 'edit' AND l.target_credential_id IS NOT NULL";
}

$query .= " ORDER BY l.created_at DESC LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($query);

if ($filter_user) {
    $stmt->bindValue(':filter_user', "%$filter_user%");
}
if ($filter_date) {
    $stmt->bindValue(':filter_date', $filter_date);
}
if ($filter_login_status) {
    $stmt->bindValue(':filter_login_status', $filter_login_status);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count total logs for pagination
$count_query = "SELECT COUNT(*) FROM logs l WHERE 1=1";
if ($filter_user) {
    $count_query .= " AND (u1.first_name LIKE :filter_user OR u1.second_name LIKE :filter_user)";
}
if ($filter_date) {
    $count_query .= " AND DATE(l.created_at) = :filter_date";
}
if ($filter_login_status) {
    $count_query .= " AND l.action = :filter_login_status";
}
if ($filter_password_history) {
    $count_query .= " AND l.action_type = 'edit' AND l.target_credential_id IS NOT NULL";
}

$count_stmt = $pdo->prepare($count_query);

if ($filter_user) {
    $count_stmt->bindValue(':filter_user', "%$filter_user%");
}
if ($filter_date) {
    $count_stmt->bindValue(':filter_date', $filter_date);
}
if ($filter_login_status) {
    $count_stmt->bindValue(':filter_login_status', $filter_login_status);
}

$count_stmt->execute();
$total_logs = $count_stmt->fetchColumn();
$total_pages = ceil($total_logs / $limit);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h1>Logs</h1>
    <form method="GET" action="logs.php" class="mb-4">
        <div class="form-row">
            <div class="form-group col-md-3">
                <input type="text" name="filter_user" class="form-control" placeholder="By User" value="<?= $filter_user ?>">
            </div>
            <div class="form-group col-md-3">
                <input type="date" name="filter_date" class="form-control" value="<?= $filter_date ?>">
            </div>
            <div class="form-group col-md-3">
                <select name="filter_login_status" class="form-control">
                    <option value="">Login Status</option>
                    <option value="success" <?= $filter_login_status === 'success' ? 'selected' : '' ?>>Success</option>
                    <option value="failed" <?= $filter_login_status === 'failed' ? 'selected' : '' ?>>Failed</option>
                </select>
            </div>
            <div class="form-group col-md-3">
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
        </div>
    </form>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Action</th>
                <th>Details</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td>
                        <?php if ($log['action_type'] === 'create'): ?>
                            <span class="badge badge-success">Added</span>
                        <?php elseif ($log['action_type'] === 'edit'): ?>
                            <span class="badge badge-warning">Edited</span>
                        <?php elseif ($log['action_type'] === 'delete'): ?>
                            <span class="badge badge-danger">Deleted</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= $log['details'] ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <nav>
        <ul class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>&filter_user=<?= $filter_user ?>&filter_date=<?= $filter_date ?>&filter_login_status=<?= $filter_login_status ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
</div>
</body>
</html>

<?php include 'footer.php'; ?>