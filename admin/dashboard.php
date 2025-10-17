<?php
session_start();
require_once __DIR__ . '/../conexion.php';

// --- Obtener datos para el dashboard ---

// Total de películas
$totalPeliculas = $conn->query("SELECT COUNT(*) as total FROM peliculas")->fetch_assoc()['total'];

// Total de géneros únicos
$totalGeneros = $conn->query("SELECT COUNT(DISTINCT genero) as total FROM peliculas")->fetch_assoc()['total'];

// Películas por género
$generosData = $conn->query("SELECT genero, COUNT(*) as total FROM peliculas GROUP BY genero ORDER BY total DESC")->fetch_all(MYSQLI_ASSOC);

// Películas por año (últimos 10 años)
$yearsData = $conn->query("SELECT anio, COUNT(*) as total FROM peliculas WHERE anio >= YEAR(CURDATE())-10 GROUP BY anio ORDER BY anio ASC")->fetch_all(MYSQLI_ASSOC);

// Película más larga
$peliculaMasLarga = $conn->query("SELECT titulo, duracion FROM peliculas ORDER BY duracion DESC LIMIT 1")->fetch_assoc();

// Película más corta  
$peliculaMasCorta = $conn->query("SELECT titulo, duracion FROM peliculas ORDER BY duracion ASC LIMIT 1")->fetch_assoc();

// Género dominante
$generoDominante = $conn->query("SELECT genero, COUNT(*) as total FROM peliculas GROUP BY genero ORDER BY total DESC LIMIT 1")->fetch_assoc();
$porcentajeGenero = $totalPeliculas > 0 ? round(($generoDominante['total'] / $totalPeliculas) * 100) : 0;

// Preparar datos para Chart.js
$generosLabels = json_encode(array_column($generosData, 'genero'));
$generosCounts = json_encode(array_column($generosData, 'total'));

$yearsLabels = json_encode(array_column($yearsData, 'anio'));
$yearsCounts = json_encode(array_column($yearsData, 'total'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Dashboard Analytics - Netbeam</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="admin.css">
<link rel="shortcut icon" type="image/jpg" href="../img/image.png" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
</head>
<body>
<div class="admin-layout">
  <aside class="admin-sidebar">
    <div class="admin-logo"><img src="../img/image2.png" alt="logo"></div>
    <nav>
      <ul>
        <li class="active"><a href="dashboard.php">Dashboard</a></li>
        <li><a href="admin.php">Lista de peliculas</a></li>
        <li><a href="subir_pelicula.php">Añadir película</a></li>
        <li><a href="../logout.php">Cerrar sesión</a></li>
      </ul>
    </nav>
  </aside>

  <main class="admin-main">
    <header class="analytics-header">
      <h1>Dashboard Analytics</h1>
      <p>Análisis completo de tu catálogo de películas</p>
    </header>

    <!-- Stats Cards -->
    <div class="stats-grid">
      <div class="stat-card">
        <span class="stat-icon">🎬</span>
        <div class="stat-label">Total Películas</div>
        <div class="stat-value"><?= $totalPeliculas ?></div>
        <div class="stat-subtitle">En tu catálogo</div>
      </div>

      <div class="stat-card">
        <span class="stat-icon">🎭</span>
        <div class="stat-label">Géneros</div>
        <div class="stat-value"><?= $totalGeneros ?></div>
        <div class="stat-subtitle">Categorías diferentes</div>
      </div>
    </div>

    <!-- Charts Grid -->
    <div class="charts-grid">
      <!-- Donut Chart - Distribución por Género -->
      <div class="chart-card span-4">
        <div class="chart-header">
          <div class="chart-title">
            Distribución por Género
            <span class="chart-badge"><?= count($generosData) ?></span>
          </div>
        </div>
        <div class="chart-container">
          <canvas id="generoDonutChart"></canvas>
        </div>
      </div>

      <!-- Line Chart - Tendencia por Año -->
      <div class="chart-card span-8">
        <div class="chart-header">
          <div class="chart-title">
            Tendencia por Año
            <span class="chart-badge">10 años</span>
          </div>
        </div>
        <div class="chart-container">
          <canvas id="yearLineChart"></canvas>
        </div>
      </div>

      <!-- Bar Chart Horizontal - Top Géneros -->
      <div class="chart-card span-12">
        <div class="chart-header">
          <div class="chart-title">
            Top Géneros
            <span class="chart-badge ranking">Ranking</span>
          </div>
        </div>
        <div class="chart-container">
          <canvas id="generoBarChart"></canvas>
        </div>
      </div>
    </div>

    <!-- Highlight Cards -->
    <div class="highlights-grid">
      <div class="highlight-card">
        <span class="highlight-icon">🎞️</span>
        <div class="highlight-label">Película más larga</div>
        <div class="highlight-title"><?= htmlspecialchars($peliculaMasLarga['titulo'] ?? 'N/A') ?></div>
        <div class="highlight-subtitle">Duración: <?= $peliculaMasLarga['duracion'] ?? 0 ?> minutos</div>
      </div>

      <div class="highlight-card">
        <span class="highlight-icon">⚡</span>
        <div class="highlight-label">Película más corta</div>
        <div class="highlight-title"><?= htmlspecialchars($peliculaMasCorta['titulo'] ?? 'N/A') ?></div>
        <div class="highlight-subtitle">Duración: <?= $peliculaMasCorta['duracion'] ?? 0 ?> minutos</div>
      </div>

      <div class="highlight-card">
        <span class="highlight-icon">🏆</span>
        <div class="highlight-label">Género dominante</div>
        <div class="highlight-title"><?= htmlspecialchars($generoDominante['genero'] ?? 'N/A') ?></div>
        <div class="highlight-subtitle"><?= $generoDominante['total'] ?? 0 ?> películas (<?= $porcentajeGenero ?>%)</div>
      </div>
    </div>

    <footer class="admin-footer" style="margin-top:12px; text-align:center; padding:10px; color:#666; font-size:11px;">
      <p>© 2025 Netbeam — Panel de administración de películas</p>
    </footer>
  </main>
</div>

<script>
// Configuración global de Chart.js
Chart.defaults.color = '#999';
Chart.defaults.borderColor = '#333';
Chart.defaults.font.family = 'Poppins, sans-serif';

// Donut Chart - Distribución por Género
const ctxDonut = document.getElementById('generoDonutChart').getContext('2d');
new Chart(ctxDonut, {
  type: 'doughnut',
  data: {
    labels: <?= $generosLabels ?>,
    datasets: [{
      data: <?= $generosCounts ?>,
      backgroundColor: [
        '#e50914',
        '#ff6b81', 
        '#ff4757',
        '#ff8c94',
        '#ff7f50',
        '#ffa07a'
      ],
      borderWidth: 0,
      hoverOffset: 15
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        position: 'right',
        labels: {
          padding: 15,
          font: { size: 13 },
          usePointStyle: true,
          pointStyle: 'circle'
        }
      },
      tooltip: {
        backgroundColor: 'rgba(0,0,0,0.8)',
        padding: 12,
        titleFont: { size: 14 },
        bodyFont: { size: 13 }
      }
    }
  }
});

// Line Chart - Tendencia por Año
const ctxLine = document.getElementById('yearLineChart').getContext('2d');
const gradient = ctxLine.createLinearGradient(0, 0, 0, 300);
gradient.addColorStop(0, 'rgba(229, 9, 20, 0.4)');
gradient.addColorStop(1, 'rgba(229, 9, 20, 0)');

new Chart(ctxLine, {
  type: 'line',
  data: {
    labels: <?= $yearsLabels ?>,
    datasets: [{
      label: 'Películas',
      data: <?= $yearsCounts ?>,
      borderColor: '#e50914',
      backgroundColor: gradient,
      borderWidth: 3,
      fill: true,
      tension: 0.4,
      pointRadius: 5,
      pointHoverRadius: 8,
      pointBackgroundColor: '#e50914',
      pointBorderColor: '#fff',
      pointBorderWidth: 2
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
      mode: 'index',
      intersect: false
    },
    plugins: {
      legend: { display: false },
      tooltip: {
        backgroundColor: 'rgba(0,0,0,0.8)',
        padding: 12
      }
    },
    scales: {
      y: {
        beginAtZero: true,
        grid: { color: '#222' },
        ticks: { stepSize: 1 }
      },
      x: {
        grid: { display: false }
      }
    }
  }
});

// Bar Chart Horizontal - Top Géneros
const ctxBar = document.getElementById('generoBarChart').getContext('2d');
new Chart(ctxBar, {
  type: 'bar',
  data: {
    labels: <?= $generosLabels ?>,
    datasets: [{
      label: 'Películas',
      data: <?= $generosCounts ?>,
      backgroundColor: [
        '#e50914',
        '#ff2e3a',
        '#ff4d5a',
        '#ff6b7a',
        '#ff8a99',
        '#ffa9b8'
      ],
      borderWidth: 0,
      borderRadius: 8,
      barThickness: 26,
      categoryPercentage: 0.75,
      barPercentage: 0.85
    }]
  },
  options: {
    indexAxis: 'y',
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { display: false },
      tooltip: {
        backgroundColor: 'rgba(0,0,0,0.9)',
        padding: 12,
        titleFont: { size: 13, weight: 'bold' },
        bodyFont: { size: 12 },
        borderColor: '#e50914',
        borderWidth: 1
      }
    },
    scales: {
      x: {
        beginAtZero: true,
        grid: { 
          color: '#333',
          lineWidth: 1
        },
        ticks: {
          stepSize: 2,
          font: { size: 11, weight: '600' },
          color: '#bbb'
        }
      },
      y: {
        grid: { display: false },
        ticks: {
          font: { size: 12, weight: '600' },
          color: '#fff',
          padding: 10
        }
      }
    },
    layout: {
      padding: {
        left: 10,
        right: 10,
        top: 5,
        bottom: 5
      }
    }
  }
});
</script>
</body>
</html>