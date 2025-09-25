<?php
session_start();
require 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_SESSION['user_id'])) { exit('Unauthorized access.'); }

    $application_id = intval($_POST['application_id']);
    $decision = $_POST['decision'];
    $remarks = trim($_POST['remarks']);
    $current_user_id = $_SESSION['user_id'];
    $current_user_role = $_SESSION['role'];

    // Fetch current application info
    $stmt = $conn->prepare("SELECT a.status, a.company_name, u.program_type FROM applications a JOIN users u ON a.student_id = u.id WHERE a.id = ?");
    $stmt->bind_param("i", $application_id);
    $stmt->execute();
    $application = $stmt->get_result()->fetch_assoc();
    if (!$application) { exit('Application not found.'); }
    $current_status = $application['status'];
    $is_pg_student = ($application['program_type'] == 'PG');
    $stmt->close();

    $next_status = '';
    $final_decision = $decision;
    $final_remarks = $remarks;

    // IQAC smart logic (optional, can remove if not needed)
    if ($current_user_role == 'iqac' && $decision === 'Approved') {
        $company_stmt = $conn->prepare("SELECT rating FROM companies WHERE name = ?");
        $company_stmt->bind_param("s", $application['company_name']);
        $company_stmt->execute();
        $company_result = $company_stmt->get_result();
        if ($company_row = $company_result->fetch_assoc()) {
            $rating = $company_row['rating'];
            if ($rating < 30) {
                $final_decision = 'Rejected';
                $final_remarks = "Auto-Rejected: Company rating is poor ({$rating}%). Minimum 30% required.";
            } elseif ($rating <= 50) {
                $final_remarks = "Approved (Conditional): Company rating average ({$rating}%).";
            } elseif ($rating <= 80) {
                $final_remarks = "Approved: Company has good rating ({$rating}%).";
            } else {
                $final_remarks = "Approved: Company has excellent rating ({$rating}%).";
            }
        }
        $company_stmt->close();
    }

    // Determine next status
    if ($final_decision === 'Approved') {
        if ($current_user_role == 'staff_advisor' && $current_status == 'Pending Staff Advisor') {
            $next_status = 'Pending HOD Approval';
        } elseif ($current_user_role == 'hod' && $current_status == 'Pending HOD Approval') {
            $next_status = $is_pg_student ? 'Approved' : 'Pending DQAC Approval';
        } elseif ($current_user_role == 'dqac' && $current_status == 'Pending DQAC Approval') {
            $next_status = 'Pending IQAC Approval';
        } elseif ($current_user_role == 'iqac' && $current_status == 'Pending IQAC Approval') {
            $next_status = 'Approved';
        }
    } else { // Rejected
        $next_status = 'Rejected by ' . ucwords(str_replace('_', ' ', $current_user_role));
    }

    // Update application status & remarks
    $update_stmt = $conn->prepare("UPDATE applications SET status = ?, remarks = ? WHERE id = ?");
    $update_stmt->bind_param("ssi", $next_status, $final_remarks, $application_id);
    $update_stmt->execute();
    $update_stmt->close();

    // Insert approval record
    $approval_stmt = $conn->prepare("INSERT INTO approvals (application_id, approver_id, approval_level, decision, remarks) VALUES (?, ?, ?, ?, ?)");
    $approval_stmt->bind_param("iisss", $application_id, $current_user_id, $current_user_role, $final_decision, $final_remarks);
    $approval_stmt->execute();
    $approval_stmt->close();

    // Handle signature (if provided)
    if (!empty($_POST['signature'])) {
        $signature_data = $_POST['signature'];
        list($type, $signature_data) = explode(';', $signature_data);
        list(, $signature_data) = explode(',', $signature_data);
        $signature_data = base64_decode($signature_data);

        $signature_filename = 'signature_' . $application_id . '_' . $current_user_role . '.png';
        $signature_path = 'uploads/' . $signature_filename;
        file_put_contents($signature_path, $signature_data);

        $signature_column = ($current_user_role == 'staff_advisor') ? "staff_advisor_signature_path" : "hod_signature_path";
        $sig_stmt = $conn->prepare("UPDATE applications SET $signature_column = ? WHERE id = ?");
        $sig_stmt->bind_param("si", $signature_path, $application_id);
        $sig_stmt->execute();
        $sig_stmt->close();
    }

    header("Location: " . $current_user_role . "_dashboard.php");
    exit();
}
?>
<?php
session_start();
require 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    exit('Invalid request method.');
}
if (!isset($_SESSION['user_id'])) {
    exit('Unauthorized access.');
}

$application_id = intval($_POST['application_id']);
$decision = $_POST['decision']; // 'Approved' or 'Rejected'
$remarks = trim($_POST['remarks']);
$current_user_id = $_SESSION['user_id'];
$current_user_role = $_SESSION['role'];

// Fetch application details
$stmt = $conn->prepare("SELECT a.status, a.company_name, u.program_type FROM applications a JOIN users u ON a.student_id = u.id WHERE a.id = ?");
$stmt->bind_param("i", $application_id);
$stmt->execute();
$application = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$application) exit('Application not found.');

$current_status = $application['status'];
$is_pg_student = ($application['program_type'] == 'PG');
$next_status = '';
$final_decision = $decision;
$final_remarks = $remarks;

// --- IQAC smart logic ---
if ($current_user_role == 'iqac' && $decision === 'Approved') {
    $company_stmt = $conn->prepare("SELECT rating FROM companies WHERE name = ?");
    $company_stmt->bind_param("s", $application['company_name']);
    $company_stmt->execute();
    $company_result = $company_stmt->get_result();
    if ($company_row = $company_result->fetch_assoc()) {
        $rating = $company_row['rating'];
        if ($rating < 30) {
            $final_decision = 'Rejected';
            $final_remarks = "Auto-Rejected: Company rating is poor ({$rating}%). Minimum 30% required.";
        } elseif ($rating <= 50) {
            $final_remarks = "Approved (Conditional): Company rating average ({$rating}%).";
        } elseif ($rating <= 80) {
            $final_remarks = "Approved: Company has good rating ({$rating}%).";
        } else {
            $final_remarks = "Approved: Company has excellent rating ({$rating}%).";
        }
    }
    $company_stmt->close();
}

// --- Determine next status based on role & current status ---
if ($final_decision === 'Approved') {
    switch ($current_user_role) {
        case 'staff_advisor':
            if ($current_status == 'Pending Staff Advisor') $next_status = 'Pending HOD Approval';
            break;
        case 'hod':
            if ($current_status == 'Pending HOD Approval') {
                $next_status = $is_pg_student ? 'Approved' : 'Pending DQAC Approval';
            }
            break;
        case 'dqac':
            if ($current_status == 'Pending DQAC Approval') $next_status = 'Pending IQAC Approval';
            break;
        case 'iqac':
            if ($current_status == 'Pending IQAC Approval') $next_status = 'Approved';
            break;
    }
} else { // Rejected
    $next_status = 'Rejected by ' . ucwords(str_replace('_',' ', $current_user_role));
}

// --- Update application status & remarks ---
$update_stmt = $conn->prepare("UPDATE applications SET status = ?, remarks = ? WHERE id = ?");
$update_stmt->bind_param("ssi", $next_status, $final_remarks, $application_id);
$update_stmt->execute();
$update_stmt->close();

// --- Insert into approvals table ---
$approval_stmt = $conn->prepare("INSERT INTO approvals (application_id, approver_id, approval_level, decision, remarks) VALUES (?, ?, ?, ?, ?)");
$approval_stmt->bind_param("iisss", $application_id, $current_user_id, $current_user_role, $final_decision, $final_remarks);
$approval_stmt->execute();
$approval_stmt->close();

// --- Handle signature upload ---
if (!empty($_POST['signature'])) {
    $signature_data = $_POST['signature'];
    list($type, $signature_data) = explode(';', $signature_data);
    list(, $signature_data) = explode(',', $signature_data);
    $signature_data = base64_decode($signature_data);

    $signature_filename = 'signature_' . $application_id . '_' . $current_user_role . '.png';
    $signature_path = 'uploads/' . $signature_filename;
    file_put_contents($signature_path, $signature_data);

    switch ($current_user_role) {
        case 'staff_advisor': $sig_column = 'staff_advisor_signature_path'; break;
        case 'hod': $sig_column = 'hod_signature_path'; break;
        case 'dqac': $sig_column = 'dqac_signature_path'; break;
        case 'iqac': $sig_column = 'iqac_signature_path'; break;
    }

    $sig_stmt = $conn->prepare("UPDATE applications SET $sig_column = ? WHERE id = ?");
    $sig_stmt->bind_param("si", $signature_path, $application_id);
    $sig_stmt->execute();
    $sig_stmt->close();
}

// --- Redirect back to the correct dashboard ---
header("Location: " . $current_user_role . "_dashboard.php");
exit();
?>
