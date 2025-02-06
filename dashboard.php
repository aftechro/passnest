<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('config.php');  // Include the database configuration to connect to the database
include "functions.php";
include "header.php";

// Fetch departments the user belongs to
$userDepartments = [];
if ($role !== 'admin' && $role !== 'manager') {
    $stmt = $pdo->prepare("
        SELECT d.department_name 
        FROM departments d
        JOIN department_members dm ON d.department_id = dm.department_id
        WHERE dm.user_id = :user_id
    ");
    $stmt->execute(['user_id' => $user_id]);
    $userDepartments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Admins and managers can see all departments
    $stmt = $pdo->prepare("SELECT department_name FROM departments");
    $stmt->execute();
    $userDepartments = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch credentials based on the selected department filter
$selectedDepartment = $_GET['department'] ?? 'All Passwords';
$credentials = [];

if ($selectedDepartment === 'All Passwords') {
    if ($role === 'admin' || $role === 'manager') {
        // Admins and managers can see all credentials
        $stmt = $pdo->prepare("
            SELECT c.*, d.department_name, u.first_name, u.second_name 
            FROM credentials c
            LEFT JOIN departments d ON c.department_id = d.department_id
            LEFT JOIN users u ON c.user_id = u.user_id
            ORDER BY c.created_at DESC
        ");
        $stmt->execute();
    } else {
        // Regular users can only see credentials for their departments
        $stmt = $pdo->prepare("
            SELECT c.*, d.department_name, u.first_name, u.second_name 
            FROM credentials c
            LEFT JOIN departments d ON c.department_id = d.department_id
            LEFT JOIN users u ON c.user_id = u.user_id
            WHERE c.department_id IN (
                SELECT dm.department_id 
                FROM department_members dm 
                WHERE dm.user_id = :user_id
            )
            ORDER BY c.created_at DESC
        ");
        $stmt->execute(['user_id' => $user_id]);
    }
} elseif ($selectedDepartment === 'My Private Vault') {
    // Show credentials where department_id is NULL (private vault)
    $stmt = $pdo->prepare("
        SELECT c.*, d.department_name, u.first_name, u.second_name 
        FROM credentials c
        LEFT JOIN departments d ON c.department_id = d.department_id
        LEFT JOIN users u ON c.user_id = u.user_id
        WHERE c.department_id IS NULL AND c.user_id = :user_id
        ORDER BY c.created_at DESC
    ");
    $stmt->execute(['user_id' => $user_id]);
} else {
    // Show credentials for a specific department
    $stmt = $pdo->prepare("
        SELECT c.*, d.department_name, u.first_name, u.second_name 
        FROM credentials c
        LEFT JOIN departments d ON c.department_id = d.department_id
        LEFT JOIN users u ON c.user_id = u.user_id
        WHERE d.department_name = :department_name
        ORDER BY c.created_at DESC
    ");
    $stmt->execute(['department_name' => $selectedDepartment]);
}

$credentials = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch counts for each department and private vault
$counts = [];
$stmt = $pdo->prepare("
    SELECT d.department_name, COUNT(c.credential_id) as count 
    FROM credentials c
    LEFT JOIN departments d ON c.department_id = d.department_id
    GROUP BY d.department_name
");
$stmt->execute();
$departmentCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($departmentCounts as $dc) {
    $counts[$dc['department_name']] = $dc['count'];
}

$stmt = $pdo->prepare("
    SELECT COUNT(c.credential_id) as count 
    FROM credentials c
    WHERE c.department_id IS NULL
");
$stmt->execute();
$privateVaultCount = $stmt->fetchColumn();
$counts['My Private Vault'] = $privateVaultCount;

$stmt = $pdo->prepare("SELECT COUNT(c.credential_id) as count FROM credentials c");
$stmt->execute();
$allPasswordsCount = $stmt->fetchColumn();
$counts['All Passwords'] = $allPasswordsCount;

$currentDepartment = $_GET['department'] ?? 'All Passwords';


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
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


    .dropdown-item:hover {
        background-color: #f8f9fa;
    }
    .dropdown-item i {
        margin-right: 10px;
    }
    .dropdown-item .fa-key {
        color: #ffc107; /* Yellow for keys */
    }
    .dropdown-item .fa-lock {
        color: #dc3545; /* Red for private vault */
    }
    .dropdown-item .fa-folder-open {
        color: #007bff; /* Blue for departments */
    }

    </style>
</head>
<body>

<div class="container-boxed mt-4">
    <h3>Dashboard</h3>
    <hr>
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($success_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php elseif (isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($error_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($showUpdateCard): ?>
    <div class="card mb-4 border-warning">
        <div class="card-header bg-warning text-dark">
            <i class="fas fa-exclamation-circle"></i> <strong>Password Rotation </strong>
        </div>
        <div class="card-body">
            <?php foreach ($credentials as $credential): ?>
                <?php 
                    $daysOld = calculateDaysOld($credential['updated_at']); // Correct column name used here
                    $isOld = $daysOld > 90; // Only show credentials older than 90 days
                ?>
                <?php if ($isOld): ?>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <strong><?php echo htmlspecialchars($credential['name']); ?></strong> - 
                            <span><?php echo $credential['updated_at'] !== null ? htmlspecialchars((new DateTime($credential['updated_at']))->format('d.m.Y - H:i:s')) : 'N/A'; ?></span> - 
                            <span class="text-muted"><?php echo $daysOld; ?> days old</span>
                            <span class="badge bg-warning text-dark">
                                <i class="fas fa-exclamation-triangle"></i> Needs Update
                            </span>
                        </div>
<button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editCredentialModal" 
        data-id="<?php echo $credential['credential_id']; ?>"
        data-name="<?php echo htmlspecialchars($credential['name']); ?>"
        data-username="<?php echo htmlspecialchars($credential['username']); ?>"
        data-password="<?php echo htmlspecialchars(decryptPassword($credential['password'])); ?>"
        data-otp="<?php echo htmlspecialchars($credential['otp']); ?>"
        data-url="<?php echo htmlspecialchars($credential['url']); ?>"
        data-department-id="<?php echo htmlspecialchars($credential['department_id']); ?>"
        onclick="populateEditModal(this)">
    <i class="fas fa-pencil-alt"></i> Update
</button>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <div class="card-footer text-danger">
            <i class="fas fa-shield-alt"></i> <strong>Recommendation:</strong> If your passwords have not been updated in over 90 days, consider changing them for security reasons.
        </div>
    </div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-12 col-md-6 d-flex align-items-center mb-3 mb-md-0">
            <span class="badge bg-secondary py-2 px-3 me-2">
                <?php echo count($credentials); ?>
            </span>
            <form method="GET" action="dashboard.php" class="d-flex align-items-center w-100">
                <input type="text" name="search" class="form-control me-2" placeholder="Search password..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary">Go</button>
            </form>
        </div>
        <div class="col-12 col-md-6 d-flex justify-content-end">
            <div class="dropdown me-2">
                <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="departmentDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                   <i class="fas fa-folder-open"></i> <?php echo htmlspecialchars($selectedDepartment); ?>
                </button>
 <ul class="dropdown-menu" aria-labelledby="departmentDropdown">
    <li><a class="dropdown-item" href="dashboard.php?department=All Passwords"><i class="fas fa-key"></i> All Passwords (<?php echo $counts['All Passwords']; ?>)</a></li>
    <li><a class="dropdown-item" href="dashboard.php?department=My Private Vault"><i class="fas fa-lock"></i> My Private Vault (<?php echo $counts['My Private Vault']; ?>)</a></li>
    <?php foreach ($userDepartments as $department): ?>
        <li><a class="dropdown-item" href="dashboard.php?department=<?php echo htmlspecialchars($department['department_name']); ?>"><i class="fas fa-folder-open"></i> <?php echo htmlspecialchars($department['department_name']); ?> (<?php echo $counts[$department['department_name']] ?? 0; ?>)</a></li>
    <?php endforeach; ?>
</ul>
            </div>
            <button class="btn btn-outline-success me-2" id="exportCsv" onclick="exportToCSV()">
                <i class="fas fa-file-export"></i> Export CSV
            </button>
            <button class="btn btn-primary" id="addCredentials" data-bs-toggle="modal" data-bs-target="#addCredentialModal">
                <i class="fas fa-plus-circle"></i> Add Credentials
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle table-modern table-bordered">
            <thead class="table-dark">
                <tr>
                    <th class="text-start"><i class="fas fa-tag"></i> Name</th>
                    <th><i class="fas fa-link"></i> URL</th>
                    <th><i class="fas fa-user"></i> Username</th>
                    <th><i class="fas fa-key"></i> Password</th>
                    <th><i class="fas fa-shield-alt"></i> OTP / 2FA </th>
                    <?php if (in_array($role, ['admin', 'manager', 'manager'])): ?>
                    <th><i class="fas fa-folder"></i> Vault</th> 
                    <?php endif; ?>
                    <th><i class="fas fa-cogs"></i> Actions</th>
                </tr>
            </thead>
            <tbody id="credentialTable">
                <?php foreach ($credentials as $credential): ?>
                    <tr>
                        <td class="text-start"><strong><?php echo htmlspecialchars($credential['name']); ?></strong></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" 
                                    data-bs-toggle="tooltip" 
                                    data-bs-placement="top" 
                                    title="<?php echo htmlspecialchars($credential['url']); ?>"
                                    onclick="window.open('<?php echo htmlspecialchars($credential['url']); ?>', '_blank')">
                                <i class="fas fa-external-link-alt"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary btn-copy" 
                                    onclick="copyToClipboard(this, '<?php echo htmlspecialchars($credential['url']); ?>')">
                                <i class="fas fa-clipboard"></i>
                            </button>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($credential['username']); ?>
                            <button class="btn btn-sm btn-outline-secondary btn-copy" onclick="copyToClipboard(this, '<?php echo htmlspecialchars($credential['username']); ?>')">
                                <i class="fas fa-clipboard"></i>
                            </button>
                        </td>
                        <td>
                            <div class="d-flex align-items-center justify-content-center">
                                <input type="password" value="<?php echo htmlspecialchars(decryptPassword($credential['password'])); ?>" class="form-control w-auto" readonly>
                                <button class="btn btn-sm btn-outline-secondary ms-2" onclick="togglePassword(this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary btn-copy ms-2" onclick="copyToClipboard(this, '<?php echo htmlspecialchars(decryptPassword($credential['password'])); ?>')">
                                    <i class="fas fa-clipboard"></i>
                                </button>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($credential['otp']); ?></td>
                        <?php if (in_array($role, ['admin', 'manager', 'manager'])): ?>
                            <td><?php echo htmlspecialchars($credential['department_name'] ?? 'Private Vault'); ?></td>
                        <?php endif; ?>
                        <td style="text-align: right;">
                            <div class="dropdown">
                                <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
<li>
    <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#editCredentialModal" 
       data-id="<?php echo $credential['credential_id']; ?>"
       data-name="<?php echo htmlspecialchars($credential['name']); ?>"
       data-username="<?php echo htmlspecialchars($credential['username']); ?>"
       data-password="<?php echo htmlspecialchars(decryptPassword($credential['password'])); ?>"
       data-otp="<?php echo htmlspecialchars($credential['otp']); ?>"
       data-url="<?php echo htmlspecialchars($credential['url']); ?>"
       data-department-id="<?php echo htmlspecialchars($credential['department_id']); ?>"
       onclick="populateEditModal(this)">
       <i class="fas fa-edit"></i> Edit
    </a>
</li>
                                    <?php if ($credential['user_id'] == $user_id || in_array($role, ['admin', 'manager'])): ?>
                                        <li>
                                            <a class="dropdown-item text-danger" href="#" data-bs-toggle="modal" data-bs-target="#deleteCredentialModal" 
                                               onclick="setDeleteCredentialInfo(<?php echo $credential['credential_id']; ?>, '<?php echo htmlspecialchars($credential['name']); ?>')">
                                               <i class="fas fa-trash"></i> Delete
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <small class="dropdown-item">
                                            <i class="fas fa-user"></i> By: <span class="text-muted"> <?php echo htmlspecialchars($credential['first_name']) . ' ' . htmlspecialchars($credential['second_name']); ?></span><br>
                                            <i class="fas fa-calendar"></i> 
                                            <span class="text-muted"> <?php echo $credential['created_at'] !== null ?  htmlspecialchars((new DateTime($credential['created_at']))->format('d.m.Y - H:i:s')) :  'N/A'; ?> </span>
                                        </small>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <nav class="d-flex justify-content-end">
            <ul class="pagination">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="dashboard.php?page=<?php echo $page - 1; ?>&search=<?php echo htmlspecialchars($search); ?>">
                            &laquo; Previous
                        </a>
                    </li>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="dashboard.php?page=<?php echo $i; ?>&search=<?php echo htmlspecialchars($search); ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="dashboard.php?page=<?php echo $page + 1; ?>&search=<?php echo htmlspecialchars($search); ?>">
                            Next &raquo;
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</div>

<?php include_once ".modal_credentials.php"; ?>
<?php include_once ".footer.php"; ?>


<script>
function exportToCSV() {
    const role = "<?php echo $role; ?>"; // Get the user's role from PHP
    const userId = "<?php echo $user_id; ?>"; // Get the user's ID from PHP
    const selectedDepartment = "<?php echo $selectedDepartment; ?>"; // Get the selected department from PHP

    // Check if the user is a staff member and not selecting "My Private Vault"
    if (role === 'staff' && selectedDepartment !== 'My Private Vault') {
        // Show a modal or alert to inform the user to select "My Private Vault" for export
        alert('Please select "My Private Vault" to export your private credentials.');
        return;
    }

    // Prepare the data for export
    let csvContent = "data:text/csv;charset=utf-8,";

    // Add the table headers as the first row in the CSV
    const headers = ["Name", "URL", "Username", "Password", "OTP / 2FA Owner", "Vault"];
    csvContent += headers.join(",") + "\n";

    // Fetch the credentials based on the user's role
    const credentials = <?php echo json_encode($credentials); ?>; // Get the credentials from PHP

    credentials.forEach(credential => {
        // For admin and manager, export all credentials except private vaults of other users
        if (role === 'admin' || role === 'manager') {
            if (credential.department_id !== null || credential.user_id == userId) {
                const row = [
                    credential.name,
                    credential.url,
                    credential.username,
                    credential.password, // Unmasked password
                    credential.otp,
                    credential.department_name || 'Private Vault'
                ];
                csvContent += row.join(",") + "\n";
            }
        }
        // For staff, export only their private vault
        else if (role === 'staff' && credential.department_id === null && credential.user_id == userId) {
            const row = [
                credential.name,
                credential.url,
                credential.username,
                credential.password, // Unmasked password
                credential.otp,
                'Private Vault'
            ];
            csvContent += row.join(",") + "\n";
        }
    });

    // Create a link element to trigger the download
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "credentials_export.csv");
    document.body.appendChild(link);

    // Trigger the download
    link.click();

    // Clean up
    document.body.removeChild(link);
}
</script>

</body>
</html>
