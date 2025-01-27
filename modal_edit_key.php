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
                    <button type="submit" name="editCredential" class="btn btn-primary mt-3">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>
