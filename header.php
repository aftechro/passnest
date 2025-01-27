<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PassNest - Password Manager</title>
<link rel="stylesheet" href="styles.css">
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

 
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark" style="border-bottom: 2px solid #0d6efd;">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="#">
            <i class="fa fa-key fa-2x me-2"></i><span style="font-size: 1.5rem; font-weight: bold;">PassNest</span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <?php 
                $current_page = basename($_SERVER['PHP_SELF']); 
                ?>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
                        <i class="fa fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                </li>
                <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'manager'])): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'admin.php' ? 'active' : '' ?>" href="admin.php">
                            <i class="fa fa-user-shield me-2"></i> Admin
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'logs.php' ? 'active' : '' ?>" href="logs.php">
                            <i class="fa fa-list me-2"></i> Logs
                        </a>
                    </li>                
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'profile.php' ? 'active' : '' ?>" href="profile.php">
                        <i class="fa fa-user me-2"></i> Profile
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'logout.php' ? 'active' : '' ?>" href="logout.php">
                        <i class="fa fa-sign-out-alt me-2"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
