<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'iqac') {
    header("Location: login.php");
    exit();
}

// --- Handle Company Update/Add ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_rating'])) {
        $company_id = intval($_POST['company_id']);
        $new_rating = floatval($_POST['rating']); // Use floatval for decimal ratings
        if ($new_rating >= 0 && $new_rating <= 100) {
            $stmt = $conn->prepare("UPDATE companies SET rating = ? WHERE id = ?");
            $stmt->bind_param("di", $new_rating, $company_id);
            $stmt->execute();
            $stmt->close();
        }
    } elseif (isset($_POST['add_company'])) {
        $company_name = trim($_POST['company_name']);
        $company_rating = floatval($_POST['company_rating']); // Use floatval for decimal ratings
        if (!empty($company_name) && $company_rating >= 0 && $company_rating <= 100) {
            $stmt = $conn->prepare("INSERT INTO companies (name, rating) VALUES (?, ?) ON DUPLICATE KEY UPDATE rating=?");
            $stmt->bind_param("sdd", $company_name, $company_rating, $company_rating);
            $stmt->execute();
            $stmt->close();
        }
    }
    header("Location: iqac_dashboard.php?success=updated");
    exit();
}

// --- Fetch Company List ---
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$companies_sql = "SELECT * FROM companies WHERE name LIKE ? ORDER BY name ASC";
$stmt = $conn->prepare($companies_sql);
$like_search = "%$search_term%";
$stmt->bind_param("s", $like_search);
$stmt->execute();
$companies_result = $stmt->get_result();
$stmt->close();

// --- Auto-reject applications for companies with rating < 30 ---
// Get all applications pending IQAC approval first
$pending_apps_sql = "SELECT a.id, a.company_name FROM applications a WHERE a.status = 'Pending IQAC Approval'";
$pending_apps_result = $conn->query($pending_apps_sql);

if ($pending_apps_result && $pending_apps_result->num_rows > 0) {
    while ($app = $pending_apps_result->fetch_assoc()) {
        // For each application, get the company's rating
        $check_stmt = $conn->prepare("SELECT rating FROM companies WHERE name = ?");
        $check_stmt->bind_param("s", $app['company_name']);
        $check_stmt->execute();
        $rating_result = $check_stmt->get_result();
        
        if ($rating_result->num_rows > 0) {
            $rating_row = $rating_result->fetch_assoc();
            // **CORRECTED LOGIC: Check if rating is below 30**
            if ($rating_row['rating'] < 30) {
                $status = "Rejected by IQAC";
                $remark = "Auto-rejected: Company rating (" . $rating_row['rating'] . "%) is below the 30% threshold.";
                $update_stmt = $conn->prepare("UPDATE applications SET status=?, remarks=? WHERE id=?");
                $update_stmt->bind_param("ssi", $status, $remark, $app['id']);
                $update_stmt->execute();
                $update_stmt->close();
            }
        }
        $check_stmt->close();
    }
}

// --- Fetch Applications (after auto-rejection has run) ---
$pending_sql = "SELECT a.id, u.full_name, u.department, a.company_name, a.status 
                FROM applications a 
                JOIN users u ON a.student_id = u.id 
                WHERE a.status='Pending IQAC Approval'
                ORDER BY a.submitted_at ASC";
$pending_result = $conn->query($pending_sql);

$reviewed_sql = "SELECT a.id, u.full_name, u.department, a.company_name, a.status 
                 FROM applications a 
                 JOIN users u ON a.student_id = u.id 
                 WHERE a.status LIKE '%IQAC%' AND a.status!='Pending IQAC Approval'
                 ORDER BY a.submitted_at DESC";
$reviewed_result = $conn->query($reviewed_sql);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>IQAC Dashboard</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<style>
:root { --navy-blue: #0a2342; --sidebar-bg: #1e293b; --content-bg: #f1f5f9; --card-bg: #ffffff; --border-color: #e2e8f0; }
body { margin:0; font-family:-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background-color: var(--content-bg);}
.main-layout{display:flex; min-height:100vh;}
.sidebar{background-color:var(--sidebar-bg); color:#e2e8f0; padding:20px 15px; width:260px; flex-shrink:0;}
.sidebar-header h3{color:#fff;text-align:center; font-weight:600;}
.sidebar-nav ul{list-style:none;padding:0;margin-top:30px;}
.sidebar-nav a{display:flex; align-items:center; gap:15px; padding:12px 15px; color:#e2e8f0; text-decoration:none; border-radius:8px; font-weight:500;}
.sidebar-nav a.active, .sidebar-nav a:hover{background-color:#334155; color:#fff;}
.main-content{flex-grow:1; display:flex; flex-direction:column;}
.top-nav{background-color:var(--card-bg); padding:15px 30px; border-bottom:1px solid var(--border-color); text-align:right;}
.logout-button{background-color:var(--navy-blue); color:white; padding:8px 15px; border-radius:5px; text-decoration:none; font-weight:500;}
.content-area{padding:30px; flex-grow:1;}
.page-title{font-size:1.8rem; font-weight:600; margin:0 0 25px 0;}
.card{background-color:var(--card-bg); padding:25px; border-radius:8px; box-shadow:0 4px 12px rgba(0,0,0,0.05); margin-bottom:30px;}
.card-header{margin:-25px -25px 25px -25px; padding:20px 25px; border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;}
.card-header h2{margin:0; font-size:1.25rem;}
.data-table{width:100%; border-collapse:collapse;}
.data-table th, .data-table td{padding:12px 15px; text-align:left; border-bottom:1px solid var(--border-color);}
.data-table th{background-color:#0a2342;color:#fff;}
.search-form input{padding:8px; border-radius:5px; border:1px solid #ccc;}
.search-form button{padding:8px 12px; border:none; background-color:var(--navy-blue); color:white; border-radius:5px; cursor:pointer;}
.rating-input{width:70px; padding:5px; text-align:center; border: 1px solid #ccc; border-radius: 4px;}
.update-btn{background-color:#3b82f6; color:white; border:none; padding:6px 12px; border-radius:5px; cursor:pointer;}
.update-btn:hover{background-color:#2563eb;}
.add-company-form{display:flex; gap:10px; padding:20px; background-color:#f8fafc; border-radius:8px; margin-top:20px; border: 1px solid var(--border-color);}
.add-company-form input{padding:10px; border:1px solid #ccc; border-radius:5px; flex-grow: 1;}
.add-company-form button{background-color:var(--navy-blue); color:white; border:none; padding:10px 15px; border-radius:5px; cursor:pointer;}
.status-approved{color:#22c55e; font-weight:600;}
.status-pending{color:#f59e0b; font-weight:600;}
.status-rejected{color:#ef4444; font-weight:600;}
.view-btn{background-color:#0a2342;color:#fff;padding:6px 12px;border-radius:5px;text-decoration:none; font-size:0.9em;}
.view-btn:hover{background-color:#1e3a8a;}
</style>
</head>
<body>
<div class="main-layout">
<aside class="sidebar">
    <div class="sidebar-header"><h3>IQAC Portal</h3></div>
    <nav class="sidebar-nav">
        <ul>
            <li><a href="iqac_dashboard.php" class="active"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
        </ul>
    </nav>
</aside>
<div class="main-content">
<header class="top-nav">
    <a href="logout.php" class="logout-button"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</header>
<main class="content-area">
<h1 class="page-title">IQAC Dashboard</h1>

<div class="card">
<div class="card-header">
<h2>Company Ratings</h2>
<form class="search-form" method="GET" action="iqac_dashboard.php">
<input type="text" name="search" placeholder="Search company..." value="<?php echo htmlspecialchars($search_term); ?>">
<button type="submit"><i class="fa-solid fa-search"></i></button>
</form>
</div>
<div style="max-height:350px; overflow-y:auto;">
<table class="data-table">
<thead><tr><th>Company Name</th><th>Current Rating (%)</th><th>New Rating</th><th>Action</th></tr></thead>
<tbody>
<?php if ($companies_result->num_rows > 0): ?>
    <?php while($row = $companies_result->fetch_assoc()): ?>
    <tr>
    <td><?php echo htmlspecialchars($row['name']); ?></td>
    <td><?php echo htmlspecialchars($row['rating']); ?>%</td>
    <form method="POST" action="iqac_dashboard.php">
    <input type="hidden" name="company_id" value="<?php echo $row['id']; ?>">
    <td><input class="rating-input" type="number" step="0.01" name="rating" value="<?php echo $row['rating']; ?>" min="0" max="100" required></td>
    <td><button type="submit" name="update_rating" class="update-btn">Update</button></td>
    </form>
    </tr>
    <?php endwhile; ?>
<?php else: ?>
    <tr><td colspan="4" style="text-align: center;">No companies found.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
<form class="add-company-form" method="POST" action="iqac_dashboard.php">
<input type="text" name="company_name" placeholder="New Company Name" required>
<input type="number" step="0.01" name="company_rating" placeholder="Rating 0-100" min="0" max="100" required>
<button type="submit" name="add_company">Add Company</button>
</form>
</div>

<div class="card">
<div class="card-header"><h2>Pending IQAC Approvals</h2></div>
<table class="data-table">
<thead><tr><th>Student</th><th>Department</th><th>Company</th><th>Status</th><th>Action</th></tr></thead>
<tbody>
<?php if($pending_result->num_rows > 0): ?>
<?php while($row = $pending_result->fetch_assoc()): ?>
<tr>
<td><?php echo htmlspecialchars($row['full_name']); ?></td>
<td><?php echo htmlspecialchars($row['department']); ?></td>
<td><?php echo htmlspecialchars($row['company_name']); ?></td>
<td><span class="status-pending"><?php echo htmlspecialchars($row['status']); ?></span></td>
<td><a class="view-btn" href="view_application.php?id=<?php echo $row['id']; ?>">View</a></td>
</tr>
<?php endwhile; ?>
<?php else: ?>
<tr><td colspan="5" style="text-align:center;">No pending applications.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>

<div class="card">
<div class="card-header"><h2>Reviewed Applications</h2></div>
<table class="data-table">
<thead><tr><th>Student</th><th>Department</th><th>Company</th><th>Status</th><th>Action</th></tr></thead>
<tbody>
<?php if($reviewed_result->num_rows > 0): ?>
<?php while($row = $reviewed_result->fetch_assoc()): ?>
<tr>
<td><?php echo htmlspecialchars($row['full_name']); ?></td>
<td><?php echo htmlspecialchars($row['department']); ?></td>
<td><?php echo htmlspecialchars($row['company_name']); ?></td>
<td>
<?php
$status = htmlspecialchars($row['status']);
if(stripos($status, 'Pending') !== false){ echo "<span class='status-pending'>".$status."</span>"; }
elseif(stripos($status, 'Approved') !== false){ echo "<span class='status-approved'>".$status."</span>"; }
else{ echo "<span class='status-rejected'>".$status."</span>"; }
?>
</td>
<td><a class="view-btn" href="view_application.php?id=<?php echo $row['id']; ?>">View</a></td>
</tr>
<?php endwhile; ?>
<?php else: ?>
<tr><td colspan="5" style="text-align:center;">No reviewed applications.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>

</main>
</div>
</div>
</body>
</html>