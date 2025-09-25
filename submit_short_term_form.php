<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}
$user_full_name = $_SESSION['full_name'];
$user_email = $_SESSION['email'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Short-Term Internship Application</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        :root { --navy-blue: #0a2342; --sidebar-bg: #1e293b; --content-bg: #f1f5f9; --card-bg: #ffffff; --text-primary: #1e293b; }
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background-color: var(--content-bg); color: var(--text-primary); }
        .main-layout { display: flex; min-height: 100vh; }
        .sidebar { background-color: var(--sidebar-bg); color: #e2e8f0; padding: 20px 15px; width: 260px; box-sizing: border-box; flex-shrink: 0; }
        .sidebar-header h3 { color: #fff; text-align: center; font-weight: 600; }
        .sidebar-nav ul { list-style: none; padding: 0; margin: 0; margin-top: 30px;}
        .sidebar-nav a { display: flex; align-items: center; gap: 15px; padding: 12px 15px; color: #e2e8f0; text-decoration: none; border-radius: 8px; font-weight: 500; }
        .sidebar-nav a:hover { background-color: #334155; color: #fff; }
        .main-content { flex-grow: 1; display: flex; flex-direction: column; }
        .top-nav { background-color: var(--card-bg); padding: 15px 30px; border-bottom: 1px solid #e2e8f0; text-align: right; }
        .logout-button { background-color: var(--navy-blue); color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; font-weight: 500;}
        .content-area { padding: 30px; flex-grow: 1; }
        .form-container { max-width: 800px; margin: auto; }
        .card { background-color: var(--card-bg); padding: 35px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .form-header { text-align: center; margin-bottom: 30px; }
        .form-header h2 { margin: 0; font-size: 1.8rem; }
        .form-header p { margin-top: 5px; color: #64748b; }
        .form-section-header { margin-top: 30px; margin-bottom: 20px; font-size: 1.25rem; font-weight: 600; color: var(--navy-blue); border-bottom: 2px solid var(--navy-blue); padding-bottom: 10px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 15px; position: relative; } /* Added position relative for error messages */
        .form-group.full-width { grid-column: 1 / -1; }
        label { display: block; margin-bottom: 5px; font-weight: 600; }
        input[type="text"], input[type="number"], input[type="email"], input[type="date"], select, input[type="file"], textarea { width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 1rem; box-sizing: border-box; background-color: #f9fafb; }
        .checkbox-group label { display: flex; align-items: center; font-weight: normal; }
        .checkbox-group input { width: auto; margin-right: 10px; }
        .primary-button { padding: 12px 25px; background-color: var(--navy-blue); border: none; border-radius: 6px; color: #fff; font-size: 16px; cursor: pointer; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.6); }
        .modal-content { background-color: #fefefe; margin: 5% auto; padding: 30px; border: 1px solid #888; width: 90%; max-width: 800px; border-radius: 8px; position: relative; }
        .close-button { color: #aaa; float: right; font-size: 28px; font-weight: bold; position: absolute; top: 10px; right: 20px; }
        .close-button:hover, .close-button:focus { color: black; text-decoration: none; cursor: pointer; }
        /* Style for validation error messages */
        .error-message { color: #ef4444; font-size: 0.875rem; margin-top: 5px; }
    </style>
</head>
<body>
<div class="main-layout">
    <aside class="sidebar">
        <div class="sidebar-header"><h3>Student Portal</h3></div>
        <nav class="sidebar-nav">
           <ul>
                <li><a href="student_dashboard.php"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
                <li><a href="select_internship_type.php"><i class="fa-solid fa-arrow-left"></i> Back to Selection</a></li>
           </ul>
        </nav>
    </aside>
    <div class="main-content">
        <header class="top-nav">
             <a href="logout.php" class="logout-button"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </header>
        <main class="content-area">
            <div class="form-container">
                <div class="card">
                    <div class="form-header">
                        <h2>Application for Short-Term Internship</h2>
                        <p>College of Engineering Trivandrum</p>
                    </div>
                    <form id="applicationForm" action="handle_application.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="internship_type" value="short_term">
                        
                        <div class="form-grid">
                            <div class="form-group full-width"><label>Name of the student</label><input type="text" name="student_name" value="<?php echo htmlspecialchars($user_full_name); ?>" readonly></div>
                            <div class="form-group"><label>Admission No.</label><input type="text" name="admission_no" required></div>
                            <div class="form-group"><label>KTU Registration No.</label><input type="text" name="ktu_reg_no" required></div>
                            <div class="form-group"><label>CGPA</label><input type="number" step="0.01" name="cgpa" required></div>
                            <div class="form-group"><label>Name of the organization</label><input type="text" name="company_name" required></div>
                            
                            <div class="form-group">
                                <label>Duration of internship</label>
                                <input type="text" name="duration" placeholder="e.g., 2 Months" required>
                                <span class="error-message" id="durationError"></span>
                            </div>
                            
                            <div class="form-group">
                                <label>Stipend offered (per month)</label>
                                <input type="number" name="stipend" placeholder="0 or min 5000" required>
                                <span class="error-message" id="stipendError"></span>
                            </div>
                            
                            <div class="form-group"><label>Programme</label><input type="text" name="program" value="<?php echo htmlspecialchars($_SESSION['program_type']); ?>" readonly></div>
                            <div class="form-group"><label>Branch</label><input type="text" name="branch" value="<?php echo htmlspecialchars($_SESSION['department']); ?>" readonly></div>
                            <div class="form-group"><label>Batch</label><input type="text" name="batch" placeholder="e.g., 2022-2026" required></div>
                            <div class="form-group"><label>Semester</label><input type="number" name="semester" required></div>
                            
                            <div class="form-group">
                                <label>Contact Number</label>
                                <input type="text" name="contact_number" required>
                                <span class="error-message" id="contactError"></span>
                            </div>
                            
                            <div class="form-group"><label>Email ID</label><input type="email" name="email_id" value="<?php echo htmlspecialchars($user_email); ?>" readonly></div>
                        </div>

                        <h3 class="form-section-header">Student Undertaking</h3>
                        <div class="form-group full-width">
                            <p>Please click the button to open and fill the mandatory undertaking form.</p>
                            <button type="button" id="openUndertakingBtn" class="primary-button" style="background-color: #3b82f6;">
                                <i class="fa-solid fa-file-signature"></i> Fill Undertaking Form
                            </button>
                            <span id="undertakingStatus" style="margin-left: 15px; font-weight: bold; color: green;"></span>
                        </div>

                        <input type="hidden" name="undertaking_parent_name" id="h_parent_name">
                        <input type="hidden" name="undertaking_address" id="h_address">
                        <input type="hidden" name="undertaking_location" id="h_location">
                        <input type="hidden" name="undertaking_months" id="h_months">
                        <input type="hidden" name="undertaking_start_date" id="h_start_date">
                        <input type="hidden" name="undertaking_end_date" id="h_end_date">
                        <input type="hidden" name="undertaking_domain" id="h_domain">
                        <input type="hidden" name="undertaking_agreed" id="h_agreed" value="no">

                        <h3 class="form-section-header">Required Documents Checklist & Uploads</h3>
                        <p>After completing the undertaking, please check the boxes and upload the required documents as a single combined PDF.</p>

                        <div class="form-grid checkbox-group">
                            <div class="form-group full-width"><label><input type="checkbox" name="doc_offer_letter" value="1" required> Offer letter or joining confirmation email</label></div>
                            <div class="form-group full-width"><label><input type="checkbox" name="doc_industry_cert" value="1" required> Certificate stating that the industry possesses necessary facilities</label></div>
                            <div class="form-group full-width"><label><input type="checkbox" name="doc_synopsis" value="1" required> Draft synopsis</label></div>
                            <div class="form-group full-width"><label><input type="checkbox" name="doc_good_standing" value="1" required> Certificate of good standing</label></div>
                        </div>

                        <div class="form-group" style="margin-top: 20px;">
                            <label>Please upload all the checked documents as a single PDF file *</label>
                            <input type="file" name="supporting_documents" accept=".pdf" required>
                        </div>

                        <div class="form-group" style="margin-top: 30px;">
                            <button type="submit" class="primary-button">Submit Application</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<div id="undertakingModal" class="modal">
    <div class="modal-content">
        <span class="close-button">&times;</span>
        <h3>Undertaking for Short-Term Internship</h3>
        <p>I, <strong><?php echo htmlspecialchars($user_full_name); ?></strong>, do hereby bind myself to College of Engineering Trivandrum for the following.</p>
        
        <form id="undertakingForm" onsubmit="return false;">
            <div class="form-grid">
                <div class="form-group"><label>Son/Daughter of</label><input type="text" id="m_parent_name" required></div>
                <div class="form-group full-width"><label>Residing at (Full Address)</label><textarea id="m_address" rows="3" required></textarea></div>
                <div class="form-group"><label>Internship Location</label><input type="text" id="m_location" required></div>
                <div class="form-group"><label>For how many months?</label><input type="number" id="m_months" required></div>
                <div class="form-group"><label>From (Start Date)</label><input type="date" id="m_start_date" required></div>
                <div class="form-group"><label>To (End Date)</label><input type="date" id="m_end_date" required></div>
                <div class="form-group full-width"><label>Internship Domain (e.g., Web Development, AI/ML)</label><input type="text" id="m_domain" required></div>
            </div>
            
            <p style="margin-top: 20px;">
                I will be responsible for meeting all academic requirements. I will write internal exams and submit assignments as per the schedule. I will submit the synopsis within one week of joining. I will forward monthly attendance and progress reports. I agree to submit the final report, certificate, and stipend proof upon completion.
            </p>

            <div class="form-group checkbox-group" style="margin-top: 20px;">
                <label>
                    <input type="checkbox" id="m_agreement" required> 
                    <strong>I have read and agree to all terms. This action serves as my digital confirmation.</strong>
                </label>
            </div>
            <div class="form-group" style="margin-top: 20px;">
                <button type="button" id="saveUndertakingBtn" class="primary-button">Save and Close</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('undertakingModal');
    const openBtn = document.getElementById('openUndertakingBtn');
    const closeBtn = document.getElementsByClassName('close-button')[0];
    const saveBtn = document.getElementById('saveUndertakingBtn');
    const applicationForm = document.getElementById('applicationForm');

    // Modal logic (unchanged)
    openBtn.onclick = () => modal.style.display = 'block';
    closeBtn.onclick = () => modal.style.display = 'none';
    window.onclick = (event) => {
        if (event.target == modal) modal.style.display = 'none';
    };

    saveBtn.onclick = () => {
        if (!document.getElementById('m_parent_name').value || !document.getElementById('m_address').value || !document.getElementById('m_agreement').checked) {
            alert('Please fill all required fields in the undertaking and agree to the terms.');
            return;
        }

        document.getElementById('h_parent_name').value = document.getElementById('m_parent_name').value;
        document.getElementById('h_address').value = document.getElementById('m_address').value;
        document.getElementById('h_location').value = document.getElementById('m_location').value;
        document.getElementById('h_months').value = document.getElementById('m_months').value;
        document.getElementById('h_start_date').value = document.getElementById('m_start_date').value;
        document.getElementById('h_end_date').value = document.getElementById('m_end_date').value;
        document.getElementById('h_domain').value = document.getElementById('m_domain').value;
        document.getElementById('h_agreed').value = 'yes';

        document.getElementById('undertakingStatus').textContent = 'Undertaking Saved Successfully ✔';
        openBtn.disabled = true;
        modal.style.display = 'none';
    };

    // --- NEW VALIDATION LOGIC ---
    applicationForm.addEventListener('submit', function(event) {
        let isValid = true;
        
        // Helper to show/clear errors
        const showError = (id, message) => {
            document.getElementById(id).textContent = message;
            isValid = false;
        };
        const clearError = (id) => {
            document.getElementById(id).textContent = '';
        };

        // Clear all previous errors
        clearError('contactError');
        clearError('stipendError');
        clearError('durationError');

        // 1. Contact Number Validation (10 digits)
        const contactInput = document.querySelector('input[name="contact_number"]');
        const contactRegex = /^\d{10}$/;
        if (!contactRegex.test(contactInput.value)) {
            showError('contactError', 'Please enter a valid 10-digit contact number.');
        }

        // 2. Stipend Validation (0 or >= 5000)
        const stipendInput = document.querySelector('input[name="stipend"]');
        const stipendValue = parseInt(stipendInput.value, 10);
        if (stipendValue !== 0 && stipendValue < 5000) {
            showError('stipendError', 'Stipend must be 0 (unpaid) or at least ₹5000.');
        }

        // 3. Duration Validation (2 weeks to 3 months)
        const durationInput = document.querySelector('input[name="duration"]');
        const durationText = durationInput.value.toLowerCase();
        const durationRegex = /(\d+)\s*(weeks?|months?|days?)/;
        const match = durationText.match(durationRegex);
        
        if (match) {
            const value = parseInt(match[1], 10);
            const unit = match[2];
            let days = 0;
            if (unit.startsWith('week')) {
                days = value * 7;
            } else if (unit.startsWith('month')) {
                days = value * 30; // Approximation
            } else {
                days = value;
            }

            // Min 2 weeks (14 days), Max 3 months (90 days)
            if (days < 14 || days > 90) {
                showError('durationError', 'Duration must be between 2 weeks and 3 months.');
            }
        } else {
            showError('durationError', 'Please use a format like "2 weeks", "1 month", or "45 days".');
        }

        // If any validation failed, prevent the form from submitting
        if (!isValid) {
            event.preventDefault();
            alert('Please correct the errors before submitting.');
        }
    });
});
</script>

</body>
</html>