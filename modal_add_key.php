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
                                <input type="text" name="credentialOTP" class="form-control" placeholder="Name/Mobile number">
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
