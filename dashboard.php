<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);



include('config.php');  // Include the database configuration to connect to the database

include "functions.php";

include "header.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">

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
        <i class="bi bi-exclamation-circle"></i> <strong>Password Rotation </strong>
        
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
                        <span><?php echo $credential['updated_at'] !== null ? htmlspecialchars((new DateTime($credential['updated_at']))->format('d.m.Y - H:i:s')) : 'N/A'; ?></span>
 - 
                        <span class="text-muted"><?php echo $daysOld; ?> days old</span>
                        <span class="badge bg-warning text-dark">
                            <i class="bi bi-exclamation-triangle"></i> Needs Update
                        </span>
                    </div>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editCredentialModal" 
                            data-id="<?php echo $credential['credential_id']; ?>"
                            data-name="<?php echo htmlspecialchars($credential['name']); ?>"
                            data-username="<?php echo htmlspecialchars($credential['username']); ?>"
                            data-password="<?php echo htmlspecialchars($credential['password']); ?>"
                            data-otp="<?php echo htmlspecialchars($credential['otp']); ?>"
                            data-url="<?php echo htmlspecialchars($credential['url']); ?>"
                            onclick="populateEditModal(this)">
                        <i class="bi bi-pencil-square"></i> Update
                    </button>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <div class="card-footer text-danger">
        <i class="bi bi-shield-lock"></i> <strong>Recommendation:</strong> If your passwords have not been updated in over 90 days, consider changing them for security reasons.
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
            <button class="btn btn-outline-success me-2" id="exportCsv" onclick="exportToCSV()">
                <i class="bi bi-file-earmark-excel"></i> Export CSV
            </button>
            <button class="btn btn-primary" id="addCredentials" data-bs-toggle="modal" data-bs-target="#addCredentialModal">
                <i class="bi bi-plus-circle"></i> Add Credentials
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle table-modern table-bordered">
            <thead class="table-dark">
                <tr>
                    <th class="text-start">Name</th>
                    <th>URL</th>
                    <th>Username</th>
                    <th>Password</th>
                    <th>OTP / 2FA Owner</th>
                    <th>Actions</th>
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
        <i class="fa fa-clipboard"></i>
    </button>
</td>
                        <td>
                            <?php echo htmlspecialchars($credential['username']); ?>
                            <button class="btn btn-sm btn-outline-secondary btn-copy" onclick="copyToClipboard(this, '<?php echo htmlspecialchars($credential['username']); ?>')">
                                <i class="fa fa-clipboard"></i>
                            </button>
                        </td>
                        <td>
                            <div class="d-flex align-items-center justify-content-center">
                                <input type="password" value="<?php echo htmlspecialchars($credential['password']); ?>" class="form-control w-auto" readonly>
                                <button class="btn btn-sm btn-outline-secondary ms-2" onclick="togglePassword(this)">
                                    <i class="fa fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary btn-copy ms-2" onclick="copyToClipboard(this, '<?php echo htmlspecialchars($credential['password']); ?>')">
                                    <i class="fa fa-clipboard"></i>
                                </button>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($credential['otp']); ?></td>
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="fa fa-ellipsis-h"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#editCredentialModal" 
                                           data-id="<?php echo $credential['credential_id']; ?>"
                                           data-name="<?php echo htmlspecialchars($credential['name']); ?>"
                                           data-username="<?php echo htmlspecialchars($credential['username']); ?>"
                                           data-password="<?php echo htmlspecialchars($credential['password']); ?>"
                                           data-otp="<?php echo htmlspecialchars($credential['otp']); ?>"
                                           data-url="<?php echo htmlspecialchars($credential['url']); ?>"
                                           onclick="populateEditModal(this)">
                                           <i class="fa fa-edit"></i> Edit
                                        </a>
                                    </li>
                                    <?php if ($credential['user_id'] == $user_id || in_array($role, ['admin', 'manager'])): ?>
                                        <li>
                                            <a class="dropdown-item text-danger" href="#" data-bs-toggle="modal" data-bs-target="#deleteCredentialModal" 
                                               onclick="setDeleteCredentialInfo(<?php echo $credential['credential_id']; ?>, '<?php echo htmlspecialchars($credential['name']); ?>')">
                                               <i class="fa fa-trash"></i> Delete
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <small class="dropdown-item">
                                            <i class="fa fa-user"></i> By: <span class="text-muted"> <?php echo htmlspecialchars($credential['first_name']) . ' ' . htmlspecialchars($credential['second_name']); ?></span><br>
                                            <i class="fa fa-calendar"></i> 
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


    <?php include_once ".footer.php"; ?>
    <?php include_once "modal_add_key.php"; include_once "modal_delete_key.php"; include_once "modal_edit_key.php"; ?>
</body>
</html>
