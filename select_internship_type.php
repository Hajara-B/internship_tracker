<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Internship Type</title>
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
        .sidebar-nav i { width: 20px; text-align: center; }
        .main-content { flex-grow: 1; display: flex; flex-direction: column; }
        .top-nav { background-color: var(--card-bg); padding: 15px 30px; border-bottom: 1px solid #e2e8f0; text-align: right; }
        .logout-button { background-color: var(--navy-blue); color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; font-weight: 500;}
        .content-area { padding: 30px; flex-grow: 1; display: flex; align-items: center; justify-content: center; }
        .selection-container { text-align: center; background: var(--card-bg); padding: 50px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .selection-container h1 { font-size: 2rem; margin-top: 0; margin-bottom: 40px; }
        .selection-box { display: inline-block; width: 250px; padding: 40px 20px; margin: 0 20px; border: 1px solid #e2e8f0; border-radius: 10px; text-decoration: none; color: var(--text-primary); transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s; }
        .selection-box:hover { transform: translateY(-5px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); border-color: var(--navy-blue); }
        .selection-box i { font-size: 3rem; color: var(--navy-blue); margin-bottom: 20px; }
        .selection-box h2 { margin: 0; font-size: 1.5rem; }
    </style>
</head>
<body>
<div class="main-layout">
    <aside class="sidebar">
        <div class="sidebar-header"><h3>Student Portal</h3></div>
         <nav class="sidebar-nav">
            <ul>
                <li><a href="student_dashboard.php"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
            </ul>
        </nav>
    </aside>
    <div class="main-content">
        <header class="top-nav">
             <a href="logout.php" class="logout-button"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </header>
        <main class="content-area">
            <div class="selection-container">
                <h1>Select the Internship Type</h1>
                <div>
                    <a href="submit_short_term_form.php" class="selection-box">
                        <i class="fa-solid fa-business-time"></i>
                        <h2>Short-Term</h2>
                    </a>
                    <a href="submit_long_term_form.php" class="selection-box">
                        <i class="fa-solid fa-calendar-alt"></i>
                        <h2>Long-Term</h2>
                    </a>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>