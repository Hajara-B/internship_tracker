<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
if (!isset($_GET['id'])) { header("Location: " . $_SESSION['role'] . "_dashboard.php"); exit(); }

$application_id = intval($_GET['id']);
$current_user_id = $_SESSION['user_id'];
$current_user_role = $_SESSION['role'];

// This SQL now includes the new signature path columns
$sql = "SELECT a.*, u.full_name, u.email, u.college_id, ut.id as undertaking_id 
        FROM applications a 
        JOIN users u ON a.student_id = u.id 
        LEFT JOIN undertakings ut ON a.id = ut.application_id 
        WHERE a.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $application_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) { exit('Application not found.'); }
$app = $result->fetch_assoc();
$stmt->close();

$docs_sql = "SELECT document_type, file_path FROM documents WHERE application_id = ?";
$docs_stmt = $conn->prepare($docs_sql);
$docs_stmt->bind_param("i", $application_id);
$docs_stmt->execute();
$docs_result = $docs_stmt->get_result();
$uploaded_pdf = null;
if($docs_result->num_rows > 0){
    $uploaded_pdf = $docs_result->fetch_assoc();
}
$docs_stmt->close();

$approval_sql = "SELECT ap.decision, ap.remarks, ap.created_at, u.full_name, u.role FROM approvals ap JOIN users u ON ap.approver_id = u.id WHERE ap.application_id = ? ORDER BY ap.created_at ASC";
$approval_stmt = $conn->prepare($approval_sql);
$approval_stmt->bind_param("i", $application_id);
$approval_stmt->execute();
$approval_history = $approval_stmt->get_result();

// MODIFIED: Update approval conditions
$can_approve = false;
$show_signature_pad = false;
$is_approved_final = ($app['status'] === 'Approved');

if (($current_user_role == 'staff_advisor' && $app['status'] == 'Pending Staff Advisor') || 
    ($current_user_role == 'hod' && $app['status'] == 'Pending HOD Approval')) {
    $can_approve = true;
    $show_signature_pad = true;
} elseif (($current_user_role == 'dqac' && $app['status'] == 'Pending DQAC Approval') ||
          // IQAC can now approve from the flagged state too
          ($current_user_role == 'iqac' && in_array($app['status'], ['Pending IQAC Approval', 'Pending Review (Low Rating)']))) {
    $can_approve = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Application</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
    <style>
        :root { --navy-blue: #0a2342; --sidebar-bg: #1e293b; --content-bg: #f1f5f9; --card-bg: #ffffff; --border-color: #e2e8f0; }
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background-color: var(--content-bg); }
        .main-layout { display: flex; min-height: 100vh; }
        .sidebar { background-color: var(--sidebar-bg); padding: 20px 15px; width: 260px; box-sizing: border-box; flex-shrink: 0; }
        .sidebar-header h3 { color: #fff; text-align: center; }
        .main-content { flex-grow: 1; display: flex; flex-direction: column; }
        .content-area { padding: 30px; max-width: 900px; margin: auto; }
        .card { background-color: var(--card-bg); padding: 25px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 25px; }
        h2, h3 { border-bottom: 2px solid var(--navy-blue); padding-bottom: 10px; margin-top:0; }
        .details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px 30px; }
        .details-grid strong { color: #334155; }
        .doc-list { list-style: none; padding-left: 0; }
        .doc-list li { padding: 5px 0; }
        .doc-list .fa-check-circle { color: #22c55e; } .doc-list .fa-times-circle { color: #ef4444; }
        .approval-form textarea, .signature-form textarea { width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ddd; }
        .approval-form button, .signature-form button { padding: 10px 20px; border: none; border-radius: 5px; color: #fff; cursor: pointer; }
        .approve-btn { background-color: #22c55e; } .reject-btn { background-color: #ef4444; }
        #signature-pad { border: 1px solid #ccc; border-radius: 5px; }
        .signature-box img { max-width: 200px; border: 1px solid #eee; margin-top:5px; }
        #signature-preview { max-width: 200px; margin-top: 10px; border: 1px solid #ccc; border-radius: 5px; display:none; }
    </style>
</head>
<body>
<div class="main-layout">
    <aside class="sidebar"><div class="sidebar-header"><h3><a href="<?php echo htmlspecialchars($current_user_role); ?>_dashboard.php" style="color:white; text-decoration:none;">Back to Dashboard</a></h3></div></aside>
    <div class="main-content">
        <main class="content-area">
            <div class="card">
                <h2>Application Details (Status: <?php echo htmlspecialchars($app['status']); ?>)</h2>
                <div class="details-grid">
                    <div><strong>Student:</strong> <?php echo htmlspecialchars($app['full_name']); ?></div>
                    <div><strong>Type:</strong> <?php echo htmlspecialchars(ucwords(str_replace('_', '-', $app['internship_type']))); ?></div>
                    <div><strong>Department:</strong> <?php echo htmlspecialchars($app['branch']); ?></div>
                    <div><strong>Programme:</strong> <?php echo htmlspecialchars($app['program']); ?></div>
                    <div><strong>Company:</strong> <?php echo htmlspecialchars($app['company_name']); ?></div>
                    <div><strong>Duration:</strong> <?php echo htmlspecialchars($app['duration']); ?></div>
                    <div><strong>Stipend:</strong> <?php echo htmlspecialchars($app['stipend']); ?></div>
                    <div><strong>CGPA:</strong> <?php echo htmlspecialchars($app['cgpa']); ?></div>
                </div>
            </div>
            <div class="card">
                <h3>Student-Confirmed Documents</h3>
                <ul class="doc-list">
                    <li><i class="<?php echo $app['doc_offer_letter'] ? 'fa-solid fa-check-circle' : 'fa-solid fa-times-circle'; ?>"></i> Offer Letter</li>
                    <li><i class="<?php echo !empty($app['undertaking_id']) ? 'fa-solid fa-check-circle' : 'fa-solid fa-times-circle'; ?>"></i> Undertaking Signed</li>
                    <li><i class="<?php echo $app['doc_industry_cert'] ? 'fa-solid fa-check-circle' : 'fa-solid fa-times-circle'; ?>"></i> Industry Facilities Certificate</li>
                    <li><i class="<?php echo $app['doc_synopsis'] ? 'fa-solid fa-check-circle' : 'fa-solid fa-times-circle'; ?>"></i> Draft Synopsis</li>
                    <li><i class="<?php echo $app['doc_good_standing'] ? 'fa-solid fa-check-circle' : 'fa-solid fa-times-circle'; ?>"></i> Good Standing Certificate</li>
                    <?php if($app['internship_type'] == 'long_term'): ?>
                    <li><i class="<?php echo $app['doc_cgpa_cert'] ? 'fa-solid fa-check-circle' : 'fa-solid fa-times-circle'; ?>"></i> CGPA Certificate</li>
                    <li><i class="<?php echo $app['doc_team_consent'] ? 'fa-solid fa-check-circle' : 'fa-solid fa-times-circle'; ?>"></i> Team Consent Letter</li>
                    <?php endif; ?>
                </ul>
                <?php if ($uploaded_pdf && !empty($uploaded_pdf['file_path'])): ?>
                <hr style="border:0; border-top:1px solid #eee; margin: 20px 0;">
               
<a href="uploads/<?php echo htmlspecialchars($uploaded_pdf['file_path']); ?>" target="_blank" style="font-weight:bold;">View Uploaded PDF Document</a>
                <?php endif; ?>
            </div>
            
            <?php if ($can_approve): ?>
            <div class="card approval-form">
                <h3>Your Action</h3>
                <form id="approvalForm" action="handle_approval.php" method="POST">
                    <input type="hidden" name="application_id" value="<?php echo $application_id; ?>">
                    <input type="hidden" name="decision" id="decisionInput">
                    <input type="hidden" name="signature" id="signatureInput">
                    <div style="margin-bottom: 15px;">
                        <label>Remarks (Required for rejection)</label>
                        <textarea name="remarks" id="remarksInput" rows="4"><?php echo htmlspecialchars($app['remarks']); ?></textarea>
                    </div>
                    <?php if ($show_signature_pad): ?>
                    <div style="margin-bottom: 15px;">
                        <label>Digital Signature (Required for Approval)</label>
                        <canvas id="signature-pad" width="400" height="200"></canvas>
                        <button type="button" id="clear-signature" style="background-color: #64748b;">Clear</button>
                    </div>
                    <?php endif; ?>
                   <div>
    <?php if ($current_user_role === 'dqac'): ?>
        <button type="submit" name="approve" class="approve-btn">
            <i class="fa-solid fa-share-from-square"></i> Forward to IQAC
        </button>
    <?php else: ?>
        <button type="submit" name="approve" class="approve-btn">
            <i class="fa-solid fa-check"></i> Approve
        </button>
    <?php endif; ?>

    <button type="submit" name="reject" class="reject-btn">
        <i class="fa-solid fa-times"></i> Reject
    </button>
</div>
                </form>
            </div>
            <?php endif; ?>

            <div class="card">
                <h3>Approval History & Signatures</h3>
                <div class="details-grid">
                    <?php if($app['staff_advisor_signature_path']): ?>
                        <div class="signature-box"><p><strong>Staff Advisor Signature:</strong></p><img src="<?php echo htmlspecialchars($app['staff_advisor_signature_path']); ?>"></div>
                    <?php endif; ?>
                    <?php if($app['hod_signature_path']): ?>
                        <div class="signature-box"><p><strong>HOD Signature:</strong></p><img src="<?php echo htmlspecialchars($app['hod_signature_path']); ?>"></div>
                    <?php endif; ?>
                    <?php if($app['principal_signature_path']): ?>
                        <div class="signature-box"><p><strong>Principal Signature:</strong></p><img src="<?php echo htmlspecialchars($app['principal_signature_path']); ?>"></div>
                    <?php endif; ?>
                    <?php if($app['dean_ug_signature_path']): ?>
                        <div class="signature-box"><p><strong>Dean UG Signature:</strong></p><img src="<?php echo htmlspecialchars($app['dean_ug_signature_path']); ?>"></div>
                    <?php endif; ?>
                </div>
                <ul style="padding-left: 20px; margin-top: 20px;">
                <?php while($row = $approval_history->fetch_assoc()): ?>
                    <li><strong><?php echo $row['decision']; ?></strong> by <?php echo $row['full_name']; ?> (<?php echo ucwords(str_replace('_', ' ', $row['role'])); ?>) on <?php echo date('d M Y, h:i A', strtotime($row['created_at'])); ?>. Remarks: <?php echo htmlspecialchars($row['remarks'] ? $row['remarks'] : 'N/A'); ?></li>
                <?php endwhile; ?>
                </ul>
            </div>

            <?php if ($is_approved_final && $current_user_role == 'iqac'): ?>
            <div class="card">
                <h3>Upload Final Signatures</h3>
                <form class="signature-form" action="handle_approval.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                    <input type="hidden" name="action" value="upload_final_signatures">
                    <div class="details-grid">
                        <div>
                            <strong>Principal's Signature</strong>
                            <?php if ($app['principal_signature_path']): ?>
                                <p>Already uploaded.</p>
                            <?php else: ?>
                                <input type="file" name="principal_signature" accept="image/*" required>
                            <?php endif; ?>
                        </div>
                        <div>
                            <strong>Dean UG's Signature</strong>
                            <?php if ($app['dean_ug_signature_path']): ?>
                                <p>Already uploaded.</p>
                            <?php else: ?>
                                <input type="file" name="dean_ug_signature" accept="image/*" required>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if (!$app['principal_signature_path'] || !$app['dean_ug_signature_path']): ?>
                        <button type="submit" class="approve-btn" style="margin-top:20px;">
                            <i class="fa-solid fa-upload"></i> Upload Signatures
                        </button>
                    <?php endif; ?>
                </form>
            </div>
            <?php endif; ?>

        </main>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const approvalForm = document.getElementById('approvalForm');
    if (approvalForm) {
        const decisionInput = document.getElementById('decisionInput');
        const remarksInput = document.getElementById('remarksInput');
        const showSignaturePad = <?php echo $show_signature_pad ? 'true' : 'false'; ?>;
        let signaturePad = null;
        if (showSignaturePad && document.getElementById('signature-pad')) {
            const canvas = document.getElementById('signature-pad');
            signaturePad = new SignaturePad(canvas);
            document.getElementById('clear-signature').addEventListener('click', () => signaturePad.clear());
        }
        approvalForm.addEventListener('submit', function(event) {
            const clickedButton = event.submitter;
            if (clickedButton.name === 'approve') {
                decisionInput.value = 'Approved';
                if (showSignaturePad && signaturePad.isEmpty()) {
                    alert('Signature is required for approval.');
                    event.preventDefault();
                    return;
                }
            } else if (clickedButton.name === 'reject') {
                decisionInput.value = 'Rejected';
                if (remarksInput.value.trim() === '') {
                    alert('Remarks are required for rejection.');
                    event.preventDefault();
                    return;
                }
            }
            if (showSignaturePad && !signaturePad.isEmpty()) {
                document.getElementById('signatureInput').value = signaturePad.toDataURL('image/png');
            }
        });
    }
});
</script>
</body>
</html>