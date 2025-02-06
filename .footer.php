<div class="footer-dark">
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-sm-6 col-md-3 item">
                    <h3>Services</h3>
                    <ul>
                        <li><a href="#">Web design</a></li>
                        <li><a href="#">Development</a></li>
                        <li><a href="#">Hosting</a></li>
                    </ul>


                </div>

              
                <div class="col-md-9 item text">
                    <h3><i class="fa fa-key"></i> PassNest</h3>
                    <p>PassNest is the ultimate password manager designed to replace outdated and insecure methods of sharing passwords, such as Excel files. With PassNest, your team can securely store, manage, and access passwords anytime, anywhere. Simplify password sharing and enhance security with effortless password retrieval and verification, ensuring your company’s sensitive data stays safe and organized.</p>
                </div>
            </div>


<!-- p class="copyright text-center">
    <i class="fa fa-key"></i> PassNest &copy; 2025 & Beyond! All Rights Reserved. Made with <span style="color: red;">&#10084;</span> by Andrei Fechete
</p -->
<p class="copyright text-center">
    <i class="fa fa-key"></i> PassNest &copy; 2025 & Beyond! All Rights Reserved. <!--- Made with <span style="color: red;">&#10084;</span--> By Mnezo Piszti
</p>

        </div>
    </footer>
</div>




    
    <!-- Go to Top Button -->
<button id="goToTop" onclick="window.scrollTo({ top: 0, behavior: 'smooth' });">
    <i class="fa fa-arrow-up"></i>
</button>


<script>
    // Show "Go to Top" button when the user scrolls down 200px
    window.onscroll = function() {
        const goToTopButton = document.getElementById('goToTop');
        if (document.body.scrollTop > 200 || document.documentElement.scrollTop > 200) {
            goToTopButton.style.display = 'block';
        } else {
            goToTopButton.style.display = 'none';
        }
    };
</script>

<style>
.copyright {
  font-size: 16px;
  color: #f8f9fa; /* Light color for dark backgrounds */
  display: flex;
  align-items: center;
  gap: 8px; /* Spacing between icon and text */
}

.copyright .fa {
  font-size: 18px; /* Adjust icon size */
  color: #ffd700; /* Gold color for the key */
}


/* Go to Top button styles */
#goToTop {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background-color: #333;
    color: white;
    border: none;
    padding: 12px 16px; /* Adjust padding for a square shape */
    display: none; /* Initially hidden */
    font-size: 16px;
    cursor: pointer;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
}

#goToTop i {
    font-size: 20px;
}



    .footer-dark {
  padding:50px 0;
  color:#f0f9ff;
  background-color:#282d32;
}

.footer-dark h3 {
  margin-top:0;
  margin-bottom:12px;
  font-weight:bold;
  font-size:16px;
}

.footer-dark ul {
  padding:0;
  list-style:none;
  line-height:1.6;
  font-size:14px;
  margin-bottom:0;
}

.footer-dark ul a {
  color:inherit;
  text-decoration:none;
  opacity:0.6;
}

.footer-dark ul a:hover {
  opacity:0.8;
}

@media (max-width:767px) {
  .footer-dark .item:not(.social) {
    text-align:center;
    padding-bottom:20px;
  }
}

.footer-dark .item.text {
  margin-bottom:36px;
}

@media (max-width:767px) {
  .footer-dark .item.text {
    margin-bottom:0;
  }
}

.footer-dark .item.text p {
  opacity:0.6;
  margin-bottom:0;
}

.footer-dark .item.social {
  text-align:center;
}

@media (max-width:991px) {
  .footer-dark .item.social {
    text-align:center;
    margin-top:20px;
  }
}

.footer-dark .item.social > a {
  font-size:20px;
  width:36px;
  height:36px;
  line-height:36px;
  display:inline-block;
  text-align:center;
  border-radius:50%;
  box-shadow:0 0 0 1px rgba(255,255,255,0.4);
  margin:0 8px;
  color:#fff;
  opacity:0.75;
}

.footer-dark .item.social > a:hover {
  opacity:0.9;
}

.footer-dark .copyright {
  text-align:center;
  padding-top:24px;
  opacity:0.3;
  font-size:13px;
  margin-bottom:0;
}


</style>

<script>
function togglePassword(button) {
    const input = button.previousElementSibling;
    if (input.type === "password") {
        input.type = "text";
    } else {
        input.type = "password";
    }
}

function copyToClipboard(button, text) {
    navigator.clipboard.writeText(text).then(() => {
        button.classList.add('copied');
        setTimeout(() => button.classList.remove('copied'), 2000);
    });
}

// Delete credentials
function setDeleteCredentialInfo(credentialId, credentialName) {
    // Set the credential ID in the hidden input field
    document.getElementById('credentialId').value = credentialId;
    
    // Set the credential name in the modal body
    document.getElementById('credentialName').textContent = credentialName;
}

// Edit credentials
// Edit credentials
function populateEditModal(button) {
    // Get the data attributes from the clicked button
    const credentialId = button.getAttribute('data-id');
    const name = button.getAttribute('data-name');
    const username = button.getAttribute('data-username');
    const password = button.getAttribute('data-password');
    const otp = button.getAttribute('data-otp');
    const url = button.getAttribute('data-url');
    const departmentId = button.getAttribute('data-department-id'); // Get the department ID

    // Populate the modal fields with the data
    document.getElementById('editCredentialId').value = credentialId;
    document.getElementById('editCredentialName').value = name;
    document.getElementById('editCredentialUsername').value = username;
    document.getElementById('editCredentialPassword').value = password;
    document.getElementById('editCredentialOTP').value = otp;
    document.getElementById('editCredentialURL').value = url;

    // Set the selected department in the dropdown
    const departmentDropdown = document.getElementById('editDepartmentId');
    if (departmentDropdown) {
        departmentDropdown.value = departmentId; // Pre-select the department
    }
}

// Initialize tooltips on page load
document.addEventListener('DOMContentLoaded', function () {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(function (tooltipTriggerEl) {
        new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Export to CSV
function exportToCSV() {
    const table = document.getElementById('credentialTable'); // Get the table body
    let csvContent = "data:text/csv;charset=utf-8,";

    // Add headers
    const headers = ["Name", "URL", "Username", "Password", "OTP", "Department", "Added By", "Created On"];
    csvContent += headers.join(",") + "\n";

    // Loop through table rows to extract data
    for (let row of table.rows) {
        const name = row.querySelector("td:nth-child(1)")?.innerText.trim() || "";
        const urlButton = row.querySelector("td:nth-child(2) button.btn-outline-primary");
        const url = urlButton ? urlButton.getAttribute('onclick').match(/window\.open\('([^']+)'/)[1] : "";

        const username = row.querySelector("td:nth-child(3)")?.innerText.trim() || "";
        const password = row.querySelector("td:nth-child(4) input")?.value.trim() || "";
        const otp = row.querySelector("td:nth-child(5)")?.innerText.trim() || "";
        const department = row.querySelector("td:nth-child(6)")?.innerText.trim() || "";

        // Extract added by and created on from the dropdown
        const addedBy = row.querySelector("td:nth-child(7) .dropdown-item small span.text-muted")?.innerText.trim() || "";
        const createdOn = row.querySelector("td:nth-child(7) .dropdown-item small span.text-muted + br + span.text-muted")?.innerText.trim() || "";

        // Add row data to CSV
        const rowData = [name, url, username, password, otp, department, addedBy, createdOn];
        csvContent += rowData.map(data => `"${data.replace(/"/g, '""')}"`).join(",") + "\n";
    }

    // Create a download link for the CSV file
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "credentials.csv");
    document.body.appendChild(link); // Append link to the body
    link.click(); // Programmatically click the link to trigger download
    document.body.removeChild(link); // Remove the link after download
}


</script>

