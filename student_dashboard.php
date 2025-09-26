<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['user_id'];

// Fetch student applications securely
$stmt = $conn->prepare("
    SELECT a.id, a.company_name, a.submitted_at, a.status, c.rating
    FROM applications a
    LEFT JOIN companies c ON a.company_name = c.name
    WHERE a.student_id = ?
    ORDER BY a.submitted_at DESC
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();

// Status class mapping for badges
$status_classes = [
    'Pending Staff Advisor' => 'pending-staff-advisor',
    'Pending HOD Approval' => 'pending-hod-approval',
    'Pending DQAC Approval' => 'pending-dqac-approval',
    'Pending IQAC Approval' => 'pending-iqac-approval',
    'Approved' => 'approved',
    'Rejected' => 'rejected',
    'Rejected (Low Rating)' => 'rejected',
    'Rejected by Staff Advisor' => 'rejected-by-staff-advisor',
    'Rejected by HOD' => 'rejected-by-hod',
    'Rejected by DQAC' => 'rejected-by-dqac',
    'Rejected by Iqac' => 'rejected-by-iqac',
    'Pending Review (Low Rating)' => 'pending-review-low-rating'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Dashboard</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<style>
:root {
    --navy-blue: #0a2342;
    --sidebar-bg: #1e293b;
    --sidebar-text: #e2e8f0;
    --sidebar-hover: #334155;
    --content-bg: #f1f5f9;
    --card-bg: #ffffff;
    --text-primary: #1e293b;
    --text-secondary: #64748b;
    --border-color: #e2e8f0;
}
body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background-color: var(--content-bg); color: var(--text-primary);}
.main-layout { display: flex; min-height: 100vh; }
.sidebar { background-color: var(--sidebar-bg); color: var(--sidebar-text); padding: 20px 15px; width: 260px; flex-shrink: 0; }
.sidebar-header { text-align: center; padding-bottom: 20px; margin-bottom: 20px; border-bottom: 1px solid var(--sidebar-hover);}
.sidebar-header h3 { color: #fff; margin: 0; font-weight: 600; }
.sidebar-nav ul { list-style: none; padding: 0; margin: 0; }
.sidebar-nav a { display: flex; align-items: center; gap: 15px; padding: 12px 15px; color: var(--sidebar-text); text-decoration: none; border-radius: 8px; margin-bottom: 5px; font-weight: 500; }
.sidebar-nav a.active, .sidebar-nav a:hover { background-color: var(--sidebar-hover); color: #fff; }
.main-content { flex-grow: 1; display: flex; flex-direction: column; }
.top-nav { display: flex; justify-content: space-between; align-items: center; background-color: var(--card-bg); padding: 15px 30px; border-bottom: 1px solid var(--border-color);}
.logout-button { background-color: var(--navy-blue); color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; display: flex; align-items: center; gap: 8px; font-weight: 500; }
.content-area { padding: 30px; flex-grow: 1; }
.page-title { font-size: 1.8rem; font-weight: 600; margin: 0 0 25px 0; }
.card { background-color: var(--card-bg); padding: 25px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
.data-table { width: 100%; border-collapse: collapse; }
.data-table th, .data-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid var(--border-color); }
.data-table th { background-color: #f8fafc; font-weight: 600; }
.data-table tr:hover { background-color: #f8fafc; }
.data-table td a { color: var(--navy-blue); font-weight: 600; text-decoration: none; }
.status { padding: 5px 12px; border-radius: 15px; font-size: 0.8rem; color: white; font-weight: 500; text-transform: capitalize; display: inline-block; white-space: nowrap; }
.status.pending-staff-advisor { background-color: #f59e0b; }
.status.pending-hod-approval { background-color: #8b5cf6; }
.status.pending-dqac-approval { background-color: #0ea5e9; }
.status.pending-iqac-approval { background-color: #d946ef; }
.status.approved { background-color: #22c55e; }
.status.rejected, .status.rejected-by-staff-advisor, .status.rejected-by-hod, .status.rejected-by-dqac, .status.rejected-by-iqac { background-color: #ef4444; }
.rating-badge { padding: 4px 10px; border-radius: 5px; color: white; font-weight: 500; font-size: 0.85rem; }
.rating-low { background-color: red; }
.rating-high { background-color: green; }
.status.pending-review-low-rating { background-color: #e2e8f0; color: #1e293b; }
</style>
</head>
<body>
<div class="main-layout">
    <aside class="sidebar">
        <div class="sidebar-header"><h3>Student Portal</h3></div>
        <nav class="sidebar-nav">
            <ul>
                <li><a href="student_dashboard.php" class="active"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
                <li><a href="select_internship_type.php"><i class="fa-solid fa-file-pen"></i> New Application</a></li>
            </ul>
        </nav>
    </aside>
    <div class="main-content">
        <header class="top-nav">
            <span>Welcome, <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?>!</strong></span>
            <a href="logout.php" class="logout-button"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </header>
        <main class="content-area">
            <h1 class="page-title">My Internship Applications</h1>
            <div class="card">
                <table class="data-table">
                    <thead>
                        <tr><th>Company</th><th>Rating</th><th>Submitted On</th><th>Status</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['company_name']); ?></td>
                            <td>
                                <?php
                                $rating = $row['rating'] ?? 0;
                                $badge_class = ($rating < 30) ? 'rating-low' : 'rating-high';
                                echo "<span class='rating-badge $badge_class'>$rating%</span>";
                                ?>
                            </td>
                            <td><?php echo date('d M, Y', strtotime($row['submitted_at'])); ?></td>
                            <td>
                                <?php
                                // --- THIS IS THE FIX ---
                                // This simplified code now displays the full status from the database
                                $status_text = htmlspecialchars($row['status']);
                                $class = $status_classes[$row['status']] ?? '';
                                echo "<span class='status $class'>".$status_text."</span>";
                                ?>
                            </td>
                            <td><a href="view_application.php?id=<?php echo $row['id']; ?>">View Details</a></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align:center;">You have not submitted any applications yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>
</body>
</html>