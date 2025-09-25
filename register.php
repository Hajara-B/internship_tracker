<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - CET Internship Tracker</title>
    <style>
        :root { --navy-blue: #0a2342; --light-grey: #f0f2f5; --card-bg: #ffffff; --text-primary: #1e293b; --text-secondary: #64748b; --error-color: #ef4444; }
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background-color: var(--light-grey); display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px 0; }
        .auth-card { background-color: var(--card-bg); padding: 40px; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); width: 100%; max-width: 400px; text-align: center; }
        h1 { margin-top: 0; color: var(--text-primary); font-size: 28px; }
        .input-group { margin-bottom: 15px; text-align: left; }
        input, select { width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; box-sizing: border-box; font-size: 1rem; background-color: #f9fafb; }
        button { width: 100%; padding: 14px; background-color: var(--navy-blue); border: none; border-radius: 6px; color: #fff; font-size: 16px; cursor: pointer; margin-top: 10px; }
        .message { padding: 10px 15px; margin-bottom: 20px; border-radius: 6px; border-left: 5px solid var(--error-color); background-color: #fee2e2; color: #b91c1c; text-align: left; }
        a { color: var(--navy-blue); font-weight: 500; text-decoration: none; }
    </style>
</head>
<body>
    <div class="auth-card">
        <h1>Create an Account</h1>
        <?php if (isset($_GET['error'])) { echo '<div class="message">'.htmlspecialchars($_GET['error']).'</div>'; } ?>
        <form action="handle_registration.php" method="POST">
            <div class="input-group"><input type="text" name="full_name" placeholder="Full Name" required></div>
            <div class="input-group"><input type="text" name="college_id" placeholder="College ID" required></div>
            <div class="input-group"><input type="email" name="email" placeholder="Email Address" required></div>
            <div class="input-group">
                <select name="role" id="role-select" onchange="toggleFields()" required>
                    <option value="" disabled selected>Select Your Role</option>
                    <option value="student">Student</option>
                    <option value="staff_advisor">Staff Advisor</option>
                    <option value="hod">HOD</option>
                    <option value="dqac">DQAC</option>
                    <option value="iqac">IQAC</option>
                </select>
            </div>
            <div id="student-fields" style="display:none;" class="input-group">
                <select name="program_type" onchange="populateDepartments(this.value)">
                     <option value="" disabled selected>Select Programme Type</option><option value="UG">UG</option><option value="PG">PG</option>
                </select>
            </div>
            <div id="department-field" style="display:none;" class="input-group">
                <select name="department" id="department-select"></select>
            </div>
            <div class="input-group"><input type="password" name="password" placeholder="Password (min 6 chars)" required></div>
            <div class="input-group"><input type="password" name="confirm_password" placeholder="Confirm Password" required></div>
            <button type="submit">Register</button>
            <p style="margin-top: 20px;">Already have an account? <a href="login.php">Login here</a></p>
        </form>
    </div>
    <script>
        const ug_departments = ["Civil", "Electrical and Electronics", "Electrical and Computer", "Electronics & Communication", "Applied Electronics & Instrumentation", "Mechanical", "Industrial", "B.Arch", "Computer Science"];
        const pg_departments = ["MCA", "MBA", "MTECH(Structural Engineering)", "MTECH(Traffic & Transportation Engineering)", "MTECH(Hydraulics Engineering)", "MTECH(Environmental Engineering)", "MTECH(Geotechnical Engineering)", "MTECH(Geo Informatics)", "MTECH(Machine Design)", "MTECH(Propulsion Engineering)", "MTECH(Thermal Sciences)", "MTECH(Industrial Engineering)", "MTECH(Financial Engineering)", "MTECH(Manufacturing & Automation)", "MTECH(Control Systems)", "MTECH(Power Systems)", "MTECH(Guidance & Navigational Control)", "MTECH(Electrical Machines)", "MTECH(Communication Systems)", "MTECH(Applied Electronics & Instrumentation)", "MTECH(Signal Processing)", "MTECH(Micro & Nano Electronics)", "MTECH(Robotics & Automation)", "MTECH(Computer Science & Engineering)", "MTECH(Information Security)", "Urban Design (M. Arch.)", "Planning (M.Plan)"];
        
        function toggleFields() {
            const role = document.getElementById('role-select').value;
            const studentFields = document.getElementById('student-fields');
            const deptField = document.getElementById('department-field');
            const deptSelect = document.getElementById('department-select');

            studentFields.style.display = (role === 'student') ? 'block' : 'none';
            deptField.style.display = (role === 'staff_advisor' || role === 'hod') ? 'block' : 'none';
            if(role === 'staff_advisor' || role === 'hod') populateDepartments('ALL');
        }
        function populateDepartments(type) {
            const deptSelect = document.getElementById('department-select');
            let depts = [];
            if (type === 'UG') depts = ug_departments;
            else if (type === 'PG') depts = pg_departments;
            else if (type === 'ALL') depts = [...new Set([...ug_departments, ...pg_departments])].sort();
            
            deptSelect.innerHTML = '<option value="" disabled selected>Select Department</option>';
            depts.forEach(d => deptSelect.add(new Option(d, d)));
            document.getElementById('department-field').style.display = 'block';
        }
    </script>
</body>
</html>

