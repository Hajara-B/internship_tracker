<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hod') {
    header("Location: login.php");
    exit();
}
$department = $_SESSION['department'];

$sql = "SELECT COUNT(a.id) as count FROM applications a JOIN users u ON a.student_id = u.id WHERE u.department = ? AND a.status = 'Pending HOD Approval'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $department);
$stmt->execute();
$pending_count = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HOD Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        :root { --navy-blue: #0a2342; --sidebar-bg: #1e293b; --sidebar-text: #e2e8f0; --sidebar-hover: #334155; --content-bg: #f1f5f9; --card-bg: #ffffff; --text-primary: #1e293b; --text-secondary: #64748b; --border-color: #e2e8f0; }
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background-color: var(--content-bg); color: var(--text-primary); }
        .main-layout { display: flex; min-height: 100vh; }
        .sidebar { background-color: var(--sidebar-bg); color: var(--sidebar-text); padding: 20px 15px; width: 260px; box-sizing: border-box; flex-shrink: 0; }
        .sidebar-header { text-align: center; padding-bottom: 20px; margin-bottom: 20px; border-bottom: 1px solid var(--sidebar-hover); }
        .sidebar-header h3 { color: #fff; margin: 0; font-weight: 600; }
        .sidebar-nav ul { list-style: none; padding: 0; margin: 0; }
        .sidebar-nav a { display: flex; align-items: center; gap: 15px; padding: 12px 15px; color: var(--sidebar-text); text-decoration: none; border-radius: 8px; margin-bottom: 5px; font-weight: 500; }
        .sidebar-nav a.active, .sidebar-nav a:hover { background-color: var(--sidebar-hover); color: #fff; }
        .sidebar-nav i { width: 20px; text-align: center; }
        .main-content { flex-grow: 1; display: flex; flex-direction: column; }
        .top-nav { display: flex; justify-content: space-between; align-items: center; background-color: var(--card-bg); padding: 15px 30px; border-bottom: 1px solid var(--border-color); flex-shrink: 0; }
        .logout-button { background-color: var(--navy-blue); color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; display: flex; align-items: center; gap: 8px; font-weight: 500; }
        .content-area { padding: 30px; flex-grow: 1; }
        .page-title { font-size: 1.8rem; font-weight: 600; margin: 0 0 25px 0; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 25px; margin-bottom: 30px; }
        .stat-card { background-color: var(--card-bg); padding: 20px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 20px; }
        .stat-icon { width: 60px; height: 60px; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-size: 1.5rem; }
        .stat-info .stat-number { font-size: 2rem; font-weight: 600; }
        .stat-info .stat-label { font-size: 0.9rem; color: var(--text-secondary); }
        .card { background-color: var(--card-bg); padding: 25px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .card-header { margin: -25px -25px 25px -25px; padding: 20px 25px; border-bottom: 1px solid var(--border-color); }
        .card-header h2 { margin: 0; font-size: 1.25rem; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th, .data-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid var(--border-color); }
        .data-table th { background-color: #f8fafc; font-weight: 600; }
        .data-table tr:hover { background-color: #f8fafc; }
        .data-table td a { color: var(--navy-blue); font-weight: 600; text-decoration: none; }
    </style>
</head>
<body>
<div class="main-layout">
    <aside class="sidebar">
        <div class="sidebar-header"><h3>HOD Portal</h3></div>
        <nav class="sidebar-nav">
            <ul>
                <li><a href="hod_dashboard.php" class="active"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
            </ul>
        </nav>
    </aside>
    <div class="main-content">
        <header class="top-nav">
            <span>Welcome, <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?>!</strong></span>
            <a href="logout.php" class="logout-button"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </header>
        <main class="content-area">
            <h1 class="page-title">Dashboard</h1>
             <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background-color: #f3e8ff;"><i class="fa-solid fa-inbox" style="color: #7e22ce;"></i></div>
                    <div class="stat-info">
                        <span class="stat-number"><?php echo $pending_count; ?></span>
                        <span class="stat-label">Applications Awaiting HOD Approval</span>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h2>Pending Applications</h2></div>
                <table class="data-table">
                    <thead>
                        <tr><th>Student Name</th><th>Company</th><th>Submitted On</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT a.id, a.company_name, a.submitted_at, u.full_name 
                                FROM applications a 
                                JOIN users u ON a.student_id = u.id 
                                WHERE u.department = ? AND a.status = 'Pending HOD Approval'
                                ORDER BY a.submitted_at ASC";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("s", $department);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        if ($result->num_rows > 0):
                            while($row = $result->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['company_name']); ?></td>
                            <td><?php echo date('d M, Y', strtotime($row['submitted_at'])); ?></td>
                            <td><a href="view_application.php?id=<?php echo $row['id']; ?>">Review Application</a></td>
                        </tr>
                        <?php
                            endwhile;
                        else:
                        ?>
                        <tr><td colspan="4" style="text-align:center;">No applications are awaiting HOD approval.</td></tr>
                        <?php endif; $stmt->close(); ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>
</body>
</html>