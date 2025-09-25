<?php
session_start();
require 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
        exit('Unauthorized access.');
    }

    // Begin Database Transaction for safety
    $conn->begin_transaction();

    try {
        // ---------- Collect Main Form Data ----------
        $student_id      = $_SESSION['user_id'];
        $internship_type = $_POST['internship_type'];
        $admission_no    = $_POST['admission_no'];
        $ktu_reg_no      = $_POST['ktu_reg_no'];
        $cgpa            = $_POST['cgpa'];
        $company_name    = $_POST['company_name'];
        $duration        = $_POST['duration'];
        $stipend         = $_POST['stipend'];
        $program         = $_POST['program'];
        $branch          = $_POST['branch'];
        $batch           = $_POST['batch'];
        $semester        = $_POST['semester'];
        $contact_number  = $_POST['contact_number'];
        $email_id        = $_POST['email_id'];

        // Main form checkbox values
        $doc_offer_letter  = isset($_POST['doc_offer_letter']) ? 1 : 0;
        $doc_cgpa_cert     = isset($_POST['doc_cgpa_cert']) ? 1 : 0;
        $doc_industry_cert = isset($_POST['doc_industry_cert']) ? 1 : 0;
        $doc_synopsis      = isset($_POST['doc_synopsis']) ? 1 : 0;
        $doc_good_standing = isset($_POST['doc_good_standing']) ? 1 : 0;
        $doc_team_consent  = isset($_POST['doc_team_consent']) ? 1 : 0;

        // ---------- ADDED: Collect Undertaking Data ----------
        $u_parent_name = $_POST['undertaking_parent_name'] ?? null;
        $u_address     = $_POST['undertaking_address'] ?? null;
        $u_location    = $_POST['undertaking_location'] ?? null;
        $u_months      = $_POST['undertaking_months'] ?? null;
        $u_start_date  = $_POST['undertaking_start_date'] ?? null;
        $u_end_date    = $_POST['undertaking_end_date'] ?? null;
        $u_domain      = $_POST['undertaking_domain'] ?? null;
        $u_agreed      = $_POST['undertaking_agreed'] ?? 'no';

        // ADDED: Validation for undertaking
        if ($u_agreed !== 'yes') {
            throw new Exception("The undertaking form was not completed. Please go back and fill it out.");
        }

        // ---------- MODIFIED: Insert into `applications` (removed doc_undertaking) ----------
        $sql = "INSERT INTO applications
                    (student_id, internship_type, admission_no, ktu_reg_no, cgpa, company_name,
                     duration, stipend, program, branch, batch, semester, contact_number,
                     email_id, doc_offer_letter, doc_cgpa_cert, doc_industry_cert,
                     doc_synopsis, doc_good_standing, doc_team_consent)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"; // MODIFIED: 20 placeholders now

        $stmt = $conn->prepare($sql);
        // MODIFIED: Type string is now 20 characters
        $stmt->bind_param(
            "isssdsissssisssiiiii",
            $student_id, $internship_type, $admission_no, $ktu_reg_no, $cgpa,
            $company_name, $duration, $stipend, $program, $branch,
            $batch, $semester, $contact_number, $email_id,
            $doc_offer_letter, $doc_cgpa_cert, $doc_industry_cert, 
            $doc_synopsis, $doc_good_standing, $doc_team_consent
        );

        if (!$stmt->execute()) {
             throw new Exception("Error saving application: " . $stmt->error);
        }

        $application_id = $stmt->insert_id;
        $stmt->close();

        // ---------- ADDED: Insert into `undertakings` table ----------
        $sql_undertaking = "INSERT INTO undertakings (application_id, parent_name, residing_at, location, duration_months, start_date, end_date, domain) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_undertaking = $conn->prepare($sql_undertaking);
        $stmt_undertaking->bind_param(
            "isssisss",
            $application_id, $u_parent_name, $u_address, $u_location, $u_months,
            $u_start_date, $u_end_date, $u_domain
        );
        
        if (!$stmt_undertaking->execute()) {
            throw new Exception("Error saving undertaking details: " . $stmt_undertaking->error);
        }
        $stmt_undertaking->close();


        // ---------- File Upload Logic (Preserved and placed inside transaction) ----------
        if (!empty($_FILES['supporting_documents']['name']) && $_FILES['supporting_documents']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // Using a clean filename for security
            $file_extension = pathinfo($_FILES['supporting_documents']['name'], PATHINFO_EXTENSION);
            $file_name = "application_" . $application_id . "_docs." . $file_extension;
            $file_path = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['supporting_documents']['tmp_name'], $file_path)) {
                $doc_stmt = $conn->prepare(
                    "INSERT INTO documents (application_id, document_type, file_path) VALUES (?, ?, ?)"
                );
                $doc_type = "supporting_documents";
                // We save just the filename in the DB, not the full server path
                $doc_stmt->bind_param("iss", $application_id, $doc_type, $file_name);
                if (!$doc_stmt->execute()) {
                    throw new Exception("Error saving document record: " . $doc_stmt->error);
                }
                $doc_stmt->close();
            } else {
                 throw new Exception("Failed to move uploaded file.");
            }
        }

        // ---------- If all steps are successful, commit the transaction ----------
        $conn->commit();

        // ---------- Redirect on success ----------
        header("Location: student_dashboard.php?success=submitted");
        exit();

    } catch (Exception $e) {
        // --- If any error occurred, roll back all database changes ---
        $conn->rollback();
        // You can redirect to an error page or show a generic error message
        exit("Error submitting application: " . $e->getMessage());
    } finally {
        // --- Always close the connection ---
        if (isset($stmt)) $stmt->close();
        if (isset($stmt_undertaking)) $stmt_undertaking->close();
        if (isset($doc_stmt)) $doc_stmt->close();
        $conn->close();
    }
}
?>