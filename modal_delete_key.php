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
