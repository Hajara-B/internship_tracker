<?php
session_start();
require 'db_connect.php';

// --- NEW: HANDLES FINAL SIGNATURE UPLOADS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_final_signatures') {
    if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['iqac', 'admin'])) { exit('Unauthorized.'); }
    
    $application_id = intval($_POST['application_id']);

    function upload_final_signature($file_key, $application_id, $role, $conn) {
        if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/signatures/';
            if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }
            
            $file_extension = strtolower(pathinfo($_FILES[$file_key]['name'], PATHINFO_EXTENSION));
            $new_filename = $role . '_sig_' . $application_id . '_' . time() . '.' . $file_extension;
            $target_path = $upload_dir . $new_filename;

            if (move_uploaded_file($_FILES[$file_key]['tmp_name'], $target_path)) {
                $column = ($role === 'principal') ? 'principal_signature_path' : 'dean_ug_signature_path';
                $stmt = $conn->prepare("UPDATE applications SET $column = ? WHERE id = ?");
                $stmt->bind_param("si", $target_path, $application_id);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    upload_final_signature('principal_signature', $application_id, 'principal', $conn);
    upload_final_signature('dean_ug_signature', $application_id, 'dean_ug', $conn);

    $conn->close();
    header("Location: view_application.php?id=" . $application_id . "&success=final_signatures_uploaded");
    exit(); 
}

// --- MAIN APPROVAL/REJECTION WORKFLOW ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_SESSION['user_id'])) { exit('Unauthorized access.'); }

    $application_id = intval($_POST['application_id']);
    $decision = $_POST['decision'];
    $remarks = trim($_POST['remarks']);
    $current_user_id = $_SESSION['user_id'];
    $current_user_role = $_SESSION['role'];

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

    // IQAC smart logic
    if ($current_user_role == 'iqac' && $decision === 'Approved') {
        $company_stmt = $conn->prepare("SELECT rating FROM companies WHERE name = ?");
        $company_stmt->bind_param("s", $application['company_name']);
        $company_stmt->execute();
        $company_result = $company_stmt->get_result();
        if ($company_row = $company_result->fetch_assoc()) {
            $rating = $company_row['rating'];
            if ($rating < 30) {
                // If they approve a low-rated company, it's an override. Still gets approved.
                $final_remarks = "Approved (Override): Manually approved despite low company rating ({$rating}%). " . $remarks;
            }
        }
        $company_stmt->close();
    }
    
    // Determine next status
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
                // MODIFIED: IQAC can now approve from the flagged state as well
                if ($current_status == 'Pending IQAC Approval' || $current_status == 'Pending Review (Low Rating)') $next_status = 'Approved';
                break;
        }
    } else { // Rejected
        $next_status = 'Rejected by ' . ucwords(str_replace('_', ' ', $current_user_role));
    }

    if (empty($next_status)) {
        header("Location: " . $current_user_role . "_dashboard.php?error=invalid_action");
        exit();
    }

    $update_stmt = $conn->prepare("UPDATE applications SET status = ?, remarks = ? WHERE id = ?");
    $update_stmt->bind_param("ssi", $next_status, $final_remarks, $application_id);
    $update_stmt->execute();
    $update_stmt->close();

    $approval_stmt = $conn->prepare("INSERT INTO approvals (application_id, approver_id, approval_level, decision, remarks) VALUES (?, ?, ?, ?, ?)");
    $approval_stmt->bind_param("iisss", $application_id, $current_user_id, $current_user_role, $final_decision, $final_remarks);
    $approval_stmt->execute();
    $approval_stmt->close();

    // Handle Base64 signature for Staff Advisor/HOD
    if (!empty($_POST['signature'])) {
        $signature_data = $_POST['signature'];
        list($type, $signature_data) = explode(';', $signature_data);
        list(, $signature_data) = explode(',', $signature_data);
        $signature_data = base64_decode($signature_data);

        $signature_filename = 'signature_' . $application_id . '_' . $current_user_role . '.png';
        $signature_path = 'uploads/signatures/' . $signature_filename;
        if (!is_dir('uploads/signatures/')) { mkdir('uploads/signatures/', 0755, true); }
        file_put_contents($signature_path, $signature_data);

        $sig_column = '';
        switch ($current_user_role) {
            case 'staff_advisor': $sig_column = 'staff_advisor_signature_path'; break;
            case 'hod': $sig_column = 'hod_signature_path'; break;
        }
        if($sig_column){
            $sig_stmt = $conn->prepare("UPDATE applications SET $sig_column = ? WHERE id = ?");
            $sig_stmt->bind_param("si", $signature_path, $application_id);
            $sig_stmt->execute();
            $sig_stmt->close();
        }
    }
    
    // MODIFIED: Redirect to the view page if approved by IQAC to allow for final signature upload
    if ($current_user_role == 'iqac' && $next_status == 'Approved') {
        header("Location: view_application.php?id=" . $application_id . "&success=approved");
    } else {
        header("Location: " . $current_user_role . "_dashboard.php?success=processed");
    }
    exit();
}
?>