<?php
// trend_analysis.php
// Include this file wherever you want to display the PG trend chart
require 'db_connect.php';

// Fetch all PG applications for trend analysis
$trend_sql = "SELECT company_name, COUNT(*) AS num_applications 
              FROM applications a 
              JOIN users u ON a.student_id = u.id 
              WHERE u.program_type='PG' 
              GROUP BY company_name";
$trend_result = $conn->query($trend_sql);

// Prepare labels and data for Chart.js
$labels = [];
$data = [];
while($row = $trend_result->fetch_assoc()){
    $labels[] = $row['company_name'];
    $data[] = $row['num_applications'];
}
?>

<div class="card">
    <h2>PG Applications Trend Analysis</h2>
    <canvas id="trendChart"></canvas>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('trendChart').getContext('2d');
const trendChart = new Chart(ctx, {
    type: 'pie',
    data: {
        labels: <?php echo json_encode($labels); ?>,
        datasets: [{
            label: 'Number of Applications',
            data: <?php echo json_encode($data); ?>,
            backgroundColor: [
                '#4ade80','#facc15','#60a5fa','#f472b6','#f87171','#a78bfa','#34d399',
                '#fcd34d','#f871b2','#22d3ee','#fde68a','#38bdf8','#a5f3fc'
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom'
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.label + ': ' + context.parsed;
                    }
                }
            }
        }
    }
});
</script>
