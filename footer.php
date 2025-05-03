<footer class="main-footer">
    <div class="float-right d-none d-sm-inline">
        Admin Panel
    </div>
    <strong>Iqbolshoh &copy;2025 <a href="https://Iqbolshoh.uz">NextDot</a>.</strong> All rights reserved.
</footer>
<!-- Include Chart.js library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>

<!-- Your custom JavaScript for charts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Task Completion Trends Chart
  const taskCompletionCtx = document.getElementById('ndt-task-completion-chart').getContext('2d');
  const taskCompletionChart = new Chart(taskCompletionCtx, {
    type: 'line',
    data: {
      labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
      datasets: [
        {
          label: 'Completed Tasks',
          data: [12, 19, 15, 22, 18, 10, 14],
          borderColor: '#1cc88a',
          backgroundColor: 'rgba(28, 200, 138, 0.1)',
          tension: 0.4,
          fill: true
        },
        {
          label: 'Created Tasks',
          data: [15, 22, 18, 24, 21, 12, 18],
          borderColor: '#4e73df',
          backgroundColor: 'rgba(78, 115, 223, 0.1)',
          tension: 0.4,
          fill: true
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'top',
        },
        tooltip: {
          mode: 'index',
          intersect: false,
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          grid: {
            drawBorder: false
          }
        },
        x: {
          grid: {
            display: false
          }
        }
      }
    }
  });

  // Task Distribution Chart (Doughnut)
  const taskDistributionCtx = document.getElementById('ndt-task-distribution-chart').getContext('2d');
  const taskDistributionChart = new Chart(taskDistributionCtx, {
    type: 'doughnut',
    data: {
      labels: ['Development', 'Design', 'Marketing', 'Research'],
      datasets: [{
        data: [42, 28, 18, 12],
        backgroundColor: [
          '#4e73df',
          '#1cc88a',
          '#36b9cc',
          '#f6c23e'
        ],
        borderWidth: 0,
        hoverOffset: 5
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '75%',
      plugins: {
        legend: {
          display: false
        }
      }
    }
  });

  // Team Productivity Chart (Bar)
  const teamProductivityCtx = document.getElementById('ndt-team-productivity-chart').getContext('2d');
  const teamProductivityChart = new Chart(teamProductivityCtx, {
    type: 'bar',
    data: {
      labels: ['John', 'Sarah', 'Michael', 'Emily', 'David'],
      datasets: [{
        label: 'Tasks Completed',
        data: [25, 18, 22, 15, 20],
        backgroundColor: [
            'rgba(78, 115, 223, 0.8)',
          'rgba(28, 200, 138, 0.8)',
          'rgba(54, 185, 204, 0.8)',
          'rgba(246, 194, 62, 0.8)',
          'rgba(231, 74, 59, 0.8)'
        ],
        borderWidth: 0,
        borderRadius: 4
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          grid: {
            drawBorder: false
          },
          ticks: {
            precision: 0
          }
        },
        x: {
          grid: {
            display: false
          }
        }
      }
    }
  });
});
</script>