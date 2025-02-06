<!-- Modal for Adding Credentials -->
<div class="modal fade" id="addCredentialModal" tabindex="-1" aria-labelledby="addCredentialModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #333; color: white;">
                <h5 class="modal-title" id="addCredentialModalLabel">Add Credentials</h5>
                <button type="button" class="btn-closebtn btn-dark" data-bs-dismiss="modal" aria-label="Close" style="color: #fff;"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="dashboard.php" id="addCredentialForm">
                    <div class="row">
                        <div class="col-md-6">
                            <label for="credentialName" class="form-label">Name</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" name="credentialName" class="form-control" placeholder="Office365" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="credentialUsername" class="form-label">Username</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user-circle"></i></span>
                                <input type="text" name="credentialUsername" class="form-control" placeholder="john@doe.com / jdoe" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <label for="credentialPassword" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-key"></i></span>
                                <input type="text" name="credentialPassword" class="form-control" placeholder="MySupperDupper!23" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="credentialOTP" class="form-label">OTP/2FA Owner</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-shield-alt"></i></span>
                                <input type="text" name="credentialOTP" id="credentialOTP" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <label for="credentialURL" class="form-label">URL</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-link"></i></span>
                                <input type="url" name="credentialURL" class="form-control" placeholder="https://johndoe.com">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <label for="department_id" class="form-label">Department</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-building"></i></span>
                                <select name="department_id" class="form-control" required>
                                    <?php
                                    // Fetch departments the user belongs to
                                    $stmt = $pdo->prepare("SELECT d.department_id, d.department_name 
                                                           FROM departments d 
                                                           JOIN department_members dm ON d.department_id = dm.department_id 
                                                           WHERE dm.user_id = :user_id");
                                    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
                                    $stmt->execute();
                                    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                    foreach ($departments as $department) {
                                        echo "<option value='{$department['department_id']}'>{$department['department_name']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <button type="submit" name="addCredential" class="btn btn-primary mt-3">Add</button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    /* Custom styles for placeholder text */
    input::placeholder {
        color: #ececec;
        font-size: 0.875rem; /* Smaller font size */
    }
</style>


<!-- Modal for Editing Credentials -->
<div class="modal fade" id="editCredentialModal" tabindex="-1" aria-labelledby="editCredentialModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #333; color: white;">
                <h5 class="modal-title" id="editCredentialModalLabel">Edit Credentials</h5>
                <button type="button" class="btn-closebtn btn-dark" data-bs-dismiss="modal" aria-label="Close" style="color: #fff;"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="dashboard.php" id="editCredentialForm">
                    <!-- Hidden Field for Credential ID -->
                    <input type="hidden" name="credential_id" id="editCredentialId">

                    <div class="row">
                        <div class="col-md-6">
                            <label for="editCredentialName" class="form-label">Name</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" name="credentialName" id="editCredentialName" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="editCredentialUsername" class="form-label">Username</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user-circle"></i></span>
                                <input type="text" name="credentialUsername" id="editCredentialUsername" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <label for="editCredentialPassword" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-key"></i></span>
                                <input type="text" name="credentialPassword" id="editCredentialPassword" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="editCredentialOTP" class="form-label">OTP</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-shield-alt"></i></span>
                                <input type="text" name="credentialOTP" id="editCredentialOTP" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <label for="editCredentialURL" class="form-label">URL</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-link"></i></span>
                                <input type="url" name="credentialURL" id="editCredentialURL" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <label for="editDepartmentId" class="form-label">Department</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-building"></i></span>
                                <select name="department_id" id="editDepartmentId" class="form-control" required>
                                    <?php
                                    // Fetch departments the user belongs to
                                    $stmt = $pdo->prepare("SELECT d.department_id, d.department_name 
                                                           FROM departments d 
                                                           JOIN department_members dm ON d.department_id = dm.department_id 
                                                           WHERE dm.user_id = :user_id");
                                    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
                                    $stmt->execute();
                                    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                    foreach ($departments as $department) {
                                        echo "<option value='{$department['department_id']}'>{$department['department_name']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <button type="submit" name="editCredential" class="btn btn-primary mt-3">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>
                                    
                                    
<div class="modal fade" id="deleteCredentialModal" tabindex="-1" aria-labelledby="deleteCredentialModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteCredentialModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete the credentials for <strong><span id="credentialName"></span></strong>?
            </div>
            <div class="modal-footer">
                <form id="deleteCredentialForm" method="POST" action="dashboard.php" onsubmit="return deleteCredential(event)">
                    <input type="hidden" name="credential_id" id="credentialId">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="deleteCredential" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>
                                    